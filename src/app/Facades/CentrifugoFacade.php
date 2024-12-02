<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;
use Opekunov\Centrifugo\Centrifugo;

class CentrifugoFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return Centrifugo::class;
    }
}
