# Commands

Commands are the primary way users interact with your Discord bot. Tempcord provides a powerful and flexible command system that supports both slash commands and traditional text commands.

## Basic Commands

### Creating a Command

Create a command by defining a class with the `#[Command]` attribute:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Description;
use Tempcord\Discord\Interaction;

class HelloCommand
{
    #[Command('hello')]
    #[Description('Say hello to the bot')]
    public function handle(Interaction $interaction): void
    {
        $interaction->reply('Hello there! 👋');
    }
}
```

### Command Attributes

- `#[Command('name')]` - Defines the command name
- `#[Description('text')]` - Provides a description for the command
- `#[Option()]` - Defines command options/parameters

## Command Options

Add parameters to your commands using the `#[Option]` attribute:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Description;
use Tempcord\Attributes\Option;
use Tempcord\Discord\Interaction;
use Tempcord\Enums\OptionType;

class GreetCommand
{
    #[Command('greet')]
    #[Description('Greet a user with a custom message')]
    #[Option('user', OptionType::USER, 'The user to greet', required: true)]
    #[Option('message', OptionType::STRING, 'Custom greeting message')]
    public function handle(Interaction $interaction): void
    {
        $user = $interaction->getOption('user');
        $message = $interaction->getOption('message') ?? 'Hello';
        
        $interaction->reply("{$message}, {$user->mention()}!");
    }
}
```

### Option Types

- `OptionType::STRING` - Text input
- `OptionType::INTEGER` - Whole numbers
- `OptionType::NUMBER` - Decimal numbers
- `OptionType::BOOLEAN` - True/false values
- `OptionType::USER` - Discord user
- `OptionType::CHANNEL` - Discord channel
- `OptionType::ROLE` - Discord role
- `OptionType::MENTIONABLE` - User, role, or channel
- `OptionType::ATTACHMENT` - File attachment

### Option Parameters

```php
#[Option(
    name: 'option_name',
    type: OptionType::STRING,
    description: 'Option description',
    required: true,
    choices: ['choice1', 'choice2'],
    minValue: 1,
    maxValue: 100,
    minLength: 1,
    maxLength: 50
)]
```

## Command Groups

Organize related commands into groups:

```php
<?php

namespace App\Commands\Admin;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\CommandGroup;
use Tempcord\Attributes\Description;
use Tempcord\Discord\Interaction;

#[CommandGroup('admin', 'Administrative commands')]
class BanCommand
{
    #[Command('ban')]
    #[Description('Ban a user from the server')]
    #[Option('user', OptionType::USER, 'User to ban', required: true)]
    #[Option('reason', OptionType::STRING, 'Reason for ban')]
    public function handle(Interaction $interaction): void
    {
        // Ban logic here
    }
}
```

This creates a command accessible as `/admin ban`.

## Subcommands

Create subcommands for more complex command structures:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;
use Tempcord\Attributes\Description;
use Tempcord\Discord\Interaction;

class ConfigCommand
{
    #[Command('config')]
    #[Description('Bot configuration commands')]
    public function handle(Interaction $interaction): void
    {
        // Default handler
    }
    
    #[Subcommand('set')]
    #[Description('Set a configuration value')]
    #[Option('key', OptionType::STRING, 'Configuration key', required: true)]
    #[Option('value', OptionType::STRING, 'Configuration value', required: true)]
    public function set(Interaction $interaction): void
    {
        $key = $interaction->getOption('key');
        $value = $interaction->getOption('value');
        
        // Set configuration logic
        $interaction->reply("Set {$key} to {$value}");
    }
    
    #[Subcommand('get')]
    #[Description('Get a configuration value')]
    #[Option('key', OptionType::STRING, 'Configuration key', required: true)]
    public function get(Interaction $interaction): void
    {
        $key = $interaction->getOption('key');
        
        // Get configuration logic
        $interaction->reply("Value for {$key}: ...");
    }
}
```

## Response Types

### Basic Responses

```php
// Simple text response
$interaction->reply('Hello!');

// Ephemeral response (only visible to the user)
$interaction->reply('Secret message', ephemeral: true);

// Deferred response (for long-running operations)
$interaction->defer();
// ... do work ...
$interaction->followUp('Operation completed!');
```

### Rich Responses

```php
use Tempcord\Discord\Embed;
use Tempcord\Discord\Button;
use Tempcord\Discord\ActionRow;

// Embed response
$embed = new Embed(
    title: 'Command Result',
    description: 'This is an embedded response',
    color: 0x00ff00
);

$interaction->reply(embeds: [$embed]);

// Response with buttons
$button = new Button(
    style: ButtonStyle::PRIMARY,
    label: 'Click me!',
    customId: 'my_button'
);

$actionRow = new ActionRow([$button]);

$interaction->reply(
    content: 'Choose an option:',
    components: [$actionRow]
);
```

## Command Validation

### Built-in Validation

Tempcord automatically validates:
- Required options
- Option types
- Min/max values and lengths
- Choice constraints

### Custom Validation

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Description;
use Tempcord\Attributes\Option;
use Tempcord\Discord\Interaction;
use Tempcord\Enums\OptionType;
use Tempcord\Exceptions\ValidationException;

class AgeCommand
{
    #[Command('age')]
    #[Description('Set your age')]
    #[Option('age', OptionType::INTEGER, 'Your age', required: true)]
    public function handle(Interaction $interaction): void
    {
        $age = $interaction->getOption('age');
        
        if ($age < 13) {
            throw new ValidationException('You must be at least 13 years old.');
        }
        
        if ($age > 120) {
            throw new ValidationException('Please enter a realistic age.');
        }
        
        $interaction->reply("Your age has been set to {$age}.");
    }
}
```

## Error Handling

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Discord\Interaction;
use Exception;

class RiskyCommand
{
    #[Command('risky')]
    public function handle(Interaction $interaction): void
    {
        try {
            // Risky operation
            $this->performRiskyOperation();
            $interaction->reply('Operation successful!');
        } catch (Exception $e) {
            $interaction->reply(
                'An error occurred: ' . $e->getMessage(),
                ephemeral: true
            );
        }
    }
    
    private function performRiskyOperation(): void
    {
        // Implementation
    }
}
```

## Command Permissions

### Discord Permissions

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\RequirePermissions;
use Tempcord\Discord\Interaction;
use Tempcord\Enums\Permission;

class KickCommand
{
    #[Command('kick')]
    #[RequirePermissions([Permission::KICK_MEMBERS])]
    public function handle(Interaction $interaction): void
    {
        // Only users with KICK_MEMBERS permission can use this
    }
}
```

### Custom Permission Checks

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Discord\Interaction;

class AdminCommand
{
    #[Command('admin')]
    public function handle(Interaction $interaction): void
    {
        if (!$this->isAdmin($interaction->user)) {
            $interaction->reply('You do not have permission to use this command.', ephemeral: true);
            return;
        }
        
        // Admin-only logic
    }
    
    private function isAdmin($user): bool
    {
        // Custom admin check logic
        return in_array($user->id, config('admin_users'));
    }
}
```

## Command Registration

### Automatic Registration

By default, Tempcord automatically discovers and registers commands in the `App\Commands` namespace.

### Manual Registration

```php
// In your bootstrap file
use Tempcord\CommandRegistry;
use App\Commands\MyCommand;

$registry = new CommandRegistry();
$registry->register(MyCommand::class);
```

### Conditional Registration

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\Environment;
use Tempcord\Discord\Interaction;

class DebugCommand
{
    #[Command('debug')]
    #[Environment('development')] // Only register in development
    public function handle(Interaction $interaction): void
    {
        // Debug information
    }
}
```

## Best Practices

### Command Naming
- Use lowercase names
- Use hyphens for multi-word commands
- Keep names short but descriptive
- Avoid special characters

### Response Guidelines
- Always respond to interactions
- Use ephemeral responses for error messages
- Provide clear, helpful error messages
- Use embeds for rich content

### Performance
- Defer responses for long-running operations
- Use database connections efficiently
- Cache frequently accessed data
- Implement proper error handling

### Security
- Validate all user input
- Check permissions before executing commands
- Sanitize data before database operations
- Log security-relevant events

## Examples

Check out the `examples/` directory for more command examples and patterns.