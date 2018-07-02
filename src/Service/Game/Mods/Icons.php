<?php

namespace App\Service\Game\Mods;

class Icons
{
    /**
     * Converts an icon number into a full icon filename;
     */
    public static function convert($number)
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
        return implode('/', $path) .'.png';
    }
}
