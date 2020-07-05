<?php

namespace romanzipp\CalendarGenerator\Generator\Abstracts;

use Carbon\Carbon;
use romanzipp\CalendarGenerator\Generator\Interfaces\EventInterface;

abstract class AbstractEvent implements EventInterface
{
    public ?Carbon $start = null;
    public ?Carbon $end = null;

    public string $url;
    public string $description;
    public string $location;

    public string $league;

    public string $type;
    public string $shortType;

    public string $fullTitle;
    public string $title;

    public function getFullTitle(): string
    {
        return sprintf('%s %s: %s', $this->league, $this->location, $this->shortType);
    }
}
