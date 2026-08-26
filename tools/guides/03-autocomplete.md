# Autocomplete

Autocomplete suggests values while the user is still typing, before they submit the command.

## The built-in

`ArrayAutocomplete` filters a fixed list by what has been typed so far.

<!-- include: tests/Fixtures/SearchCommand.php -->

## Writing your own

Anything implementing `Autocomplete` can supply suggestions, which is what you want when
they come from a database or an API.

```php
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Interfaces\Autocomplete;

final readonly class TrackAutocomplete implements Autocomplete
{
    public function __construct(
        private TrackRepository $tracks,
    ) {}

    public function handle(CommandInteraction $interaction, mixed $value): array
    {
        return $this->tracks->matching((string) $value);
    }
}
```

## What you may return

| Return | Result |
| --- | --- |
| A list | Each value is shown as its own label |
| A map | Keys are the labels users see, values are what your handler receives |
| A single scalar | One suggestion |
| `ApplicationCommandOptionChoice` objects | Passed through untouched |

Discord accepts at most 25 choices and rejects a response carrying more, so anything beyond
that is dropped before sending.

## Timing

Discord expects an autocomplete response within about three seconds. Keep the work small,
and cache rather than querying on every keystroke.

Note that an autocomplete implementation is constructed as part of the attribute, so it is
rebuilt whenever the command tree is read. Hold configuration in it, not a warm cache.
