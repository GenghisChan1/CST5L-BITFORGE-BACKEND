<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Item extends Model
{
    use HasFactory;

    public $incrementing = false;         // Prevent Eloquent from treating 'id' as auto-increment
    protected $keyType = 'string';        // Treat 'id' as a string

    protected $fillable = [
        'image_url',
        'item_name',
        'tags',
        'warehouse',
        'price',
        'stocks',
        'processor',
        'graphics_card',
        'ram',
        'storage',
        'motherboard',
        'display',
        'battery',
        'keyboard',
        'mouse',
        'microphone',
        'headset',
        'ports',
        'connectivity',
        'operating_system',
        'power_supply',
        'cooling',
        'dimensions',
        'weight',
    ];

    protected $casts = [
        'processor' => 'array',
        'graphics_card' => 'array',
        'ram' => 'array',
        'storage' => 'array',
        'motherboard' => 'array',
        'display' => 'array',
        'battery' => 'array',
        'keyboard' => 'array',
        'mouse' => 'array',
        'microphone' => 'array',
        'headset' => 'array',
    ];

    /**
     * Boot method to handle model events.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            if (empty($item->id)) {
                $item->id = static::generateItemId();
            }
        });
    }

    /**
     * Generate sequential ID in i1, i2, i3... format
     */
    public static function generateItemId()
    {
        try {
            return DB::transaction(function () {
                // Get the highest existing numeric ID
                $lastItem = static::select('id')
                    ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $lastItem ? ((int) substr($lastItem->id, 1)) + 1 : 1;
                
                // Verify the ID doesn't exist (handles gaps in sequence)
                while (static::where('id', 'i'.$nextNumber)->exists()) {
                    $nextNumber++;
                }

                return 'i' . $nextNumber;
            });
        } catch (\Exception $e) {
            // Fallback mechanism if transaction fails
            $lastItem = static::select('id')
                ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                ->first();

            $nextNumber = $lastItem ? ((int) substr($lastItem->id, 1)) + 1 : 1;
            
            // Add random number to prevent collisions in fallback scenario
            return 'i' . ($nextNumber + mt_rand(1, 100));
        }
    }
}
