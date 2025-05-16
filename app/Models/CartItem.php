<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CartItem extends Model
{
    use HasFactory;

    public $incrementing = false; // Prevent auto-increment
    protected $keyType = 'string'; // Use string for ID

    protected $fillable = [
        'user_id',
        'item_id',
        'quantity',
        'unit_price',
        'grand_total',
        'stocks',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    /**
     * Generate sequential ID in c1, c2, c3... format
     */
    public static function generateCartItemId()
    {
        try {
            return DB::transaction(function () {
                $last = static::select('id')
                    ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $last ? ((int) substr($last->id, 1)) + 1 : 1;

                // Ensure uniqueness
                while (static::where('id', 'c' . $nextNumber)->exists()) {
                    $nextNumber++;
                }

                return 'c' . $nextNumber;
            });
        } catch (Exception $e) {
            // Fallback if the transaction fails
            $last = static::select('id')
                ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                ->first();

            $nextNumber = $last ? ((int) substr($last->id, 1)) + 1 : 1;

            return 'c' . ($nextNumber + mt_rand(1, 100));
        }
    }

    /**
     * Boot method to assign ID when creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($cartItem) {
            if (empty($cartItem->id)) {
                $cartItem->id = self::generateCartItemId();
            }
        });
    }
}
