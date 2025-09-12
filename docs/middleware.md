# Middleware

Middleware provides a convenient mechanism for filtering and modifying requests before they reach your command handlers. You can use middleware for authentication, logging, rate limiting, and more.

## Creating Middleware

### Basic Middleware

Create middleware by implementing the `MiddlewareInterface`:

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;

#[Middleware]
class LoggingMiddleware implements MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed
    {
        // Before the command
        logger()->info('Command executed', [
            'command' => $interaction->commandName,
            'user' => $interaction->user->tag,
            'guild' => $interaction->guild?->name,
        ]);
        
        // Execute the command
        $result = $next($interaction);
        
        // After the command
        logger()->info('Command completed', [
            'command' => $interaction->commandName,
        ]);
        
        return $result;
    }
}
```

### Middleware with Parameters

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;

#[Middleware]
class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $maxRequests = 5,
        private int $windowSeconds = 60
    ) {}
    
    public function handle(Interaction $interaction, callable $next): mixed
    {
        $userId = $interaction->user->id;
        $key = "rate_limit:{$userId}";
        
        $requests = cache()->get($key, 0);
        
        if ($requests >= $this->maxRequests) {
            $interaction->reply(
                '⚠️ You are being rate limited. Please try again later.',
                ephemeral: true
            );
            return;
        }
        
        cache()->put($key, $requests + 1, $this->windowSeconds);
        
        return $next($interaction);
    }
}
```

## Applying Middleware

### Global Middleware

Apply middleware to all commands:

```php
// In your configuration
use Tempcord\Config\TempcordConfig;
use App\Middleware\LoggingMiddleware;
use App\Middleware\RateLimitMiddleware;

return new TempcordConfig(
    // ... other config
    globalMiddleware: [
        LoggingMiddleware::class,
        RateLimitMiddleware::class,
    ]
);
```

### Command-Specific Middleware

Apply middleware to specific commands:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\UseMiddleware;
use Tempcord\Discord\Interaction;
use App\Middleware\AdminOnlyMiddleware;
use App\Middleware\AuditLogMiddleware;

class AdminCommand
{
    #[Command('admin')]
    #[UseMiddleware([AdminOnlyMiddleware::class, AuditLogMiddleware::class])]
    public function handle(Interaction $interaction): void
    {
        // Admin-only command logic
    }
}
```

### Group Middleware

Apply middleware to command groups:

```php
<?php

namespace App\Commands\Admin;

use Tempcord\Attributes\CommandGroup;
use Tempcord\Attributes\UseMiddleware;
use App\Middleware\AdminOnlyMiddleware;

#[CommandGroup('admin', 'Administrative commands')]
#[UseMiddleware([AdminOnlyMiddleware::class])]
class AdminCommands
{
    // All commands in this group will use AdminOnlyMiddleware
}
```

## Built-in Middleware

### Permission Middleware

Check Discord permissions:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\UseMiddleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\RequirePermissions;
use Tempcord\Enums\Permission;

class ModerateCommand
{
    #[Command('kick')]
    #[UseMiddleware([RequirePermissions::class])]
    #[RequirePermissions([Permission::KICK_MEMBERS])]
    public function handle(Interaction $interaction): void
    {
        // User must have KICK_MEMBERS permission
    }
}
```

### Guild Only Middleware

Ensure commands are only used in guilds:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\UseMiddleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\GuildOnly;

class GuildCommand
{
    #[Command('guild-info')]
    #[UseMiddleware([GuildOnly::class])]
    public function handle(Interaction $interaction): void
    {
        // This command can only be used in guilds
        $guild = $interaction->guild; // Will never be null
    }
}
```

### Owner Only Middleware

Restrict commands to bot owners:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\UseMiddleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\OwnerOnly;

class OwnerCommand
{
    #[Command('shutdown')]
    #[UseMiddleware([OwnerOnly::class])]
    public function handle(Interaction $interaction): void
    {
        // Only bot owners can use this command
    }
}
```

## Common Middleware Patterns

### Authentication Middleware

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;
use App\Services\UserService;

#[Middleware]
class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private UserService $userService
    ) {}
    
    public function handle(Interaction $interaction, callable $next): mixed
    {
        $user = $this->userService->findByDiscordId($interaction->user->id);
        
        if (!$user) {
            $interaction->reply(
                '❌ You need to register first. Use `/register` to get started.',
                ephemeral: true
            );
            return;
        }
        
        // Add user to interaction context
        $interaction->setContext('user', $user);
        
        return $next($interaction);
    }
}
```

### Cooldown Middleware

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;

#[Middleware]
class CooldownMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $cooldownSeconds = 30
    ) {}
    
    public function handle(Interaction $interaction, callable $next): mixed
    {
        $userId = $interaction->user->id;
        $commandName = $interaction->commandName;
        $key = "cooldown:{$userId}:{$commandName}";
        
        $lastUsed = cache()->get($key);
        
        if ($lastUsed && (time() - $lastUsed) < $this->cooldownSeconds) {
            $remaining = $this->cooldownSeconds - (time() - $lastUsed);
            $interaction->reply(
                "⏰ Please wait {$remaining} seconds before using this command again.",
                ephemeral: true
            );
            return;
        }
        
        cache()->put($key, time(), $this->cooldownSeconds);
        
        return $next($interaction);
    }
}
```

### Maintenance Mode Middleware

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;

#[Middleware]
class MaintenanceModeMiddleware implements MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed
    {
        if (config('app.maintenance_mode', false)) {
            // Allow owners to bypass maintenance mode
            if (!in_array($interaction->user->id, config('bot.owners', []))) {
                $interaction->reply(
                    '🔧 The bot is currently under maintenance. Please try again later.',
                    ephemeral: true
                );
                return;
            }
        }
        
        return $next($interaction);
    }
}
```

### Audit Log Middleware

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;
use App\Services\AuditLogService;

#[Middleware]
class AuditLogMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuditLogService $auditLog
    ) {}
    
    public function handle(Interaction $interaction, callable $next): mixed
    {
        $startTime = microtime(true);
        
        try {
            $result = $next($interaction);
            
            $this->auditLog->log([
                'command' => $interaction->commandName,
                'user_id' => $interaction->user->id,
                'guild_id' => $interaction->guild?->id,
                'channel_id' => $interaction->channel->id,
                'options' => $interaction->options,
                'execution_time' => microtime(true) - $startTime,
                'status' => 'success',
                'timestamp' => now(),
            ]);
            
            return $result;
        } catch (Exception $e) {
            $this->auditLog->log([
                'command' => $interaction->commandName,
                'user_id' => $interaction->user->id,
                'guild_id' => $interaction->guild?->id,
                'channel_id' => $interaction->channel->id,
                'options' => $interaction->options,
                'execution_time' => microtime(true) - $startTime,
                'status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => now(),
            ]);
            
            throw $e;
        }
    }
}
```

## Middleware Priority

Control the order in which middleware is executed:

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Attributes\Priority;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;

#[Middleware]
#[Priority(100)] // Higher priority = runs first
class HighPriorityMiddleware implements MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed
    {
        // This runs before lower priority middleware
        return $next($interaction);
    }
}

#[Middleware]
#[Priority(10)] // Lower priority = runs later
class LowPriorityMiddleware implements MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed
    {
        // This runs after higher priority middleware
        return $next($interaction);
    }
}
```

## Conditional Middleware

Apply middleware based on conditions:

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;

#[Middleware]
class ConditionalMiddleware implements MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed
    {
        // Only apply to specific commands
        if (!in_array($interaction->commandName, ['sensitive-command', 'admin-command'])) {
            return $next($interaction);
        }
        
        // Only apply in specific guilds
        if (!in_array($interaction->guild?->id, ['guild-1', 'guild-2'])) {
            return $next($interaction);
        }
        
        // Apply middleware logic
        return $this->applyMiddleware($interaction, $next);
    }
    
    private function applyMiddleware(Interaction $interaction, callable $next): mixed
    {
        // Middleware logic here
        return $next($interaction);
    }
}
```

## Error Handling in Middleware

```php
<?php

namespace App\Middleware;

use Tempcord\Attributes\Middleware;
use Tempcord\Discord\Interaction;
use Tempcord\Middleware\MiddlewareInterface;
use Exception;

#[Middleware]
class ErrorHandlingMiddleware implements MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed
    {
        try {
            return $next($interaction);
        } catch (Exception $e) {
            // Log the error
            logger()->error('Command execution failed', [
                'command' => $interaction->commandName,
                'user' => $interaction->user->tag,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            // Send user-friendly error message
            $interaction->reply(
                '❌ An error occurred while processing your command. Please try again later.',
                ephemeral: true
            );
            
            // Optionally re-throw for global error handling
            // throw $e;
        }
    }
}
```

## Middleware Groups

Create reusable middleware groups:

```php
<?php

// In your configuration
use Tempcord\Config\TempcordConfig;

return new TempcordConfig(
    // ... other config
    middlewareGroups: [
        'admin' => [
            AdminOnlyMiddleware::class,
            AuditLogMiddleware::class,
            RateLimitMiddleware::class,
        ],
        'public' => [
            LoggingMiddleware::class,
            CooldownMiddleware::class,
        ],
    ]
);
```

Use middleware groups in commands:

```php
<?php

namespace App\Commands;

use Tempcord\Attributes\Command;
use Tempcord\Attributes\UseMiddlewareGroup;
use Tempcord\Discord\Interaction;

class AdminCommand
{
    #[Command('admin-action')]
    #[UseMiddlewareGroup('admin')]
    public function handle(Interaction $interaction): void
    {
        // Uses all middleware from the 'admin' group
    }
}
```

## Testing Middleware

```php
<?php

namespace Tests\Unit\Middleware;

use PHPUnit\Framework\TestCase;
use App\Middleware\RateLimitMiddleware;
use Tempcord\Discord\Interaction;
use Mockery;

class RateLimitMiddlewareTest extends TestCase
{
    public function test_allows_request_within_limit(): void
    {
        $middleware = new RateLimitMiddleware(maxRequests: 5, windowSeconds: 60);
        $interaction = Mockery::mock(Interaction::class);
        $interaction->user = (object) ['id' => '123'];
        
        $nextCalled = false;
        $next = function () use (&$nextCalled) {
            $nextCalled = true;
            return 'success';
        };
        
        $result = $middleware->handle($interaction, $next);
        
        $this->assertTrue($nextCalled);
        $this->assertEquals('success', $result);
    }
    
    public function test_blocks_request_over_limit(): void
    {
        // Test rate limiting logic
    }
}
```

## Best Practices

### Performance
- Keep middleware lightweight
- Cache expensive operations
- Use early returns when possible
- Avoid blocking operations

### Security
- Validate input in middleware
- Implement proper authentication
- Use rate limiting to prevent abuse
- Log security-relevant events

### Organization
- Keep middleware focused on single responsibilities
- Use descriptive names
- Group related middleware
- Document complex middleware logic

### Error Handling
- Handle errors gracefully
- Provide user-friendly error messages
- Log errors with sufficient context
- Don't expose sensitive information

## Examples

Check out the `examples/middleware/` directory for more middleware examples and patterns.