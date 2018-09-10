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

    public function compress($data): string
    {
        $data = serialize($data);
        $data = gzdeflate($data, 9);
        $data = gzdeflate($data, 9);
        return $data;
    }

    public function decompress($data): string
    {
        $data = gzinflate($data);
        $data = gzinflate($data);
        $data = unserialize($data);
        return $data;
    }

    public function createDirectory(string $directory)
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }
    }
    
    public function listDirectory(string $directory): array
    {
        return array_diff(scandir($directory), ['.','..','.gitkeep']);
    }

    public function listFolders(string $directory): array
    {
        $folders = [];
        foreach ($this->listDirectory($directory) as $dir) {
            if (is_dir($directory . $dir)) {
                $folders[] = $directory . $dir;
            }
        }

        return $folders;
    }

    public function deleteFile(string $filename)
    {
        @unlink($filename);
    }

    public function deleteDirectory(string $folder, $keepRootFolder = false)
    {
        // Handle bad arguments.
        if (empty($folder) || !file_exists($folder)) {
            return true; // No such file/folder exists.
        } elseif (is_file($folder) || is_link($folder)) {
            return @unlink($folder); // Delete file/link.
        }

        // Delete all children.
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($folder, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $action = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            if (!@$action($fileinfo->getRealPath())) {
                return false; // Abort due to the failure.
            }
        }

        // Delete the root folder itself?
        return (!$keepRootFolder ? @rmdir($folder) : true);
    }
}
