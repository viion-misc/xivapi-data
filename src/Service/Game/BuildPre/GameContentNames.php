<?php

namespace App\Service\Game\BuildPre;

/**
 * This changes some names of things, for example
 * SE call Minions "Companions", which doesn't
 * make any sense to the community in general.
 *
 * It also generalises some stuff so "Masculine" and
 * "Singular" become "Name" where it is appropriate
 */
class GameContentNames
{
    const PRIORITY = 9999;

    const SHEETS = [
        'Companion' => 'Minion'
    ];

    const COLUMNS = [
        'BNpcName' => [
            'Singular' => 'Name'
        ],
        'ENpcResident' => [
            'Singular' => 'Name'
        ],
        'Mount' => [
            'Singular' => 'Name'
        ],
        'Companion' => [
            'Singular' => 'Name'
        ],
        'Title' => [
            'Masculine' => 'Name',
            'Feminine' => 'NameFemale'
        ],
        'Race' => [
            'Masculine' => 'Name',
            'Feminine' => 'NameFemale'
        ],
        'Tribe' => [
            'Masculine' => 'Name',
            'Feminine' => 'NameFemale'
        ],
        'Quest' => [
            'Id' => 'TextFile',
        ]
    ];

    public function process()
    {

    }
}
