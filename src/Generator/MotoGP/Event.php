<?php

namespace romanzipp\CalendarGenerator\Generator\MotoGP;

use romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent;

class Event extends AbstractEvent
{
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
