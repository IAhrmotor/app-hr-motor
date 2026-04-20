<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';

    public const ROLE_MANAGER = 'gestor';

    public const ROLE_COMMERCIAL = 'comercial';

    public const ROLE_STORE_MANAGER = 'jefe_tienda';

    public const DEFAULT_AVATAR_PATH = 'images/users/hrmotor-default-user-avatar.png';

    public static function roleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Gestor',
            self::ROLE_COMMERCIAL => 'Comercial',
            self::ROLE_STORE_MANAGER => 'Jefe de tienda',
        ];
    }

    public static function notificationTargetRolesFor(self $user): array
    {
        return $user->role === self::ROLE_ADMIN
            ? [self::ROLE_ADMIN, self::ROLE_MANAGER, self::ROLE_COMMERCIAL, self::ROLE_STORE_MANAGER]
            : [self::ROLE_COMMERCIAL, self::ROLE_STORE_MANAGER];
    }

    public const DEALERSHIPS = [
        'Torrejón',
        'Manresa',
        'Pamplona',
        'Zaragoza',
        'Bilbao',
        'Sevilla',
        'Fontellas',
        'Valencia',
        'Alicante',
        'Villalba',
        'Sant Boi de Llobregat',
        'Rivas-Vaciamadrid',
        'Gijón',
        'Murcia',
        'Valladolid',
        'Alcalá de Guadaira',
        'Lleida',
        'San Sebastián',
        'Lliçà de Vall',
        'Alcobendas',
        'Badalona',
        'Girona',
        'Mallorca',
        'A Coruña',
        'Paterna',
        'Málaga',
        'Málaga Centro',
        'Castellón',
        'Sedaví',
        'Elche',
        'Alcoy',
        'Villarreal',
        'Dos Hermanas',
    ];

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
        'dealership',
        'dealership_id',
        'avatar_path',
        'linkedin_url',
        'phone',
        'threecx_extension',
        'enreach_extension',
        'password',
        'is_active',
        'must_change_password',
        'activated_at',
        'invitation_sent_at',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (blank($user->avatar_path)) {
                $user->avatar_path = self::DEFAULT_AVATAR_PATH;
            }
        });
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $this->normalizeAgendaValue($value);
    }

    public function setThreecxExtensionAttribute($value): void
    {
        $this->attributes['threecx_extension'] = $this->normalizeAgendaValue($value);
    }

    public function setEnreachExtensionAttribute($value): void
    {
        $this->attributes['enreach_extension'] = $this->normalizeAgendaValue($value);
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
            'invitation_sent_at' => 'datetime',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getAvatarUrlAttribute(): string
    {
        return asset($this->avatar_path ?: self::DEFAULT_AVATAR_PATH);
    }

    public function assignedDealership(): BelongsTo
    {
        return $this->belongsTo(Dealership::class, 'dealership_id');
    }

    public function forumThreads(): HasMany
    {
        return $this->hasMany(ForumThread::class);
    }

    public function forumReplies(): HasMany
    {
        return $this->hasMany(ForumReply::class);
    }

    public function getResolvedDealershipNameAttribute(): ?string
    {
        return $this->assignedDealership?->name ?: $this->dealership;
    }

    public function isCommercialLike(): bool
    {
        return in_array($this->role, [self::ROLE_COMMERCIAL, self::ROLE_STORE_MANAGER], true);
    }

    public function getRoleLabelAttribute(): string
    {
        return self::roleLabels()[$this->role] ?? 'Comercial';
    }

    public function isInvitationExpired(): bool
    {
        if ($this->is_active || ! $this->must_change_password) {
            return false;
        }

        $sentAt = $this->invitation_sent_at ?? $this->created_at;

        if (! $sentAt) {
            return false;
        }

        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);

        return $sentAt->lt(now()->subMinutes($expiresInMinutes));
    }

    protected function normalizeAgendaValue(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
