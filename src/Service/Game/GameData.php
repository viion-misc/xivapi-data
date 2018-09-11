<?php

namespace App\Service\Game;

use App\Service\Game\{
    Main\DataMainBuild,
    Post\DataPostBuild,
    Pre\DataPreBuild
};

/**
 * Build CSV game content into JSON files.
 */
class GameData
{
    /** @var DataPreBuild */
    private $dataPreBuild;
    /** @var DataMainBuild */
    private $dataMainBuild;
    /** @var DataPostBuild */
    private $dataPostBuild;

    public function __construct()
    {
        $this->dataPreBuild = new DataPreBuild();
        $this->dataMainBuild = new DataMainBuild();
        $this->dataPostBuild = new DataPostBuild();
    }


    public function PreBuild()
    {
        $this->dataPreBuild
            //->processCsvSerialization()
            //->processMinionAndMountLargeIcons()
            //->processMapImages()
            ->processQuestIcons()
            ->processRecipeIcons()
        ;
    }

    public function MainBuild()
    {
    }

    public function PostBuild()
    {
    }
}
