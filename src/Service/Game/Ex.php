<?php

namespace App\Service\Game;

use App\Service\IO\Console;

class Ex
{
    const FILENAME = __DIR__.'/Resources/ex.json';

    /** @var null|\stdClass */
    private $ex = null;

    public function __construct()
    {
        // download saint ex if it does not exist
        if (!file_exists(self::FILENAME)) {
            Console::text("Downloading: ". getenv('APP_SAINT_EX'));
            file_put_contents(self::FILENAME, file_get_contents(
                getenv('APP_SAINT_EX')
            ));
        }

        // decode ex
        $this->ex = \GuzzleHttp\json_decode(
            file_get_contents(self::FILENAME)
        );
    }

    public function getVersion()
    {
        return $this->ex->version;
    }

    public function getSheets()
    {
        return $this->ex->sheets;
    }
}
