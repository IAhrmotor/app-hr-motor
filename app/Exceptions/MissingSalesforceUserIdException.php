<?php

namespace App\Exceptions;

class MissingSalesforceUserIdException extends CommercialCommissionException
{
    public function __construct()
    {
        parent::__construct('El usuario no tiene Salesforce User ID configurado.');
    }
}
