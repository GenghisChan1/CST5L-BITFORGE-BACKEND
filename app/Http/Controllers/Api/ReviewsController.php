<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Itemsrate;
use App\Models\PurchaseReceipt;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ReviewsController extends Controller
{
    public function RecordReviews(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:userstable,id',
            'item_id' => 'required|exists:items,id',
            'rate' => 'required|integer|min:1|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Check if a rating from the user already exists for the item
            $existingRate = Itemsrate::where('user_id', $request->user_id)
                ->where('item_id', $request->item_id)
                ->first();

            $useralreadypurchased = PurchaseReceipt::where('user_id', $request->user_id)
                ->where('item_id', $request->item_id)
                ->first();

            if (!$useralreadypurchased) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'You must purchase this item before rating.',
                ], 403); // 403 is more appropriate for "forbidden" access
            }

            if ($existingRate) {
                // Update existing rate
                $existingRate->rate = $request->rate;
                $existingRate->save();

                $reviews_count = Itemsrate::where('item_id', $request->item_id)->count();
                $totalrate = Itemsrate::where('item_id', $request->item_id)->sum('rate');
                $average_rating = $totalrate / $reviews_count;

                $item = Item::findOrFail($request->item_id);
                $item->reviews_count = $reviews_count;
                $item->average_rating = $average_rating;
                $item->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Rating updated successfully.',
                    'id' => $existingRate->id,
                ], 200);
            } else {
                // Create new rate
                $itemsrate = Itemsrate::create([
                    'user_id' => $request->user_id,
                    'item_id' => $request->item_id,
                    'rate' => $request->rate
                ]);

                $reviews_count = Itemsrate::where('item_id', $request->item_id)->count();
                $totalrate = Itemsrate::where('item_id', $request->item_id)->sum('rate');
                $average_rating = $totalrate / $reviews_count;

                $item = Item::findOrFail($request->item_id);
                $item->reviews_count = $reviews_count;
                $item->average_rating = $average_rating;
                $item->save();

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Rating created successfully.',
                    'id' => $itemsrate->id,
                ], 200);
            }

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to record review',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function AddComment(Request $request){
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:userstable,id',
            'item_id' => 'required|exists:items,id',
            'comment' => 'required|string|min:3|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Invalid input.',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $comment = Comment::create([
                'item_id' => $request->item_id,
                'user_id' => $request->user_id,
                'comment' => $request->comment,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'comment created successfully.',
                'id' => $comment->id,
            ], 200);


        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
