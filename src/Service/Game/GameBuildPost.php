<?php

namespace App\Service\Game;

/**
 * Perform custom modifications to the
 * game data after it has been built
 */
class GameBuildPost
{
    const FOLDER = __DIR__.'/BuildPre';

    public static function process()
    {
        $files = array_diff(scandir(self::FOLDER), ['.','..','.gitkeep']);

        print_r($files);
        die;
    }
}
