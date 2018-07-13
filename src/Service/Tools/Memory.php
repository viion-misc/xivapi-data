<?php

namespace App\Service\Tools;

class Memory
{
    public function get()
    {
        $size   = memory_get_usage();
        $unit   = ['b','kb','mb','gb','tb','pb'];
        $memory = @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
        return $memory;
    }
    
    public function report()
    {
        return "Memory Usage: <comment>{$this->get()}</comment>";
    }
}
