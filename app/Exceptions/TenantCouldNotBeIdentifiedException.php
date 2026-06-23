<?php

namespace App\Exceptions;

use Exception;

class TenantCouldNotBeIdentifiedException extends Exception
{
    public function __construct($message = "Tenant or Workspace could not be identified for the current context.")
    {
        parent::__construct($message);
    }
}
