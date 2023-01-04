<?php

namespace romanzipp\CalendarGenerator\Generator\Abstracts;

use Carbon\Carbon;
use romanzipp\CalendarGenerator\Generator\Interfaces\EventInterface;

abstract class AbstractEvent implements EventInterface
{
    public ?string $id = null;

    public ?Carbon $start = null;
    public ?Carbon $end = null;

    public ?string $url = null;
    public ?string $description = null;
    public ?string $location = null;
    public ?string $title = null;
}
