<?php

namespace App\Service\IO;

class Http
{
    /**
     * Download a file
     */
    public static function download($filename)
    {
        return file_get_contents($filename);
    }
}
