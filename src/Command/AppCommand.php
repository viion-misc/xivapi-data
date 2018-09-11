<?php

namespace App\Command;

use App\Service\Game\GameData;
use App\Service\Github\SaintCoinach;
use App\Service\Tools\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppCommand extends Command
{
    private $Tools;
    
    public function __construct(?string $name = null)
    {
        parent::__construct($name);
        
        $this->Tools = new Tools();
    }
    
    protected function configure()
    {
        $this
            ->setName('xiv')
            ->setDescription('Run the XIV Web Tools')
            ->addArgument('auto', InputArgument::OPTIONAL, 'Automate actions, eg: 1,3,5,2');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Tools::Console()->set($input, $output)->title('Welcome');
        Tools::Console()->text( Tools::Memory()->report() );

        // if we're in auto mode.
        if ($automate = $input->getArgument('auto')) {
            $this->handle(str_getcsv($automate));
            return;
        }

        // show menu
        $choice = Tools::Console()->choice([
            'SaintCoinach: Download - This will delete any existing download',
            'SaintCoinach: allrawexd - Extract all game data (slow)',
            'SaintCoinach: Version check - check the version against extract folders',

            // data
            'GameData: Pre-Build - Preps the game data for data build, must be done before Post-Build',
            'GameData: Main-Build - Automatically build game data into documents based on SaintCoinach Ex.JSON',
            'GameData: Post-Build - Run through a series of custom scripts that extend or modify game data',

        ]);

        $this->handle([ $choice ]);
    }

    /**
     * Handle web tools menu
     */
    private function handle(array $choices)
    {
        foreach ($choices as $choice){
            switch ($choice) {
                default:
                    Tools::Console()->error("There is no available command for the menu option: {$choice}");
                    break;

                case 1:
                    Tools::Console()->title('SaintCoinach: Download');
                    (new SaintCoinach())->download();
                    break;
                    
                case 2:
                    Tools::Console()->title('SaintCoinach: allrawexd');
                    (new SaintCoinach())->extract('allrawexd');
                    break;

                case 3:
                    Tools::Console()->title('SaintCoinach: Version check');
                    (new SaintCoinach())->versions();
                    break;

                case 4:
                    Tools::Console()->title('GameData: Pre-Build');
                    (new GameData())->PreBuild();
                    break;

                case 5:
                    Tools::Console()->title('GameData: Main-Build');
                    (new GameData())->MainBuild();
                    break;

                case 6:
                    Tools::Console()->title('GameData: Post-Build');
                    (new GameData())->PostBuild();
                    break;
            }
        }
    }
}
