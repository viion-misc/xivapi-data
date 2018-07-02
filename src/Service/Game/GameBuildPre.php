<?php

namespace App\Service\Game;

use App\Service\Game\BuildPre\GameContentNames;
use App\Service\IO\Console;
use App\Service\IO\Memory;
use App\Service\IO\Timer;

/**
 * Perform custom modifications to the
 * game data before it has been built
 */
class GameBuildPre
{
    const FOLDER = __DIR__.'/BuildPre';

    public static function process()
    {
        Console::section("Game Content Pre-Build");

        $files = array_diff(scandir(self::FOLDER), ['.','..','.gitkeep']);
        $classes = [];

        foreach ($files as $filename) {
            $filename = str_ireplace('.php', null, $filename);
            /** @var GameContentNames $class */
            $class = "\\App\\Service\\Game\\BuildPre\\". $filename;
            $classes[$class] = $class::PRIORITY;
        }

        // sort by priority
        arsort($classes);

        // run each one
        Timer::start();
        foreach ($classes as $class => $priority) {
            Console::text("[<info>{$class}</info>]");
            (new $class())->process();
        }

        Console::text("Completed: ". Timer::stop(true) .' - '. Memory::report(true));
    }
}
