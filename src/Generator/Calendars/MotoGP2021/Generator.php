<?php

namespace romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2021;

use Carbon\Carbon;
use Exception;
use HeadlessChromium\BrowserFactory;
use Illuminate\Support\Str;
use PHPHtmlParser\Dom;
use PHPHtmlParser\Dom\HtmlNode;
use PHPHtmlParser\Exceptions\EmptyCollectionException;
use romanzipp\CalendarGenerator\Generator\Abstracts\AbstractGenerator;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Generator extends AbstractGenerator
{
    /**
     * @var \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent[]
     */
    private array $events = [];

    /**
     * @return \romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2021\Event[]
     */
    public function generateEvents(): array
    {
        $this->generate();

        return $this->events;
    }

    public function getCommandQuestions(): array
    {
        return [
            'leagues' => new ChoiceQuestion('Which leagues should be included?', [
                'All',
                'MotoGP',
                'Moto2',
                'Moto3',
                'MotoGP + Moto2',
                'MotoGP + Moto2 + Moto3',
                'MotoE',
            ]),
            'events' => new ChoiceQuestion('Which events should be included?', [
                'All',
                'RAC',
                'RAC + Q',
                'RAC + Q + FP',
            ]),
        ];
    }

    private function fetchCalendarDom(): Dom
    {
        $calendarHtml = file_get_contents('https://www.motogp.com/en/calendar');

        $dom = new Dom();
        $dom->load($calendarHtml);

        return $dom;
    }

    private function findCalendarDomEvents(Dom $dom)
    {
        return $dom->find('.calendar_events .event.shadow_block');
    }

    public function shouldLeagueBeGenerated($league): bool
    {
        switch ($choice = $this->getQuestionResponse('leagues')) {
            case 'All':
                return true;
            case 'MotoGP + Moto2':
                return in_array($league, ['MotoGP', 'Moto2']);
            case 'MotoGP + Moto2 + Moto3':
                return in_array($league, ['MotoGP', 'Moto2', 'Moto3']);
        }

        return $league == $choice;
    }

    public function shouldEventBeGenerated($event): bool
    {
        switch ($this->getQuestionResponse('events')) {
            case 'All':
                return true;
            case 'RAC':
                return Str::contains($event, ['Race']);
            case 'RAC + Q':
                return 'Race' === $event || Str::startsWith($event, 'Qualifying Nr.');
            case 'RAC + Q + FP':
                return 'Race' === $event || Str::startsWith($event, 'Qualifying Nr.') || Str::startsWith($event, 'Free Practice');
        }

        return false;
        /*
         * Possible:
         * MotoGP™ Test Day 1
         * Press Conference
         * Free Practice Nr. 1
         * Qualifying Nr. 1
         * Qualifying Press Conference
         * Warm Up
         * Race
         * After The Flag
         * Race Press Conference
         */
    }

    public function spawnBrowser()
    {
        $browserFactory = new BrowserFactory($_ENV['CHROME_BINARY']);

        return $browserFactory->createBrowser();
    }

    private function generate(): void
    {
        $events = $this->findCalendarDomEvents(
            $this->fetchCalendarDom()
        );

        foreach ($events as $event) {
            try {
                $dayElement = $event->find('.event_day');
                $monthElement = $event->find('.event_month');
            } catch (EmptyCollectionException $e) {
                continue;
            }

            $day = (int) $dayElement->innerHtml;
            $month = trim($monthElement->innerHtml);

            if ('&nbsp' === $month) {
                continue;
            }

            try {
                $date = Carbon::createFromFormat('Y-M-j', sprintf('2021-%s-%s', $month, $day));
            } catch (Exception $exception) {
                continue;
            }

            $circuit = trim($event->find('.event_title .location span')[0]->innerHtml);
            $country = trim($event->find('.event_title .location span')[1]->innerHtml);
            $title = trim($event->find('.event_title .event_name')->innerHtml);

            preg_match('/.*- (.*)/', $title, $matches);

            if (count($matches) > 1) {
                $title = $matches[1];
            }

            try {
                $thumb = $event->find('.event_image_container');
            } catch (EmptyCollectionException $e) {
                continue;
            }

            if (HtmlNode::class === get_class($thumb)) {
                continue;
            }

            //##################################
            // print_r('Event ' . $date->format('Y-m-d') . ' "' . $title . '" @ ' . $country);
            // echo PHP_EOL;
            //##################################

            $eventUrl = $thumb->getAttribute('href');

            $browser = $this->spawnBrowser();
            $page = $browser->createPage();
            $page->navigate($eventUrl)->waitForNavigation();

            $scheduleHtml = $page->evaluate('document.querySelector(".c-schedule").innerHTML')->getReturnValue();

            $eventDom = new Dom();
            $eventDom->load($scheduleHtml);

            $daysElements = $eventDom->find('.c-schedule__table-container');

            foreach ($daysElements as $dayElement) {
                $dayDateElement = $eventDom->find('.c-schedule__days-tabs .c-schedule__date[data-tab="' . $dayElement->getAttribute('data-tab') . '"]');

                preg_match('/>([ 0-9]+).*> ([A-z]+) ([0-9]+)/', $dayDateElement->innerHtml, $matches);

                try {
                    $dayDate = Carbon::createFromFormat('Y-F-j', sprintf('%s-%s-%s', $matches[3], $matches[2], (int) $matches[1]));
                } catch (Exception $exception) {
                    continue;
                }

                //#######################################
                // print_r('----> ' . $dayDate->format('Y-m-d'));
                // echo PHP_EOL;
                //#######################################

                $dayTableRowElements = $dayElement->find('.c-schedule__table-row');

                foreach ($dayTableRowElements as $dayTableRowElement) {
                    $dayCells = $dayTableRowElement->find('.c-schedule__table-cell');

                    $dayRaceLeague = trim($dayCells[1]->text);
                    $dayRaceTitle = trim($dayCells[2]->find('.hidden-xs')->text);
                    $dayRaceTitleShort = trim($dayCells[2]->find('.visible-xs')->text);

                    if ( ! $this->shouldLeagueBeGenerated($dayRaceLeague)) {
                        continue;
                    }

                    $dayRaceDates = $dayCells[3]->find('.c-schedule__time span');

                    try {
                        $dayRaceDateFrom = Carbon::parse($dayRaceDates[0]->getAttribute('data-ini-time'));
                    } catch (Exception $exception) {
                        continue 2;
                    }

                    $dayRaceDateTo = null;

                    if (2 === count($dayRaceDates)) {
                        try {
                            $dayRaceDateTo = Carbon::parse($dayRaceDates[1]->getAttribute('data-end'));
                        } catch (Exception $exception) {
                            continue 2;
                        }
                    }

                    if ( ! $this->shouldEventBeGenerated($dayRaceTitle)) {
                        continue;
                    }

                    //#######################################
                    // print_r('--------> ' . '[' . $dayRaceLeague . '] ' . $dayRaceTitleShort . ' - ' . $dayRaceTitle . ' :: ' . $dayRaceDateFrom->format('Y-m-d H:i:s') . ' - ' . ($dayRaceDateTo ? $dayRaceDateTo->format('Y-m-d H:i:s') : '...'));
                    // echo PHP_EOL;
                    //#######################################

                    $country = ucfirst(strtolower($country));

                    $cEvent = new Event();

                    $cEvent->description = $dayRaceLeague . ', ' . $dayRaceTitle . ', ' . $title;
                    $cEvent->start = $dayRaceDateFrom;
                    $cEvent->end = $dayRaceDateTo;
                    $cEvent->location = $country;
                    $cEvent->url = $eventUrl;

                    $cEvent->league = $dayRaceLeague;

                    $cEvent->type = $dayRaceTitle;
                    $cEvent->shortType = $dayRaceTitleShort;

                    $cEvent->title = $title;
                    $cEvent->fullTitle = $dayRaceLeague . ' ' . $country . ': ' . $dayRaceTitleShort;

                    $this->events[] = $cEvent;
                }
            }
        }
    }
}
