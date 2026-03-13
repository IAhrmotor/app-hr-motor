<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const DEFAULT_AVATAR_PATH = 'images/users/hrmotor-default-user-avatar.png';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'salesforce_user_id',
        'avatar_path',
        'linkedin_url',
        'password',
        'is_active',
        'must_change_password',
        'activated_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->avatar_path)) {
                $user->avatar_path = self::DEFAULT_AVATAR_PATH;
            }
        });
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        return asset($this->avatar_path ?: self::DEFAULT_AVATAR_PATH);
    }
}
