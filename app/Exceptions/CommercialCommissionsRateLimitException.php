<?php

namespace App\Exceptions;

class CommercialCommissionsRateLimitException extends CommercialCommissionException
{
    public function __construct(public readonly ?int $retryAfter = null)
    {
        parent::__construct('Se ha alcanzado temporalmente el límite de peticiones del servicio de comisiones.');
    }
}
