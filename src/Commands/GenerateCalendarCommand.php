<?php

namespace romanzipp\CalendarGenerator\Commands;

use Eluceo\iCal\Component\Calendar as iCalCalendar;
use Eluceo\iCal\Component\Event as iCalEvent;
use InvalidArgumentException;
use romanzipp\CalendarGenerator\Generator\Calendar;
use romanzipp\CalendarGenerator\Generator\Interfaces\GeneratorInterface;
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
            ->setHelp(
                sprintf('Available calendars: %s', implode(', ', Calendar::getKeys()))
            )
            ->setDescription('Generates a MotoGP ICS file')
            ->addArgument('calendar', InputArgument::REQUIRED, 'The calendar to generator')
            ->addArgument('output', InputArgument::OPTIONAL, 'The output ics file', 'motogp.ics');
    }

    private function spawnGenerator(InputInterface $input): GeneratorInterface
    {
        $calendar = Calendar::getCalendar(
            $input->getArgument('calendar')
        );

        if ($calendar === null) {
            throw new InvalidArgumentException('Cant find calendar');
        }

        return new $calendar->generator;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $generator = $this->spawnGenerator($input);

        $events = $generator->generateEvents();

        foreach ($events as $event) {
            $output->writeln(
                $event->getLogLine()
            );
        }

        $calendar = $this->generateCalendar($events);

        $success = $this->writeFile($input, $calendar);

        if ($success) {
            return Command::SUCCESS;
        }

        return Command::FAILURE;
    }

    private function writeFile(InputInterface $input, iCalCalendar $calendar): bool
    {
        $success = file_put_contents(
            $input->getArgument('output'),
            $calendar->render()
        );

        if (is_bool($success)) {
            return true;
        }

        return false;
    }

    /**
     * @param \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent[] $events
     * @return \Eluceo\iCal\Component\Calendar
     */
    private function generateCalendar(array $events): iCalCalendar
    {
        $iCalCalendar = new iCalCalendar('https://www.motogp.com');
        $iCalCalendar->setDescription('MotoGP 2020');
        $iCalCalendar->setCalendarColor('yellow');
        $iCalCalendar->setName('MotoGP 2020');
        $iCalCalendar->setCalId('motogp-2020');

        foreach ($events as $event) {

            /** @var \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent $event */

            $iCalEvent = new iCalEvent();

            $iCalEvent->setDtStart($event->start);
            $iCalEvent->setTimezoneString('UTC');

            if ($event->end) {
                $iCalEvent->setDtEnd($event->end);
            } else {
                $iCalEvent->setDtEnd(
                    (clone $event->start)->addHours(1)
                );
            }

            $iCalEvent->setUrl($event->url);
            $iCalEvent->setSummary($event->getFullTitle());
            $iCalEvent->setDescription($event->description);
            $iCalEvent->setLocation($event->location);

            $iCalCalendar->addComponent($iCalEvent);
        }

        return $iCalCalendar;
    }
}
