<?php

namespace App\Service\Tools;

class Download
{
    public function get(string $filename): string
    {
        return file_get_contents($filename);
    }
}
