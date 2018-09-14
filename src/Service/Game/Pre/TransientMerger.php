<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;

/**
 * A lot of documents have transient files which have extended information (usually descriptions)
 * these can be merged onto the main sheet
 */
class TransientMerger
{
    const ENABLED = true;
    const ORDER = 50;

    public function handle()
    {
        // Get SaintCoinach Sheet data
        foreach ((new SaintCoinach())->sheets() as $i => $sheet) {
            // look for a transient file
            $transientSheetName = "{$sheet->sheet}Transient";
            $transientData = GameData::loadDocument($transientSheetName);
            if (!$transientData) {
                continue;
            }

            // grab the base document
            $document = GameData::loadDocument($sheet->sheet);
            if (!$document) {
                continue;
            }

            Tools::Console()->text("- Transient file found: ". $transientSheetName);
            foreach ($transientData->Documents as $j => $transient) {
                // remove some columns
                unset($transient->ID);

                if (empty(array_values((array)$transient))) {
                    continue;
                }

                foreach ($transient as $col => $value) {
                    // store transient column
                    $document->Columns->{$col} = $transientData->Columns->{$col};

                    // store transient value
                    $document->Documents[$j]->{$col} = $value;
                }
            }

            // save document
            GameData::saveDocument($sheet->sheet, $document);
        }
    }
}
