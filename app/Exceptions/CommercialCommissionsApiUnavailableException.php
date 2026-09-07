<?php

namespace App\Exceptions;

class CommercialCommissionsApiUnavailableException extends CommercialCommissionException
{
    public function __construct()
    {
        parent::__construct('El servicio de comisiones no está disponible temporalmente.');
    }
}
