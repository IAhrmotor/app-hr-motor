<?php

namespace App\Exceptions;

class InvalidCommercialCommissionParametersException extends CommercialCommissionException
{
    public function __construct(string $message = 'Los parámetros de la consulta de comisiones no son válidos.')
    {
        parent::__construct($message);
    }
}
