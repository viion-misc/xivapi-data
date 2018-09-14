<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Console;
use App\Service\Tools\Tools;

/**
 * Add the hex value to Stain colours
 */
class StainHexCodes
{
    const ENABLED = true;
    const ORDER = 100;

    public function handle()
    {
        $document = GameData::loadDocument('Stain');

        // add columns
        GameData::addColumn($document, 'Hex', 'string');
        foreach ($document->Documents as $stain) {
            $stain->Hex = str_pad(dechex($stain->Color), 6, '0', STR_PAD_LEFT);
        }

        GameData::saveDocument('Stain', $document);
    }
}
