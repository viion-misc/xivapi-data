<?php

namespace App\Service\IO;

class Memory
{
    public static function report()
    {
        $size   = memory_get_usage();
        $unit   = ['b','kb','mb','gb','tb','pb'];
        $memory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];

        Console::text("Memory Usage: <comment>{$memory}</comment>");
    }
}
