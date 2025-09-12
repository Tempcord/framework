# Configuration

Tempcord provides a flexible configuration system that allows you to customize your bot's behavior, set up integrations, and manage environment-specific settings.

## Configuration File

The main configuration is defined in `config/tempcord.php`:

```php
<?php

use Tempcord\Config\TempcordConfig;
use App\Middleware\LoggingMiddleware;
use App\Middleware\RateLimitMiddleware;

return new TempcordConfig(
    token: env('DISCORD_TOKEN'),
    applicationId: env('DISCORD_APPLICATION_ID'),
    publicKey: env('DISCORD_PUBLIC_KEY'),
    
    // Command discovery
    commandPaths: [
        app_path('Commands'),
    ],
    
    // Event listener discovery
    eventPaths: [
        app_path('Events'),
    ],
    
    // Global middleware
    globalMiddleware: [
        LoggingMiddleware::class,
        RateLimitMiddleware::class,
    ],
    
    // Middleware groups
    middlewareGroups: [
        'admin' => [
            AdminOnlyMiddleware::class,
            AuditLogMiddleware::class,
        ],
    ],
    
    // Bot owners (can bypass certain restrictions)
    owners: explode(',', env('BOT_OWNERS', '')),
    
    // Guild-specific settings
    guilds: [
        'default' => [
            'prefix' => '!',
            'features' => ['commands', 'events'],
        ],
    ],
    
    // Cache configuration
    cache: [
        'driver' => env('CACHE_DRIVER', 'file'),
        'ttl' => 3600,
    ],
    
    // Logging configuration
    logging: [
        'level' => env('LOG_LEVEL', 'info'),
        'channels' => ['single', 'discord'],
    ],
);
```

## Environment Variables

Create a `.env` file in your project root:

```env
# Discord Configuration
DISCORD_TOKEN=your_bot_token_here
DISCORD_APPLICATION_ID=your_application_id_here
DISCORD_PUBLIC_KEY=your_public_key_here

# Bot Configuration
BOT_OWNERS=123456789012345678,987654321098765432
BOT_PREFIX=!

# Environment
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tempcord
DB_USERNAME=root
DB_PASSWORD=

# Cache
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Logging
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Queue
QUEUE_CONNECTION=redis

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
```

## Discord Application Setup

### 1. Create Discord Application

1. Go to [Discord Developer Portal](https://discord.com/developers/applications)
2. Click "New Application"
3. Give your application a name
4. Navigate to the "Bot" section
5. Click "Add Bot"
6. Copy the bot token to your `.env` file

### 2. Configure Bot Permissions

In the "Bot" section, configure:

- **Privileged Gateway Intents** (if needed):
  - Presence Intent
  - Server Members Intent
  - Message Content Intent

- **Bot Permissions**:
  - Send Messages
  - Use Slash Commands
  - Read Message History
  - Add Reactions
  - Embed Links
  - Attach Files

### 3. OAuth2 Setup

In the "OAuth2" section:

1. Select "bot" and "applications.commands" scopes
2. Select required permissions
3. Use the generated URL to invite your bot

## Configuration Options

### Core Settings

```php
return new TempcordConfig(
    // Required Discord credentials
    token: env('DISCORD_TOKEN'),
    applicationId: env('DISCORD_APPLICATION_ID'),
    publicKey: env('DISCORD_PUBLIC_KEY'),
    
    // Optional settings
    debug: env('APP_DEBUG', false),
    environment: env('APP_ENV', 'production'),
    timezone: env('APP_TIMEZONE', 'UTC'),
);
```

### Command Configuration

```php
return new TempcordConfig(
    // Command discovery paths
    commandPaths: [
        app_path('Commands'),
        app_path('Modules/*/Commands'),
    ],
    
    // Command registration settings
    commandRegistration: [
        'auto_register' => true,
        'global_commands' => true,
        'guild_commands' => [],
        'delete_missing' => false,
    ],
    
    // Command defaults
    commandDefaults: [
        'ephemeral' => false,
        'defer' => false,
        'timeout' => 30,
    ],
);
```

### Event Configuration

```php
return new TempcordConfig(
    // Event listener discovery paths
    eventPaths: [
        app_path('Events'),
        app_path('Modules/*/Events'),
    ],
    
    // Event settings
    events: [
        'auto_discover' => true,
        'async_processing' => true,
        'max_listeners' => 100,
    ],
    
    // Gateway intents
    intents: [
        'guilds',
        'guild_messages',
        'guild_message_reactions',
        'direct_messages',
    ],
);
```

### Middleware Configuration

```php
return new TempcordConfig(
    // Global middleware (applied to all commands)
    globalMiddleware: [
        LoggingMiddleware::class,
        RateLimitMiddleware::class,
        MaintenanceModeMiddleware::class,
    ],
    
    // Middleware groups
    middlewareGroups: [
        'admin' => [
            AdminOnlyMiddleware::class,
            AuditLogMiddleware::class,
        ],
        'public' => [
            CooldownMiddleware::class,
            ValidationMiddleware::class,
        ],
    ],
    
    // Middleware settings
    middleware: [
        'auto_discover' => true,
        'priority_sorting' => true,
    ],
);
```

### Database Configuration

```php
return new TempcordConfig(
    // Database settings
    database: [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'migrations_path' => database_path('migrations'),
        'auto_migrate' => env('AUTO_MIGRATE', false),
    ],
    
    // Model settings
    models: [
        'auto_discover' => true,
        'paths' => [
            app_path('Models'),
        ],
    ],
);
```

### Cache Configuration

```php
return new TempcordConfig(
    // Cache settings
    cache: [
        'driver' => env('CACHE_DRIVER', 'file'),
        'prefix' => env('CACHE_PREFIX', 'tempcord'),
        'ttl' => 3600,
        
        // Driver-specific settings
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'password' => env('REDIS_PASSWORD'),
            'database' => env('REDIS_DB', 0),
        ],
        
        'file' => [
            'path' => storage_path('cache'),
        ],
    ],
);
```

### Logging Configuration

```php
return new TempcordConfig(
    // Logging settings
    logging: [
        'default' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'info'),
        
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single', 'discord'],
            ],
            
            'single' => [
                'driver' => 'single',
                'path' => storage_path('logs/tempcord.log'),
                'level' => 'debug',
            ],
            
            'discord' => [
                'driver' => 'discord',
                'webhook_url' => env('DISCORD_LOG_WEBHOOK'),
                'level' => 'error',
            ],
        ],
    ],
);
```

### Queue Configuration

```php
return new TempcordConfig(
    // Queue settings
    queue: [
        'default' => env('QUEUE_CONNECTION', 'sync'),
        
        'connections' => [
            'sync' => [
                'driver' => 'sync',
            ],
            
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => 'default',
                'retry_after' => 90,
            ],
        ],
    ],
);
```

## Guild-Specific Configuration

```php
return new TempcordConfig(
    // Guild-specific settings
    guilds: [
        // Default settings for all guilds
        'default' => [
            'prefix' => '!',
            'features' => ['commands', 'events', 'moderation'],
            'permissions' => [
                'admin_roles' => [],
                'mod_roles' => [],
                'banned_users' => [],
            ],
        ],
        
        // Specific guild overrides
        '123456789012345678' => [
            'prefix' => '?',
            'features' => ['commands', 'events'],
            'disabled_commands' => ['admin', 'moderation'],
        ],
        
        '987654321098765432' => [
            'prefix' => '!',
            'features' => ['commands'],
            'custom_settings' => [
                'welcome_channel' => '123456789012345678',
                'log_channel' => '987654321098765432',
            ],
        ],
    ],
);
```

## Feature Flags

```php
return new TempcordConfig(
    // Feature flags
    features: [
        'slash_commands' => true,
        'context_menus' => true,
        'message_commands' => false,
        'auto_complete' => true,
        'modals' => true,
        'select_menus' => true,
        'buttons' => true,
        
        // Experimental features
        'experimental' => [
            'voice_support' => false,
            'ai_integration' => false,
            'advanced_permissions' => true,
        ],
    ],
);
```

## Performance Configuration

```php
return new TempcordConfig(
    // Performance settings
    performance: [
        'command_cache' => true,
        'event_cache' => true,
        'middleware_cache' => true,
        
        'limits' => [
            'max_commands' => 100,
            'max_events' => 50,
            'max_middleware' => 20,
            'command_timeout' => 30,
            'event_timeout' => 10,
        ],
        
        'optimization' => [
            'lazy_loading' => true,
            'preload_commands' => false,
            'compress_responses' => true,
        ],
    ],
);
```

## Security Configuration

```php
return new TempcordConfig(
    // Security settings
    security: [
        'verify_signatures' => true,
        'rate_limiting' => [
            'enabled' => true,
            'max_requests' => 60,
            'window_seconds' => 60,
        ],
        
        'permissions' => [
            'strict_mode' => true,
            'require_permissions' => true,
            'check_bot_permissions' => true,
        ],
        
        'validation' => [
            'sanitize_input' => true,
            'validate_options' => true,
            'max_input_length' => 2000,
        ],
    ],
);
```

## Development Configuration

```php
return new TempcordConfig(
    // Development settings
    development: [
        'hot_reload' => env('APP_DEBUG', false),
        'debug_mode' => env('APP_DEBUG', false),
        'profiling' => env('APP_DEBUG', false),
        
        'testing' => [
            'mock_discord' => env('MOCK_DISCORD', false),
            'fake_interactions' => env('FAKE_INTERACTIONS', false),
        ],
        
        'debugging' => [
            'log_all_interactions' => false,
            'dump_payloads' => false,
            'trace_middleware' => false,
        ],
    ],
);
```

## Configuration Validation

Tempcord automatically validates your configuration on startup:

```php
// Custom validation rules
return new TempcordConfig(
    // ... your config
    
    validation: [
        'strict' => true,
        'rules' => [
            'token' => 'required|string|min:50',
            'applicationId' => 'required|string|size:18',
            'owners' => 'array',
            'owners.*' => 'string|size:18',
        ],
    ],
);
```

## Environment-Specific Configuration

### Local Development

```php
// config/tempcord.local.php
return [
    'debug' => true,
    'logging' => [
        'level' => 'debug',
    ],
    'cache' => [
        'driver' => 'file',
    ],
    'development' => [
        'hot_reload' => true,
        'profiling' => true,
    ],
];
```

### Production

```php
// config/tempcord.production.php
return [
    'debug' => false,
    'logging' => [
        'level' => 'warning',
    ],
    'cache' => [
        'driver' => 'redis',
    ],
    'performance' => [
        'command_cache' => true,
        'optimization' => [
            'lazy_loading' => true,
            'preload_commands' => true,
        ],
    ],
];
```

## Configuration Helpers

### Accessing Configuration

```php
// Get configuration value
$token = config('tempcord.token');
$debug = config('tempcord.debug', false);

// Get nested configuration
$cacheDriver = config('tempcord.cache.driver');
$logLevel = config('tempcord.logging.level');

// Check if feature is enabled
if (config('tempcord.features.slash_commands')) {
    // Slash commands are enabled
}
```

### Dynamic Configuration

```php
// Set configuration at runtime
config(['tempcord.debug' => true]);

// Merge configuration
config()->merge('tempcord', [
    'custom_setting' => 'value',
]);
```

## Best Practices

### Security
- Never commit sensitive values to version control
- Use environment variables for secrets
- Validate all configuration values
- Use secure defaults

### Performance
- Cache configuration in production
- Use lazy loading for large configurations
- Minimize configuration file size
- Profile configuration loading

### Maintainability
- Use descriptive configuration keys
- Group related settings
- Document configuration options
- Use type hints and validation

### Environment Management
- Use different configurations per environment
- Validate environment-specific settings
- Use feature flags for gradual rollouts
- Monitor configuration changes

## Troubleshooting

### Common Issues

1. **Invalid Discord Token**
   ```
   Error: Invalid token provided
   Solution: Check your DISCORD_TOKEN in .env
   ```

2. **Missing Permissions**
   ```
   Error: Missing Access
   Solution: Check bot permissions in Discord
   ```

3. **Configuration Not Found**
   ```
   Error: Configuration file not found
   Solution: Ensure config/tempcord.php exists
   ```

### Debug Configuration

```php
// Enable debug mode
config(['tempcord.debug' => true]);

// Dump configuration
dd(config('tempcord'));

// Validate configuration
Tempcord::validateConfig();
```

## Examples

Check out the `examples/configuration/` directory for complete configuration examples and common setups.