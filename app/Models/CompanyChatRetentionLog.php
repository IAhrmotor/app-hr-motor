<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyChatRetentionLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'executed_at',
        'cutoff',
        'status',
        'deleted_count',
        'affected_user_ids',
        'affected_users',
        'error_count',
        'error_summary',
        'errors',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'executed_at' => 'datetime',
            'cutoff' => 'datetime',
            'affected_user_ids' => 'array',
            'affected_users' => 'array',
            'errors' => 'array',
        ];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'success' ? 'Correcto' : 'Con errores';
    }
}
