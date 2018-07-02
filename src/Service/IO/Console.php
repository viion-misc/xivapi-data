<?php

namespace App\Service\IO;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Console
{
    /** @var SymfonyStyle */
    private static $io;
    /** @var bool */
    private static $auto = false;

    /**
     * Set the console class;
     */
    public static function set(InputInterface $input, OutputInterface $output)
    {
        self::$io = new SymfonyStyle($input, $output);
    }

    /**
     * Set CLI as automatic
     */
    public static function setAuto()
    {
        self::$auto = true;
    }

    /**
     * Is CLI in auto mode?
     */
    public static function isAuto()
    {
        return self::$auto;
    }

    /**
     * write some tet
     */
    public static function text($text)
    {
        self::$io->text($text);
    }

    /**
     * write an error
     */
    public static function error($text)
    {
        self::$io->error($text);
    }

    /**
     * write a nice title
     */
    public static function title($title)
    {
        $bar     = str_pad('', 50, '-', STR_PAD_LEFT);
        $titleA  = str_pad(getenv('APP_NAME'), 50, ' ', STR_PAD_BOTH);
        $titleB  = str_pad($title, 50, ' ', STR_PAD_BOTH);

        self::$io->text([
            "<fg=yellow>+{$bar}+</>",
            "<fg=yellow>|{$titleA}|</>",
            "<fg=yellow>|{$titleB}|</>",
            "<fg=yellow>+{$bar}+</>",
            ''
        ]);
    }

    /**
     * Show a choice menu
     */
    public static function choice($list)
    {
        foreach ($list as $i => $line) {
            self::$io->text("<info>". ($i+1) ."</info> - {$line}");
        }

        return self::$io->ask('Please pick an option');
    }

    /**
     * Ask to confirm something
     */
    public static function confirm($text)
    {
        return self::$io->confirm($text);
    }
}
