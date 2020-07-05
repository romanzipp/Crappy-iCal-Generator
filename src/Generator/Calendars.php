<?php

namespace romanzipp\CalendarGenerator\Generator;

use romanzipp\CalendarGenerator\Generator\MotoGP\Generator;

class Calendars
{
    public static array $generators = [
        'motogp-2020' => Generator::class,
    ];

    public static function getKeys(): array
    {
        return array_keys(self::$generators);
    }
}
