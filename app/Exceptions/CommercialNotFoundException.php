<?php

namespace App\Exceptions;

class CommercialNotFoundException extends CommercialCommissionException
{
    public function __construct()
    {
        parent::__construct('El comercial no existe o no es elegible para consultar comisiones.');
    }
}
