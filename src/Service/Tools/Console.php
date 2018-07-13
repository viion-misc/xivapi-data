<?php

namespace App\Service\Tools;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Console
{
    /** @var SymfonyStyle */
    private $io;

    /**
     * Set the console class;
     */
    public function set(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        return $this;
    }

    /**
     * write some tet
     */
    public function text($text)
    {
        $this->io->text($text);
        return $this;
    }

    /**
     * write an error
     */
    public function error($text)
    {
        $this->io->error($text);
        die;
    }

    /**
     * write a section heading
     */
    public function section($section)
    {
        $section  = str_pad("[ {$section} ]", 50, '-', STR_PAD_BOTH);
        $this->io->text([
            '', "<fg=yellow>-{$section}-</>", ''
        ]);
        
        return $this;
    }

    /**
     * write a nice title
     */
    public function title($title)
    {
        $bar     = str_pad('', 50, '-', STR_PAD_LEFT);
        $titleA  = str_pad(getenv('APP_NAME'), 50, ' ', STR_PAD_BOTH);
        $titleB  = str_pad($title, 50, ' ', STR_PAD_BOTH);

        $this->io->text([
            "<fg=yellow>+{$bar}+</>",
            "<fg=yellow>|{$titleA}|</>",
            "<fg=yellow>|{$titleB}|</>",
            "<fg=yellow>+{$bar}+</>",
            ''
        ]);
        
        return $this;
    }

    /**
     * Show a choice menu
     */
    public function choice($list)
    {
        foreach ($list as $i => $line) {
            $this->io->text("<info>". ($i+1) ."</info> - {$line}");
        }

        return $this->io->ask('Please pick an option');
    }

    /**
     * Ask to confirm something
     */
    public function confirm($text)
    {
        return $this->io->confirm($text);
    }
}
