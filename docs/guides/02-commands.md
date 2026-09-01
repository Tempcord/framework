# Commands and options

## Subcommands

A class whose methods carry `#[Subcommand]` exposes each one as a subcommand. There is no
`__invoke` in this case — the methods are the handlers.

```php
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;

#[Command(description: 'Moderation tools')]
final class ModerationCommand
{
    #[Subcommand(name: 'kick', description: 'Kick a member')]
    public function kick(
        #[Option(description: 'Reason for the kick')] string $reason,
    ): string {
        return 'kicked: ' . $reason;
    }
}
```

<small>From [`tests/Fixtures/ModerationCommand.php`](../../tests/Fixtures/ModerationCommand.php) — compiled and exercised by the test suite.</small>

That registers `/moderation kick`.

## Grouping subcommands

Adding `#[SubcommandGroup]` to the class nests everything one level deeper, which is how
Discord models `/command group subcommand`.

```php
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;

#[Command(description: 'Music controls')]
#[SubcommandGroup(name: 'playlist', description: 'Playlist controls')]
final class MusicCommand
{
    #[Subcommand(name: 'play', description: 'Play a track')]
    public function play(
        #[Option(description: 'Track title')] string $title,
    ): string {
        return 'playing ' . $title;
    }

    #[Subcommand(name: 'stop', description: 'Stop playback')]
    public function stop(): string
    {
        return 'stopped';
    }

    public function notASubcommand(): void {}
}
```

<small>From [`tests/Fixtures/MusicCommand.php`](../../tests/Fixtures/MusicCommand.php) — compiled and exercised by the test suite.</small>

That registers `/music playlist play` and `/music playlist stop`. A method without
`#[Subcommand]` is ignored, so helpers can sit alongside handlers.

## Option types

The Discord option type is read from the parameter's PHP type:

| PHP type | Discord option type |
| --- | --- |
| `string` | STRING |
| `int` | INTEGER |
| `float` | NUMBER |
| `bool` | BOOLEAN |
| `Tempcord\Discord\Parts\User` | USER |
| `Tempcord\Discord\Parts\Channel` | CHANNEL |
| `Tempcord\Discord\Parts\Role` | ROLE |

A parameter typed `User`, `Channel` or `Role` is fetched from Discord before your handler
runs, so you receive the entity rather than an id.

An unsupported type fails at boot rather than on the first interaction that reaches it.

## Receiving the interaction

A parameter named `$interaction` receives the `CommandInteraction`, which is how you reply.
It needs no attribute.

## Constraining what users may send

Discord can enforce constraints before your handler is ever called, which is cheaper and
gives the user immediate feedback.

```php
use Tempcord\Discord\Enums\ChannelType;
use Tempcord\Discord\Parts\Channel;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Every option constraint Discord accepts')]
final class ConstrainedCommand
{
    public function __invoke(
        #[Option(description: 'A labelled set', choices: ['Small' => 's', 'Large' => 'l'])]
        string $size,
        #[Option(description: 'A bare list', choices: ['red', 'green'])]
        string $colour,
        #[Option(description: 'Bounded number', minValue: 1, maxValue: 10)]
        int $count,
        #[Option(description: 'Bounded text', minLength: 2, maxLength: 32)]
        string $note,
        #[Option(description: 'Text channels only', channelTypes: [ChannelType::GUILD_TEXT])]
        Channel $channel,
    ): void {}
}
```

<small>From [`tests/Fixtures/ConstrainedCommand.php`](../../tests/Fixtures/ConstrainedCommand.php) — compiled and exercised by the test suite.</small>

Choices accept either shape. A map uses its keys as the labels users see; a list has no
labels of its own, so each value stands in as its own.

## Restricting who may use a command

`permissions` sets the default a member needs. An empty list leaves the command unrestricted.

```php
use Tempcord\Discord\Enums\Permission;
use Tempcord\Attributes\Command;

#[Command(
    description: 'Only for moderators',
    permissions: [Permission::KICK_MEMBERS, Permission::BAN_MEMBERS],
)]
final class RestrictedCommand
{
    public function __invoke(): void {}
}
```

<small>From [`tests/Fixtures/RestrictedCommand.php`](../../tests/Fixtures/RestrictedCommand.php) — compiled and exercised by the test suite.</small>

Server administrators can override this per guild, so treat it as a default rather than a
security boundary.

## Scoping a command to one guild

`guildId` registers a command in a single guild instead of globally. Guild commands appear
immediately, which makes them useful while developing; global commands can take up to an
hour to propagate.

```php
use Tempcord\Attributes\Command;

#[Command(name: 'alpha', description: 'Alpha, scoped to guild 111', guildId: 111)]
final class GuildAlphaCommand
{
    public function __invoke(): void {}
}
```

<small>From [`tests/Fixtures/GuildAlphaCommand.php`](../../tests/Fixtures/GuildAlphaCommand.php) — compiled and exercised by the test suite.</small>
