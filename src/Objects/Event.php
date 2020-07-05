<?php

namespace romanzipp\MotoGP\Objects;

use Carbon\Carbon;

class Event
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
