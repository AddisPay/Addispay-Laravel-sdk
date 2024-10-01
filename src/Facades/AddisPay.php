<?php

namespace AshenafiPixel\AddisPaySDK\Facades;

use Illuminate\Support\Facades\Facade;

class AddisPay extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'addispay';
    }
}
