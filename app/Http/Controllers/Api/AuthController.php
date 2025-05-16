<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\SignUpRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;  
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $data = $request->validated();

        // Manually retrieve the user by username
        $user = User::where('username', $data['username'])->first();

        // Check if user exists
        if (!$user) {
            return response([
                'message' => 'Invalid username or password'
            ], 401);
        }

        // Check password using Hash::check
        if (!Hash::check($data['password'], $user->password)) {
            return response([
                'message' => 'Invalid username or password'
            ], 401);
        }

        // Generate token
        $token = $user->createToken('main')->plainTextToken;

        // Fetch related data
        $cartItems = DB::table('cart_items')
            ->join('items', 'items.id', '=', 'cart_items.item_id')
            ->where('cart_items.user_id', $user->id)
            ->select(
                'items.id as item_id',
                'items.item_name as item_name',
                'items.image_url',
                'cart_items.id',
                'cart_items.quantity',
                'cart_items.unit_price',
                'cart_items.grand_total',
                'cart_items.stocks'
            )
            ->get();

        $purchaseReceipts = DB::table('purchase_receipts')
            ->join('items', 'items.id', '=', 'purchase_receipts.item_id')
            ->where('purchase_receipts.user_id', $user->id)
            ->select(
                'purchase_receipts.*',
                'items.item_name as item_name',
                'items.image_url',
                DB::raw('ROUND(purchase_receipts.total_price / purchase_receipts.quantity, 2) as unit_price') // Fixed calculation
            )
            ->get()
            ->map(function ($receipt) {
                $receipt->unit_price = (float)$receipt->unit_price;
                return $receipt;
            });

        $pendingOrders = DB::table('pending_orders')
            ->join('items', 'items.id', '=', 'pending_orders.item_id')
            ->where('pending_orders.user_id', $user->id)
            ->select(
                'pending_orders.*',
                'items.item_name as item_name',
                'items.image_url'
            )
            ->get();

        // Return user data and token
        return response()->json([
            'user' => [
                'profile_picture' => $user->profile_picture,
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'phone_number' => $user->phone_number,
                'default_address' => [
                    'street' => $user->street,
                    'city' => $user->city,
                    'region' => $user->region,
                    'postal_code' => $user->postal_code,
                ],
                'overall_spend' => $user->overall_spend ?? 0,
                'average_spend' => $user->average_spend ?? 0,
                'items_ordered' => $user->items_ordered ?? 0,
                'cart_items_quantity' => $user->cart_items_quantity ?? 0,
                'pending_orders_quantity' => $user->pending_orders_quantity ?? 0,
                'cart_items' => $cartItems,
                'purchase_receipts' => $purchaseReceipts,
                'pending_orders' => $pendingOrders,
            ],
            'token' => $token,
        ]);
    }

    public function signUp(SignUpRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'phone_number' => $data['phone_number'],
            'street' => $data['street'],
            'city' => $data['city'],
            'region' => $data['region'],
            'postal_code' => $data['postal_code'],
        ]);

        $token = $user->createToken('main')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'profile_picture' => null,
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role, 
                'phone_number' => $user->phone_number,
                'password' => $user->password,
                'default_address' => [
                    'street' => $user->street,
                    'city' => $user->city,
                    'region' => $user->region,
                    'postal_code' => $user->postal_code,
                ],
                'overall_spend' => 0,
                'average_spend' => 0,
                'items_ordered' => 0,
                'cart_items_quantity' => 0,
                'pending_orders_quantity' => 0,
                'cart_items' => [],
                'purchase_receipts' => [],
                'pending_orders' => [],
            ],
        ]);
    }

    public function logout (Request $request)
    {
       $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated.',
                'role' => 'guest'
            ], 401); // 401 Unauthorized
        }
        $user->currentAccessToken()->delete();
        return response()->json([
            'message' => 'Successfully logged out.',
            'role' => 'guest'
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'username' => 'nullable|string|max:255|unique:userstable,username,' . $id,
            'email' => 'nullable|email|max:255|unique:userstable,email,' . $id,
            'password' => 'nullable|string|min:6',
            'phone_number' => 'nullable|string|max:20',
            'default_address.street' => 'nullable|string|max:255',
            'default_address.city' => 'nullable|string|max:255',
            'default_address.region' => 'nullable|string|max:255',
            'default_address.postal_code' => 'nullable|string|max:20',
        ]);

        if ($request->hasFile('profile_picture')) {
            $file = $request->file('profile_picture');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_pictures', $filename, 'public');
            $user->profile_picture = '/storage/' . $path;
        }

        if ($request->has('username')) $user->username = $request->username;
        if ($request->has('email')) $user->email = $request->email;
        if ($request->has('phone_number')) $user->phone_number = $request->phone_number;

        if ($request->filled('password')) {
            if (Hash::needsRehash($request->password)) {
                $user->password = $request->password; // Already hashed
            } else {
                $user->password = Hash::make($request->password);
            }
        }

        if ($request->has('default_address')) {
            $address = $request->input('default_address');
            if (isset($address['street'])) $user->street = $address['street'];
            if (isset($address['city'])) $user->city = $address['city'];
            if (isset($address['region'])) $user->region = $address['region'];
            if (isset($address['postal_code'])) $user->postal_code = $address['postal_code'];
        }

        $user->save();

        // --- Aggregates ---
        $overallSpend = DB::table('purchase_receipts')
            ->where('user_id', $user->id)
            ->sum('grand_total');

        $averageSpend = DB::table('purchase_receipts')
            ->selectRaw('DATE(ordered_at) as date, SUM(grand_total) as total')
            ->where('user_id', $user->id)
            ->groupBy(DB::raw('DATE(ordered_at)'))
            ->get()
            ->avg('total');

        $itemsOrdered = DB::table('pending_orders')
            ->where('user_id', $user->id)
            ->count();

        // --- Existing joins ---
        $cartItems = DB::table('cart_items')
            ->join('items', 'items.id', '=', 'cart_items.item_id')
            ->where('cart_items.user_id', $user->id)
            ->select(
                'items.id as item_id',
                'items.item_name as item_name',
                'items.image_url',
                'cart_items.quantity',
                'cart_items.unit_price',
                'cart_items.grand_total',
                'cart_items.stocks'
            )
            ->get();

        $purchaseReceipts = DB::table('purchase_receipts')
            ->join('items', 'items.id', '=', 'purchase_receipts.item_id')
            ->where('purchase_receipts.user_id', $user->id)
            ->select(
                'purchase_receipts.*',
                'items.item_name as item_name',
                'items.image_url'
            )
            ->get();

        $pendingOrders = DB::table('pending_orders')
            ->join('items', 'items.id', '=', 'pending_orders.item_id')
            ->where('pending_orders.user_id', $user->id)
            ->select(
                'pending_orders.*',
                'items.item_name as item_name',
                'items.image_url'
            )
            ->get();

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => [
                'profile_picture' => $user->profile_picture,
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
                'phone_number' => $user->phone_number,
                'default_address' => [
                    'street' => $user->street,
                    'city' => $user->city,
                    'region' => $user->region,
                    'postal_code' => $user->postal_code,
                ],
                'overall_spend' => round($overallSpend ?? 0, 2),
                'average_spend' => round($averageSpend ?? 0, 2),
                'items_ordered' => $itemsOrdered ?? 0,
                'cart_items' => $cartItems ?? [],
                'purchase_receipts' => $purchaseReceipts ?? [],
                'pending_orders' => $pendingOrders ?? [],
            ],
        ]);
    }


    public function deleteUser($id)
    {
        $user = DB::table('userstable')->where('id', $id)->first();
        
        if (!$user) {
            return response()->json([
                'message' => "User with ID {$id} not found."
            ], 404);
        }

        DB::table('userstable')->where('id', $id)->delete();
        return response()->json([
            'message' => "User with ID {$id} has been deleted successfully."
        ], 200);
    }

    public function displayAdminDetails(Request $request) 
    {
        $total_items = DB::table('items')
            ->count('id');

        $total_sold = DB::table('purchase_receipts')
            ->sum('quantity');

        $total_earning = DB::table('purchase_receipts')
            ->sum('grand_total');

        // Calculate average earnings per day
        $first_purchase = DB::table('purchase_receipts')->min('created_at');
        $last_purchase = DB::table('purchase_receipts')->max('created_at');

        if ($first_purchase && $last_purchase && $total_earning > 0) {
            $days = max(1, now()->diffInDays(\Carbon\Carbon::parse($first_purchase))); // Avoid division by zero
            $average_earnings_perDay = $total_earning / $days;
        } else {
            $average_earnings_perDay = 0;
        }

        $total_users = DB::table('userstable')->count('id');

        $total_pending_orders = DB::table('pending_orders')->count('id');

        return response()->json([
            'total_items' => $total_items,
            'total_sold' => $total_sold,
            'total_earning' => $total_earning,
            'average_earnings_per_day' => round($average_earnings_perDay, 2),
            'total_users' => $total_users,
            'total_pending_orders' => $total_pending_orders
        ]);
        
    }

    
}
