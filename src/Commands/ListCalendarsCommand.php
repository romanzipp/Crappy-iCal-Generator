<?php

namespace romanzipp\CalendarGenerator\Commands;

use romanzipp\CalendarGenerator\Generator\Calendar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListCalendarsCommand extends Command
{
    protected static $defaultName = 'list';

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Available calendars:');

        foreach (Calendar::getKeys() as $calendarKey) {
            $output->writeln('  - ' . $calendarKey);
        }

        return Command::SUCCESS;
    }
}
