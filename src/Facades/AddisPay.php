<?php

namespace AddisPay\AddisPaySDK\Facades;

use Illuminate\Support\Facades\Facade;

class AddisPay extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'addispay';
    }
}
