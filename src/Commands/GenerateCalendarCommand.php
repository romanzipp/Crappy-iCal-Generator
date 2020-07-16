<?php

namespace romanzipp\CalendarGenerator\Commands;

use Carbon\Carbon;
use Eluceo\iCal\Component\Calendar as iCalCalendar;
use Eluceo\iCal\Component\Event as iCalEvent;
use romanzipp\CalendarGenerator\Generator\Calendar;
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
            ->setDescription('Generates a calendar ICS file')
            ->addArgument('calendar', InputArgument::REQUIRED, 'The calendar to generator')
            ->addArgument('output', InputArgument::OPTIONAL, 'The output ics file', 'calendar.ics');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $calendar = Calendar::getCalendar(
            $input->getArgument('calendar')
        );

        if ($calendar === null) {
            $output->writeln('<error>Can not find calendar</error>');
            return Command::FAILURE;
        }

        $output->writeln("<comment>Generating {$calendar->title} calendar...</comment>");

        /** @var \romanzipp\CalendarGenerator\Generator\Interfaces\GeneratorInterface $generator */
        $generator = new $calendar->generator;

        // Questions

        $helper = $this->getHelper('question');

        if ( ! empty($questions = $generator->getCommandQuestions())) {

            foreach ($questions as $name => $question) {

                $generator->setQuestionResponse(
                    $name,
                    $response = $helper->ask($input, $output, $question)
                );

                if ( ! $response) {
                    return Command::SUCCESS;
                }
            }
        }

        $events = $generator->generateEvents();

        foreach ($events as $event) {
            $output->writeln("<fg=cyan>    {$event->getLogLine()}</>");
        }

        $success = $this->writeFile(
            $fileName = $this->generateFileName($input),
            $this->generateCalendar($calendar, $events)
        );

        if ($success === false) {

            $output->writeln("<error>Error writing file for {$calendar->title} calendar</error>");

            return Command::FAILURE;
        }

        $eventCount = count($events);

        $output->writeln("<info>Successfully written {$eventCount} events of {$calendar->title} calendar to {$fileName}</info>");

        return Command::SUCCESS;
    }

    private function generateFileName(InputInterface $input): string
    {
        return sprintf('%s-%s', Carbon::now()->format('Ymd-his'), $input->getArgument('output'));
    }

    private function writeFile(string $fileName, iCalCalendar $calendar): bool
    {
        $success = file_put_contents(
            sprintf('%s/../../out/%s', __DIR__, $fileName),
            $calendar->render()
        );

        if (is_int($success)) {
            return true;
        }

        return false;
    }

    /**
     * @param \romanzipp\CalendarGenerator\Generator\Calendar $calendar
     * @param \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent[] $events
     * @return \Eluceo\iCal\Component\Calendar
     */
    private function generateCalendar(Calendar $calendar, array $events): iCalCalendar
    {
        $iCalCalendar = new iCalCalendar($calendar->url);
        $iCalCalendar->setDescription($calendar->title);

        if ($calendar->color) {
            $iCalCalendar->setCalendarColor($calendar->color);
        }

        $iCalCalendar->setName($calendar->title);
        $iCalCalendar->setCalId($calendar->key);

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

            $iCalEvent->setSummary($event->getFullTitle());

            if ($event->description) {
                $iCalEvent->setDescription($event->description);
            }

            if ($event->url) {
                $iCalEvent->setUrl($event->url);
            }

            if ($event->location) {
                $iCalEvent->setLocation($event->location);
            }

            $iCalCalendar->addComponent($iCalEvent);
        }

        return $iCalCalendar;
    }
}
