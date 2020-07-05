<?php

namespace romanzipp\MotoGP\Commands;

use romanzipp\MotoGP\Generator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCalendarCommand extends Command
{
    protected static $defaultName = 'generate';

    protected function configure()
    {
        $this
            ->setDescription('Generates a MotoGP ICS file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        (new Generator)();

        return Command::SUCCESS;
    }
}
