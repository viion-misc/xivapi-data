<?php

namespace App\Service\Game\Mods;

/**
 * This will convert SaintEx column names into a more readable format
 */
class Columns
{
    public static function convert(array $columns)
    {
        // switch # to ID
        $columns[0] = $columns[0] == '#' ? 'ID' : $columns[0];

        // switch some column names
        $replace = [
            '[' => ' ',
            ']' => '',
            '{' => ' ',
            '}' => '',
            '<' => ' ',
            '>' => '',
            '(' => ' ',
            ')' => '',
        ];

        $columns = str_replace(array_keys($replace), $replace, $columns);

        if (is_array($columns)) {
            foreach ($columns as $i => $col) {
                $columns[$i] = ucwords($col);
            }
        } else {
            $columns = ucwords($columns);
        }

        return str_ireplace(' ', null, $columns);
    }
}
