<?php

namespace romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2023;

use Carbon\Carbon;
use romanzipp\CalendarGenerator\Generator\Abstracts\AbstractGenerator;
use Symfony\Component\Console\Question\ChoiceQuestion;

class Generator extends AbstractGenerator
{
    private const LEAGUE_ALL = 'ALL';
    private const LEAGUE_MOTOGP = 'MGP';
    private const LEAGUE_MOTO2 = 'MT2';
    private const LEAGUE_MOTO3 = 'MT3';
    private const LEAGUE_MOTOE = 'MTE';

    private const KIND_ALL = 'ALL';
    private const KIND_GP = 'GP';
    private const KIND_TEST = 'TEST';

    /**
     * @var \romanzipp\CalendarGenerator\Generator\Abstracts\AbstractEvent[]
     */
    private array $events = [];

    public static function getName(): string
    {
        return 'motogp-2023';
    }

    /**
     * @return \romanzipp\CalendarGenerator\Generator\Calendars\MotoGP2023\Event[]
     */
    public function generateEvents(): array
    {
        $this->generate();

        return $this->events;
    }

    public function getCommandQuestions(): array
    {
        return [
            'leagues' => new ChoiceQuestion('Which leagues should be included? (Default: All)', [
                self::LEAGUE_ALL => self::getLeagueName(self::LEAGUE_ALL),
                self::LEAGUE_MOTOGP => self::getLeagueName(self::LEAGUE_MOTOGP),
                self::LEAGUE_MOTO2 => self::getLeagueName(self::LEAGUE_MOTO2),
                self::LEAGUE_MOTO3 => self::getLeagueName(self::LEAGUE_MOTO3),
                self::LEAGUE_MOTOE => self::getLeagueName(self::LEAGUE_MOTOE),
            ], self::LEAGUE_ALL),
            'kind' => new ChoiceQuestion('Which events should be included? (Default: All)', [
                self::KIND_ALL => self::getKindName(self::KIND_ALL),
                self::KIND_GP => self::getKindName(self::KIND_GP),
                self::KIND_TEST => self::getKindName(self::KIND_TEST),
            ], self::KIND_ALL),
        ];
    }

    private static function getLeagueName(string $id): string
    {
        return match ($id) {
            self::LEAGUE_ALL => 'All Leagues',
            self::LEAGUE_MOTOGP => 'MotoGP',
            self::LEAGUE_MOTO2 => 'Moto2',
            self::LEAGUE_MOTO3 => 'Moto3',
            self::LEAGUE_MOTOE => 'MotoE'
        };
    }

    private function includeInLeagues(array $categories): bool
    {
        $choice = $this->getQuestionResponse('leagues');

        if (self::LEAGUE_ALL == $choice) {
            return true;
        }

        $ok = false;

        foreach ($categories as $category) {
            if ($category->acronym === $choice) {
                $ok = true;
            }
        }

        return $ok;
    }

    private static function getKindName(string $id): string
    {
        return match ($id) {
            self::KIND_ALL => 'All Kinds',
            self::KIND_GP => 'Grand Prix',
            self::KIND_TEST => 'Test',
        };
    }

    public function includeInKind(string $kind): bool
    {
        $choice = $this->getQuestionResponse('kind');

        return self::KIND_ALL === $choice || $kind === $choice;
    }

    private function generate(): void
    {
        $data = file_get_contents('https://www.motogp.com/api/calendar-front/be/events-api/api/v1/business-unit/mgp/season/2023/events?type=SPORT&upcoming=true&tmp=' . time());
        $items = json_decode($data)->events;

        foreach ($items as $item) {
            if (2023 !== $item->season->year) {
                continue;
            }

            if ( ! $this->includeInLeagues($item->categories)) {
                continue;
            }

            if ( ! $this->includeInKind($item->kind)) {
                continue;
            }

            $category = $item->categories[0];

            foreach ($item->broadcasts as $broadcast) {
                $event = new Event();
                $event->id = $item->id . '@' . $broadcast->id;

                $event->description = implode(PHP_EOL, [
                    'Kind: ' . self::getKindName($item->kind),
                    'League: ' . implode(', ', array_map(fn ($league) => $league->name, $item->categories)),
                    'Circuit: ' . $item->circuit->name,
                    '----------------------',
                    'Broadcasts:',
                    ...array_map(fn ($broadcast) => '- ' . Carbon::parse($broadcast->date_start)->format('d.m. H:i') . ' ' . $broadcast->name . ': ' . ucfirst(strtolower($broadcast->kind)), $item->broadcasts),
                    '----------------------',
                    'Live: ' . ($broadcast->has_live ? '✅' : '❌'),
                    'VOD: ' . ($broadcast->has_vod ? '✅' : '❌'),
                    'Timing: ' . ($broadcast->has_timing ? '✅' : '❌'),
                ]);

                $end = Carbon::parse($broadcast->date_end, $item->time_zone)->shiftTimezone('Europe/Berlin');

                $event->start = $start = Carbon::parse($broadcast->date_start, $item->time_zone)->shiftTimezone('Europe/Berlin');
                $event->end = $end->eq($start) ? (clone $start)->addMinutes(90) : $end;

                $event->location = $item->circuit?->country ?? $item->country;
                $event->url = "https://www.motogp.com/en/calendar/2023/event/{$item->url}";

                $event->league = $category->name;

                $event->type = $item->kind;
                $event->shortType = $item->kind;

                $event->title = ucfirst(strtolower($broadcast->kind)) . ': ' . trim($item->name) . ' - ' . self::getLeagueName($category->acronym);
                $event->fullTitle = $item->name;

                $this->events[] = $event;
            }
        }
    }
}
