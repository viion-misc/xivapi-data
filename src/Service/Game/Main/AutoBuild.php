<?php

namespace App\Service\Game\Main;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;

class AutoBuild
{
    const ENABLED = true;
    const ORDER = 1;

    public function handle()
    {
        Tools::Console()->section('Building Game Data');

        // loop through each saintcoinach ex.json sheet
        foreach ((new SaintCoinach())->sheets() as $sheet) {
            Tools::Console()->text("- Sheet: {$sheet->sheet}");

            // grab sheet document
            $document = GameData::loadDocument($sheet->sheet);

            // grab sheet definitions
            $definitions = $document->Definitions;

            // process definitions
            foreach ($definitions as $definition) {
                // try to detect definition type
                $isConverter = (isset($definition->converter));
                $isRepeater  = (isset($definition->type) && $definition->type == 'repeat');

                // handle direct converters
                if ($isConverter) {
                    switch($definition->converter->type) {
                        default:
                            Tools::Console()->error("Unknown converter: {$definition->converter->type}");
                            break;

                        case 'link':
                            $this->handleLink($document, $definition);
                            break;

                        case 'complexlink':
                            $this->handleComplexLink($document, $definition);
                            break;

                        case 'icon':
                            // don't need to do anything for icons
                            break;
                    }
                }

                // handle repeaters
                if ($isRepeater) {
                    $this->handleRepeater($document, $definition);
                }

            }

            die;
        }
    }

    private function handleLink($document, $definition)
    {
        $targetSheet = $definition->converter->target;

        Tools::Console()->text("Link: ". $definition->name ." to ". $targetSheet);
    }

    private function handleComplexLink($document, $definition)
    {
        Tools::Console()->text("ComplexLink: ". $definition->name);
    }

    private function handleRepeater($document, $definition)
    {
        $count      = $definition->count;
        $definition = $definition->definition;

        Tools::Console()->text("Repeater [0-{$count}]: ". $definition->name);
    }
}
