<?php

namespace App\Service\IO;

class CsvCache
{
    const FOLDER = __DIR__ . '/Resources/CsvCache';

    private static $files = [];

    /**
     * Look for a local cache of CSV data, if one does not exist, download it.
     */
    public static function check()
    {
        Console::text("Verify game files ...");

        $files = array_diff(scandir(self::FOLDER), ['.','..','.gitkeep']);

        if (!empty($files)) {
            Console::text('CsvCache Ready');
            return;
        }

        // request to download csv files
        if (Console::isAuto() || Console::confirm("Download the latest CSV files from GitHub?")) {
            $ex = self::getExJson();

            // download each CSV file
            Console::text("Downloading ". count($ex->sheets) ." CSV files ...");
            Timer::start();
            foreach ($ex->sheets as $sheet) {
                self::save(
                    "/csv/{$sheet->sheet}.csv",
                    Http::csv($sheet->sheet)
                );
            }

            Console::text("Completed: ". Timer::stop(true) .' - '. Memory::report(true));
        }
    }

    /**
     * Download ex.json
     */
    public static function getExJson()
    {
        $ex = Http::ex();

        // issue downloading json
        if (json_last_error()) {
            throw new \Exception("Could not download ex.json file, reason: ". json_last_error_msg());
        }

        // Save version
        self::save('/json/ex.json', json_encode($ex));
        self::save('/json/ex.original.json', json_encode($ex));
        Game::version();

        return $ex;
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

    /**
     * Load a file
     */
    public static function load($filename)
    {
        // return instanced version
        if (isset(self::$files[$filename])) {
            return self::$files[$filename];
        }

        // check exists
        if (!file_exists(self::FOLDER . $filename)) {
            Console::text("File does not exist: ". $filename);
            return false;
        }

        // load
        $data = file_get_contents(self::FOLDER . $filename);

        // instance cached
        self::$files[$filename] = $data;
        return $data;
    }
}
