<?php

namespace App\Exceptions;

class InvalidCommercialCommissionsResponseException extends CommercialCommissionException
{
    public function __construct()
    {
        parent::__construct('La respuesta del servicio de comisiones no tiene un formato válido.');
    }
}
