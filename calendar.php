<?php

require 'vendor/autoload.php';

$calendarHtml = file_get_contents('https://www.motogp.com/en/calendar');

$dom = new \PHPHtmlParser\Dom;
$dom->load($calendarHtml);

$calendarEvents = [];

$events = $dom->find('.calendar_events .event.shadow_block');

foreach ($events as $event) {

    echo PHP_EOL;

    try {

        $dayElement = $event->find('.event_day');
        $monthElement = $event->find('.event_month');

    } catch (\PHPHtmlParser\Exceptions\EmptyCollectionException $e) {
        continue;
    }

    $day = (int) $dayElement->innerHtml;
    $month = trim($monthElement->innerHtml);

    if ($month === '&nbsp') {
        continue;
    }

    $date = \Carbon\Carbon::createFromFormat('Y-M-j', sprintf('2020-%s-%s', $month, $day));

    $circuit = trim($event->find('.event_title .location span')[0]->innerHtml);
    $country = trim($event->find('.event_title .location span')[1]->innerHtml);
    $title = trim($event->find('.event_title .event_name')->innerHtml);

    preg_match('/.*- (.*)/', $title, $matches);

    if (count($matches) > 1) {
        $title = $matches[1];
    }

    try {
        $buttonElement = $event->find('.event_buttons a');
    } catch (\PHPHtmlParser\Exceptions\EmptyCollectionException $e) {
        continue;
    }

    if (get_class($buttonElement) === \PHPHtmlParser\Dom\HtmlNode::class) {
        continue;
    }

    $buttonElement = $buttonElement[0];

    $buttonText = trim($buttonElement->innerHtml);

    if ($buttonText === 'View Results') {
        continue;
    }

    ###################################
    print_r('Event ' . $date->format('Y-m-d') . ' "' . $title . '" @ ' . $country);
    echo PHP_EOL;
    ###################################

    $buttonUrl = $buttonElement->getAttribute('href');

    $eventDom = new \PHPHtmlParser\Dom;
    $eventDom->loadFromUrl($buttonUrl);

    $daysElements = $eventDom->find('.c-schedule__table-container');

    foreach ($daysElements as $dayElement) {

        $dayDateElement = $eventDom->find('.c-schedule__days-tabs .c-schedule__date[data-tab="' . $dayElement->getAttribute('data-tab') . '"]');

        preg_match('/>([ 0-9]+).*> ([A-z]+) ([0-9]+)/', $dayDateElement->innerHtml, $matches);

        $dayDate = \Carbon\Carbon::createFromFormat('Y-F-j', sprintf('%s-%s-%s', $matches[3], $matches[2], (int) $matches[1]));

        ########################################
        print_r('----> ' . $dayDate->format('Y-m-d'));
        echo PHP_EOL;
        ########################################

        $dayTableRowElements = $dayElement->find('.c-schedule__table-row');

        foreach ($dayTableRowElements as $dayTableRowElement) {

            $dayCells = $dayTableRowElement->find('.c-schedule__table-cell');

            $dayRaceLeague = trim($dayCells[1]->text);
            $dayRaceTitle = trim($dayCells[2]->find('.hidden-xs')->text);
            $dayRaceTitleShort = trim($dayCells[2]->find('.visible-xs')->text);

            $dayRaceDates = $dayCells[3]->find('.c-schedule__time span');

            $dayRaceDateFrom = \Carbon\Carbon::parse($dayRaceDates[0]->getAttribute('data-ini-time'));

            $dayRaceDateTo = null;

            if (count($dayRaceDates) == 2) {
                $dayRaceDateTo = \Carbon\Carbon::parse($dayRaceDates[1]->getAttribute('data-end'));
            }

            if (\Illuminate\Support\Str::contains($dayRaceTitle, ['Warm Up', 'Free Practice'])) {
                continue;
            }

            ########################################
            print_r('--------> ' . '[' . $dayRaceLeague . '] ' . $dayRaceTitleShort . ' - ' . $dayRaceTitle . ' :: ' . $dayRaceDateFrom->format('Y-m-d H:i:s') . ' - ' . ($dayRaceDateTo ? $dayRaceDateTo->format('Y-m-d H:i:s') : '...'));
            echo PHP_EOL;
            ########################################

            $country = ucfirst(strtolower($country));

            $calendarEvents[] = (object) [
                'title' => $dayRaceLeague . ' ' . $country . ': ' . $dayRaceTitleShort,
                'description' => $dayRaceLeague . ', ' . $dayRaceTitle . ', ' . $title,
                'start' => $dayRaceDateFrom,
                'end' => $dayRaceDateTo,
                'location' => $country,
                'url' => $buttonUrl,
            ];
        }
    }

    echo PHP_EOL;
}

$vCalendar = new \Eluceo\iCal\Component\Calendar('https://www.motogp.com');
$vCalendar->setDescription('MotoGP 2020');
$vCalendar->setCalendarColor('yellow');
$vCalendar->setName('MotoGP 2020');
$vCalendar->setCalId('motogp-2020');

foreach ($calendarEvents as $calendarEvent) {

    $vEvent = new \Eluceo\iCal\Component\Event();

    $vEvent->setDtStart($calendarEvent->start);
    $vEvent->setTimezoneString('UTC');

    if ($calendarEvent->end) {
        $vEvent->setDtEnd($calendarEvent->end);
    } else {
        $vEvent->setDtEnd(
            (clone $calendarEvent->start)->addHours(1)
        );
    }

    $vEvent->setUrl($calendarEvent->url);
    $vEvent->setSummary($calendarEvent->title);
    $vEvent->setDescription($calendarEvent->description);
    $vEvent->setLocation($calendarEvent->location);

    $vCalendar->addComponent($vEvent);
}

file_put_contents('motogp.ics', $vCalendar->render());
