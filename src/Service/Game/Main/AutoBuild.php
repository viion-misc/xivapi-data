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

    // Self linking is when a definition links back on itself, eg:
    // ClassJob #1 (Gladiator), Field: ClassJobParent = ClassJob #1 (Gladiator).
    const OPTION_ALLOW_SELF_LINKING = false;

    /** @var int */
    private $currentLoop = 0;

    public function handle()
    {
        foreach(range(1, self::LOOPS) as $loop) {
            $this->currentLoop = $loop;
            $this->runLoop();
        }
    }

    /**
     * todo - find a better name
     * Run loop
     */
    private function runLoop()
    {
        Tools::Console()->title("- AutoBuild Loop: {$this->currentLoop}");

        // loop through each SaintCoinach ex.json sheet
        foreach ((new SaintCoinach())->sheets() as $i => $sheet) {
            Tools::Console()->text("<info>[{$this->currentLoop}] - Sheet: {$sheet->sheet}</info>");

            // grab sheet document, if it's first iteration grab pre document (to start fresh)
            $document = ($this->currentLoop == 1)
                ? GameData::loadPreDocument($sheet->sheet)
                : GameData::loadPostDocument($sheet->sheet);


            if ($this->currentLoop == 1 && $sheet->sheet == 'Achievement') {
                file_put_contents(__DIR__.'/Achievement_BEFORE.json', json_encode($document, JSON_PRETTY_PRINT));
            }

            // grab sheet definitions
            $definitions = $document->Definitions ?? null;

            if (!isset($document->Definitions)) {
                file_put_contents(__DIR__."/no_definitions_{$this->currentLoop}_{$sheet->sheet}.json", json_encode($document, JSON_PRETTY_PRINT));
                throw new \Exception("No definitions defined");
                #continue;
            }

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
                            $this->handleLink($document, $definition, $sheet->sheet);
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

            // save document with new linked data
            GameData::savePostDocument($sheet->sheet, $document);

            if ($sheet->sheet == 'Achievement') {
                file_put_contents(__DIR__.'/Achievement_AFTER.json', json_encode($document, JSON_PRETTY_PRINT));
            }

            Tools::Console()->line();
        }

        Tools::Console()->line();
    }

    private function handleLink($document, $definition, $sheetName)
    {
        $targetSheet     = $definition->converter->target;
        $targetName      = Tools::SaintCsv()->refactorColumnName($definition->name);

        Tools::Console()->text("[{$this->currentLoop}] Link: {$targetName} to {$targetSheet}");

        $targetDocuments = ($this->currentLoop == 1)
            ? GameData::loadPreDocument($targetSheet)
            : GameData::loadPostDocument($targetSheet);

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
            if (!property_exists($doc, $targetName)) {
                Tools::Console()->text('Could not find: '. $targetName .' on doc');
                print_r($doc);
                die('no definition?');
            }

            // grab id
            $targetId = is_object($doc->{$targetName}) ? $doc->{$targetName}->ID : $doc->{$targetName};

            // add Target and TargetID
            $doc->{$targetName . "Target"}  = $targetSheet;
            $doc->{$targetName . "ID"}      = $targetId;
            $doc->{$targetName}             = null;

            // todo - decide if self-linking should be a thing
            // don't link to itself, example of this happening is ClassJobs where the
            // base classes (Gladiator, Pugilist, etc) will link back to themselves, rather than 0.
            // however advanced glasses eg Summoner will link to their parent class as expected (Arcanist).
            // Can cause duplicate information.
            if (!self::OPTION_ALLOW_SELF_LINKING && $doc->ID === $targetId && $sheetName === $targetSheet) {
                continue;
            }

            // store the target sheet name, ID and then the value from the target documents
            $doc->{$targetName} = $targetDocuments[ $targetId ] ?? null;
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
