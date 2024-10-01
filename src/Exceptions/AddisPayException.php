<?php

namespace AshenafiPixel\AddisPaySDK\Exceptions;

use Exception;

class AddisPayException extends Exception
{
    /**
     * Custom constructor for AddisPayException.
     *
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     */
    public function __construct($message = "AddisPay SDK Error", $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
