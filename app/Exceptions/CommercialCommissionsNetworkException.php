<?php

namespace App\Exceptions;

class CommercialCommissionsNetworkException extends CommercialCommissionException
{
    public function __construct()
    {
        parent::__construct('No se ha podido conectar con el servicio de comisiones.');
    }
}
