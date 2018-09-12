<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Console;
use App\Service\Tools\Tools;

/**
 * Copy RewardItem names to the Recipe name
 */
class RecipeNames
{
    const ENABLED = false;
    const ORDER = 100;

    public function handle()
    {
        $document = GameData::loadDocument('Recipe');
        $itemDocument = GameData::loadDocument('Item');

        // convert for easy access
        $itemDocument = GameData::getDocumentsByField($itemDocument, 'ID');

        // add columns
        GameData::addColumn($document, 'Name_en', 'string');
        GameData::addColumn($document, 'Name_de', 'string');
        GameData::addColumn($document, 'Name_fr', 'string');
        GameData::addColumn($document, 'Name_ja', 'string');

        foreach ($document->Documents as $recipe) {
            /** @var \stdClass $item */
            $item = $itemDocument[$recipe->ItemResult] ?? false;

            if (!$item) {
                // should this ever happen....
                continue;
            }

            $recipe->Name_en = $item->Name_en;
            $recipe->Name_de = $item->Name_de;
            $recipe->Name_fr = $item->Name_fr;
            $recipe->Name_ja = $item->Name_ja;
        }

        GameData::saveDocument('Recipe', $document);
    }
}
