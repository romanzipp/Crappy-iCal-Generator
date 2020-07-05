# Crappy iCal Generator

### Supported calendars:

- [MotoGP 2020](https://www.motogp.com/en/calendar)

## Setup

1. Clone project
2. `composer install`

## Usage

### Show available calendars

```
php calendar list
```

### Generate calendar

```
php calendar generate <calendar>
```

```
php calendar generate motogp-2020
```

## Development

### Register new calendar

Take a look at the [Dummy Generator](https://github.com/romanzipp/Crappy-iCal-Generator/tree/master/src/Generator/Dummy).

- Create `romanzipp\CalendarGenerator\Generator\<Calendar>\Generator` class
- Create `romanzipp\CalendarGenerator\Generator\<Calendar>\Event` class
- Register the calendar in [`romanzipp\CalendarGenerator\Generator\Calendar::getCalendars`](https://github.com/romanzipp/Crappy-iCal-Generator/blob/master/src/Generator/Calendar.php).

## Credits

- [Roman Zipp](https://github.com/romanzipp)
