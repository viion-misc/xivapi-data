<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Tools\Tools;

/**
 * Swaps Banner for the icon and switches out some icons based on the content type of the quest
 */
class QuestIcons
{
    const ENABLED = false;
    const ORDER = 100;

    public function handle()
    {
        $document = GameData::loadPreDocument('Quest');
        $journalGenreDocument = GameData::loadPreDocument('JournalGenre');

        // add columns
        GameData::addColumn($document, 'Banner', 'Image');
        GameData::addColumn($document, 'BannerID', 'Image');
        GameData::addColumn($document, 'Icon', 'Image');
        GameData::addColumn($document, 'IconID', 'Image');
        GameData::addColumn($document, 'IconSmall', 'Image');
        GameData::addColumn($document, 'IconSmallID', 'Image');

        // process each document
        foreach ($document->Documents as $quest) {
            // move icon over to the banner
            $quest->Banner      = $quest->Icon;
            $quest->BannerID    = $quest->IconID;

            // set icon to a default of 71221
            $quest->IconID      = 71221;
            $quest->Icon        = Tools::SaintCsv()->getImagePath(71221);

            // set default small icon to that of the "Icon"
            $quest->IconSmall   = $quest->Icon;
            $quest->IconSmallID = $quest->IconID;

            // grab the journal genre
            $journalGenre = $journalGenreDocument->Documents[$quest->JournalGenre] ?? false;
            if ($journalGenre && $journalGenre->IconID) {
                // tweak some journal icons to higher res versions
                $ids = [
                    '61411' => '71221',
                    '61412' => '71201',
                    '61413' => '71222',
                    '61414' => '71281',
                    '61415' => '60552',
                    '61416' => '61436',

                    // grand companies
                    '61401' => '62951', // limsa
                    '61402' => '62952', // grid
                    '61403' => '62953', // uldah
                ];

                // Use the high res journal genre icon otherwise use whatever it is currently set to
                $quest->IconID = $ids[$journalGenre->IconID] ?? $journalGenre->IconID;
                $quest->Icon = Tools::SaintCsv()->getImagePath($quest->IconID);
            }

            // if there is a special icon, use that (eg Seasonal events get nice fancy icons)
            if ($quest->IconSpecial) {
                $quest->Icon      = $quest->IconSpecial;
                $quest->IconID    = $quest->IconSpecialID;
            }
        }

        GameData::savePreDocument('Quest', $document);
    }
}
