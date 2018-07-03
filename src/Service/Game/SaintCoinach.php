<?php

namespace App\Service\Game;

use App\Service\IO\Console;
use Github\Client;

class SaintCoinach
{
    const SAINT_FOLDER = __DIR__.'/Resources/SaintCoinach';
    const SAINT_FOLDER_OUTPUT = __DIR__.'/Resources/SaintCoinach/App';

    public function __construct()
    {
        if (!is_dir(self::SAINT_FOLDER)) {
            mkdir(self::SAINT_FOLDER, 0777, true);
        }

        // grab the latest release
        $release = (new Client())->api('repo')->releases()->latest(
            'ufx', 'SaintCoinach'
        );

        Console::text("Latest SaintCoinach Build: <info>{$release['tag_name']}</info>");
        Console::text("<comment>Choose a release to download:</comment>");

        $choices = [];
        foreach ($release['assets'] as $i => $build) {
            $choices[] = $build['name'];
        }

        $choice = Console::choice($choices)-1;

        // Download
        $download = $release['assets'][$choice];
        $filename = self::SAINT_FOLDER . "/{$download['name']}";

        Console::text("Downloading: <info>{$download['name']}</info>");
        file_put_contents(
            $filename, file_get_contents($download['browser_download_url'])
        );

        // extract file
        Console::text("Extracting to: ". self::SAINT_FOLDER_OUTPUT);
        $zip = new \ZipArchive;
        $result = $zip->open($filename);
        if ($result === true) {
            $zip->extractTo(self::SAINT_FOLDER_OUTPUT);
            $zip->close();
        }
    }

    public function extractCsvs()
    {
        $cmd = $this->buildCommand('allrawexd');
        Console::text("Running command: {$cmd}");
    }

    public function extractIcons()
    {
        $cmd = $this->buildCommand('ui');
        Console::text("Running command: {$cmd}");
    }

    public function extractMaps()
    {
        $cmd = $this->buildCommand('maps');
        Console::text("Running command: {$cmd}");
    }

    /**
     * WINDOWS ONLY
     */
    private function buildCommand($command)
    {
        $exe     = self::SAINT_FOLDER_OUTPUT . "/SaintCoinach.Cmd.exe";
        $history = self::SAINT_FOLDER_OUTPUT . "/SaintCoinach.History.zip";
        $cmd     = "{$exe} \"". getenv('APP_FFXIV_PATH') ."\" {$command}";

        // remove history file
        @unlink($history);
        return $cmd;
    }
}
