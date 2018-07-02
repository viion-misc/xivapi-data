<?php

namespace App\Service\IO;

class Memory
{
    public static function report($return = false)
    {
        $size   = memory_get_usage();
        $unit   = ['b','kb','mb','gb','tb','pb'];
        $memory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        $text   = "Memory Usage: <comment>{$memory}</comment>";

        if ($return) {
            return $text;
        }

        Console::text($text);
    }
}
