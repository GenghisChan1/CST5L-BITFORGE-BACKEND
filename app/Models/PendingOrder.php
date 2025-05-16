<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PendingOrder extends Model
{
    use HasFactory;

    public $incrementing = false; // Prevent auto-increment
    protected $keyType = 'string'; // Use string for ID

    protected $fillable = [
        'user_id',
        'item_id',
        'quantity',
        'unit_price',
        'total_amount',
        'shipping_fee',
        'grand_total',
        'deliver_to',
        'shipped_from',
        'fullname',
        'phone_number',
        'ordered_at',
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
     * Generate sequential ID in po1, po2, po3... format
     */
    public static function generatePendingOrderId()
    {
        try {
            return DB::transaction(function () {
                $lastOrder = static::select('id')
                    ->orderByRaw("CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC")
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $lastOrder ? ((int) substr($lastOrder->id, 2)) + 1 : 1;

                while (static::where('id', 'po' . $nextNumber)->exists()) {
                    $nextNumber++;
                }

                return 'po' . $nextNumber;
            });
        } catch (Exception $e) {
            // Fallback mechanism if transaction fails
            $lastOrder = static::select('id')
                ->orderByRaw("CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC")
                ->first();

            $nextNumber = $lastOrder ? ((int) substr($lastOrder->id, 2)) + 1 : 1;

            return 'po' . ($nextNumber + mt_rand(1, 100));
        }
    }

    /**
     * Boot method to assign ID when creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->id)) {
                $order->id = self::generatePendingOrderId();
            }
        });
    }
}
