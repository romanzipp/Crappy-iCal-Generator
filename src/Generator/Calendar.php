<?php

namespace romanzipp\CalendarGenerator\Generator;

use romanzipp\CalendarGenerator\Generator;

class Calendar
{
    public string $key;

    public string $title;

    public string $generator;

    public function __construct(string $key, string $title, string $generator)
    {
        $this->key = $key;
        $this->title = $title;
        $this->generator = $generator;
    }

    public static function getCalendars(): array
    {
        return [
            new self('motogp-2020', 'MotoGP 2020', Generator\MotoGP\Generator::class),
        ];
    }

    public static function getKeys(): array
    {
        return array_map(fn(Calendar $calendars) => $calendars->key, self::getCalendars());
    }

    public static function getCalendar(string $key): ?self
    {
        foreach (self::getCalendars() as $calendar) {

            if ($calendar->key === $key) {
                return $calendar;
            }
        }

        return null;
    }
}
