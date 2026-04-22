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

    public const ROLE_USER = 'usuario';

    public const ROLE_COMMERCIAL = 'comercial';

    public const ROLE_STORE_MANAGER = 'jefe_tienda';

    public const ROLE_INFORMATION_TECHNOLOGY = 'informatica';

    public const ROLE_MARKETING = 'marketing';

    public const ROLE_ADMINISTRATION = 'administracion';

    public const ROLE_AREA_MANAGER = 'area_manager';

    public const ROLE_LEGAL = 'legal';

    public const ROLE_CALL_CENTER = 'call_center';

    public const ROLE_CAPTADOR = 'captador';

    public const ROLE_WORKSHOP = 'taller';

    public const ROLE_FINANCING = 'financiacion';

    public const ROLE_TRAINING = 'formacion';

    public const ROLE_MANAGEMENT = 'gerencia';

    public const ROLE_LOGISTICS = 'logistica';

    public const ROLE_HUMAN_RESOURCES = 'recursos_humanos';

    public const ROLE_SPARE_PARTS = 'recambios';

    public const ROLE_RENTING = 'renting';

    public const DEFAULT_AVATAR_PATH = 'images/users/hrmotor-default-user-avatar.png';

    public static function roleLabels(): array
    {
        return array_merge(self::baseRoleLabels(), self::extraRoleLabels());
    }

    public static function baseRoleLabels(): array
    {
        return [
            self::ROLE_ADMIN => 'Admin',
            self::ROLE_MANAGER => 'Gestor',
            self::ROLE_USER => 'Usuario',
        ];
    }

    public static function extraRoleLabels(): array
    {
        return [
            self::ROLE_COMMERCIAL => 'Comercial',
            self::ROLE_STORE_MANAGER => 'Jefe de tienda',
            self::ROLE_INFORMATION_TECHNOLOGY => 'Informática',
            self::ROLE_MARKETING => 'Marketing',
            self::ROLE_ADMINISTRATION => 'Administración',
            self::ROLE_AREA_MANAGER => 'Área Manager',
            self::ROLE_LEGAL => 'Legal',
            self::ROLE_CALL_CENTER => 'Call Center',
            self::ROLE_CAPTADOR => 'Captador',
            self::ROLE_WORKSHOP => 'Taller',
            self::ROLE_FINANCING => 'Financiación',
            self::ROLE_TRAINING => 'Formación',
            self::ROLE_MANAGEMENT => 'Gerencia',
            self::ROLE_LOGISTICS => 'Logística',
            self::ROLE_HUMAN_RESOURCES => 'Recursos Humanos',
            self::ROLE_SPARE_PARTS => 'Recambios',
            self::ROLE_RENTING => 'Renting',
        ];
    }

    public static function notificationTargetRolesFor(self $user): array
    {
        return $user->role === self::ROLE_ADMIN
            ? array_keys(self::roleLabels())
            : array_keys(self::extraRoleLabels());
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
        'extra_role',
        'salesforce_user_id',
        'dealership',
        'dealership_id',
        'avatar_path',
        'linkedin_url',
        'phone',
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
            if (blank($user->role)) {
                $user->role = self::ROLE_USER;
            }

            if (blank($user->avatar_path)) {
                $user->avatar_path = self::DEFAULT_AVATAR_PATH;
            }
        });
    }

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $this->normalizeAgendaValue($value);
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
        return $this->role === self::ROLE_USER && filled($this->extra_role);
    }

    public function isRankedCommercial(): bool
    {
        return $this->role === self::ROLE_USER
            && in_array($this->extra_role, [self::ROLE_COMMERCIAL, self::ROLE_STORE_MANAGER], true);
    }

    public function isStoreManager(): bool
    {
        return $this->extra_role === self::ROLE_STORE_MANAGER;
    }

    public function getRoleLabelAttribute(): string
    {
        $baseLabel = self::baseRoleLabels()[$this->role] ?? 'Usuario';
        $extraLabel = $this->extra_role ? (self::extraRoleLabels()[$this->extra_role] ?? ucfirst($this->extra_role)) : null;

        if (! $extraLabel) {
            return $baseLabel;
        }

        if ($this->role === self::ROLE_USER) {
            return $extraLabel;
        }

        return $baseLabel . ' · ' . $extraLabel;
    }

    public function getIsStoreManagerAttribute(): bool
    {
        return $this->isStoreManager();
    }

    public function getIsRankedCommercialAttribute(): bool
    {
        return $this->isRankedCommercial();
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
