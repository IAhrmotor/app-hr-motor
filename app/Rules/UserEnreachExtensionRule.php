<?php

namespace App\Rules;

use App\Models\User;
use App\Services\EnreachExtensionConflictResolver;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UserEnreachExtensionRule implements ValidationRule
{
    public function __construct(
        protected readonly ?int $ignoreUserId = null,
        protected readonly string $action = 'crear',
    ) {
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $message = app(EnreachExtensionConflictResolver::class)
            ->resolveConflictMessage($value, $this->ignoreUserId, $this->action);

        if ($message !== null) {
            $fail($message);
        }
    }
}
