<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


class Itemsrate extends Model
{
    use HasFactory;
    
    protected $table = 'itemsrate'; // Specify the table name if different
    
    public $incrementing = false; // Prevent auto-increment
    protected $keyType = 'string'; // Use string for ID

    protected $fillable = [
        'item_id',
        'user_id',
        'rate',
    ];

    // Relationships
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Generate sequential ID in ir1, ir2, ir3... format
     */
    public static function generateItemsRateId()
    {
        try {
            return DB::transaction(function () {
                $lastRate = static::select('id')
                    ->orderByRaw("CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC")
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $lastRate ? ((int) substr($lastRate->id, 2)) + 1 : 1;

                while (static::where('id', 'ir' . $nextNumber)->exists()) {
                    $nextNumber++;
                }

                return 'ir' . $nextNumber;
            });
        } catch (\Exception $e) {
            // Fallback mechanism if transaction fails
            $lastRate = static::select('id')
                ->orderByRaw("CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC")
                ->first();

            $nextNumber = $lastRate ? ((int) substr($lastRate->id, 2)) + 1 : 1;

            return 'ir' . ($nextNumber + mt_rand(1, 100));
        }
    }

    /**
     * Boot method to assign ID when creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($itemRate) {
            if (empty($itemRate->id)) {
                $itemRate->id = self::generateItemsRateId();
            }
        });
    }
}
