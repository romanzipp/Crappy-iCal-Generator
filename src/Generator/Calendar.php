<?php

namespace romanzipp\CalendarGenerator\Generator;

class Calendar
{
    public string $key;

    public string $title;

    public string $generator;

    public ?string $url;

    public ?string $color;

    public function __construct(string $key, string $title, string $generator, ?string $url = null, ?string $color = null)
    {
        $this->key = $key;
        $this->title = $title;
        $this->generator = $generator;
        $this->url = $url;
        $this->color = $color;
    }

    public static function getCalendars(): array
    {
        return [
            new self('motogp-2020', 'MotoGP 2020', Calendars\MotoGP2020\Generator::class, 'https://www.motogp.com', 'yellow'),
        ];
    }

    public static function getKeys(): array
    {
        return array_map(fn (Calendar $calendars) => $calendars->key, self::getCalendars());
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
