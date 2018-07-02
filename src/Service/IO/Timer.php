<?php

namespace App\Service\IO;

use Carbon\Carbon;

class Timer
{
    const FORMAT = '%y year, %m months, %d days, %h hours, %i minutes and %s seconds';

    /** @var Carbon */
    protected static $startTime;

    /**
     * Start a clock
     */
    public static function start(): void
    {
        self::$startTime = Carbon::now();
    }

    /**
     * End the clock!
     */
    public static function stop($return = false): ?string
    {
        $duration = self::$startTime->diff(Carbon::now())->format(self::FORMAT);

        if ($return) {
            return $duration;
        }

        Console::text([
            "", "Duration: <info>{$duration}</info>", "",
        ]);
    }
}
