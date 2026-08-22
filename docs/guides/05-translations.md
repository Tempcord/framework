# Translations

Discord can show a command's name and description in each user's own language. Tempcord reads
those from Tempest's translation catalog, so they live in the same files as the rest of your
application's translations rather than inline in PHP.

## Setup

Translations need `tempest/intl`, which is not installed by default because it requires the
`intl` and `dom` extensions:

```bash
composer require tempest/intl
```

Without it, commands register with their declared names and descriptions and nothing else.

## Declaring a key

A command declares one key. Everything beneath it follows from its position in the tree.

```php
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\SubcommandGroup;

#[Command(description: 'Music controls', translationKey: 'commands.music')]
#[SubcommandGroup(name: 'playlist', description: 'Playlist controls')]
final class LocalizedCommand
{
    #[Subcommand(name: 'play', description: 'Play a track')]
    public function play(
        #[Option(description: 'Track title')] string $title,
    ): void {}
}
```

<small>From [`tests/Fixtures/LocalizedCommand.php`](../../tests/Fixtures/LocalizedCommand.php) — compiled and exercised by the test suite.</small>

That reads these keys:

```
commands.music.name
commands.music.description
commands.music.playlist.name
commands.music.playlist.description
commands.music.playlist.play.name
commands.music.playlist.play.description
commands.music.playlist.play.title.name
commands.music.playlist.play.title.description
```

An option on an invokable command hangs directly off the command's key —
`commands.greet.name.description` for an option named `name`.

## Providing the translations

Anywhere Tempest reads translations from:

```json
{
  "commands.music.description": "Musiksteuerung",
  "commands.music.playlist.description": "Wiedergabeliste",
  "commands.music.playlist.play.description": "Titel abspielen",
  "commands.music.playlist.play.title.description": "Titelname"
}
```

## What gets sent

Only locales that actually have a translation. A missing one is left out rather than filled
in, so Discord falls back to the declared text — which is why a partly translated command is
perfectly fine.

Discord accepts 34 locales; see [DiscordLocale](../reference/enums/discord-locale.md) for the
full list and how each maps onto a Tempest locale.

A command with no `translationKey` reads nothing and sends no localization fields at all.
