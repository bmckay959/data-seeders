<?php

namespace Bmckay959\DataSeeders\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Bmckay959\DataSeeders\DataSeeders
 */
class DataSeeders extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Bmckay959\DataSeeders\DataSeeders::class;
    }
}
