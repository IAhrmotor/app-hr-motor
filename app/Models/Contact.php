<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'enreach_extension',
    ];

    public function setPhoneAttribute($value): void
    {
        $this->attributes['phone'] = $this->normalizeAgendaValue($value);
    }

    public function setEnreachExtensionAttribute($value): void
    {
        $this->attributes['enreach_extension'] = $this->normalizeAgendaValue($value);
    }

    protected function normalizeAgendaValue(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }
}
