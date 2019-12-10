<?php

namespace Shetabit\Chunky\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Class viewer
 *
 * @package Shetabit\Chunky\Facade
 */
class Chunky extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    public static function getFacadeAccessor()
    {
        return 'shetabit-chunky';
    }
}
