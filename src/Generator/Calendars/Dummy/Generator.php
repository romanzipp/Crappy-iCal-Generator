<?php

namespace romanzipp\CalendarGenerator\Generator\Calendars\Dummy;

use Carbon\Carbon;
use romanzipp\CalendarGenerator\Generator\Abstracts\AbstractGenerator;

class Generator extends AbstractGenerator
{
    public function generateEvents(): array
    {
        $event = new Event();
        $event->title = 'Dummy Event';
        $event->start = Carbon::now();
        $event->end = Carbon::now()->addHour();

        return [
            $event,
        ];
    }
}
