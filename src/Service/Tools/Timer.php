<?php

namespace App\Service\Tools;

use Carbon\Carbon;

class Timer
{
    const FORMAT = '%y year, %m months, %d days, %h hours, %i minutes and %s seconds';

    /** @var Carbon */
    protected $start;

    public function start(): void
    {
        $this->start = Carbon::now();
    }

    public function stop(): ?string
    {
        $duration = $this->start->diff(Carbon::now())->format(self::FORMAT);
        return "Duration: <info>{$duration}</info>";
    }
}
