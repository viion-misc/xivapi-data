<?php

namespace App\Service\Game\Pre;

use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;

/**
 * Pulls all the data together and reformat some areas of it for easier usage.
 */
class DataPreBuild
{
    /**
     * Serialize all CSV documents, this is for quicker access as PHP CSV loading is very slow
     * and very memory hungry. This will also convert column names to a simplified format.
     */
    public function serializeCsvDocuments()
    {
        Tools::Timer()->start();
        Tools::Console()->section('Serialize CSV Documents');

        // Create the storage directory
        Tools::FileManager()->createDirectory(__DIR__ .'/../data/');

        // Get SaintCoinach Sheet data
        $sheets  = (new SaintCoinach())->sheets();
        $total   = count($sheets);
        $current = 0;

        Tools::Console()->text("Processing: {$total} CSV sheets");

        foreach ($sheets as $i => $sheet) {
            $current++;
            $start     = microtime(true);
            $sheetName = str_pad($sheet->sheet, 50, ' ', STR_PAD_RIGHT);

            $data = Tools::SaintCsv()->get('Achievement');

            // append on default column and sheet definitions
            $data->DefaultColumn = $sheet->defaultColumn ?? null;
            $data->Definitions   = $sheet->definitions;

            // serialize and save
            file_put_contents(__DIR__ . "/../data/{$sheet->sheet}.serialize", serialize($data));
            unset($data);

            $duration = str_pad(round(microtime(true) - $start, 3) ." sec", 10);
            $counter  = str_pad("{$current}/{$total}", 8);
            Tools::Console()->text(
                "- <info>{$counter}</info> {$sheetName} {$duration} - ". Tools::Memory()->report()
            );
        }

        unset($data, $sheets);

        Tools::Console()->text([
            '',
            'CSV Serialization complete',
            'This action does not need to be run every time, only on game updates',
            Tools::Timer()->stop(),
            Tools::Memory()->report(),
            ''
        ]);
    }
}
