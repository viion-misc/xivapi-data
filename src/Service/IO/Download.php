<?php

namespace App\Service\IO;

class Download
{
    /**
     * Download the ex json file
     */
    public static function ex()
    {
        return json_decode(
            self::download(
                getenv('APP_SAINT_EX')
            )
        );
    }

    /**
     * Download a CSV File
     */
    public static function csv($filename)
    {
        return self::download(
            sprintf(getenv("APP_SAINT_CSV"), $filename)
        );
    }

    /**
     * Download a file
     */
    public static function download($filename)
    {
        Console::text("Downloading: <info>{$filename}</info>");
        return file_get_contents($filename);
    }
}
