<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;

/**
 * Serialize all CSV documents, this is for quicker access as PHP CSV loading is very slow
 * and very memory hungry. This will also convert column names to a simplified format.
 */
class CsvSerialization
{
    const ENABLED = true;
    const ORDER = 1;

    const ZERO_CONTENT = [
        'GatheringType',
        'Cabinet',
        'World'
    ];

    public function handle()
    {
        // Create the storage directory
        Tools::FileManager()->createDirectory(GameData::ROOT);
        
        $versions = (new SaintCoinach())->versions();
        print_r($versions);

        // Get SaintCoinach Sheet data
        $sheets  = (new SaintCoinach())->sheets();
        $total   = count($sheets);
        $current = 0;

        foreach ($sheets as $i => $sheet) {
            $current++;
            $start     = microtime(true);
            $sheetName = str_pad($sheet->sheet, 50, ' ', STR_PAD_RIGHT);
            $document  = Tools::SaintCsv()->get($sheet->sheet);

            // remove the 0 entry from all documents that are not in the
            // zero content list, this is because SE will often use 0 as
            // a default value, causing auto-linking of content to spiral
            // out of control.
            if (!in_array($sheet->sheet, self::ZERO_CONTENT)) {
                foreach ($document->Documents as $j => $doc) {
                    if ((int)$doc->ID === 0) {
                        unset($document->Documents[$j]); break;
                    }
                }
            }

            // append on default column and sheet definitions
            $document->DefaultColumn = $sheet->defaultColumn ?? null;
            $document->Definitions   = $sheet->definitions;

            // serialize and save
            GameData::savePreDocument($sheet->sheet, $document);
            unset($data);

            $duration = str_pad(round(microtime(true) - $start, 3) ." sec", 10);
            $counter  = str_pad("{$current}/{$total}", 8);
            Tools::Console()->text(
                "- <info>{$counter}</info> {$sheetName} {$duration} - ". Tools::Memory()->report()
            );
        }

        unset($data, $sheets);
    }
}
