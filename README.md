# Crappy iCal Generator

### Supported calendars:

- [MotoGP 2020](https://www.motogp.com/en/calendar)

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

- Create new `Generator` and `Event` classes.
- Register the calendar in `romanzipp\CalendarGenerator\Generator\Calendar::getCalendars`.
