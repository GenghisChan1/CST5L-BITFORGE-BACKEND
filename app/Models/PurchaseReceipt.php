<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PurchaseReceipt extends Model
{
    use HasFactory;

    public $incrementing = false; // Prevent auto-increment
    protected $keyType = 'string'; // Use string for ID

    protected $table = 'purchase_receipts';

    protected $fillable = [
        'user_id',
        'item_id',
        'quantity',
        'total_price',
        'shipping_fee',
        'grand_total',
        'ordered_at',
        'delivered_at',
        'deliver_to',
        'shipped_from',
        'fullname',
        'phone_number'
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
     * Generate sequential ID in r1, r2, r3... format
     */
    public static function generateReceiptId()
    {
        try {
            return DB::transaction(function () {
                $last = static::select('id')
                    ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $last ? ((int) substr($last->id, 1)) + 1 : 1;

                while (static::where('id', 'r' . $nextNumber)->exists()) {
                    $nextNumber++;
                }

                return 'r' . $nextNumber;
            });
        } catch (Exception $e) {
            // Fallback mechanism if transaction fails
            $last = static::select('id')
                ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                ->first();

            $nextNumber = $last ? ((int) substr($last->id, 1)) + 1 : 1;

            return 'r' . ($nextNumber + mt_rand(1, 100));
        }
    }

    /**
     * Boot method to assign ID when creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($receipt) {
            if (empty($receipt->id)) {
                $receipt->id = self::generateReceiptId();
            }
        });
    }
}
