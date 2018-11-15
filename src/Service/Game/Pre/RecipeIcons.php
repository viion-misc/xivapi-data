<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Console;
use App\Service\Tools\Tools;

/**
 * Copy RewardItem icon to the Recipe so there is an icon at base level
 */
class RecipeIcons
{
    const ENABLED = false;
    const ORDER = 100;

    public function handle()
    {
        $document     = GameData::loadPreDocument('Recipe');
        $itemDocument = GameData::getDocumentsByField(GameData::loadPreDocument('Item'), 'ID');

        // add columns
        GameData::addColumn($document, 'Icon', 'Image');
        GameData::addColumn($document, 'IconID', 'Image');

        foreach ($document->Documents as $recipe) {
            /** @var \stdClass $item */
            $item = $itemDocument[$recipe->ItemResult] ?? false;

            if (!$item) {
                // should this ever happen....
                continue;
            }

            $recipe->Icon = $item->Icon;
            $recipe->IconID = $item->IconID;
        }

        GameData::savePreDocument('Recipe', $document);
    }
}
