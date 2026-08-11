<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Str;

trait HandlesAgendaExtensions
{
    protected function agendaPhoneRules(bool $required = false): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'max:32',
        ]));
    }

    protected function agendaExtensionRules(bool $required = false, ?int $ignoreUserId = null, ?int $ignoreContactId = null): array
    {
        return array_values(array_filter([
            $required ? 'required' : 'nullable',
            'string',
            'max:20',
        ]));
    }

    protected function agendaValidationHook(Validator $validator, ?int $ignoreUserId = null, ?int $ignoreContactId = null): void
    {
        $validator->after(function (Validator $validator) use ($ignoreUserId, $ignoreContactId): void {
            $enreach = $this->normalizeAgendaValue($validator->getData()['enreach_extension'] ?? null);

            if ($enreach && $this->agendaExtensionExists('enreach_extension', $enreach, $ignoreUserId, $ignoreContactId)) {
                $validator->errors()->add('enreach_extension', 'Esa extension de Enreach ya esta asignada.');
            }
        });
    }

    protected function normalizeAgendaValue(mixed $value): ?string
    {
        $normalized = preg_replace('/\D+/', '', trim((string) $value));

        return $normalized === '' ? null : $normalized;
    }

    protected function normalizeAgendaText(mixed $value): string
    {
        return Str::ascii(Str::lower(trim((string) $value)));
    }

    protected function agendaExtensionExists(string $field, string $value, ?int $ignoreUserId = null, ?int $ignoreContactId = null): bool
    {
        $userQuery = User::query()->where($field, $value);
        $contactQuery = Contact::query()->where($field, $value);

        if ($ignoreUserId) {
            $userQuery->whereKeyNot($ignoreUserId);
        }

        if ($ignoreContactId) {
            $contactQuery->whereKeyNot($ignoreContactId);
        }

        return $userQuery->exists() || $contactQuery->exists();
    }
}
