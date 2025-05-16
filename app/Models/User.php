<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    // ✅ Use the custom table name
    protected $table = 'userstable';

    // ✅ Use custom primary key (string, like 'u1')
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'username',
        'email',
        'password',
        'phone_number',
        'street',
        'city',
        'region',
        'postal_code',
        'profile_picture',
        'role',
    ];

    /**
     * The attributes that should be hidden for arrays and JSON serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to other types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Automatically boot and assign custom user ID.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            // ✅ Ensure role is set before generating the ID
            if (empty($user->role)) {
                $user->role = 'user'; // default to 'user' if not provided
            }

            if (empty($user->id)) {
                $user->id = self::generateUserId($user->role);
            }
        });
    }

    /**
     * Generate a custom user ID based on the user's role.
     */
    public static function generateUserId($role)
    {
        if ($role === 'admin') {
            // Admin IDs: u1 to u999
            $lastAdmin = self::where('role', 'admin')
                ->whereRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) <= 999")
                ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                ->first();

            $nextIdNum = $lastAdmin ? intval(substr($lastAdmin->id, 1)) + 1 : 1;
            return 'u' . $nextIdNum;
        }

        if ($role === 'user') {
            // User IDs: u1000+
            $lastUser = self::where('role', 'user')
                ->whereRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) >= 1000")
                ->orderByRaw("CAST(SUBSTRING(id, 2) AS UNSIGNED) DESC")
                ->first();

            $nextIdNum = $lastUser ? intval(substr($lastUser->id, 1)) + 1 : 1000;
            return 'u' . $nextIdNum;
        }

        // fallback: null (should not happen if role is correctly set)
        return null;
    }
}
