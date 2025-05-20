<?php

namespace App\Http\Controllers\Api;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use App\Models\Item;
use App\models\PendingOrder;

class PopulateController extends Controller
{
    public function populateUsers(Request $request)
    {
        $sortBy = $request->query('sortby'); // Optional sort: 'id' or 'username'
        $username = $request->query('username'); // Optional search term

        // Validate sortBy if present
        if ($sortBy && !in_array($sortBy, ['id', 'username'])) {
            return response()->json([
                'message' => "Invalid sort parameter. Use 'id' or 'username'."
            ], 400);
        }

        // Base query
        $query = DB::table('userstable')->select(
            'id as user_id',
            'profile_picture',
            'username',
            'email',
            'role',
            'phone_number',
            'street',
            'city',
            'region',
            'postal_code'
        );

        // Filter by username if provided
        if ($username) {
            $query->where('username', 'like', '%' . $username . '%');
        }

        // Determine sorting
        if ($sortBy) {
            $query->orderBy($sortBy);
        } else {
            $query->orderBy('id'); // default sorting
        }

        // Execute query
        $users = $query->get();

        // Format response
        $formatted = $users->map(function ($user) {
            return [
                'profile_picture' => $user->profile_picture ?? null,
                'user_id' => $user->user_id,
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
            ];
        });

        return response()->json($formatted, 200);
    }

    public function populateTopPreBuildDesktops(Request $request)
    {
        try {
            $items = Item::where('tags', 'like', '%pre-build desktop%')
                ->where('stocks', '>', 0)
                ->orderBy('reviews_count', 'desc')
                ->orderBy('id', 'desc')
                ->take(4)
                ->get()
                ->map(function ($item) {
                    return [
                        'item_id' => $item->id, // Use simple 'id' key
                        'image_url' => $item->image_url,
                        'item_name' => $item->item_name,
                        'tags' => array_map('trim', explode(',', $item->tags)),
                        'price' => 'PHP' . number_format($item->price, 0, '.', ','),
                        'stocks' => (int) $item->stocks,
                        'rating' => round($item->average_rating, 1),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Top pre-build desktops retrieved successfully',
                'data' => $items
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve pre-build desktops',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function populatetopitems(Request $request)
    {
        $quer1 = $request->query('query1'); // Optional search term
        $quer2 = $request->query('query2'); // Optional search term

        try {
            $items = Item::where('tags', 'like', '%'.$quer1.'%')
                ->where('tags', 'like', '%'.$quer2.'%')
                ->where('stocks', '>', 0)
                ->orderBy('reviews_count', 'desc')
                ->orderBy('id', 'desc')
                ->take(4)
                ->get()
                ->map(function ($item) {
                    return [
                        'item_id' => $item->id, // Use simple 'id' key
                        'image_url' => $item->image_url,
                        'item_name' => $item->item_name,
                        'tags' => array_map('trim', explode(',', $item->tags)),
                        'price' => 'PHP' . number_format($item->price, 0, '.', ','),
                        'stocks' => (int) $item->stocks,
                        'rating' => round($item->average_rating, 1),
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Top gaming desktops retrieved successfully',
                'data' => $items
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve gaming desktops',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchItems(Request $request)
    {
        $query = Item::query();

        $searchBy = strtolower($request->query('searchby', 'tags')); // default to tags
        $keyword = strtolower($request->query('keyword', ''));
        $sort = strtolower($request->query('sortby', '')); // stocks, price, ratings
        $noStockItems = filter_var($request->query('noStockItems', true), FILTER_VALIDATE_BOOLEAN);

        // 🔍 SEARCHING
        if (!empty($keyword)) {
            if ($searchBy === 'id') {
                $query->where('id', $keyword);
            } elseif ($searchBy === 'name') {
                $query->where('item_name', 'LIKE', '%' . $keyword . '%');
            } else { // default: tags
                $query->where('tags', 'LIKE', '%' . $keyword . '%');
            }
        }

        if (!$noStockItems) { $query->where('stocks', '>', 0); }

        // 🔃 SORTING
        if ($sort === 'stocks') {
            $query->orderByDesc('stocks')->orderBy('id');
        } elseif ($sort === 'price') {
            $query->orderByDesc('price')->orderBy('id');
        } elseif ($sort === 'ratings') {
            $query->orderByDesc('average_rating')->orderBy('id');
        } elseif ($sort === 'rated') {
            $query->orderByDesc('average_rating')->orderBy('id');
        } elseif ($sort === 'popularity') {
            $query->orderByDesc('reviews_count')->orderBy('id');
        } elseif ($sort === 'latest') {
            $query->orderByDesc('id');
        } else {
            $query->orderBy('id');
        }

        $items = $query->get();

        // 📦 TRANSFORM
        $formattedItems = $items->map(function ($item) {
            return [
                'item_id' => $item->id,
                'image_url' => $item->image_url,
                'item_name' => $item->item_name,
                'tags' => array_map('trim', explode(',', strtolower($item->tags))),
                'price' => 'PHP' . number_format($item->price, 0),
                'rating' => round($item->average_rating, 1),// Simulated
                'stocks' => $item->stocks,
            ];
        });

        return response()->json($formattedItems);
    }

    
    public function AdminPendingOrders(Request $request)
    {
        try {
            $query = PendingOrder::with(['item:id,image_url,tags,item_name'])
                ->select([
                    'pending_orders.id as pending_order_id',
                    'pending_orders.item_id',
                    'pending_orders.user_id',
                    'pending_orders.quantity as amount',
                    'pending_orders.shipping_fee',
                    'pending_orders.grand_total',
                    'pending_orders.fullname',
                    'pending_orders.phone_number',
                    'pending_orders.ordered_at',
                    'pending_orders.deliver_to'
                ]);

            // Apply filters if provided
            if ($request->has('item_id')) {
                $query->where('item_id', $request->item_id);
            }

            if ($request->has('tag')) {
                $query->whereHas('item', function($q) use ($request) {
                    $q->where('tags', 'like', '%'.$request->tag.'%');
                });
            }

            // Apply sorting (default: oldest first)
            $sortOrder = $request->input('sort', 'oldest') === 'newest' ? 'desc' : 'asc';
            $query->orderBy('ordered_at', $sortOrder);

            $pendingOrders = $query->get()
                ->map(function ($order) {
                    return [
                        'pending_order_id' => $order->pending_order_id,
                        'item_id' => $order->item_id,
                        'user_id' => $order->user_id,
                        'image_url' => $order->item->image_url,
                        'fullname' => $order->fullname,
                        'phone_number' => $order->phone_number,
                        'amount' => $order->amount,
                        'address' => $order->deliver_to,
                        'item_name' => $order->item->item_name,
                        'shipping_fee' => (float)$order->shipping_fee,
                        'grand_total' => (float)$order->grand_total,
                        'ordered_at' => $order->ordered_at instanceof \Carbon\Carbon 
                            ? $order->ordered_at->toDateTimeString() 
                            : $order->ordered_at,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pendingOrders,
                'message' => 'Pending orders retrieved successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch pending orders',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    
}
