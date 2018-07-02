<?php

namespace App\Command;

use App\Service\Game\CsvCache;
use App\Service\IO\Console;
use App\Service\IO\Memory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('xiv')
            ->setDescription('Run the XIV Web Tools')
            ->addArgument('automate', InputArgument::OPTIONAL, 'Automate actions, eg: 1,3,5,2');
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        Console::set($input, $output);
        Memory::report();

        if ($automate = $input->getArgument('automate')) {
            Console::setAuto();
            $this->handle(str_getcsv($automate));
            return;
        }

        // show menu
        Console::title("Menu");

        $choice = Console::choice([
            'Check local CSV cache',
            'Check SaintCoinach version',
            'Run SaintCoinach',
            'Create Custom Extraction Script',
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
                    Console::error("No available command for option: {$choice}");
                    break;

                case 1:
                    CsvCache::verify();
            }
        }
    }
}
