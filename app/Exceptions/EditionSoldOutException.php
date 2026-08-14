<?php

namespace App\Exceptions;

use RuntimeException;

class EditionSoldOutException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The Founding Edition is sold out.');
    }
}
