<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;

/**
 * Add JPG map image URLs to Map content
 */
class MapFileImages
{
    const ENABLED = true;
    const ORDER = 100;

    public function handle()
    {
        $document = GameData::loadDocument('Map');

        // add column
        GameData::addColumn($document, 'FileImage', 'Image');

        // process each map
        foreach ($document->Documents as $row => $data) {
            if (empty($data->File_en)) {
                continue;
            }

            [$folder, $layer] = explode('/', $data->File_en);
            $document->Documents[$row]->FileImage = "/m/{$folder}/{$folder}.{$layer}.jpg";
        }

        GameData::saveDocument('Map', $document);
    }
}

