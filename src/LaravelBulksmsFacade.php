<?php

namespace BluedotBd\LaravelBulksms;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \BluedotBd\LaravelBulksms\LaravelBulksms to(string $to)
 * @method static \BluedotBd\LaravelBulksms\LaravelBulksms message(string $message)
 * @method static \BluedotBd\LaravelBulksms\LaravelBulksms line(string $line = '')
 * @method static object|string send()
 * @method static float balance()
 * @method static array config()
 * @method static void loadConfig(string $config)
 * @method static array checkAndSave(array $params, string $url, string $config)
 *
 * @see \BluedotBd\LaravelBulksms\LaravelBulksms
 */
class LaravelBulksmsFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return LaravelBulksms::class;
    }
}
