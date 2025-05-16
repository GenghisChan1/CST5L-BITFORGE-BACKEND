<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Comment extends Model
{
    use HasFactory;

    public $incrementing = false;   // Disable auto-increment
    protected $keyType = 'string';  // Treat ID as string

    protected $table = 'comments';

    protected $fillable = [
        'item_id',
        'user_id',
        'comment',
        // note: 'id' is deliberately omitted
    ];

    // Relationship with Item
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id');
    }

    // Relationship with User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Generate sequential ID in cm1, cm2, cm3... format
     */
    public static function generateCommentId()
    {
        try {
            return DB::transaction(function () {
                $last = static::select('id')
                    ->orderByRaw("CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC")
                    ->lockForUpdate()
                    ->first();

                $nextNumber = $last ? ((int) substr($last->id, 2)) + 1 : 1;

                // Ensure uniqueness (handles any gaps)
                while (static::where('id', 'cm' . $nextNumber)->exists()) {
                    $nextNumber++;
                }

                return 'cm' . $nextNumber;
            });
        } catch (Exception $e) {
            // Fallback if transaction fails
            $last = static::select('id')
                ->orderByRaw("CAST(SUBSTRING(id, 3) AS UNSIGNED) DESC")
                ->first();

            $nextNumber = $last ? ((int) substr($last->id, 2)) + 1 : 1;

            return 'cm' . ($nextNumber + mt_rand(1, 100));
        }
    }

    /**
     * Boot method to auto-assign ID before creating
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($comment) {
            if (empty($comment->id)) {
                $comment->id = self::generateCommentId();
            }
        });
    }
}
