# Autocomplete

Autocomplete suggests values while the user is still typing, before they submit the command.

There are three ways to supply the suggestions, and they differ only in where the code lives.

## From the command itself

The lightest way: a method carrying `#[Autocomplete]`, naming the option it completes. No
extra class, and the command's own dependencies are already to hand.

```php
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Autocomplete;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

/**
 * A command that suggests its own values, using the dependencies it already has.
 */
#[Command(description: 'Suggests its own values')]
final readonly class SelfCompletingCommand
{
    public function __construct(
        private TrackRepository $tracks,
    ) {}

    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Which track')] string $track,
        #[Option(description: 'Which mood')] string $mood = '',
    ): void {}

    /**
     * @return list<string>
     */
    #[Autocomplete(option: 'track')]
    public function completeTrack(string $typed): array
    {
        return $this->tracks->matching($typed);
    }

    /**
     * Takes the interaction too, in the other order, to show the arguments are
     * matched by what they are rather than by position.
     *
     * @return list<string>
     */
    #[Autocomplete(option: 'mood')]
    public function completeMood(CommandInteraction $interaction, mixed $typed): array
    {
        return [$interaction->interaction->id, (string) $typed];
    }
}
```

<small>From [`tests/Fixtures/SelfCompletingCommand.php`](../../tests/Fixtures/SelfCompletingCommand.php) — compiled and exercised by the test suite.</small>

The method takes whatever it asks for, in whatever order: the `CommandInteraction` where a
parameter is typed as one, and what has been typed so far everywhere else. It may take
neither.

## From a fixed list

`ArrayAutocomplete` filters a list by what has been typed, for the case where the values
never change.

```php
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\AutoCompletes\ArrayAutocomplete;

#[Command(description: 'Suggests as you type')]
final class SearchCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'What to look for', autocomplete: new ArrayAutocomplete(['alpha', 'beta', 'gamma']))]
        string $query,
        #[Option(description: 'Plain option with no suggestions')] string $note = '',
    ): void {}
}
```

<small>From [`tests/Fixtures/SearchCommand.php`](../../tests/Fixtures/SearchCommand.php) — compiled and exercised by the test suite.</small>

## From a class of its own

When the same suggestions are wanted by more than one command, name a class implementing
`Autocomplete`. It is built by the container, so it may take whatever dependencies it needs.

```php
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Interfaces\Autocomplete;

/**
 * An autocomplete with a dependency, which only works because it is named by
 * class and built by the container.
 */
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

<small>From [`tests/Fixtures/TrackAutocomplete.php`](../../tests/Fixtures/TrackAutocomplete.php) — compiled and exercised by the test suite.</small>

Point the option at it by name:

```php
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Suggests from a service')]
final class InjectedSearchCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Which track', autocomplete: TrackAutocomplete::class)]
        string $track,
    ): void {}
}
```

<small>From [`tests/Fixtures/InjectedSearchCommand.php`](../../tests/Fixtures/InjectedSearchCommand.php) — compiled and exercised by the test suite.</small>

Writing `autocomplete: new TrackAutocomplete(...)` also works, but an object built inside an
attribute cannot be given anything the container holds — which is why naming the class is
usually what you want.

## What you may return

| Return | Result |
| --- | --- |
| A list | Each value is shown as its own label |
| A map | Keys are the labels users see, values are what your handler receives |
| A single scalar | One suggestion |
| `ApplicationCommandOptionChoice` objects | Passed through untouched |

Discord accepts at most 25 choices and rejects a response carrying more, so anything beyond
that is dropped before sending.

## Timing and failure

Discord expects an autocomplete response within about three seconds. Keep the work small,
and cache rather than querying on every keystroke.

Suggestions run inside a fiber, so they may await the REST API or a database. One that
throws is logged and contained: the user simply sees no suggestions, and the gateway carries
on.
