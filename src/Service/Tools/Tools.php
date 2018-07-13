<?php

namespace App\Service\Tools;

class Tools
{
    const ROOT = __DIR__.'\\..\\..\\..\\data';
    
    /** @var Console */
    private static $Console;
    /** @var Download */
    private static $Download;
    /** @var FileManager */
    private static $FileManager;
    /** @var Memory */
    private static $Memory;
    /** @var Timer */
    private static $Timer;
    
    public static function Console(): Console
    {
        if (!self::$Console) {
            self::$Console = new Console();
        }
        
        return self::$Console;
    }
    
    public static function Download(): Download
    {
        if (!self::$Download) {
            self::$Download = new Download();
        }
        
        return self::$Download;
    }
    
    public static function FileManager(): FileManager
    {
        if (!self::$FileManager) {
            self::$FileManager = new FileManager();
        }
        
        return self::$FileManager;
    }
    
    public static function Memory(): Memory
    {
        if (!self::$Memory) {
            self::$Memory = new Memory();
        }
        
        return self::$Memory;
    }
    
    public static function Timer(): Timer
    {
        if (!self::$Timer) {
            self::$Timer = new Timer();
        }
        
        return self::$Timer;
    }
}
