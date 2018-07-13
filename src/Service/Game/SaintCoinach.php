<?php

namespace App\Service\Game;

use App\Service\Tools\Tools;
use Github\Client;

class SaintCoinach
{
    /**
     * Download and extract the latest SaintCoinach.Cmd
     */
    public function download()
    {
        Tools::Console()->text("Fetching SaintCoinach Github releases");
        $release = (new Client())->api('repo')->releases()->latest(
            'ufx', 'SaintCoinach'
        );
        
        Tools::Console()->text("Latest SaintCoinach Build: <info>{$release['tag_name']}</info>");
        
        // auto-detect saint build
        $build = false;
        foreach ($release['assets'] as $i => $asset) {
            if (strpos($asset['name'], 'SaintCoinach.Cmd-master') !== false) {
                $build = $asset;
                break;
            }
        }
        
        if (!$build) {
            Tools::Console()->error("Unable to find a SaintCoinach.Cmd-master build in the latest releases.");
        }
        
        $filename = Tools::ROOT . '/SaintCoinachCmd/' . $build['name'];
        
        Tools::Console()->text([
            "Downloading: <info>{$build['name']}</info>",
            "- From: <info>{$build['browser_download_url']}</info>"
        ]);
        
        unlink($filename);
        Tools::FileManager()->save(
            $filename,
            Tools::Download()->get($build['browser_download_url'])
        );
        
        Tools::Console()->text("Extracting ZIP file ...");
        $zip = new \ZipArchive();
        $res = $zip->open($filename);
        
        if ($res === false) {
            Tools::Console()->error("Could not extract zip file, is it broken? Filename: {$filename}");
        }
        
        $appRoot = Tools::ROOT . '/SaintCoinachCmd/App';
        $zip->extractTo($appRoot);
        $zip->close();
    
        $exe     = Tools::ROOT . "\\SaintCoinachCmd\\App\\SaintCoinach.Cmd.exe";
        $history = Tools::ROOT . "\\SaintCoinachCmd\\App\\SaintCoinach.History.zip";
        
        // create bat script
        $bat = [];
        $bat[] = "cd ". Tools::ROOT . '\\SaintCoinachCmd\\App';
        $bat[] = "del {$history}";
        $bat[] = "{$exe} \"". getenv('APP_FFXIV_PATH') ."\" %s";
        $bat = implode("\n", $bat);;
    
        // save bat scripts
        Tools::FileManager()->save("{$appRoot}/extract-allrawexd.bat", sprintf($bat, 'allrawexd'));
        Tools::FileManager()->save("{$appRoot}/extract-ui.bat", sprintf($bat, 'ui'));
        Tools::FileManager()->save("{$appRoot}/extract-bgm.bat", sprintf($bat, 'bgm'));
        Tools::FileManager()->save("{$appRoot}/extract-maps.bat", sprintf($bat, 'maps'));
    
        Tools::Console()->text([
            "SaintCoinach.Cmd extracted to: <info>{$filename}</info>",
            "You can now run extraction in <info>menu option 2</info>",
            "The following extraction bat scripts have been created:",
            "- <comment>allrawexd, ui, bgm, maps</comment>"
        ]);
    }
    
    /**
     * Extract stuff!!
     */
    public function extract($command)
    {
        $script = Tools::ROOT . "/SaintCoinachCmd/App/extract-{$command}.bat";
        
        // run command
        Tools::Console()->text([
            "Running script: <info>{$script}</info>",
            "- This may take some time, <fg=red>do not cancel it</>."
        ]);
        
        exec($script);
        
        Tools::Console()->text("Command completed: <info>{$command}</info>");
    }
}
