# Getting started

Tempcord builds Discord bots on top of [Tempest](https://tempestphp.com). A command is a
class, its options are typed parameters, and the framework works out the rest at boot.

## Requirements

- PHP 8.5 or newer
- A Discord application with a bot token
- The intents your bot needs, enabled in the Discord developer portal

## Installing

```bash
composer create-project tempcord/tempcord my-bot
cd my-bot
```

Copy the environment file and add your token:

```bash
cp .env.example .env
```

```env
DISCORD_TOKEN=your_bot_token_here
```

## Your first command

A command is a class carrying `#[Command]`. If it declares an `__invoke` method, that method
handles it, and each parameter marked `#[Option]` becomes an option Discord shows the user.

```php
use CyberWolf\Discord\Interaction\CommandInteraction;
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Option;

#[Command(description: 'Replies with pong')]
final class PingCommand
{
    public function __invoke(
        CommandInteraction $interaction,
        #[Option(description: 'Who to greet')] string $name,
        #[Option(description: 'How many times')] int $times = 1,
    ): string {
        return $name . ':' . $times;
    }
}
```

<small>From [`tests/Fixtures/PingCommand.php`](../../tests/Fixtures/PingCommand.php) — compiled and exercised by the test suite.</small>

The name comes from the class — `PingCommand` becomes `/ping` — with a `Command` prefix or
suffix stripped and the rest snake_cased. Pass `name:` to choose it yourself.

Whether an option is required comes from whether its parameter has a default. Here `name` is
required and `times` is not.

## Registering and running

Registration replaces the whole set of commands each time, so a command you delete from your
code disappears from Discord rather than lingering there:

```bash
./tempcord boot --register
```

Once registered, boot without the flag:

```bash
./tempcord boot
```

Registration needs a request to Discord, so run it when your commands change rather than on
every start.

## Where to go next

- [Commands and options](02-commands.md) — subcommands, groups, and every option constraint
- [Autocomplete](03-autocomplete.md) — suggesting values as the user types
- [Events](04-events.md) — reacting to what happens on the gateway
- [Translations](05-translations.md) — showing commands in the user's own language
