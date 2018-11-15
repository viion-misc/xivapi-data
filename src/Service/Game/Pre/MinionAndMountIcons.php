<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;

/**
 * Append on the large icon for Minions and Mounts
 */
class MinionAndMountIcons
{
    const ENABLED = false;
    const ORDER = 100;

    public function handle()
    {
        // the folder paths to replace to access the "large" icon
        $rep = [
            '/004' => '/068',
            '/008' => '/077',
        ];

        foreach (['Companion', 'Mount'] as $contentName) {
            $document = GameData::loadPreDocument($contentName);

            // add column
            GameData::addColumn($document, 'IconLarge', 'Image');

            // add each large icon
            foreach ($document->Documents as $row => $data) {
                $document->Documents[$row]->IconLarge = str_ireplace(array_keys($rep), $rep, $data->Icon);
            }

            // save
            GameData::savePreDocument($contentName, $document);
            unset($document);
        }
    }
}
