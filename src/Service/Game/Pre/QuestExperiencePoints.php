<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;

/**
 * Add the correct Experience Points reward onto quests
 */
class QuestExperiencePoints
{
    const ENABLED = false;
    const ORDER = 100;

    public function handle()
    {
        $questDocument      = GameData::loadPreDocument('Quest');
        $paramGrowDocument  = GameData::loadPreDocument('ParamGrow');

        // add the new experience points column
        GameData::addColumn($questDocument, 'ExperiencePoints', 'int');

        // process each quest
        foreach ($questDocument->Documents as $i => $quest) {
            // Skip quests with weird levels
            if ($quest->ClassJobLevel0 < 1 || $quest->ClassJobLevel0 > 100) {
                continue;
            }

            // grab the param grow for this quest
            $paramGrow = $paramGrowDocument->Documents[$quest->ClassJobLevel0];

            // CORE = Quest.ExpFactor * ParamGrow.QuestExpModifier * (45 + (5 * Quest.ClassJobLevel0)) / 100
            $EXP = $quest->ExpFactor * $paramGrow->QuestExpModifier * (45 + (5 * $quest->ClassJobLevel0)) / 100;

            // CORE + ((400 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-52) * (400 * (Quest.ExpFactor/100))))
            if (in_array($quest->ClassJobLevel0, [50])) {
                $EXP = $EXP + ((400 * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 50) * (400 * ($quest->ExpFactor / 100))));
            }

            // CORE + ((800 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-52) * (800 * (Quest.ExpFactor/100))))
            else if (in_array($quest->ClassJobLevel0, [51])) {
                $EXP = $EXP + ((800 * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 50) * (400 * ($quest->ExpFactor / 100))));
            }

            // CORE + ((2000 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-52) * (2000 * (Quest.ExpFactor/100))))
            else if (in_array($quest->ClassJobLevel0, [52,53,54,55,56,57,58,59])) {
                $EXP = $EXP + ((2000  * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 52) * (2000  * ($quest->ExpFactor / 100))));
            }

            // CORE + ((37125 * (Quest.ExpFactor / 100)) + ((Quest.ClassJobLevel0-60) * (3375 * (Quest.ExpFactor/100))))
            else if (in_array($quest->ClassJobLevel0, [60,61,62,63,64,65,66,67,68,69])) {
                $EXP = $EXP + ((37125  * ($quest->ExpFactor / 100)) + (($quest->ClassJobLevel0 - 60) * (3375  * ($quest->ExpFactor / 100))));
            }

            $questDocument->Documents[$i]->ExperiencePoints = $EXP;
        }

        GameData::savePreDocument('Quest', $questDocument);
    }
}
