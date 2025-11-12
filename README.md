# Tempcord Framework

[![Version](https://img.shields.io/badge/version-0.5.0-blue.svg)](https://github.com/tempcord/framework)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-%5E8.3-blue.svg)](https://php.net)
[![Build Status](https://img.shields.io/badge/build-passing-brightgreen.svg)](#)

## Description

Tempcord is a modern, elegant PHP framework designed specifically for building Discord bots with ease. Built on top of the powerful Tempest Console framework and Ragnarok Fenrir Discord library, Tempcord provides a clean, attribute-based approach to creating sophisticated Discord bots with minimal boilerplate code.

The framework leverages PHP 8.3+ features and modern development practices to deliver a robust foundation for Discord bot development, featuring automatic command registration, event handling, and seamless integration with Discord's API.

> **Getting Started**: Use the `tempcord/tempcord` boilerplate to quickly create new Discord bot applications. This repository contains the core framework - for building applications, use `composer create-project tempcord/tempcord your-bot-name`.

## Features

- 🚀 **Modern PHP 8.3+** - Leverages the latest PHP features and syntax
- 🎯 **Attribute-Based Commands** - Define Discord commands using PHP attributes
- 📡 **Event-Driven Architecture** - Handle Discord events with clean, organized handlers
- 🔧 **Auto-Discovery** - Automatic command and event registration
- 🏗️ **Dependency Injection** - Built-in container for clean architecture
- 📝 **Console Integration** - Rich console output and logging capabilities
- 🔌 **Extensible** - Easy to extend with custom functionality
- 🛡️ **Type Safety** - Full type hints and strict typing throughout
- 📊 **Logging Support** - Comprehensive logging with multiple channels
- ⚡ **Performance Optimized** - Efficient command and event handling

## Installation

### Prerequisites

- PHP 8.3 or higher
- Composer
- A Discord Bot Token (from [Discord Developer Portal](https://discord.com/developers/applications))

### Step 1: Create New Application

Use the Tempcord application boilerplate to create a new Discord bot project:

```bash
composer create-project tempcord/tempcord my-discord-bot
cd my-discord-bot
```

### Step 2: Configure Your Bot

Copy the example environment file and configure your bot:

```bash
cp .env.example .env
```

Edit the `.env` file with your Discord bot credentials:

```env
DISCORD_TOKEN=your_bot_token_here
```

### Step 3: Install Dependencies

```bash
composer install
```

### Step 4: Create Your First Command

The boilerplate includes example commands. Create a new command in any folder, for example inside `app/Commands/`:

```php
<?php

namespace App\Commands;

use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;use Tempcord\Attributes\Commands\Command;

#[Command(name: 'hello', description: 'Say hello!')]
class HelloCommand
{
    public function __invoke(InteractionCreate $interaction): void
    {
        $interaction->respondWithMessage([
            'content' => 'Hello, World! 👋'
        ]);
    }
}
```

## Usage

### Running Your Bot

Use the built-in console commands to manage your bot:

```bash
# Boot the bot
php tempcord boot

# Boot and register commands with Discord
php tempcord boot --register

# Register commands only (without starting the bot)
php tempcord register
```

### Creating Commands

Commands are defined using the `#[Command]` attribute:

```php
#[Command(
    name: 'ping',
    description: 'Check bot latency'
)]
class PingCommand
{
    public function __invoke(InteractionCreate $interaction): void
    {
        $interaction->respondWithMessage([
            'content' => 'Pong! 🏓'
        ]);
    }
}
```

### Handling Events

Create event handlers using the `#[Event]` attribute:

```php
use Tempcord\Attributes\Event;
use Ragnarok\Fenrir\Gateway\Events\MessageCreate;

#[Event(name: 'message_create')]
class MessageHandler
{
    public function __invoke(MessageCreate $message): void
    {
        // Handle message events
        if ($message->content === '!hello') {
            // Respond to the message
        }
    }
}
```

### Command Options and Subcommands

```php
#[Command(name: 'user', description: 'User management commands')]
class UserCommand
{
    #[Subcommand(name: 'info', description: 'Get user information')]
    public function info(
        #[Option(name: 'user', description: 'Target user', required: true)]
        User $user
    ): void {
        // Handle user info command
    }
}
```

## Contributing

We welcome contributions! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to Tempcord.

### Development Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/framework.git`
3. Install dependencies: `composer install`
4. Create a feature branch: `git checkout -b feature/amazing-feature`
5. Make your changes and add tests
6. Run the test suite: `composer test`
7. Commit your changes: `git commit -m 'Add amazing feature'`
8. Push to your branch: `git push origin feature/amazing-feature`
9. Open a Pull Request

### Reporting Issues

When reporting issues, please include:

- PHP version
- Framework version
- Steps to reproduce
- Expected vs actual behavior
- Error messages or logs

### Feature Requests

We're always looking for ways to improve Tempcord. Feel free to:

- Open an issue with the `enhancement` label
- Discuss your ideas in our community channels
- Submit a pull request with your implementation

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

**Built with ❤️ by [CyberWolf.Studio](https://cyberwolf.studio) team.**
using [Tempest](https://tempestphp.com)

For more information, visit our [documentation](https://tempcord.dev) or join our [Discord community](https://discord.gg/tempcord).