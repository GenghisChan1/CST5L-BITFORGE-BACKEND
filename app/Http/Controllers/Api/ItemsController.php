<?php

namespace App\Http\Controllers\api;

use Exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Item;
use App\Models\Comment;
use App\Models\Itemsrate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;


class ItemsController extends Controller
{
    public function store(Request $request)
    {
        Log::info('Received request data:', $request->all());
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'item_name' => 'required|string|max:255',
            'tags' => [
                'required',
                'string',
                'regex:/^([a-z0-9\-]+( [a-z0-9\-]+)*)(,([a-z0-9\-]+( [a-z0-9\-]+)*))*$/i'
            ],
            'warehouse' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
            'processor_brand' => 'nullable|string',
            'processor_model' => 'nullable|string',
            'processor_cores' => 'nullable|string',
            'processor_threads' => 'nullable|string',
            'processor_base_clock' => 'nullable|string',
            'processor_boost_clock' => 'nullable|string',
            'gpu_brand' => 'nullable|string',
            'gpu_model' => 'nullable|string',
            'gpu_vram' => 'nullable|string',
            'gpu_clock_speed' => 'nullable|string',
            'ram_type' => 'nullable|string',
            'ram_capacity' => 'nullable|string',
            'ram_speed' => 'nullable|string',
            'storage_type' => 'nullable|string',
            'storage_capacity' => 'nullable|string',
            'motherboard_chipset' => 'nullable|string',
            'motherboard_form_factor' => 'nullable|string',
            'motherboard_socket' => 'nullable|string',
            'display_size' => 'nullable|string',
            'display_resolution' => 'nullable|string',
            'display_panel_type' => 'nullable|string',
            'display_refresh_rate' => 'nullable|string',
            'keyboard_type' => 'nullable|string',
            'keyboard_backlit' => 'nullable|string',
            'mouse_type' => 'nullable|string',
            'mouse_dpi' => 'nullable|string',
            'microphone_type' => 'nullable|string',
            'microphone_pattern' => 'nullable|string',
            'headset_driver_size' => 'nullable|string',
            'battery_capacity' => 'nullable|string',
            'battery_life' => 'nullable|string',
            'ports' => 'nullable|string',
            'connectivity' => 'nullable|string',
            'operating_system' => 'nullable|string',
            'power_supply' => 'nullable|string',
            'cooling' => 'nullable|string',
            'dimensions' => 'nullable|string',
            'weight' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            Log::error('Validation failed:', $validator->errors()->toArray());
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $path = $request->file('image')->store('items', 'public');
            $imageUrl = '/storage/' . $path;

            $data = [
                'image_url' => $imageUrl,
                'item_name' => $request->item_name,
                'tags' => $request->tags,
                'warehouse' => $request->warehouse,
                'price' => $request->price,
                'stocks' => $request->stocks,
                'processor' => json_encode([
                    'brand' => $request->processor_brand,
                    'model' => $request->processor_model,
                    'cores' => $request->processor_cores,
                    'threads' => $request->processor_threads,
                    'base_clock' => $request->processor_base_clock,
                    'boost_clock' => $request->processor_boost_clock,
                ]),
                'graphics_card' => json_encode([
                    'brand' => $request->gpu_brand,
                    'model' => $request->gpu_model,
                    'vram' => $request->gpu_vram,
                    'clock_speed' => $request->gpu_clock_speed,
                ]),
                'ram' => json_encode([
                    'type' => $request->ram_type,
                    'capacity' => $request->ram_capacity,
                    'speed' => $request->ram_speed,
                ]),
                'storage' => json_encode([
                    'type' => $request->storage_type,
                    'capacity' => $request->storage_capacity,
                ]),
                'motherboard' => json_encode([
                    'chipset' => $request->motherboard_chipset,
                    'form_factor' => $request->motherboard_form_factor,
                    'socket' => $request->motherboard_socket,
                ]),
                'display' => json_encode([
                    'size' => $request->display_size,
                    'resolution' => $request->display_resolution,
                    'panel_type' => $request->display_panel_type,
                    'refresh_rate' => $request->display_refresh_rate,
                ]),
                'keyboard' => json_encode([
                    'type' => $request->keyboard_type,
                    'backlit' => $request->keyboard_backlit,
                ]),
                'mouse' => json_encode([
                    'type' => $request->mouse_type,
                    'dpi' => $request->mouse_dpi,
                ]),
                'microphone' => json_encode([
                    'type' => $request->microphone_type,
                    'pattern' => $request->microphone_pattern,
                ]),
                'headset' => json_encode([
                    'driver_size' => $request->headset_driver_size,
                ]),
                'battery' => json_encode([
                    'capacity' => $request->battery_capacity,
                    'life' => $request->battery_life,
                ]),
                'ports' => $request->ports,
                'connectivity' => $request->connectivity,
                'operating_system' => $request->operating_system,
                'power_supply' => $request->power_supply,
                'cooling' => $request->cooling,
                'dimensions' => $request->dimensions,
                'weight' => $request->weight,
            ];

            $itemData = array_merge($data, ['id' => Item::generateItemId()]);
            $item = Item::create($itemData);

            return response()->json([
                'message' => 'Item created successfully.',
                'item' => $item
            ], 201);
        } catch (Exception $e) {
            Log::error('Item creation failed:', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request_data' => $request->all()
        ]);
        return response()->json([
            'message' => 'Server error',
            'error' => $e->getMessage() // Only in development!
        ], 500);
            }
    }

    public function show(Request $request, $id)
    {
        // Find the item by ID
        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Calculate the average rating from the itemsrate table
        //$totalRating = Itemsrate::where('item_id', $item->id)->sum('rate'); // Sum all ratings for this item
        //$ratingCount = Itemsrate::where('item_id', $item->id)->count(); // Count the number of ratings
        $rating = round($item->average_rating, 1);

        // Convert comma-separated tags and arrays to actual arrays
        $tags = $item->tags ? explode(',', $item->tags) : [];
        $ports = $item->ports ? explode(',', $item->ports) : [];
        $connectivity = $item->connectivity ? explode(',', $item->connectivity) : [];

        // Trim all entries (to remove leading/trailing spaces)
        $tags = array_map('trim', $tags);
        $ports = array_map('trim', $ports);
        $connectivity = array_map('trim', $connectivity);

        // Load comments with user info
        $comments = Comment::where('item_id', $item->id)
            ->with(['user' => function ($query) {
                $query->select('id', 'username', 'role', 'profile_picture');
            }])
            ->orderBy('created_at', 'desc')  // <-- This line ensures newest to oldest
            ->get()
            ->map(function ($comment) {
                return [
                    'profile_picture' =>$comment->user->profile_picture ?? null,
                    'user_id' => $comment->user->id ?? null,
                    'username' => $comment->user->username ?? null,
                    'role' => $comment->user->role ?? null,
                    'comment' => $comment->comment,
                ];
            });

        // Return the response with the data
        $data = [
            'item_id' => $item->id,
            'image_url' => $item->image_url,
            'item_name' => $item->item_name,
            'tags' => $tags,
            'warehouse' => $item->warehouse,
            'price' => (float) $item->price,
            'rating' => (float) $rating,  // Using the calculated rating
            'stocks' => (int) $item->stocks,
            'specs' => [
                'processor' => json_decode($item->processor, true),
                'graphics_card' => json_decode($item->graphics_card, true),
                'ram' => json_decode($item->ram, true),
                'storage' => json_decode($item->storage, true),
                'motherboard' => json_decode($item->motherboard, true),
                'display' => json_decode($item->display, true),
                'battery' => json_decode($item->battery, true),
                'keyboard' => json_decode($item->keyboard, true),
                'mouse' => json_decode($item->mouse, true),
                'microphone' => json_decode($item->microphone, true),
                'headset' => json_decode($item->headset, true),
                'ports' => $ports,
                'connectivity' => $connectivity,
                'operating_system' => $item->operating_system,
                'power_supply' => $item->power_supply,
                'cooling' => $item->cooling,
                'dimensions' => $item->dimensions,
                'weight' => $item->weight,
            ],
            'comments' => $comments
        ];

        return response()->json($data, 200);
    }

    public function update(Request $request, $id)
    {
        // Find the item or return 404
        $item = Item::findOrFail($id);

        // Validate the request data
        $validator = Validator::make($request->all(), [
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'item_name' => 'required|string|max:255',
            'tags' => [
                'required',
                'string',
                'regex:/^([a-z0-9\-]+( [a-z0-9\-]+)*)(,([a-z0-9\-]+( [a-z0-9\-]+)*))*$/i'
            ],
            'warehouse' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'stocks' => 'required|integer|min:0',
            
            // Processor fields
            'processor_brand' => 'nullable|string',
            'processor_model' => 'nullable|string',
            'processor_cores' => 'nullable|string',
            'processor_threads' => 'nullable|string',
            'processor_base_clock' => 'nullable|string',
            'processor_boost_clock' => 'nullable|string',
            
            // Graphics card fields
            'gpu_brand' => 'nullable|string',
            'gpu_model' => 'nullable|string',
            'gpu_vram' => 'nullable|string',
            'gpu_clock_speed' => 'nullable|string',
            
            // RAM fields
            'ram_type' => 'nullable|string',
            'ram_capacity' => 'nullable|string',
            'ram_speed' => 'nullable|string',
            
            // Storage fields
            'storage_type' => 'nullable|string',
            'storage_capacity' => 'nullable|string',
            
            // Motherboard fields
            'motherboard_chipset' => 'nullable|string',
            'motherboard_form_factor' => 'nullable|string',
            'motherboard_socket' => 'nullable|string',
            
            // Display fields
            'display_size' => 'nullable|string',
            'display_resolution' => 'nullable|string',
            'display_panel_type' => 'nullable|string',
            'display_refresh_rate' => 'nullable|string',
            
            // Battery fields
            'battery_capacity' => 'nullable|string',
            'battery_life' => 'nullable|string',
            
            // Keyboard fields
            'keyboard_type' => 'nullable|string',
            'keyboard_backlit' => 'nullable|string',
            
            // Mouse fields
            'mouse_type' => 'nullable|string',
            'mouse_dpi' => 'nullable|string',
            
            // Microphone fields
            'microphone_type' => 'nullable|string',
            'microphone_pattern' => 'nullable|string',
            
            // Headset fields
            'headset_driver_size' => 'nullable|string',
            
            // Other fields
            'ports' => 'nullable|string',
            'connectivity' => 'nullable|string',
            'operating_system' => 'nullable|string',
            'power_supply' => 'nullable|string',
            'cooling' => 'nullable|string',
            'dimensions' => 'nullable|string',
            'weight' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Handle image upload if new image is provided
            if ($request->hasFile('image')) {
                // Delete old image if it exists
                if ($item->image_url) {
                    $oldImagePath = str_replace('/storage/', 'public/', $item->image_url);
                    Storage::delete($oldImagePath);
                }
                
                // Store new image
                $path = $request->file('image')->store('items', 'public');
                $item->image_url = '/storage/' . $path;
            }

            // Update basic fields
            $item->item_name = $request->item_name;
            $item->tags = $request->tags;
            $item->warehouse = $request->warehouse;
            $item->price = $request->price;
            $item->stocks = $request->stocks;
            
            // Update JSON fields
            $item->processor = json_encode([
                'brand' => $request->processor_brand ?? null,
                'model' => $request->processor_model ?? null,
                'cores' => $request->processor_cores ?? null, 
                'threads' => $request->processor_threads ?? null,
                'base_clock' => $request->processor_base_clock ?? null,
                'boost_clock' => $request->processor_boost_clock ?? null,
            ]);
            
            $item->graphics_card = json_encode([
                'brand' => $request->gpu_brand ?? null,
                'model' => $request->gpu_model ?? null,
                'vram' => $request->gpu_vram ?? null,
                'clock_speed' => $request->gpu_clock_speed ?? null,
            ]);
            
            $item->ram = json_encode([
                'type' => $request->ram_type ?? null,
                'capacity' => $request->ram_capacity ?? null,
                'speed' => $request->ram_speed ?? null,
            ]);
            
            $item->storage = json_encode([
                'type' => $request->storage_type ?? null,
                'capacity' => $request->storage_capacity ?? null,
            ]);
            
            $item->motherboard = json_encode([
                'chipset' => $request->motherboard_chipset ?? null,
                'form_factor' => $request->motherboard_form_factor ?? null,
                'socket' => $request->motherboard_socket ?? null,
            ]);
            
            $item->display = json_encode([
                'size' => $request->display_size ?? null,
                'resolution' => $request->display_resolution ?? null,
                'panel_type' => $request->display_panel_type ?? null, 
                'refresh_rate' => $request->display_refresh_rate ?? null, 
            ]);
            
            $item->battery = json_encode([
                'capacity' => $request->battery_capacity ?? null,
                'life' => $request->battery_life ?? null,
            ]);
            
            $item->keyboard = json_encode([
                'type' => $request->keyboard_type ?? null,
                'backlit' => $request->keyboard_backlit ?? null, 
            ]);
            
            $item->mouse = json_encode([
                'type' => $request->mouse_type ?? null,
                'dpi' => $request->mouse_dpi ?? null,
            ]);
            
            $item->microphone = json_encode([
                'type' => $request->microphone_type ?? null,
                'pattern' => $request->microphone_pattern ?? null,
            ]);
            
            $item->headset = json_encode([
                'driver_size' => $request->headset_driver_size ?? null,
            ]);
            
            // Update other fields
            $item->ports = $request->ports ?? null;
            $item->connectivity = $request->connectivity ?? null;
            $item->operating_system = $request->operating_system ?? null;
            $item->power_supply = $request->power_supply ?? null;
            $item->cooling = $request->cooling ?? null;
            $item->dimensions = $request->dimensions ?? null;
            $item->weight = $request->weight ?? null;
            
            // Save the updated item
            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'item' => $item
            ]);

        } catch (\Exception $e) {
            Log::error('Item update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
                'error' => $e->getMessage()
            ], 500);
        }
    }   

    public function destroy($id)
    {
        try {
            $item = Item::findOrFail($id);
            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Item deleted permanently'
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Item not found'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateStocks(Request $request, $id)
    {
        $item = Item::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'stocks' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $item->stocks = $request->stocks;

            $item->save();

            return response()->json([
                'success' => true,
                'message' => 'Item updated successfully',
                'item' => $item
            ]);

        } catch (\Exception $e) {
            Log::error('Item update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update item',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
