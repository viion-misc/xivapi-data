<?php

namespace App\Service\Github;

use App\Service\Tools\Tools;
use Github\Client;

class SaintCoinach
{
    const ROOT = __DIR__.'/data/SaintCoinachCmd';

    /**
     * Download and extract the latest SaintCoinach.Cmd
     */
    public function download()
    {
        $release = (new Client())->api('repo')->releases()->latest('ufx', 'SaintCoinach');
        Tools::Console()->text("Latest SaintCoinach Build: <info>{$release['tag_name']}</info>");
        
        // auto-detect saint build
        $build = false;
        foreach ($release['assets'] as $i => $asset) {
            if (strpos($asset['name'], 'SaintCoinach.Cmd-master') !== false) {
                $build = $asset; break;
            }
        }
        
        if (!$build) {
            Tools::Console()->error("Unable to find a SaintCoinach.Cmd-master build in the latest releases.");
        }

        // create data directory if it does not exist
        Tools::FileManager()->createDirectory(self::ROOT);
        $filename = self::ROOT . '/' . $build['name'];
        
        Tools::Console()->text([
            "Downloading: <info>{$build['name']}</info>",
            "==> From: <info>{$build['browser_download_url']}</info>",
            ''
        ]);

        Tools::FileManager()->deleteFile($filename);
        Tools::FileManager()->save(
            $filename,
            Tools::Download()->get($build['browser_download_url'])
        );

        $zip = new \ZipArchive();
        $res = $zip->open($filename);
        if ($res === false) {
            Tools::Console()->error("Could not extract zip file, is it broken? Filename: {$filename}");
        }
        
        $appRoot = self::ROOT . '/App';
        Tools::FileManager()->deleteDirectory($appRoot);

        $zip->extractTo($appRoot);
        $zip->close();
    
        $exe     = self::ROOT . "/App/SaintCoinach.Cmd.exe";
        $history = self::ROOT . "/App/SaintCoinach.History.zip";
        
        // create bat script
        $bat = [];
        $bat[] = "cd ". self::ROOT . '/App';
        $bat[] = "del {$history}";
        $bat[] = "{$exe} \"". getenv('APP_FFXIV_PATH') ."\" %s";
        $bat = implode("\n", $bat);
        $bat = str_ireplace('/', '\\', $bat);
    
        // save bat scripts
        Tools::FileManager()->save("{$appRoot}/extract-allrawexd.bat", sprintf($bat, 'allrawexd'));
        Tools::FileManager()->save("{$appRoot}/extract-ui.bat", sprintf($bat, 'ui'));
        Tools::FileManager()->save("{$appRoot}/extract-bgm.bat", sprintf($bat, 'bgm'));
        Tools::FileManager()->save("{$appRoot}/extract-maps.bat", sprintf($bat, 'maps'));
    
        Tools::Console()->text([
            "SaintCoinach.Cmd extracted to: <info>{$filename}</info>",
            "You can now run an extraction script via: <info>menu option 2</info>",
            '',
            "The following extraction bat scripts have been created:",
            "- <comment>allrawexd, ui, bgm, maps</comment>",
            ''
        ]);
    }
    
    /**
     * Extract stuff!!
     */
    public function extract($command)
    {
        $script = self::ROOT . "/App/extract-{$command}.bat";
        
        // run command
        Tools::Console()->text([
            "Running script: <info>{$script}</info>",
            "- This may take some time, <fg=red>do not cancel it</>.",
            ''
        ]);
        
        exec($script);
        Tools::Console()->text("Command completed: <info>{$command}</info>");
    }

    /**
     * Fetch or render the Saint version information
     */
    public function versions($return = false)
    {
        $versions = (Object)[
            'Folder'            => null,
            'FolderPath'        => null,
            'FolderTimestamp'   => null,
            'Ex'                => null,
        ];

        if (!file_exists(self::ROOT .'/App/ex.json')) {
            Tools::Console()->error('SaintCoinach extract not found, missing file: '. self::ROOT .'/App/ex.json');
        }

        // set ex version
        $json = file_get_contents(self::ROOT .'/App/ex.json');
        $versions->Ex = \GuzzleHttp\json_decode($json)->version;

        // attempt to find folder version
        $folders = Tools::FileManager()->listFolders(self::ROOT .'/App/');
        if (empty($folders)) {
            Tools::Console()->error('No SaintCoinach extraction folders found, please run allrawexd extract.');
        }

        // default
        foreach ($folders as $folder) {
            if (filemtime($folder) > $versions->FolderTimestamp) {
                $versions->FolderTimestamp  = filemtime($folder);
                $versions->FolderPath       = $folder;
                $versions->Folder           = basename($versions->FolderPath);
            }
        }

        if ($versions->Folder !== $versions->Ex) {
            Tools::Console()->notice('The extraction folder and sheet version do not match, the extraction 
           process will always take the folder version over the sheet version.');
        }

        if ($return) {
            return $versions;
        }

        Tools::Console()->text([
            "ExJson Version:    {$versions->Ex}",
            "Extract Version:   {$versions->Folder}",
            "Extract Folder:    {$versions->FolderPath}",
            "Extract Date:      {$versions->FolderTimestamp}",
            ""
        ]);
    }

    /**
     * Fetch or render the Saint ex.json file
     */
    public function sheets(): array
    {
        if (!file_exists(self::ROOT .'/App/ex.json')) {
            Tools::Console()->error('SaintCoinach extract not found, missing file: '. self::ROOT .'/App/ex.json');
        }

        return json_decode(file_get_contents(self::ROOT .'/App/ex.json'))->sheets;
    }
}
