<?php

namespace App\Service\Game;

use App\Service\IO\Console;

class Csv
{
    const FOLDER = __DIR__.'/Resources/csv/';

    public function __construct()
    {
        // create folder if it does not exist
        if (!is_dir(self::FOLDER)) {
            mkdir(self::FOLDER, 0777, true);
        }

        // check cache
        if (!$this->isCacheEmpty()) {
            Console::text("CSV Cache is ready");
            return;
        }

        // initialize saint coinach
        $saintCoinach = new SaintCoinach();

        // extract CSV's
        $saintCoinach->extractCsvs();


        die;

    }

    private function isCacheEmpty()
    {
        $files = array_diff(scandir(self::FOLDER), ['.','..']);
        return empty($files);
    }
}
