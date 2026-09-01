# Components

Buttons, select menus and modals are answered the same way commands are: an attribute
names the custom id, and the framework routes the interaction to your method.

## Buttons

An invokable class carrying `#[Button]` handles one button. With no id given, the class
name carries it — `ReportButton` answers `report`.

```php
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Attributes\Button;

#[Button]
final class ReportButton
{
    /** @var list<ButtonInteraction> */
    public static array $presses = [];

    public function __invoke(ButtonInteraction $interaction): void
    {
        self::$presses[] = $interaction;
    }
}
```

<small>From [`tests/Fixtures/ReportButton.php`](../../tests/Fixtures/ReportButton.php) — compiled and exercised by the test suite.</small>

Ask for the interaction in whichever shape you need it: `ButtonInteraction` to reply,
`ComponentInteraction` to also read the custom id and component type, or the raw
`InteractionCreate` payload.

## Ids that carry state

Discord hands back exactly the string a component was built with, so anything the handler
needs to know — which team, which petition — has to travel inside the id. Name those
segments with `{placeholders}` and they arrive as parameters, cast to the parameter's type.

The attribute is repeatable and may sit on a method, so a family of related buttons lives
on one class:

```php
use Tempcord\Attributes\Button;

/**
 * Several buttons on one class, keyed by what travels in the custom id.
 */
final class TournamentButtons
{
    /** @var list<array{string, string|int}> */
    public static array $calls = [];

    #[Button(id: 'tournament.accept.{team}')]
    public function accept(string $team): void
    {
        self::$calls[] = ['accept', $team];
    }

    #[Button(id: 'tournament.reject.{team}')]
    #[Button(id: 'tournament.drop.{team}')]
    public function reject(int $team): void
    {
        self::$calls[] = ['reject', $team];
    }
}
```

<small>From [`tests/Fixtures/TournamentButtons.php`](../../tests/Fixtures/TournamentButtons.php) — compiled and exercised by the test suite.</small>

To build a matching id when you create the button, compile the same pattern:

```php
use Tempcord\Runtime\CustomId;

CustomId::compile('tournament.accept.{team}')->build(['team' => $team->id]);
```

A literal id always wins over a pattern that would also match it, so a single id can be
carved out of a family without worrying about declaration order.

## Select menus

`#[SelectMenu]` covers every select type — string, user, role, mentionable and channel —
since Discord reports them all alike. A `$values` parameter receives everything the user
picked, and `$value` the first pick, which is null when the menu was cleared.

```php
use Tempcord\Attributes\SelectMenu;

#[SelectMenu]
final class PunishmentSelectMenu
{
    /** @var list<array{?string, list<string>}> */
    public static array $calls = [];

    /**
     * @param list<string> $values
     */
    public function __invoke(?string $value, array $values): void
    {
        self::$calls[] = [$value, $values];
    }
}
```

<small>From [`tests/Fixtures/PunishmentSelectMenu.php`](../../tests/Fixtures/PunishmentSelectMenu.php) — compiled and exercised by the test suite.</small>

## Modals

`#[ModalSubmit]` answers a submitted modal. Beyond placeholders from the id, a parameter
named after a field's custom id receives what was typed into it. A field the user left out
is not supplied at all, so the parameter's own default applies.

```php
use Tempcord\Attributes\ModalSubmit;

#[ModalSubmit(id: 'ban.{member}')]
final class BanModal
{
    /** @var list<array{string, ?string, string}> */
    public static array $calls = [];

    public function __invoke(string $member, ?string $reason, string $duration = 'forever'): void
    {
        self::$calls[] = [$member, $reason, $duration];
    }
}
```

<small>From [`tests/Fixtures/BanModal.php`](../../tests/Fixtures/BanModal.php) — compiled and exercised by the test suite.</small>

## Unmatched ids

An interaction whose custom id nothing answers is ignored rather than reported: it is
normally a component left over from an older version of the bot, or one another bot owns.

A handler that throws is logged and contained, exactly as a command or a listener is.
