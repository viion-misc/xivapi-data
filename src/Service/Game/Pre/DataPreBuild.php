<?php

namespace App\Service\Game\Pre;

use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;

/**
 * Pulls all the data together and reformat some areas of it for easier usage.
 */
class DataPreBuild
{
    /**
     * Serialize all CSV documents, this is for quicker access as PHP CSV loading is very slow
     * and very memory hungry. This will also convert column names to a simplified format.
     */
    public function processCsvSerialization()
    {
        Tools::Timer()->start();
        Tools::Console()->section('Serialize CSV Documents');

        // Create the storage directory
        Tools::FileManager()->createDirectory(__DIR__ .'/../data/');

        // Get SaintCoinach Sheet data
        $sheets  = (new SaintCoinach())->sheets();
        $total   = count($sheets);
        $current = 0;

        Tools::Console()->text("Processing: {$total} CSV sheets");

        foreach ($sheets as $i => $sheet) {
            $current++;
            $start     = microtime(true);
            $sheetName = str_pad($sheet->sheet, 50, ' ', STR_PAD_RIGHT);

            $document = Tools::SaintCsv()->get($sheet->sheet);

            // append on default column and sheet definitions
            $document->DefaultColumn = $sheet->defaultColumn ?? null;
            $document->Definitions   = $sheet->definitions;

            // serialize and save
            file_put_contents(__DIR__ . "/../data/{$sheet->sheet}", serialize($document));
            unset($data);

            $duration = str_pad(round(microtime(true) - $start, 3) ." sec", 10);
            $counter  = str_pad("{$current}/{$total}", 8);
            Tools::Console()->text(
                "- <info>{$counter}</info> {$sheetName} {$duration} - ". Tools::Memory()->report()
            );
        }

        unset($data, $sheets);

        Tools::Console()->text([
            '',
            'CSV Serialization complete',
            'This action does not need to be run every time, only on game updates',
            Tools::Timer()->stop(),
            Tools::Memory()->report(),
            ''
        ]);

        return $this;
    }

    /**
     * Some content needs a search result
     */
    public function processMinionAndMountLargeIcons()
    {
        // the folder paths to replace to access the "large" icon
        $rep = [
            '/004' => '/068',
            '/008' => '/077',
        ];

        foreach (['Companion', 'Mount'] as $contentName) {
            $document = unserialize(file_get_contents(__DIR__."/../data/{$contentName}"));
            $document->Columns['IconLarge'] = (Object)[
                'Name' => 'IconLarge',
                'Type' => 'Image',
            ];

            // add each large icon
            foreach ($document->Documents as $row => $data) {
                $document->Documents[$row]['IconLarge'] = str_ireplace(array_keys($rep), $rep, $data['Icon']);
            }

            // save
            file_put_contents(__DIR__ . "/../data/{$contentName}", serialize($document));
            unset($document);
        }

        return $this;
    }

    /**
     * Add map images to map content
     */
    public function processMapImages()
    {
        $document = unserialize(file_get_contents(__DIR__."/../data/Map"));
        $document->Columns['FileImage'] = (Object)[
            'Name' => 'FileImage',
            'Type' => 'Image',
        ];

        foreach ($document->Documents as $row => $data) {
            if (empty($data['File_en'])) {
                continue;
            }

            [$folder, $layer] = explode('/', $data['File_en']);
            $document->Documents[$row]['FileImage'] = "/m/{$folder}/{$folder}.{$layer}.jpg";
        }
    }

    /**
     * Set quest icons and move the current "Icon" to "Banner"
     */
    public function processQuestIcons()
    {
        // todo (v3 = setQuestIcons)
        return $this;
    }

    /**
     * Copy the ResultItem icon to the main Recipe itself
     */
    public function processRecipeIcons()
    {
        // todo (v3 = setRecipeIcon)
        return $this;
    }
}
