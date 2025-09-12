# Events

Events allow your bot to respond to various activities that happen in Discord, such as messages being sent, users joining servers, or reactions being added to messages.

## Event Listeners

### Creating an Event Listener

Create event listeners using the `#[EventListener]` attribute:

```php
<?php

namespace App\Listeners;

use Tempcord\Attributes\EventListener;
use Tempcord\Events\MessageCreate;

class MessageListener
{
    #[EventListener]
    public function onMessageCreate(MessageCreate $event): void
    {
        $message = $event->message;
        
        // Ignore bot messages
        if ($message->author->bot) {
            return;
        }
        
        // Respond to specific content
        if ($message->content === 'hello') {
            $message->reply('Hello there!');
        }
    }
}
```

### Event Method Naming

Event listener methods should follow the pattern `on{EventName}`:

- `onMessageCreate` for `MessageCreate` events
- `onGuildMemberAdd` for `GuildMemberAdd` events
- `onReactionAdd` for `ReactionAdd` events

## Available Events

### Message Events

#### MessageCreate
Triggered when a message is sent:

```php
use Tempcord\Events\MessageCreate;

#[EventListener]
public function onMessageCreate(MessageCreate $event): void
{
    $message = $event->message;
    $author = $message->author;
    $content = $message->content;
    $channel = $message->channel;
    $guild = $message->guild;
}
```

#### MessageUpdate
Triggered when a message is edited:

```php
use Tempcord\Events\MessageUpdate;

#[EventListener]
public function onMessageUpdate(MessageUpdate $event): void
{
    $oldMessage = $event->oldMessage; // May be null if not cached
    $newMessage = $event->newMessage;
}
```

#### MessageDelete
Triggered when a message is deleted:

```php
use Tempcord\Events\MessageDelete;

#[EventListener]
public function onMessageDelete(MessageDelete $event): void
{
    $message = $event->message; // May be null if not cached
    $messageId = $event->messageId;
    $channelId = $event->channelId;
    $guildId = $event->guildId;
}
```

### Guild Events

#### GuildMemberAdd
Triggered when a user joins a server:

```php
use Tempcord\Events\GuildMemberAdd;

#[EventListener]
public function onGuildMemberAdd(GuildMemberAdd $event): void
{
    $member = $event->member;
    $guild = $member->guild;
    $user = $member->user;
    
    // Send welcome message
    $welcomeChannel = $guild->getChannel('welcome-channel-id');
    $welcomeChannel->send("Welcome {$user->mention()} to {$guild->name}!");
}
```

#### GuildMemberRemove
Triggered when a user leaves a server:

```php
use Tempcord\Events\GuildMemberRemove;

#[EventListener]
public function onGuildMemberRemove(GuildMemberRemove $event): void
{
    $user = $event->user;
    $guild = $event->guild;
    
    // Log the departure
    $logChannel = $guild->getChannel('log-channel-id');
    $logChannel->send("{$user->tag} has left the server.");
}
```

#### GuildCreate
Triggered when the bot joins a new server:

```php
use Tempcord\Events\GuildCreate;

#[EventListener]
public function onGuildCreate(GuildCreate $event): void
{
    $guild = $event->guild;
    
    // Setup default configuration for new guild
    $this->setupGuildDefaults($guild);
}
```

### Reaction Events

#### ReactionAdd
Triggered when a reaction is added to a message:

```php
use Tempcord\Events\ReactionAdd;

#[EventListener]
public function onReactionAdd(ReactionAdd $event): void
{
    $reaction = $event->reaction;
    $user = $event->user;
    $message = $reaction->message;
    $emoji = $reaction->emoji;
    
    // Role reactions
    if ($message->id === 'role-message-id' && $emoji->name === '🎮') {
        $role = $message->guild->getRole('gamer-role-id');
        $member = $message->guild->getMember($user->id);
        $member->addRole($role);
    }
}
```

#### ReactionRemove
Triggered when a reaction is removed from a message:

```php
use Tempcord\Events\ReactionRemove;

#[EventListener]
public function onReactionRemove(ReactionRemove $event): void
{
    $reaction = $event->reaction;
    $user = $event->user;
    $emoji = $reaction->emoji;
    
    // Remove role when reaction is removed
    if ($emoji->name === '🎮') {
        $role = $reaction->message->guild->getRole('gamer-role-id');
        $member = $reaction->message->guild->getMember($user->id);
        $member->removeRole($role);
    }
}
```

### Voice Events

#### VoiceStateUpdate
Triggered when a user's voice state changes:

```php
use Tempcord\Events\VoiceStateUpdate;

#[EventListener]
public function onVoiceStateUpdate(VoiceStateUpdate $event): void
{
    $oldState = $event->oldState;
    $newState = $event->newState;
    $member = $newState->member;
    
    // User joined a voice channel
    if (!$oldState->channel && $newState->channel) {
        // Handle voice channel join
    }
    
    // User left a voice channel
    if ($oldState->channel && !$newState->channel) {
        // Handle voice channel leave
    }
    
    // User moved between channels
    if ($oldState->channel && $newState->channel && $oldState->channel->id !== $newState->channel->id) {
        // Handle voice channel move
    }
}
```

## Event Filtering

### Guild-Specific Listeners

Listen to events only from specific guilds:

```php
use Tempcord\Attributes\EventListener;
use Tempcord\Attributes\GuildOnly;
use Tempcord\Events\MessageCreate;

class GuildMessageListener
{
    #[EventListener]
    #[GuildOnly(['guild-id-1', 'guild-id-2'])]
    public function onMessageCreate(MessageCreate $event): void
    {
        // Only processes messages from specified guilds
    }
}
```

### Channel-Specific Listeners

Listen to events only from specific channels:

```php
use Tempcord\Attributes\EventListener;
use Tempcord\Attributes\ChannelOnly;
use Tempcord\Events\MessageCreate;

class ChannelMessageListener
{
    #[EventListener]
    #[ChannelOnly(['channel-id-1', 'channel-id-2'])]
    public function onMessageCreate(MessageCreate $event): void
    {
        // Only processes messages from specified channels
    }
}
```

### Conditional Listeners

Use custom conditions to filter events:

```php
use Tempcord\Attributes\EventListener;
use Tempcord\Events\MessageCreate;

class ConditionalListener
{
    #[EventListener]
    public function onMessageCreate(MessageCreate $event): void
    {
        $message = $event->message;
        
        // Only process messages with attachments
        if (empty($message->attachments)) {
            return;
        }
        
        // Only process messages from non-bots
        if ($message->author->bot) {
            return;
        }
        
        // Process the message
        $this->processMessageWithAttachment($message);
    }
}
```

## Event Priority

Control the order in which event listeners are executed:

```php
use Tempcord\Attributes\EventListener;
use Tempcord\Attributes\Priority;
use Tempcord\Events\MessageCreate;

class HighPriorityListener
{
    #[EventListener]
    #[Priority(100)] // Higher numbers = higher priority
    public function onMessageCreate(MessageCreate $event): void
    {
        // This runs before lower priority listeners
    }
}

class LowPriorityListener
{
    #[EventListener]
    #[Priority(10)] // Lower numbers = lower priority
    public function onMessageCreate(MessageCreate $event): void
    {
        // This runs after higher priority listeners
    }
}
```

## Async Event Handling

For long-running event handlers, use async processing:

```php
use Tempcord\Attributes\EventListener;
use Tempcord\Attributes\Async;
use Tempcord\Events\MessageCreate;

class AsyncListener
{
    #[EventListener]
    #[Async]
    public function onMessageCreate(MessageCreate $event): void
    {
        // This runs asynchronously and won't block other events
        $this->performLongRunningTask($event->message);
    }
    
    private function performLongRunningTask($message): void
    {
        // Long-running operation like API calls, file processing, etc.
        sleep(5); // This won't block other events
    }
}
```

## Error Handling

### Try-Catch in Listeners

```php
use Tempcord\Attributes\EventListener;
use Tempcord\Events\MessageCreate;
use Exception;

class SafeListener
{
    #[EventListener]
    public function onMessageCreate(MessageCreate $event): void
    {
        try {
            $this->processMessage($event->message);
        } catch (Exception $e) {
            // Log the error
            logger()->error('Error processing message', [
                'message_id' => $event->message->id,
                'error' => $e->getMessage(),
            ]);
            
            // Optionally notify administrators
            $this->notifyAdmins($e, $event->message);
        }
    }
}
```

### Global Error Handling

Configure global error handling for events:

```php
// In your configuration
use Tempcord\Config\TempcordConfig;

return new TempcordConfig(
    // ... other config
    eventErrorHandler: function (Exception $e, $event) {
        logger()->error('Event handler error', [
            'event' => get_class($event),
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
    }
);
```

## Event Data Access

### Accessing Discord Objects

```php
use Tempcord\Events\MessageCreate;

#[EventListener]
public function onMessageCreate(MessageCreate $event): void
{
    $message = $event->message;
    
    // Message properties
    $content = $message->content;
    $id = $message->id;
    $timestamp = $message->timestamp;
    $editedTimestamp = $message->editedTimestamp;
    $attachments = $message->attachments;
    $embeds = $message->embeds;
    $mentions = $message->mentions;
    $reactions = $message->reactions;
    
    // Author information
    $author = $message->author;
    $authorId = $author->id;
    $authorTag = $author->tag;
    $authorAvatar = $author->avatar;
    
    // Channel information
    $channel = $message->channel;
    $channelId = $channel->id;
    $channelName = $channel->name;
    $channelType = $channel->type;
    
    // Guild information (if in a guild)
    if ($message->guild) {
        $guild = $message->guild;
        $guildId = $guild->id;
        $guildName = $guild->name;
        $member = $message->member; // Guild member object
    }
}
```

## Custom Events

Create your own custom events:

```php
<?php

namespace App\Events;

use Tempcord\Events\Event;

class UserLevelUp extends Event
{
    public function __construct(
        public readonly string $userId,
        public readonly int $oldLevel,
        public readonly int $newLevel,
        public readonly string $guildId
    ) {}
}
```

Dispatch custom events:

```php
use App\Events\UserLevelUp;
use Tempcord\EventDispatcher;

// Somewhere in your code
$event = new UserLevelUp(
    userId: '123456789',
    oldLevel: 5,
    newLevel: 6,
    guildId: '987654321'
);

EventDispatcher::dispatch($event);
```

Listen to custom events:

```php
use App\Events\UserLevelUp;
use Tempcord\Attributes\EventListener;

class LevelUpListener
{
    #[EventListener]
    public function onUserLevelUp(UserLevelUp $event): void
    {
        // Congratulate the user
        $guild = discord()->getGuild($event->guildId);
        $user = discord()->getUser($event->userId);
        
        $channel = $guild->getChannel('general');
        $channel->send("🎉 Congratulations {$user->mention()}! You reached level {$event->newLevel}!");
    }
}
```

## Best Practices

### Performance
- Keep event handlers lightweight
- Use async processing for heavy operations
- Cache frequently accessed data
- Avoid blocking operations in event handlers

### Error Handling
- Always wrap risky operations in try-catch blocks
- Log errors with sufficient context
- Don't let one event handler failure affect others
- Implement graceful degradation

### Security
- Validate event data before processing
- Check permissions before performing actions
- Rate limit event-triggered actions
- Sanitize user input from events

### Organization
- Group related listeners in the same class
- Use descriptive method names
- Keep listeners focused on single responsibilities
- Document complex event handling logic

## Examples

Check out the `examples/events/` directory for more event handling examples and patterns.