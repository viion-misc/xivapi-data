<?php

namespace App\Service\Game\Main;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;

class AutoBuild
{
    const ENABLED = true;
    const ORDER   = 1;
    const LOOPS   = 2;

    public function handle()
    {
        Tools::Console()->title('Building Game Data');

        foreach(range(1, self::LOOPS) as $loop) {
            $this->runLoop($loop);
            break;
        }
    }

    /**
     * todo - find a better name
     * Run loop
     */
    private function runLoop($loop)
    {
        Tools::Console()->title("- AutoBuild Loop: {$loop}");

        // loop through each SaintCoinach ex.json sheet
        foreach ((new SaintCoinach())->sheets() as $i => $sheet) {
            Tools::Console()->section("- Sheet: {$sheet->sheet}");

            // grab sheet document, if it's first iteration grab pre document (to start fresh)
            $document = ($loop == 1)
                ? GameData::loadPreDocument($sheet->sheet)
                : GameData::loadPostDocument($sheet->sheet);

            // todo (note) save a before
            if (!file_exists(__DIR__.'/before.json') || $sheet->sheet == 'Achievement') {
                file_put_contents(__DIR__.'/before.json', json_encode($document, JSON_PRETTY_PRINT));
            }

            // grab sheet definitions
            $definitions = $document->Definitions;

            // process definitions
            foreach ($definitions as $definition) {
                // try to detect definition type
                $isConverter = (isset($definition->converter));
                $isRepeater  = (isset($definition->type) && $definition->type == 'repeat');

                // handle non-named definitions
                if (isset($definition->definition) && !isset($definition->definition->name)) {
                    $definition->definition->name = "NoName_". $definition->type;
                }

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

                        case 'multiref':
                            $this->handleMultiRef($document, $definition);
                            break;

                        case 'generic':
                            $this->handleGeneric($document, $definition);
                            break;

                        case 'color':
                            $this->handleColor($document, $definition);
                            break;

                        case 'tomestone':
                            $this->handleTomestone($document, $definition);
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

            if ($sheet->sheet == 'Achievement') {
                // todo (note) save a after
                file_put_contents(__DIR__.'/after.json', json_encode($document, JSON_PRETTY_PRINT));
            }

            Tools::Console()->line();
        }

        Tools::Console()->line();
    }

    private function handleLink($document, $definition)
    {
        $targetSheet     = $definition->converter->target;
        $targetName      = Tools::SaintCsv()->refactorColumnName($definition->name);

        Tools::Console()->text("Link: {$targetName} to {$targetSheet}");

        $targetDocuments = GameData::loadPreDocument($targetSheet);

        if (!$targetDocuments) {
            // This is expected, we can find links but nothing in the file can be known, eg:
            // BGMFade links to BGMFadeType, however BGMFadeType has not been mapped
            // - Ignore and return
            return;
        }

        // Grab target documents by their ID
        $targetDocuments = GameData::getDocumentsByField($targetDocuments, 'ID');

        // process documents
        foreach ($document->Documents as $doc) {
            // grab id
            $targetId = is_object($doc->{$targetName}) ? $doc->{$targetName}->ID : $doc->{$targetName};

            // store the target sheet name, ID and then the value from the target documents
            $doc->{$targetName . "Target"}  = $targetSheet;
            $doc->{$targetName . "ID"}      = $targetId;
            $doc->{$targetName}             = $targetDocuments[ $targetId ] ?? null;
        }
    }

    private function handleComplexLink($document, $definition)
    {
        Tools::Console()->text("ComplexLink: ". $definition->name);
    }

    private function handleRepeater($document, $definition)
    {
        $count      = $definition->count;
        $definition = $definition->definition;

        //$definition->name = empty($definition->name) ? 'NoName_Repeater'.$definition->name;

        Tools::Console()->text("Repeater [0-{$count}]: ". $definition->name);
    }

    private function handleGeneric($document, $definition)
    {
        Tools::Console()->text("ComplexLink: ". $definition->name);
    }

    private function handleColor($document, $definition)
    {
        Tools::Console()->text("Color: ". $definition->name);
    }

    private function handleMultiRef($document, $definition)
    {
        Tools::Console()->text("MultiRef: ". $definition->name);
    }

    private function handleTomestone($document, $definition)
    {
        // todo - i think this is handled internally in saint for the A/B/C/D currencies
        Tools::Console()->text("Tomestone: ". $definition->name);
    }
}
