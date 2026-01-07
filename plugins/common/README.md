# Tempcord Common Plugin

Common middleware, tasks, and utilities that every Discord bot developer typically needs.

## Installation

```bash
composer require tempcord/common
```

The plugin is auto-discovered and registered automatically when your bot boots.

## Configuration

Configure the plugin by providing a `CommonConfig` instance:

```php
use Tempcord\Common\CommonConfig;

$config = new CommonConfig(
    ownerIds: ['YOUR_USER_ID'],
    maintenanceMode: false,
    defaultCooldown: 3,
    statusRotation: [
        ['type' => 0, 'name' => 'with commands'],
        ['type' => 2, 'name' => 'to your feedback'],
    ],
);

$container->singleton(CommonConfig::class, fn() => $config);
```

## Middleware

### GuildOnlyMiddleware

Ensures commands only run in servers (not DMs):

```php
#[Command(middleware: [GuildOnlyMiddleware::class])]
class ServerInfoCommand { ... }
```

### DirectMessageOnlyMiddleware

Ensures commands only run in DMs:

```php
#[Command(middleware: [DirectMessageOnlyMiddleware::class])]
class PrivateSettingsCommand { ... }
```

### BotOwnerOnlyMiddleware

Restricts commands to configured bot owners:

```php
#[Command(middleware: [BotOwnerOnlyMiddleware::class])]
class EvalCommand { ... }
```

### MaintenanceModeMiddleware

Blocks all commands during maintenance (applied globally by default):

```php
// Enable maintenance mode
$config = $config->withMaintenance(true, 'Upgrading to v2.0...');
```

### CooldownMiddleware

Enforces cooldowns between command uses:

```php
#[Command(middleware: [CooldownMiddleware::class])]
class DailyRewardCommand { ... }
```

For custom cooldowns:

```php
class LongCooldownMiddleware extends CooldownMiddleware {
    protected function getCooldownSeconds(): int {
        return 60; // 1 minute
    }
}
```

### NsfwChannelMiddleware

Requires commands to be used in age-restricted channels:

```php
#[Command(middleware: [NsfwChannelMiddleware::class])]
class AdultContentCommand { ... }
```

### RequireRoleMiddleware

Base class for role-based restrictions:

```php
class ModeratorOnlyMiddleware extends RequireRoleMiddleware {
    protected function getRoleIds(): array {
        return ['MOD_ROLE_ID', 'ADMIN_ROLE_ID'];
    }

    protected function getErrorMessage(): string {
        return 'Only moderators can use this command.';
    }
}
```

## Scheduled Tasks

### StatusRotationTask

Rotates the bot's Discord activity status:

```php
$config = $config->withStatusRotation([
    ['type' => 0, 'name' => 'with commands'],      // Playing
    ['type' => 2, 'name' => 'to your feedback'],   // Listening
    ['type' => 3, 'name' => 'the server grow'],    // Watching
], interval: 300); // Every 5 minutes
```

### HeartbeatLogTask

Logs a heartbeat every hour with uptime and memory stats.

### CooldownCleanupTask

Clears expired cooldown entries hourly to prevent memory leaks.

## Activity Types

| Type | Description |
|------|-------------|
| 0 | Playing |
| 1 | Streaming (requires URL) |
| 2 | Listening |
| 3 | Watching |
| 4 | Custom |
| 5 | Competing |

## License

MIT
