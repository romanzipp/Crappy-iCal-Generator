<?php

namespace romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2021;

use Carbon\Carbon;
use romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent;

class Event extends AbstractEvent
{
    public ?Carbon $start = null;
    public ?Carbon $end = null;

    public ?string $url;
    public ?string $description;
    public ?string $location;

    public string $league;

    public string $type;
    public string $shortType;

    public string $fullTitle;
    public ?string $title;

    public function getLogLine(): string
    {
        return vsprintf('%s [%s]  %s  %s  "%s"', [
            $this->start->format('Y-m-d'),
            spaces($this->league, 8),
            spaces($this->shortType, 3, STR_PAD_RIGHT),
            spaces($this->location, 14, STR_PAD_RIGHT),
            $this->title,
        ]);
    }
}
