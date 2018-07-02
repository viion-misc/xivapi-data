<?php

namespace App\Service\Game;

use App\Service\IO\Console;

class Game
{
    /**
     * Print and return game version
     */
    public static function version()
    {
        $ex = CsvCache::load('/json/ex.json');
        $version = $ex ? json_decode($ex)->version : false;
        Console::text("Game Version: <comment>". ($version ?: 'Unknown') ."</comment>");
        return $version;
    }
}
