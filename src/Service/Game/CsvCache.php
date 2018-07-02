<?php

namespace App\Service\Game;

use App\Service\IO\Console;
use App\Service\IO\Download;

class CsvCache
{
    const FOLDER = __DIR__.'/resources/CsvCache';

    public static function verify()
    {
        Console::title(__CLASS__);

        $files = array_diff(scandir(self::FOLDER), ['.','..','.gitkeep']);

        if (!empty($files)) {
            Console::text('CsvCache contains files.');
            return;
        }

        // request to download csv files
        if (Console::isAuto() || Console::confirm("Download the latest CSV files from GitHub?")) {
            $ex = Download::ex();

            // issue downloading json
            if (json_last_error()) {
                Console::error(json_last_error_msg());
                return;
            }

            Console::text("Version: <comment>{$ex->version}</comment>");
            self::save('/json/ex.json', json_encode($ex));

            Console::text("Downloading ". count($ex->sheets) ." CSV files ...");
            foreach ($ex->sheets as $sheet) {
                self::save(
                    "/csv/{$sheet->sheet}.csv",
                    Download::csv($sheet->sheet)
                );
            }
        }
    }

    /**
     * Save a file
     */
    public static function save($filename, $data)
    {
        // ensure folder exists
        $pi = pathinfo($filename);
        if (!is_dir(self::FOLDER . $pi['dirname'])) {
            mkdir(self::FOLDER . $pi['dirname'], 0777, true);
        }

        // save file
        file_put_contents(
            self::FOLDER . $filename,
            $data
        );
    }
}
