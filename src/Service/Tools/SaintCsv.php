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
        'Id'         => 'File',
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
     * Returns a class document with the CSV data
     */
    public function get(string $filename, bool $keepUnknownColumns = false): ?\stdClass
    {
        // get full filename from version
        $versions = (new SaintCoinach())->versions(true);
        $filename = "{$versions->FolderPath}/raw-exd-all/{$filename}";

        // check if this is a multi language file or not.
        $locale   = file_exists("{$filename}.en.csv");
        $filename = $locale ? "{$filename}.en.csv" : "{$filename}.csv";
    
        if (!file_exists($filename)) {
            Tools::Console()->error("File not found: {$filename}");
        }

        // create sheet
        $sheet = (Object)[
            'Filename'          => basename($filename),
            'HasLocale'         => $locale,
            'OffsetToColumn'    => [],
            'Columns'           => [],
            'Documents'         => [],
            'Total'             => 0,
        ];

        $csv      = Reader::createFromPath($filename);
        $columns  = (new Statement())->offset(1)->limit(1)->process($csv)->fetchOne();
        $types    = (new Statement())->offset(2)->limit(1)->process($csv)->fetchOne();

        // refactor columns
        $columns = $this->refactorColumns($columns);

        // store columns against type
        foreach ($columns as $i => $col) {
            // ignore non-named columns
            if (empty($col) && !$keepUnknownColumns) continue;

            $col = empty($col) ? "UnknownColumn{$i}" : $col;

            $type = $types[$i];
            $col  = $type == 'str' ? "{$col}_en" : $col;

            // save column with its type
            $sheet->OffsetToColumn[$i] = $col;
            $sheet->Columns[$col] = (Object)[
                'Name' => $col,
                'Type' => $type
            ];
        }

        // grab translated CSVs
        $translations = [
            'de' => null,
            'fr' => null,
            'ja' => null,
        ];

        foreach (array_keys($translations) as $language) {
            $translations[$language] = iterator_to_array(
                (new Statement())->offset(3)->process(
                    Reader::createFromPath(str_ireplace('.en', ".{$language}", $filename))
                )->getRecords()
            );
        }

        // parse CSV data
        foreach((new Statement())->offset(3)->process($csv)->getRecords() as $row => $record) {
            $doc = [];
            foreach ($record as $col => $value) {
                // skip empty columns
                if (!isset($sheet->OffsetToColumn[$col])) {
                    continue;
                }

                $column = $sheet->Columns[$sheet->OffsetToColumn[$col]];
                $doc[$column->Name] = $value;

                // if string column
                if ($locale && $column->Type == 'str') {
                    // store each translation value
                    foreach (array_keys($translations) as $language) {
                        $columnName = str_replace('_en', "_{$language}", $column->Name);
                        $doc[$columnName] = $translations[$language][$row][$col];
                        $sheet->Columns[$columnName] = (Object)[
                            'Name' => $columnName,
                            'Type' => 'str'
                        ];
                    }
                }
            }

            // refactor the document data
            $sheet->Documents[$row] = $doc;
        }

        // finishing touches
        $sheet->Documents = array_values($sheet->Documents);
        $sheet->Total     = count($sheet->Documents);

        // minor refactoring
        $this->refactorSheetData($sheet);

        unset($csv, $languageCsv, $translations);

        // converting to ensure everything is a stdclass
        $sheet = json_decode(json_encode($sheet));
        return $sheet;
    }

    public function refactorColumnName(string $column)
    {
        // remove all symbols
        $column = str_replace(array_keys(self::COLUMN_NAME_SYMBOLS), self::COLUMN_NAME_SYMBOLS, $column);

        // uppercase each word
        $column = str_ireplace(' ', null, ucwords($column));

        // rename some columns
        $column = str_replace(
            array_keys(self::COLUMN_NAME_REPLACEMENTS),
            self::COLUMN_NAME_REPLACEMENTS,
            $column
        );

        return $column;
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
    private function refactorSheetData(\stdClass $sheet): void
    {
        foreach ($sheet->Documents as $row => $data) {
            foreach ($data as $col => $value) {
                // remove foreign stuff
                $value = str_ireplace(self::FOREIGN_STRING_REMOVALS, null, $value);

                // ensure correct type for booleans
                $value = (strtoupper($value) === 'TRUE') ? true : $value;
                $value = (strtoupper($value) === 'FALSE') ? false : $value;

                //
                // Handle images by storing the "ID" and then converting the
                // image into a correct url path
                //
                if ($sheet->Columns[$col]->Type == 'Image') {
                    $colNew = "{$col}ID";

                    $sheet->Columns[$colNew] = (Object)[
                        'Name' => $colNew,
                        'Type' => 'Image'
                    ];

                    $sheet->Documents[$row][$colNew] = $value;
                    $value = $this->getImagePath($value);
                }

                //
                // Minor fixes to strings that broke around 30th May 2018
                //
                if ($sheet->Columns[$col]->Type == 'str') {
                    $value = str_ireplace("\r", "\n", $value);
                }

                $sheet->Documents[$row][$col] = $value;
            }
        }
    }

    /**
     * Gets the real path to an image
     */
    public function getImagePath($number): ?string
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
