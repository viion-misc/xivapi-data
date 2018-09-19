<?php

namespace App\Service\Game;

use App\Service\Game\Pre\CsvSerialization;
use App\Service\Tools\Tools;

/**
 * Build CSV game content into JSON files.
 */
class GameData
{
    const ROOT = __DIR__ .'/data/';

    /**
     * Add a column to the document Columns list
     */
    public static function addColumn($document, string $name, string $type)
    {
        $document->Columns->{$name} = (Object)[
            'Name' => $name,
            'Type' => $type
        ];
    }

    /**
     * Restructure a document to be index'd by a specific field, this does not
     * modify the document by instead returns a new array ordered by the provided field.
     *
     * This is a thousand times faster than searching a document, typical use case:
     *
     *      $itemDocument = GameData::getDocumentsByField($itemDocument, 'ID');
     *
     *      $item = $itemDocument[1675];
     *      --> Curtana data
     *
     *
     *      $itemDocument = GameData::getDocumentsByField($itemDocument, 'LevelEquip', true);
     *
     *      $items = $itemDocument[50];
     *      --> Collection of all items with LevelEquip of 50
     */
    public static function getDocumentsByField($document, $field, bool $groupUp = false)
    {
        $arr = [];
        foreach ($document->Documents as $doc) {
            if ($groupUp) {
                $arr[$doc->{$field}][] = $doc;
            } else {
                $arr[$doc->{$field}] = $doc;
            }
        }

        return $arr;
    }

    /**
     * Save a document in serialize format
     */
    public static function saveDocument(string $filename, $document): void
    {
        file_put_contents(self::ROOT . $filename, serialize($document));
        unset($document);
    }

    /**
     * Load a document
     */
    public static function loadDocument(string $filename)
    {
        if (!file_exists(self::ROOT . $filename)) {
            return null;
        }

        return unserialize(file_get_contents(self::ROOT . $filename));
    }
    
    /**
     * Returns a list of available documents
     */
    public static function getDocumentList(): array
    {
        return Tools::FileManager()->listDirectory(self::ROOT);
    }

    /**
     * Process data BEFORE building document trees
     */
    public function preBuild(): void
    {
        $this->handleBuild('Pre');
    }

    /**
     * Process data into multi depth document trees
     */
    public function mainBuild(): void
    {
        $this->handleBuild('Main');
    }

    /**
     * Process data AFTER building document trees
     */
    public function postBuild(): void
    {
        $this->handleBuild('Post');
    }

    /**
     * Process data to deployment pipelines!
     */
    public function deployBuild(): void
    {
        $this->handleBuild('Deploy');
    }

    /**
     * handle a build pipeline
     */
    private function handleBuild(string $type): void
    {
        Tools::Timer()->start();

        foreach ($this->getClassList($type, __DIR__ .'/'. $type) as $class => $priority) {
            Tools::Console()->section("[{$priority}] $class");
            (new $class())->handle();
        }

        Tools::Console()->text([
            '',
            "Game Data: {$type} - Complete",
            Tools::Timer()->stop(),
            Tools::Memory()->report(),
            ''
        ]);
    }

    /**
     * Get the class list ordered by the constant ORDER in the class file.
     */
    private function getClassList($type, $folder): array
    {
        $list = [];
        foreach (Tools::FileManager()->listDirectory($folder) as $file) {
            $info = pathinfo($file);

            if ($info['extension'] !== 'php') {
                continue;
            }

            //  the typehint here is to be helpful.
            /** @var CsvSerialization $classNamespace */
            $className = $info['filename'];
            $classNamespace = "\\App\\Service\\Game\\{$type}\\{$className}";

            // ignore non-enabled converters
            if (!$classNamespace::ENABLED) {
                continue;
            }

            // All classes will have an ORDER constant
            $list[$classNamespace] = $classNamespace::ORDER;
        }

        return $list;
    }
}
