<?php

namespace App\Service\Tools;

class Memory
{
    private $average = [];

    public function get($size = null)
    {
        // keep track of the average
        $this->average[] = memory_get_usage();

        // calculate memory size to something human readable
        $size   = $size ?: memory_get_usage();
        $unit   = ['b','kb','mb','gb','tb','pb'];
        $memory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];

        return $memory;
    }
    
    public function report()
    {
        $max     = count($this->average) ? $this->get(max($this->average)) : '-';
        $average = count($this->average) > 3
            ? $this->get(array_sum($this->average) / count($this->average))
            : '-';

        $current = str_pad($this->get(), 10);
        $average = str_pad($average, 12, ' ', STR_PAD_BOTH);
        $max     = str_pad($max, 10, ' ', STR_PAD_LEFT);

        return "mem[<comment>{$current}</comment> <comment>{$average}</comment> <comment>{$max}</comment>]";
    }
}
