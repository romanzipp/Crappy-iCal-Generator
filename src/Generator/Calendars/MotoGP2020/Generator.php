<?php

namespace romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2020;

use Carbon\Carbon;
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
     * @return \romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2020\Event[]
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
                'MotoGP + Moto2 + Moto3',
                'MotoE',
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
        $choice = $this->getQuestionResponse('leagues');

        if ('All' === $choice) {
            return true;
        }

        if ('MotoGP + Moto2 + Moto3' === $choice && in_array($league, ['MotoGP', 'Moto2', 'Moto3'])) {
            return true;
        }

        return $league == $choice;
    }

    public function spawnBrowser()
    {
        $browserFactory = new BrowserFactory('/Applications/Google Chrome.app/Contents/MacOS/Google Chrome');

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

            $date = Carbon::createFromFormat('Y-M-j', sprintf('2020-%s-%s', $month, $day));

            $circuit = trim($event->find('.event_title .location span')[0]->innerHtml);
            $country = trim($event->find('.event_title .location span')[1]->innerHtml);
            $title = trim($event->find('.event_title .event_name')->innerHtml);

            preg_match('/.*- (.*)/', $title, $matches);

            if (count($matches) > 1) {
                $title = $matches[1];
            }

            try {
                $buttonElement = $event->find('.event_buttons a');
            } catch (EmptyCollectionException $e) {
                continue;
            }

            if (HtmlNode::class === get_class($buttonElement)) {
                continue;
            }

            $buttonElement = $buttonElement[0];

            $buttonText = trim($buttonElement->innerHtml);

            if ('View Results' === $buttonText) {
                continue;
            }

            //##################################
            // print_r('Event ' . $date->format('Y-m-d') . ' "' . $title . '" @ ' . $country);
            // echo PHP_EOL;
            //##################################

            $buttonUrl = $buttonElement->getAttribute('href');

            $browser = $this->spawnBrowser();
            $page = $browser->createPage();
            $page->navigate($buttonUrl)->waitForNavigation();

            $scheduleHtml = $page->evaluate('document.querySelector(".c-schedule").innerHTML')->getReturnValue();

            $eventDom = new Dom();
            $eventDom->load($scheduleHtml);

            $daysElements = $eventDom->find('.c-schedule__table-container');

            foreach ($daysElements as $dayElement) {
                $dayDateElement = $eventDom->find('.c-schedule__days-tabs .c-schedule__date[data-tab="' . $dayElement->getAttribute('data-tab') . '"]');

                preg_match('/>([ 0-9]+).*> ([A-z]+) ([0-9]+)/', $dayDateElement->innerHtml, $matches);

                $dayDate = Carbon::createFromFormat('Y-F-j', sprintf('%s-%s-%s', $matches[3], $matches[2], (int) $matches[1]));

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

                    $dayRaceDateFrom = Carbon::parse($dayRaceDates[0]->getAttribute('data-ini-time'));

                    $dayRaceDateTo = null;

                    if (2 == count($dayRaceDates)) {
                        $dayRaceDateTo = Carbon::parse($dayRaceDates[1]->getAttribute('data-end'));
                    }

                    if (Str::contains($dayRaceTitle, ['Warm Up', 'Free Practice'])) {
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
                    $cEvent->url = $buttonUrl;

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
