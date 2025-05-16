<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use Illuminate\Http\Request;
use App\Models\PendingOrder;
use App\Models\Item;
use App\Models\PurchaseReceipt;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\TryCatch;

class TransactionsController extends Controller
{
    public function storePendingOrders(Request $request)
    {
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:userstable,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'shipping_fee' => 'required|numeric|min:0',
            'deliver_to' => 'required|string|max:255',
            'shipped_from' => 'required|string|max:255',
            'fullname' => 'required|string|max:255',
            'phone_number' => 'required|string|max:15',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Use database transaction to ensure data consistency
        DB::beginTransaction();

        try {
            // Get the item and check stock availability
            $item = Item::findOrFail($request->item_id);
            
            if ($item->stocks < $request->quantity) {
                return response()->json([
                    'message' => 'Insufficient stock available.',
                    'available_stock' => $item->stocks
                ], 400);
            }

            // Calculate totals
            $totalAmount = $request->quantity * $request->unit_price;
            $grandTotal = $totalAmount + $request->shipping_fee;

            // Create the pending order
            $order = PendingOrder::create([
                'user_id' => $request->user_id,
                'item_id' => $request->item_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'deliver_to' => $request->deliver_to,
                'shipped_from' => $request->shipped_from,
                'total_amount' => $totalAmount,
                'shipping_fee' => $request->shipping_fee,
                'grand_total' => $grandTotal,
                'ordered_at' => Carbon::now(),
                'fullname' => $request->fullname,
                'phone_number' => $request->phone_number,
            ]);


            // Update the item's stock
            $item->decrement('stocks', $request->quantity);
            $item->save();

            DB::commit();

            return response()->json([
                'message' => 'Pending order created successfully.',
                'data' => $order,
                'updated_stock' => $item->stocks
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'Failed to create pending order.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function confirmOrders(Request $request)
    {
        $request->validate([
            'pending_order_id' => 'required|string|exists:pending_orders,id'
        ]);

        DB::beginTransaction();

        try {
            $pendingOrder = PendingOrder::findOrFail($request->pending_order_id);

            // Create the purchase receipt with delivered_at set to current time
            $purchaseReceipt = PurchaseReceipt::create([
                'user_id' => $pendingOrder->user_id,
                'item_id' => $pendingOrder->item_id,
                'quantity' => $pendingOrder->quantity,
                'total_price' => $pendingOrder->total_amount,
                'shipping_fee' => $pendingOrder->shipping_fee,
                'grand_total' => $pendingOrder->grand_total,
                'ordered_at' => $pendingOrder->ordered_at ?? now(), // Use current time if null
                'delivered_at' => now(), // Set delivered_at to current time
                'deliver_to' => $pendingOrder->deliver_to,
                'shipped_from' => $pendingOrder->shipped_from,
                'fullname' => $pendingOrder->fullname,
                'phone_number' => $pendingOrder->phone_number
            ]);

            $pendingOrder->delete();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order confirmed and marked as delivered',
                'receipt_id' => $purchaseReceipt->id,
                'delivered_at' => $purchaseReceipt->delivered_at->toDateTimeString()
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function storeCartItems(Request $request) 
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:userstable,id',
            'item_id' => 'required|exists:items,id',
            'quantity' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
            'stocks' => 'required|numeric|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        $item = Item::findOrFail($request->item_id);
            
        if ($item->stocks < $request->quantity) {
            return response()->json([
                'message' => 'Insufficient stock available.',
                'available_stock' => $item->stocks
            ], 400);
        }

        try {
            $grand_total = $request->quantity * $request->unit_price;

            $cart = CartItem::create([
                'user_id' => $request->user_id,
                'item_id' => $request->item_id,
                'quantity' => $request->quantity,
                'unit_price' => $request->unit_price,
                'stocks' => $request->stocks,
                'grand_total' => $grand_total
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order confirmed and marked as delivered',
                'id' => $cart->id,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm order',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCart($id)
    {
        try {
            $item = CartItem::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'cart deleted permanently'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'cart not found'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete cart',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
