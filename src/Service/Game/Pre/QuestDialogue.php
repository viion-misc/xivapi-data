<?php

namespace App\Service\Game\Pre;

use App\Service\Game\GameData;
use App\Service\Tools\Tools;

/**
 * Quest Dialogue and text data
 */
class QuestDialogue
{
    const ENABLED = true;
    const ORDER = 150;

    private $ENpcResidentToName = [];
    private $ENpcResidentToData = [];

    public function handle()
    {
        $this->warmENpcResidents();

        $questDocument = GameData::loadDocument('Quest');

        // add the new experience points column
        GameData::addColumn($questDocument, 'TextData_en', 'array');
        GameData::addColumn($questDocument, 'TextData_de', 'array');
        GameData::addColumn($questDocument, 'TextData_fr', 'array');
        GameData::addColumn($questDocument, 'TextData_ja', 'array');

        Tools::Console()->text([
            'Total Quest documents: '. number_format(count($questDocument->Documents)),
            'This will take some time to complete',
        ]);

        $complete = 0;
        $announce = 0;
        $total    = count($questDocument->Documents);
        foreach ($questDocument->Documents as $quest) {
            $complete++;

            // skip weird file names
            if (strlen($quest->File_en) < 2) {
                continue;
            }

            $percentage = ceil(round($complete / $total, 3) * 100);
            if (($percentage % 20) == 0 && $announce !== $percentage) {
                Tools::Console()->text('- '. __CLASS__ ." {$percentage}%");
                $announce = $percentage;
            }

            $folder   = substr(explode('_', $quest->File_en)[1], 0, 3);
            $filename = "quest/{$folder}/{$quest->File_en}";

            // grab quest text info
            $questTextData = Tools::SaintCsv()->get($filename, true);
            foreach (['en', 'de', 'fr', 'ja'] as $language) {
                $arr = [];

                foreach ($questTextData->Documents as $i => $td) {
                    $text    = $td->{"UnknownColumn2_{$language}"};
                    $command = explode('_', $td->{"UnknownColumn1_{$language}"});

                    if (!trim($text)) {
                        continue;
                    }

                    $data = (Object)[
                        'Type'  => null,
                        'Npc'   => null,
                        'Order' => null,
                        'Text'  => $text,
                    ];

                    if ($command[4] == 'BATTLETALK') {
                        $data->Type = 'BattleTalk';
                        $data->Npc = $this->addQuestTextNpcSearch(trim($command[3]));
                        $data->Order = isset($command[5]) ? intval($command[5]) : $i;
                        continue;
                    }

                    // build data structure from command
                    switch($command[3]) {
                        case 'SEQ':
                            $data->Type = 'Journal';
                            $data->Order = intval($command[4]);
                            break;

                        case 'SCENE':
                            $data->Type = 'Scene';
                            $data->Order = intval($command[7]);
                            break;

                        case 'TODO':
                            $data->Type = 'ToDo';
                            $data->Order = intval($command[4]);
                            break;

                        case 'POP':
                            $data->Type = 'Pop';
                            $data->Order = $i;
                            break;

                        case 'ACCESS':
                            $data->Type = 'Access';
                            $data->Order = $i;
                            break;

                        case 'INSTANCE':
                            $data->Type = 'Instance';
                            $data->Order = $i;
                            break;

                        case 'SYSTEM':
                            $data->Type = 'System';
                            $data->Order = $i;
                            break;

                        case 'QIB':
                            $npc = filter_var($command[4], FILTER_SANITIZE_STRING);

                            // sometimes QIB can be a todo
                            if ($npc == 'TODO') {
                                $data->Type = 'Todo';
                                $data->Order = $i;
                                break;
                            }

                            $data->Type = 'BattleTalk';
                            $data->Npc = $this->addQuestTextNpcSearch(trim($npc));
                            $data->Order = $i;
                            break;

                        // 20 possible questions ...
                        case 'Q1':  case 'Q2':  case 'Q3':  case 'Q4':  case 'Q5':
                        case 'Q6':  case 'Q7':  case 'Q8':  case 'Q9':  case 'Q10':
                        case 'Q11': case 'Q12': case 'Q13': case 'Q14': case 'Q15':
                        case 'Q16': case 'Q17': case 'Q18': case 'Q19': case 'Q20':
                        $data->Type = 'QA_Question';
                        $data->Order = intval($command[4]);
                        break;

                        // with 20 possible answers ...
                        case 'A1':  case 'A2':  case 'A3':  case 'A4':  case 'A5':
                        case 'A6':  case 'A7':  case 'A8':  case 'A9':  case 'A10':
                        case 'A11': case 'A12': case 'A13': case 'A14': case 'A15':
                        case 'A16': case 'A17': case 'A18': case 'A19': case 'A20':
                        $data->Type = 'QA_Answer';
                        $data->Order = intval($command[4]);
                        break;

                        default:
                            $npc   = trim($command[3]);
                            $Order = isset($command[5]) ? intval($command[5]) : intval($command[4]);

                            // if npc is numeric, budge over 1
                            if (is_numeric($npc)) {
                                $npc   = trim($command[4]);
                                $Order = intval($command[3]);
                            }

                            $data->Type = 'Dialogue';
                            $data->Npc = $this->addQuestTextNpcSearch(trim($npc));
                            $data->Order = $Order;
                    }

                    // try get true npc
                    $arr[$data->Type][] = $data;
                }

                // set
                $quest->{"TextData_{$language}"} = $arr;
            }
        }

        //GameData::saveDocument('Quest', $questDocument);
        unset($questTextData);
    }

    private function warmENpcResidents()
    {
        foreach (GameData::loadDocument('ENpcResident')->Documents as $npc) {
            $name = preg_replace('/[0-9]+/', null, str_ireplace(' ', null, strtolower($npc->Name_en)));

            if (isset($this->ENpcResidentToName[$name])) {
                continue;
            }

            $this->ENpcResidentToName[$name]    = $npc->ID;
            $this->ENpcResidentToData[$npc->ID] = $npc;
        }
    }

    private function addQuestTextNpcSearch($npcName)
    {
        if (!$npcName) {
            return null;
        }

        $name = preg_replace('/[0-9]+/', null, str_ireplace(' ', null, strtolower($npcName)));

        // if npc exists
        if (isset($this->ENpcResidentToName[$name])) {
            $npcId  = $this->ENpcResidentToName[$name];
            $npc    = $this->ENpcResidentToData[$npcId];
            return $npc;
        }

        return ucwords(strtolower($npcName));
    }
}
