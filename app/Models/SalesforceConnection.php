<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalesforceConnection extends Model
{
    protected $fillable = [
        'provider',
        'instance_url',
        'access_token',
        'refresh_token',
        'token_type',
        'scope',
        'expires_at',
        'last_synced_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'expires_at' => 'datetime',
            'last_synced_at' => 'datetime',
            'metadata' => 'array',
        ];
    }
}
