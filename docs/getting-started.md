# Getting Started with Tempcord

Welcome to Tempcord! This guide will help you get up and running with your first Discord bot using the Tempcord framework.

## Prerequisites

Before you begin, make sure you have:

- PHP 8.2 or higher
- Composer installed
- A Discord application and bot token
- Basic knowledge of PHP and Discord bots

## Installation

### Creating a New Project

The easiest way to get started is by creating a new project using Composer:

```bash
composer create-project tempcord/tempcord my-discord-bot
cd my-discord-bot
```

### Adding to Existing Project

If you want to add Tempcord to an existing project:

```bash
composer require tempcord/tempcord
```

## Discord Application Setup

1. Go to the [Discord Developer Portal](https://discord.com/developers/applications)
2. Click "New Application" and give it a name
3. Go to the "Bot" section and click "Add Bot"
4. Copy the bot token (you'll need this later)
5. Under "Privileged Gateway Intents", enable the intents your bot needs

## Configuration

### Environment Setup

Copy the example environment file:

```bash
cp .env.example .env
```

Edit the `.env` file with your Discord bot credentials:

```env
DISCORD_TOKEN=your_bot_token_here
DISCORD_CLIENT_ID=your_client_id_here
```

### Basic Configuration

Create or edit `app/config/tempcord.config.php`:

```php
<?php

use Tempcord\Config\TempcordConfig;

return new TempcordConfig(
    token: env('DISCORD_TOKEN'),
    clientId: env('DISCORD_CLIENT_ID'),
    intents: [
        'GUILDS',
        'GUILD_MESSAGES',
        'MESSAGE_CONTENT',
    ],
    commandPrefix: '!',
    autoRegisterCommands: true,
);
```

## Your First Command

Let's create a simple ping command. Create `app/Commands/PingCommand.php`:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Description;
use Tempcord\Discord\Interaction;

class PingCommand
{
    #[Command('ping')]
    #[Description('Responds with pong!')]
    public function handle(Interaction $interaction): void
    {
        $interaction->reply('🏓 Pong!');
    }
}
```

## Running Your Bot

Start your bot with:

```bash
php tempcord start
```

You should see output indicating that your bot is connecting to Discord.

## Testing Your Bot

1. Invite your bot to a Discord server using the OAuth2 URL generator in the Discord Developer Portal
2. In a channel where your bot has permissions, type `/ping`
3. Your bot should respond with "🏓 Pong!"

## Next Steps

Now that you have a basic bot running, you can:

- [Learn about Commands](commands.md)
- [Explore Event Handling](events.md)
- [Add Middleware](middleware.md)
- [Configure Advanced Settings](configuration.md)

## Troubleshooting

### Common Issues

**Bot doesn't respond to commands:**
- Check that your bot token is correct
- Ensure the bot has the necessary permissions in your Discord server
- Verify that the required intents are enabled

**"Invalid token" error:**
- Double-check your bot token in the `.env` file
- Make sure there are no extra spaces or characters

**Commands not registering:**
- Ensure `autoRegisterCommands` is set to `true` in your config
- Check that your command classes are in the correct namespace
- Verify that your command methods have the proper attributes

### Getting Help

If you're still having issues:

1. Check the [API Reference](api-reference.md)
2. Look through existing [GitHub Issues](https://github.com/tempcord/tempcord/issues)
3. Join our Discord community for support
4. Create a new issue if you've found a bug

## What's Next?

Congratulations! You've successfully created your first Tempcord bot. Continue reading the documentation to learn about more advanced features and best practices.