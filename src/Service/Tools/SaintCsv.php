<?php

namespace App\Service\Tools;

use App\Service\Github\SaintCoinach;
use League\Csv\Reader;
use League\Csv\Statement;

class SaintCsv
{
    /**
     * A series of column names in SaintCoinach ex.json that
     * will be renamed to be more consistent with other content.
     */
    const COLUMN_NAME_REPLACEMENTS = [
        'Singular'   => 'Name',
        'Masculine'  => 'Name',
        'Feminine'   => 'NameFemale',
        'Id'         => 'TextFile',
    ];

    /**
     * A series of symbols found in headers, will be either
     * removed or turned into a space for nicer column names
     */
    const COLUMN_NAME_SYMBOLS = [
        '[' => ' ',
        ']' => '',
        '{' => ' ',
        '}' => '',
        '<' => ' ',
        '>' => '',
        '(' => ' ',
        ')' => '',
    ];

    /**
     * A series of foreign strings to remove, can be removed
     * from all pieces of content.
     */
    const FOREIGN_STRING_REMOVALS = [
        '<Emphasis>',   '</Emphasis>',  '<Emphasis/>',
        '<Indent>',     '</Indent>',    '<Indent/>',
        '<SoftHyphen/>'
    ];

    /**
     * get CSV data
     */
    public function get(string $filename): \stdClass
    {
        // get full filename from version
        $versions = (new SaintCoinach())->versions(true);
        $filename = "{$versions->FolderPath}/raw-exd-all/{$filename}";

        // check if this is a multi language file or not.
        $locale   = file_exists("{$filename}.en.csv");
        $filename = $locale ? "{$filename}.en.csv" : "{$filename}.csv";

        // get CSV data
        $csv = Reader::createFromPath($filename);

        // parse CSV headers
        $columns  = (new Statement())->offset(1)->limit(1)->process($csv)->fetchOne();
        $types    = (new Statement())->offset(2)->limit(1)->process($csv)->fetchOne();
        $data     = [];

        // rename any 'str' to _en
        foreach ($columns as $i => $col) {
            if ($types[$i] === 'str') {
                $columns[$i] = "{$col}_en";
            }
        }

        // parse CSV data
        foreach((new Statement())->offset(3)->process($csv)->getRecords() as $i => $record) {
            $data[ $record[0] ] = $record;
        }

        // refactor CSV columns
        $columns = $this->refactorColumns($columns);
        unset($csv);

        // if this was a locale sheet, get strings from other languages
        //
        // - note: This logic will not work for Korean or Chinese as their game data will be of an older
        //         version and the data offsets will be different
        if ($locale) {
            $newColumns = [];

            foreach (['de', 'fr', 'ja'] as $lang) {
                $filename = str_ireplace('.en', ".{$lang}", $filename);

                // loop through each record
                $csv = Reader::createFromPath($filename);
                foreach ((new Statement())->offset(3)->process($csv)->getRecords() as $i => $record) {

                    // loop through each types
                    foreach ($types as $o => $name) {
                        // we only care about str type
                        if ($name === 'str') {
                            $value  = $record[$o];
                            $column = str_ireplace('_en', "_{$lang}", $columns[$o]);

                            $data[ $record[0] ][] = $value;
                            $newColumns[$column] = 'str';
                        }
                    }
                }
            }

            // append new columns and types
            foreach($newColumns as $name => $type) {
                $columns[] = $name;
                $types[] = $type;
            }
        }

        unset($csv);

        // clean some of the data
        [ $data, $columns, $types ] = $this->refactorData($data, $columns, $types);

        return (Object)[
            'Columns' => $columns,
            'Types'   => $types,
            'Data'    => $data
        ];
    }

    /**
     * Refactor and rename the CSV columns, eg:
     *
     *   Hello{World[Example]}[0]  -->  HelloWorldExample0
     *   Feminine                  -->  NameFemale
     *
     */
    private function refactorColumns(array $columns): array
    {
        // switch first column to "ID"
        $columns[0] = $columns[0] == '#' ? 'ID' : $columns[0];

        // remove all symbols
        $columns = str_replace(array_keys(self::COLUMN_NAME_SYMBOLS), self::COLUMN_NAME_SYMBOLS, $columns);

        // UpperCase each word
        foreach ($columns as $i => $col) {
            $columns[$i] = str_ireplace(' ', null, ucwords($col));
        }

        // rename some columns
        $columns = str_replace(
            array_keys(self::COLUMN_NAME_REPLACEMENTS),
            self::COLUMN_NAME_REPLACEMENTS,
            $columns
        );

        return $columns;
    }

    /**
     * Refactor some of the data, for example build correct url paths for images and ensure strict types.
     */
    private function refactorData(array $data, array $columns, array $types): array
    {
        $newColumns = [];

        foreach ($data as $i => $d) {
            foreach ($d as $j => $value) {
                $type   = $types[$j];
                $column = $columns[$j];

                // remove foreign stuff
                $value = str_ireplace(self::FOREIGN_STRING_REMOVALS, null, $value);

                // ensure correct type for booleans
                $value = (strtoupper($value) === 'TRUE') ? true : $value;
                $value = (strtoupper($value) === 'FALSE') ? false : $value;

                if ($type == 'Image') {
                    // keep a record of ImageID
                    $newColumns["{$column}ID"] = $type;

                    $data[$i][] = $value;
                    $value  = $this->getImagePath($value);
                }

                if ($type == 'str') {
                    // fix new lines (broke around 30th May 2018)
                    $value = str_ireplace("\r", "\n", $value);
                }

                // save modified value
                $data[$i][$j] = $value;
            }
        }

        // save new columns
        foreach ($newColumns as $column => $type) {
            $columns[] = $column;
            $types[]   = $type;
        }

        return [ $data, $columns, $types ];
    }

    /**
     * Gets the real path to an image
     */
    private function getImagePath($number): ?string
    {
        $number = intval($number);
        $extended = (strlen($number) >= 6);

        if ($number == 0) {
            return null;
        }

        // create icon filename
        $icon = $extended ? str_pad($number, 5, "0", STR_PAD_LEFT) : '0' . str_pad($number, 5, "0", STR_PAD_LEFT);

        // create icon path
        $path = [];
        $path[] = $extended ? $icon[0] . $icon[1] . $icon[2] .'000' : '0'. $icon[1] . $icon[2] .'000';
        $path[] = $icon;

        // combine
        return '/i/'. implode('/', $path) .'.png';
    }
}
