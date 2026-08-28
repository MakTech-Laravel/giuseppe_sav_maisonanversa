<?php

namespace App\Exceptions;

use RuntimeException;

class EditionUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('The selected edition is no longer available.');
    }
}
