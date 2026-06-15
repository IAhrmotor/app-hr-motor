<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminPermissionGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_permission_group_user')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function grants(): HasMany
    {
        return $this->hasMany(AdminPermissionGrant::class, 'group_id');
    }
}
