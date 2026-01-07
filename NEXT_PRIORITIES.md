# Tempcord Framework - Next Development Priorities

## Research Summary

This document analyzes what Tempcord currently implements, what Discord officially offers, and what features are available in our dependency libraries (Fenrir and TempestPHP) that we could leverage.

---

## Current State: Tempcord v0.6.135

### What's Already Implemented

| Category | Status | Features |
|----------|--------|----------|
| **Slash Commands** | Complete | `#[Command]`, `#[Subcommand]`, `#[SubcommandGroup]`, `#[Option]` with type inference |
| **Components** | Complete | `#[Button]` with wildcards, `#[SelectMenu]` (all 5 types), `#[Modal]` |
| **Builders** | Complete | `ButtonBuilder`, `SelectMenuBuilder`, `ModalBuilder`, `ActionRowBuilder`, `TextInputBuilder` |
| **Response System** | Complete | `InteractionResponse` fluent API with embeds, fields, colors, components |
| **Middleware** | Complete | `AdminMiddleware`, `PermissionMiddleware`, `RateLimitMiddleware`, `LoggingMiddleware`, `ErrorHandlingMiddleware` |
| **Scheduled Tasks** | Complete | `#[Task]` with interval and cron scheduling |
| **Localization** | Complete | `CommandTranslator` supporting 31 Discord locales |
| **Console Commands** | Complete | `boot`, `register`, `commands:list`, `tasks:list` |

### Known Gaps in Current Implementation

1. **Event Handlers** - No system for gateway events (MessageCreate, MemberJoin, etc.)
2. **Autocomplete** - Interface defined but NOT implemented
3. **Context Menus** - USER and MESSAGE command types not supported
4. **Voice** - No voice channel support
5. **Component Middleware** - Currently skipped (line 226: "For now, skip middleware")
6. **Persistent Rate Limiting** - Uses in-memory storage only
7. **Testing Utilities** - No mock interactions or test assertions

---

## Discord API - Official Features Available

### Gateway Events (Not Implemented in Tempcord)

| Event Category | Events | Notes |
|----------------|--------|-------|
| **Guild Events** | GUILD_CREATE, GUILD_UPDATE, GUILD_DELETE | Requires GUILDS intent |
| **Channel Events** | CHANNEL_CREATE, CHANNEL_UPDATE, CHANNEL_DELETE, CHANNEL_PINS_UPDATE | Requires GUILDS intent |
| **Thread Events** | THREAD_CREATE, THREAD_UPDATE, THREAD_DELETE, THREAD_LIST_SYNC | Requires GUILDS intent |
| **Member Events** | GUILD_MEMBER_ADD, GUILD_MEMBER_UPDATE, GUILD_MEMBER_REMOVE | Requires GUILD_MEMBERS (privileged) |
| **Message Events** | MESSAGE_CREATE, MESSAGE_UPDATE, MESSAGE_DELETE, MESSAGE_DELETE_BULK | Requires MESSAGE_CONTENT (privileged) for content |
| **Reaction Events** | MESSAGE_REACTION_ADD, MESSAGE_REACTION_REMOVE, MESSAGE_REACTION_REMOVE_ALL | Requires GUILD_MESSAGE_REACTIONS |
| **Presence Events** | PRESENCE_UPDATE, TYPING_START | GUILD_PRESENCES (privileged) |
| **Voice Events** | VOICE_STATE_UPDATE, VOICE_SERVER_UPDATE | Requires GUILD_VOICE_STATES |
| **Scheduled Events** | GUILD_SCHEDULED_EVENT_CREATE, _UPDATE, _DELETE, _USER_ADD, _USER_REMOVE | Requires GUILD_SCHEDULED_EVENTS |
| **Auto Moderation** | AUTO_MODERATION_RULE_CREATE, _UPDATE, _DELETE, AUTO_MODERATION_ACTION_EXECUTION | Requires AUTO_MODERATION_CONFIGURATION |

### Interaction Types (Partially Implemented)

| Type | Value | Status in Tempcord |
|------|-------|-------------------|
| PING | 1 | N/A (Discord handles) |
| APPLICATION_COMMAND | 2 | Implemented |
| MESSAGE_COMPONENT | 3 | Implemented |
| APPLICATION_COMMAND_AUTOCOMPLETE | 4 | Interface only |
| MODAL_SUBMIT | 5 | Implemented |

### Application Command Types

| Type | Value | Status |
|------|-------|--------|
| CHAT_INPUT (Slash) | 1 | Implemented |
| USER (Context Menu) | 2 | **NOT Implemented** |
| MESSAGE (Context Menu) | 3 | **NOT Implemented** |

### Components v2 System

Discord recently introduced Components v2 with new layout capabilities:

| Component | Type | Status |
|-----------|------|--------|
| Action Row | Layout | Implemented |
| Button | Interactive | Implemented |
| String Select | Interactive | Implemented |
| Text Input | Interactive | Implemented |
| User Select | Interactive | Implemented |
| Role Select | Interactive | Implemented |
| Mentionable Select | Interactive | Implemented |
| Channel Select | Interactive | Implemented |
| **Section** | Layout (v2) | **NOT Implemented** |
| **Text Display** | Content (v2) | **NOT Implemented** |
| **Thumbnail** | Content (v2) | **NOT Implemented** |
| **Media Gallery** | Content (v2) | **NOT Implemented** |
| **Separator** | Layout (v2) | **NOT Implemented** |
| **Container** | Layout (v2) | **NOT Implemented** |

---

## Fenrir Library - Available Features

Fenrir (v1.x) is a "mostly plain wrapper over Discord's APIs/gateway" with:

### What Fenrir Provides

| Feature | Available | Notes |
|---------|-----------|-------|
| Gateway Connection | Yes | `$discord->withGateway()` |
| REST API | Yes | `$discord->rest->channel->createMessage()` |
| Intents | Yes | `Bitwise::from(Intent::*)` |
| Event System | Yes | `$discord->gateway->events->on(Events::*)` |
| MessageBuilder | Yes | `MessageBuilder::new()->setContent()` |
| InteractionCallbackBuilder | Yes | For interaction responses |
| Async (ReactPHP) | Yes | Promise-based |

### What Fenrir Does NOT Provide

- No built-in caching
- No command framework (Tempcord provides this)
- No middleware system
- No discovery system
- No component builders (Tempcord provides this)
- No embed builders (Tempcord provides `InteractionResponse`)

### Fenrir REST Endpoints (Available to Leverage)

Based on Fenrir's structure, these REST endpoints should be available:
- Channel operations (messages, pins, threads)
- Guild operations (members, roles, bans, emoji)
- User operations (DMs, connections)
- Webhook operations
- Interaction responses

---

## TempestPHP Framework - Available Features

### Features We Already Use

| Feature | Usage in Tempcord |
|---------|-------------------|
| Discovery | `CommandsDiscovery`, `ComponentsDiscovery`, `TasksDiscovery` |
| Console Commands | `#[ConsoleCommand]` for boot, register, etc. |
| DI Container | Constructor injection throughout |
| Logging | Via Monolog integration |

### Features Available BUT NOT Used

| Feature | Description | Potential Use |
|---------|-------------|---------------|
| **Event Bus** | `#[EventHandler]` attribute, `event()` dispatch | Internal events, decoupled architecture |
| **Command Bus** | CQRS-style command handling | Separate command logic from handlers |
| **Scheduling** | `#[Schedule]` with Interval/Every values | Alternative to our `#[Task]` system |
| **Console Middleware** | `CautionMiddleware`, `ForceMiddleware` | Production safeguards |
| **Validation** | Attribute-based validation | Validate command options |
| **Cache** | `tempest/cache` package | Rate limiting persistence, data caching |
| **Generation** | Code generation utilities | Scaffolding commands |

### TempestPHP Event Bus Details

```php
// Define event
class CommandExecuted {
    public function __construct(
        public string $command,
        public string $userId,
        public float $duration
    ) {}
}

// Handler (auto-discovered)
#[EventHandler]
public function onCommandExecuted(CommandExecuted $event): void {
    // Log, metrics, analytics...
}

// Dispatch
event(new CommandExecuted('ping', '123', 0.05));
```

---

## Recommended Next Priorities

### Priority 1: High Impact, Lower Effort

#### 1.1 Autocomplete Implementation
**Effort:** Medium | **Impact:** High

The interface exists but isn't implemented. Need:
- `AutocompleteBucket` to handle `APPLICATION_COMMAND_AUTOCOMPLETE` interactions
- Connect `Autocomplete` interface to the registry
- Support closure-based autocomplete in `#[Option]`

```php
#[Option(autocomplete: [MyAutocomplete::class, 'handle'])]
public string $query
```

#### 1.2 Context Menu Commands (USER & MESSAGE)
**Effort:** Medium | **Impact:** High

Discord limits: 5 global context menus per type.

```php
#[UserCommand('Get User Info')]
class GetUserInfo {
    public function __invoke(UserCommandInteraction $interaction): void {
        $targetUser = $interaction->getTargetUser();
        // ...
    }
}

#[MessageCommand('Report Message')]
class ReportMessage {
    public function __invoke(MessageCommandInteraction $interaction): void {
        $targetMessage = $interaction->getTargetMessage();
        // ...
    }
}
```

#### 1.3 Component Middleware Support
**Effort:** Low | **Impact:** Medium

Currently skipped in `ComponentsRegistry`. Enable middleware pipeline for button/select menu handlers.

### Priority 2: Gateway Events System

#### 2.1 Event Handler Attribute System
**Effort:** High | **Impact:** High

Create a system similar to commands for gateway events:

```php
#[Event(Events::MESSAGE_CREATE)]
class MessageLogger {
    public function __invoke(MessageCreate $event): void {
        // Handle message creation
    }
}

#[Event(Events::GUILD_MEMBER_ADD)]
class WelcomeNewMember {
    public function __invoke(GuildMemberAdd $event): void {
        // Send welcome message
    }
}
```

Components needed:
- `EventsDiscovery` (similar to CommandsDiscovery)
- `EventsRegistry` (bind to gateway events)
- `EventsBucket` (route events to handlers)
- Event-specific interaction classes

#### 2.2 Leverage TempestPHP Event Bus
**Effort:** Medium | **Impact:** Medium

Use Tempest's event bus for internal framework events:
- `CommandExecuted` - after command runs
- `ComponentInteracted` - after component interaction
- `TaskCompleted` - after scheduled task
- `BotReady` - when gateway connects

Benefits: Decoupled logging, metrics, analytics

### Priority 3: Persistence & Infrastructure

#### 3.1 Persistent Rate Limiting
**Effort:** Medium | **Impact:** Medium

Current: In-memory (resets on restart)
Options:
- Use `tempest/cache` for file/Redis caching
- Add cache interface for user implementation

#### 3.2 Testing Utilities
**Effort:** Medium | **Impact:** High

```php
class MyCommandTest extends TestCase {
    use InteractionTesting;

    public function test_ping_command(): void {
        $result = $this->command('ping')
            ->asUser('123456')
            ->execute();

        $result->assertResponded()
            ->assertEphemeral(false)
            ->assertContains('Pong!');
    }
}
```

### Priority 4: Advanced Discord Features

#### 4.1 Components v2 Support
**Effort:** High | **Impact:** Medium

New components in Discord's v2 system:
- Section, Container, Separator (layout)
- Text Display, Thumbnail, Media Gallery (content)

Requires `IS_COMPONENTS_V2` flag on messages.

#### 4.2 Voice Support
**Effort:** Very High | **Impact:** Low-Medium

Would require significant Fenrir extensions or integration with Lavalink.

---

## Implementation Roadmap

### Phase 1: Complete Interaction System
1. Autocomplete implementation
2. Context menu commands (USER, MESSAGE)
3. Component middleware

### Phase 2: Event-Driven Architecture
1. Gateway event handler system (`#[Event]`)
2. Internal event bus integration (TempestPHP)
3. Event middleware support

### Phase 3: Infrastructure Improvements
1. Persistent rate limiting (cache integration)
2. Testing framework
3. Metrics/observability hooks

### Phase 4: Advanced Features
1. Components v2 support
2. Guild-specific command management
3. Sharding support (if needed)

---

## Quick Wins (Can Do Now)

1. **Fix Component Middleware** - Remove "skip middleware" hack
2. **Add Missing Intents Helpers** - Constants for common intent combinations
3. **Improve Error Messages** - Better DX for misconfiguration
4. **Documentation** - Align docs with actual implementation
5. **Add `MENTIONABLE` Option Type** - Currently throws RuntimeException

---

## Sources

### Discord Official
- [Gateway Documentation](https://discord.com/developers/docs/events/gateway)
- [Application Commands](https://discord.com/developers/docs/interactions/application-commands)
- [Message Components](https://discord.com/developers/docs/interactions/message-components)

### Libraries
- [Fenrir - PHP Discord Interface](https://github.com/dc-Ragnarok/Fenrir)
- [TempestPHP Framework](https://tempestphp.com/)
- [TempestPHP Event Bus](https://tempestphp.com/1.x/features/events)
- [TempestPHP Console Commands](https://tempestphp.com/2.x/essentials/console-commands)

### Other Discord Libraries (Reference)
- [discord.js](https://discord.js.org/)
- [discord.py](https://discordpy.readthedocs.io/)
- [JDA (Java)](https://jda.wiki/)
