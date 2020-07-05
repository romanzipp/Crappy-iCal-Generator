<?php

namespace romanzipp\CalendarGenerator\Generator\Interfaces;

interface GeneratorInterface
{
    /**
     * @return \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent[]
     */
    public function generateEvents(): array;
}
