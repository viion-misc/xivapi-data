<?php

namespace App\Service\Game;

use App\Service\Game\{
    Custom\DataCustomBuild,
    Post\DataPostBuild,
    Pre\DataPreBuild
};

/**
 * Build CSV game content into JSON files.
 */
class GameData
{
    public function PreBuild()
    {
        (new DataPreBuild())
            ->serializeCsvDocuments();
    }

    public function PostBuild()
    {
        (new DataPostBuild());
    }

    public function CustomBuild()
    {
        (new DataCustomBuild());
    }
}
