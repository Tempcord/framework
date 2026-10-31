# Tempcord Framework

[![Latest Version](https://img.shields.io/packagist/v/tempcord/framework.svg)](https://packagist.org/packages/tempcord/framework)
[![Tests](https://github.com/Tempcord/framework/actions/workflows/tests.yml/badge.svg)](https://github.com/Tempcord/framework/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/Tempcord/framework/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/Tempcord/framework/actions/workflows/static-analysis.yml)
[![PHP Version](https://img.shields.io/badge/php-%5E8.5-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Tempcord is a PHP framework for Discord bots, built on [Tempest](https://tempestphp.com) and
[`tempcord/discord-php`](https://github.com/Tempcord/discord-php). A command is a class, its
options are typed parameters, and the framework works out the rest when the bot boots.

> This repository is the framework itself. To start a bot, use the application skeleton:
> `composer create-project tempcord/tempcord my-bot`.

## Features

- **Commands from attributes** — `#[Command]`, `#[Subcommand]`, `#[SubcommandGroup]`, with the
  Discord option type read from each parameter's PHP type, backed enums included.
- **Autocomplete** — from a method on the command, a fixed list, or a class of its own.
- **Context menus and activity entry points** alongside slash commands.
- **Components** — `#[Button]`, `#[SelectMenu]` and `#[ModalSubmit]`, routed by custom id, with
  `{placeholders}` that carry state through the id.
- **Middleware** — guards that run before a command or component handler and may refuse it.
- **Gateway events** — `#[Event]` listeners, each run in its own fiber with failures contained.
- **Cache** — guilds, channels, roles, members and voice states the gateway reports, readable
  without an HTTP round trip.
- **Translations** for command names and descriptions through `tempest/intl`.
- **Plugins** — Composer packages whose commands and events are discovered like your own.
- **Checked at boot** — a malformed declaration stops the bot starting, rather than failing the
  first time somebody uses it.

## Requirements

- PHP 8.5 or newer
- A Discord application with a bot token, from the
  [Discord Developer Portal](https://discord.com/developers/applications)

## Getting started

```bash
composer create-project tempcord/tempcord my-bot
cd my-bot
cp .env.example .env
```

Add your token to `.env`:

```env
DISCORD_TOKEN=your_bot_token_here
```

### Your first command

```php
use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;
use Tempcord\Discord\Interaction\CommandInteraction;

#[Command(description: 'Say hello')]
final class HelloCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Who to greet')] string $name = 'World',
    ): void {
        $interaction->reply("Hello, {$name}! 👋");
    }
}
```

The class name gives the command its name — `HelloCommand` becomes `/hello`. Whether an option is
required comes from whether its parameter has a default, so `name` here is optional.

The class can live anywhere in your application: Tempest discovers it.

### Running the bot

```bash
# Register commands with Discord, then start the bot
./tempcord boot --register

# Start the bot with the commands already registered
./tempcord boot
```

Registration replaces the whole set of commands, so a command deleted from your code disappears
from Discord too. Run it when your commands change rather than on every start.

## A quick tour

### Subcommands

```php
use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Option;
use Tempcord\Declare\Command\Subcommand;
use Tempcord\Discord\Parts\User;

#[Command(description: 'User tools')]
final class UserCommand
{
    #[Subcommand(name: 'info', description: 'Show information about a user')]
    public function info(
        #[Option(description: 'Target user')] User $user,
    ): void {
        // ...
    }
}
```

That registers `/user info`. Add `#[SubcommandGroup]` to the class for `/command group subcommand`.

### Buttons

```php
use Tempcord\Declare\Component\Button;
use Tempcord\Discord\Interaction\ButtonInteraction;

final class TournamentButtons
{
    #[Button(id: 'tournament.accept.{team}')]
    public function accept(ButtonInteraction $interaction, int $team): void
    {
        $interaction->reply("Team {$team} accepted.", ephemeral: true);
    }
}
```

Build the matching id when you send the button with
`CustomId::compile('tournament.accept.{team}')->build(['team' => 42])`.

### Events

```php
use Tempcord\Declare\Event\Event;
use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Gateway\Events\MessageCreate;

#[Event(name: Events::MESSAGE_CREATE)]
final class MessageListener
{
    public function __invoke(MessageCreate $message): void
    {
        // ...
    }
}
```

Discord only sends the events your bot has intents for, and reading message content needs the
privileged `MESSAGE_CONTENT` intent.

### Middleware

```php
use Tempcord\Declare\Command\Command;
use Tempcord\Declare\Command\Subcommand;
use Tempcord\Discord\Enums\Permission;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Support\Middleware\RequiresPermissions;

#[Command(description: 'Suggestions from the server')]
final class SuggestionCommand
{
    #[Subcommand(name: 'add', description: 'Suggest something')]
    public function add(CommandInteraction $interaction): void {}

    #[Subcommand(
        name: 'close',
        description: 'Close a suggestion',
        middleware: [new RequiresPermissions([Permission::MANAGE_GUILD])],
    )]
    public function close(CommandInteraction $interaction): void {}
}
```

Implement `Tempcord\Declare\Contracts\Middleware` to write your own.

## Configuration

The bot is configured in `app/config/tempcord.config.php` in the skeleton:

```php
use Tempcord\Discord\Bitwise\Bitwise;
use Tempcord\Discord\Enums\Intent;
use Tempcord\TempcordConfig;

return new TempcordConfig(
    token: env('DISCORD_TOKEN') ?? '',
    intents: Bitwise::from(Intent::GUILDS, Intent::GUILD_MESSAGES),
    cache: true,
    chunkMembers: true,
);
```

## Documentation

[docs/](docs/README.md) has guides for getting started, commands, autocomplete, events,
translations, plugins, components, the cache and middleware, plus a reference generated from the
source. Every code example in the guides is a file the test suite compiles.

## Upgrading from 1.x

2.0 reorganised the source into four areas, so every public class has a new namespace. Nothing
else about the API changed. The ones a bot usually imports:

| 1.x | 2.0 |
| --- | --- |
| `Tempcord\Attributes\{Command, Subcommand, SubcommandGroup, Option, Autocomplete}` | `Tempcord\Declare\Command\…` |
| `Tempcord\Attributes\{Button, SelectMenu, ModalSubmit}` | `Tempcord\Declare\Component\…` |
| `Tempcord\Attributes\Event` | `Tempcord\Declare\Event\Event` |
| `Tempcord\Interfaces\{Middleware, Autocomplete, Choosable}` | `Tempcord\Declare\Contracts\…` |
| `Tempcord\Plugins\Plugin` | `Tempcord\Declare\Contracts\Plugin` |
| `Tempcord\Runtime\CustomId` | `Tempcord\Compile\Component\CustomId` |
| `Tempcord\Cache\Cache` | `Tempcord\Support\Cache\Cache` |
| `Tempcord\Middleware\RequiresPermissions` | `Tempcord\Support\Middleware\RequiresPermissions` |
| `Tempcord\Messaging\DirectMessage` | `Tempcord\Support\Messaging\DirectMessage` |
| `Tempcord\AutoCompletes\ArrayAutocomplete` | `Tempcord\Support\Autocomplete\ArrayAutocomplete` |
| `Tempcord\Enums\DiscordLocale` | `Tempcord\Support\Localization\DiscordLocale` |
| `Tempcord\Pagination\…` | `Tempcord\Support\Pagination\…` |

The full table is in [docs/internal/architecture-review.md](docs/internal/architecture-review.md).
After upgrading, clear Tempest's discovery cache so it stops looking for the old classes.

## Project layout

```
src/
  Tempcord.php, TempcordConfig.php, *Initializer.php
  Declare/   what a bot author writes: attributes and contracts
  Compile/   turns declarations into definitions at discovery time
  Runtime/   binds definitions to the gateway, dispatches, registers commands, boots
  Support/   optional features on top: cache, localization, logging, messaging, pagination
```

`tests/Unit/` mirrors the same layout.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Pull request titles follow Conventional Commits, because
they decide the release.

```bash
composer install
composer test      # PHPUnit
composer analyse   # PHPStan
composer docs      # regenerate docs/ after changing the public API
```

The test suite fails if the committed documentation no longer matches the source.

When reporting an issue, include your PHP and framework versions, the steps to reproduce, what
you expected and what happened, and any error output.

## Credits

Tempcord does not bundle these projects; they are Composer dependencies, each under its own
license:

- **[tempcord/discord-php](https://github.com/Tempcord/discord-php)** — the Discord API and
  gateway library underneath, a maintained fork of
  **[Fenrir](https://github.com/dc-Ragnarok/Fenrir)** by Ragnarök (MIT).
- **[Tempest](https://tempestphp.com)** — the container, discovery, console and logging
  foundation (MIT).
- **[ReactPHP](https://reactphp.org)** — the async runtime behind the gateway connection (MIT).

## License

MIT — see [LICENSE](LICENSE).

Built by [CyberWolf.Studio](https://cyberwolf.studio). Documentation at
[tempcord.dev](https://tempcord.dev), community on [Discord](https://discord.gg/tempcord).
