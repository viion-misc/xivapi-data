<?php

namespace App\Service\Tools;

class FileManager
{
    public function load(string $filename): string
    {
        return file_get_contents($filename);
    }
    
    public function save(string $filename, string $data)
    {
        $pi = pathinfo($filename);
        
        if (!is_dir($pi['dirname'])) {
            mkdir($pi['dirname'], 0777, true);
        }
        
        file_put_contents($filename, $data);
    }
    
    public function exists(string $filename): bool
    {
        return file_exists($filename);
    }
    
    public function filelist(string $directory): array
    {
        return array_diff(scandir($directory), ['.','..','.gitkeep']);
    }
}
