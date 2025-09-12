# API Reference

This document provides a comprehensive reference for Tempcord's API classes, methods, and interfaces.

## Core Classes

### Tempcord\Discord\Interaction

Represents a Discord interaction (slash command, button click, etc.).

#### Properties

```php
public readonly string $id;                    // Interaction ID
public readonly string $applicationId;         // Application ID
public readonly int $type;                     // Interaction type
public readonly ?string $token;                // Interaction token
public readonly int $version;                  // Version
public readonly ?string $commandName;          // Command name
public readonly ?string $commandId;            // Command ID
public readonly array $options;                // Command options
public readonly User $user;                    // User who triggered
public readonly ?Member $member;               // Guild member (if in guild)
public readonly ?Guild $guild;                 // Guild (if in guild)
public readonly Channel $channel;              // Channel
public readonly ?Message $message;             // Message (for components)
public readonly string $locale;                // User locale
public readonly ?string $guildLocale;          // Guild locale
```

#### Methods

```php
// Response methods
public function reply(string $content, bool $ephemeral = false): void
public function editReply(string $content): void
public function deleteReply(): void
public function followUp(string $content, bool $ephemeral = false): void

// Deferred responses
public function defer(bool $ephemeral = false): void
public function deferUpdate(): void

// Modal responses
public function showModal(Modal $modal): void

// Component responses
public function updateMessage(string $content, array $components = []): void

// Option helpers
public function getOption(string $name, mixed $default = null): mixed
public function getStringOption(string $name, ?string $default = null): ?string
public function getIntegerOption(string $name, ?int $default = null): ?int
public function getBooleanOption(string $name, ?bool $default = null): ?bool
public function getUserOption(string $name): ?User
public function getChannelOption(string $name): ?Channel
public function getRoleOption(string $name): ?Role
public function getMentionableOption(string $name): User|Member|Role|null
public function getAttachmentOption(string $name): ?Attachment

// Context methods
public function setContext(string $key, mixed $value): void
public function getContext(string $key, mixed $default = null): mixed
public function hasContext(string $key): bool

// Permission checks
public function hasPermission(Permission $permission): bool
public function hasRole(string $roleId): bool
public function isOwner(): bool
public function isAdmin(): bool
```

### Tempcord\Discord\User

Represents a Discord user.

#### Properties

```php
public readonly string $id;                    // User ID
public readonly string $username;              // Username
public readonly string $discriminator;         // Discriminator (legacy)
public readonly ?string $globalName;           // Global display name
public readonly ?string $avatar;               // Avatar hash
public readonly ?bool $bot;                    // Is bot
public readonly ?bool $system;                 // Is system user
public readonly ?bool $mfaEnabled;             // MFA enabled
public readonly ?string $banner;               // Banner hash
public readonly ?int $accentColor;             // Accent color
public readonly ?string $locale;               // User locale
public readonly ?bool $verified;               // Email verified
public readonly ?string $email;                // Email address
public readonly ?int $flags;                   // User flags
public readonly ?int $premiumType;             // Nitro type
public readonly ?int $publicFlags;             // Public flags
```

#### Methods

```php
// Display methods
public function getDisplayName(): string       // Get display name
public function getTag(): string               // Get user#discriminator
public function getMention(): string           // Get <@id> mention
public function getAvatarUrl(?int $size = null): ?string // Get avatar URL
public function getBannerUrl(?int $size = null): ?string // Get banner URL

// Utility methods
public function isBot(): bool                  // Check if bot
public function hasNitro(): bool               // Check if has Nitro
public function getCreatedAt(): DateTime       // Get account creation date
```

### Tempcord\Discord\Guild

Represents a Discord guild (server).

#### Properties

```php
public readonly string $id;                    // Guild ID
public readonly string $name;                  // Guild name
public readonly ?string $icon;                 // Icon hash
public readonly ?string $iconHash;             // Icon hash (legacy)
public readonly ?string $splash;               // Splash hash
public readonly ?string $discoverySplash;      // Discovery splash
public readonly ?bool $owner;                  // Is owner
public readonly string $ownerId;               // Owner ID
public readonly ?string $permissions;          // Permissions
public readonly ?string $region;               // Voice region (deprecated)
public readonly ?string $afkChannelId;         // AFK channel ID
public readonly int $afkTimeout;               // AFK timeout
public readonly ?bool $widgetEnabled;          // Widget enabled
public readonly ?string $widgetChannelId;      // Widget channel ID
public readonly int $verificationLevel;        // Verification level
public readonly int $defaultMessageNotifications; // Default notifications
public readonly int $explicitContentFilter;   // Content filter level
public readonly array $roles;                  // Guild roles
public readonly array $emojis;                 // Guild emojis
public readonly array $features;               // Guild features
public readonly int $mfaLevel;                 // MFA level
public readonly ?string $applicationId;        // Application ID
public readonly ?string $systemChannelId;     // System channel ID
public readonly int $systemChannelFlags;      // System channel flags
public readonly ?string $rulesChannelId;      // Rules channel ID
public readonly ?int $maxPresences;           // Max presences
public readonly ?int $maxMembers;             // Max members
public readonly ?string $vanityUrlCode;       // Vanity URL
public readonly ?string $description;          // Description
public readonly ?string $banner;               // Banner hash
public readonly int $premiumTier;             // Boost tier
public readonly ?int $premiumSubscriptionCount; // Boost count
public readonly string $preferredLocale;      // Preferred locale
public readonly ?string $publicUpdatesChannelId; // Updates channel
public readonly ?int $maxVideoChannelUsers;   // Max video users
public readonly ?int $approximateMemberCount; // Approx members
public readonly ?int $approximatePresenceCount; // Approx online
```

#### Methods

```php
// Display methods
public function getIconUrl(?int $size = null): ?string // Get icon URL
public function getSplashUrl(?int $size = null): ?string // Get splash URL
public function getBannerUrl(?int $size = null): ?string // Get banner URL

// Member methods
public function getMember(string $userId): ?Member // Get member
public function getMembers(): array            // Get all members
public function getMemberCount(): int          // Get member count

// Channel methods
public function getChannel(string $channelId): ?Channel // Get channel
public function getChannels(): array           // Get all channels
public function getTextChannels(): array       // Get text channels
public function getVoiceChannels(): array      // Get voice channels

// Role methods
public function getRole(string $roleId): ?Role // Get role
public function getRoles(): array              // Get all roles
public function getEveryoneRole(): Role        // Get @everyone role

// Permission methods
public function getMemberPermissions(string $userId): array // Get permissions
public function hasFeature(string $feature): bool // Check feature

// Utility methods
public function getCreatedAt(): DateTime       // Get creation date
public function isOwner(string $userId): bool  // Check if user is owner
```

### Tempcord\Discord\Channel

Represents a Discord channel.

#### Properties

```php
public readonly string $id;                    // Channel ID
public readonly int $type;                     // Channel type
public readonly ?string $guildId;              // Guild ID
public readonly ?int $position;                // Position
public readonly ?array $permissionOverwrites; // Permission overwrites
public readonly ?string $name;                 // Channel name
public readonly ?string $topic;                // Channel topic
public readonly ?bool $nsfw;                   // Is NSFW
public readonly ?string $lastMessageId;        // Last message ID
public readonly ?int $bitrate;                 // Voice bitrate
public readonly ?int $userLimit;               // Voice user limit
public readonly ?int $rateLimitPerUser;        // Rate limit
public readonly ?array $recipients;            // DM recipients
public readonly ?string $icon;                 // Channel icon
public readonly ?string $ownerId;              // Channel owner
public readonly ?string $applicationId;        // Application ID
public readonly ?string $parentId;             // Parent category ID
public readonly ?DateTime $lastPinTimestamp;   // Last pin timestamp
public readonly ?string $rtcRegion;            // RTC region
public readonly ?int $videoQualityMode;        // Video quality
public readonly ?int $messageCount;            // Message count
public readonly ?int $memberCount;             // Member count
public readonly ?int $defaultAutoArchiveDuration; // Auto archive
public readonly ?string $permissions;          // Permissions
public readonly ?int $flags;                   // Channel flags
```

#### Methods

```php
// Message methods
public function sendMessage(string $content, array $options = []): Message
public function getMessage(string $messageId): ?Message
public function getMessages(int $limit = 50): array
public function deleteMessage(string $messageId): void
public function editMessage(string $messageId, string $content): Message

// Permission methods
public function hasPermission(string $userId, Permission $permission): bool
public function getPermissions(string $userId): array

// Utility methods
public function getMention(): string           // Get <#id> mention
public function isText(): bool                 // Is text channel
public function isVoice(): bool                // Is voice channel
public function isCategory(): bool             // Is category
public function isDM(): bool                   // Is DM channel
public function isThread(): bool               // Is thread
public function getCreatedAt(): DateTime       // Get creation date
```

## Attribute Classes

### Tempcord\Attributes\Command

Defines a slash command.

```php
#[Command(
    name: 'hello',
    description: 'Say hello to someone',
    defaultMemberPermissions: Permission::SEND_MESSAGES,
    dmPermission: true,
    nsfw: false
)]
```

#### Parameters

- `name` (string): Command name (required)
- `description` (string): Command description (required)
- `defaultMemberPermissions` (Permission|null): Default permissions
- `dmPermission` (bool): Allow in DMs
- `nsfw` (bool): NSFW command

### Tempcord\Attributes\Option

Defines a command option.

```php
#[Option(
    name: 'user',
    description: 'The user to greet',
    type: OptionType::USER,
    required: true,
    autocomplete: false
)]
```

#### Parameters

- `name` (string): Option name (required)
- `description` (string): Option description (required)
- `type` (OptionType): Option type (required)
- `required` (bool): Is required
- `autocomplete` (bool): Enable autocomplete
- `choices` (array): Predefined choices
- `minValue` (int|float): Minimum value
- `maxValue` (int|float): Maximum value
- `minLength` (int): Minimum string length
- `maxLength` (int): Maximum string length
- `channelTypes` (array): Allowed channel types

### Tempcord\Attributes\EventListener

Defines an event listener.

```php
#[EventListener(
    event: MessageCreateEvent::class,
    priority: 100,
    once: false
)]
```

#### Parameters

- `event` (string): Event class (required)
- `priority` (int): Listener priority
- `once` (bool): Run only once

### Tempcord\Attributes\Middleware

Defines middleware.

```php
#[Middleware(
    priority: 100
)]
```

#### Parameters

- `priority` (int): Middleware priority

### Tempcord\Attributes\UseMiddleware

Applies middleware to commands.

```php
#[UseMiddleware([
    AuthMiddleware::class,
    RateLimitMiddleware::class
])]
```

#### Parameters

- `middleware` (array): Array of middleware classes

## Enums

### Tempcord\Enums\OptionType

Command option types.

```php
OptionType::SUB_COMMAND         // 1
OptionType::SUB_COMMAND_GROUP   // 2
OptionType::STRING              // 3
OptionType::INTEGER             // 4
OptionType::BOOLEAN             // 5
OptionType::USER                // 6
OptionType::CHANNEL             // 7
OptionType::ROLE                // 8
OptionType::MENTIONABLE         // 9
OptionType::NUMBER              // 10
OptionType::ATTACHMENT          // 11
```

### Tempcord\Enums\Permission

Discord permissions.

```php
Permission::CREATE_INSTANT_INVITE     // 1 << 0
Permission::KICK_MEMBERS              // 1 << 1
Permission::BAN_MEMBERS               // 1 << 2
Permission::ADMINISTRATOR             // 1 << 3
Permission::MANAGE_CHANNELS           // 1 << 4
Permission::MANAGE_GUILD              // 1 << 5
Permission::ADD_REACTIONS             // 1 << 6
Permission::VIEW_AUDIT_LOG            // 1 << 7
Permission::PRIORITY_SPEAKER          // 1 << 8
Permission::STREAM                    // 1 << 9
Permission::VIEW_CHANNEL              // 1 << 10
Permission::SEND_MESSAGES             // 1 << 11
Permission::SEND_TTS_MESSAGES         // 1 << 12
Permission::MANAGE_MESSAGES           // 1 << 13
Permission::EMBED_LINKS               // 1 << 14
Permission::ATTACH_FILES              // 1 << 15
Permission::READ_MESSAGE_HISTORY      // 1 << 16
Permission::MENTION_EVERYONE          // 1 << 17
Permission::USE_EXTERNAL_EMOJIS       // 1 << 18
Permission::VIEW_GUILD_INSIGHTS       // 1 << 19
Permission::CONNECT                   // 1 << 20
Permission::SPEAK                     // 1 << 21
Permission::MUTE_MEMBERS              // 1 << 22
Permission::DEAFEN_MEMBERS            // 1 << 23
Permission::MOVE_MEMBERS              // 1 << 24
Permission::USE_VAD                   // 1 << 25
Permission::CHANGE_NICKNAME           // 1 << 26
Permission::MANAGE_NICKNAMES          // 1 << 27
Permission::MANAGE_ROLES              // 1 << 28
Permission::MANAGE_WEBHOOKS           // 1 << 29
Permission::MANAGE_EMOJIS_AND_STICKERS // 1 << 30
Permission::USE_APPLICATION_COMMANDS  // 1 << 31
Permission::REQUEST_TO_SPEAK          // 1 << 32
Permission::MANAGE_EVENTS             // 1 << 33
Permission::MANAGE_THREADS            // 1 << 34
Permission::CREATE_PUBLIC_THREADS     // 1 << 35
Permission::CREATE_PRIVATE_THREADS    // 1 << 36
Permission::USE_EXTERNAL_STICKERS     // 1 << 37
Permission::SEND_MESSAGES_IN_THREADS  // 1 << 38
Permission::USE_EMBEDDED_ACTIVITIES   // 1 << 39
Permission::MODERATE_MEMBERS          // 1 << 40
```

### Tempcord\Enums\InteractionType

Interaction types.

```php
InteractionType::PING                             // 1
InteractionType::APPLICATION_COMMAND              // 2
InteractionType::MESSAGE_COMPONENT                // 3
InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE // 4
InteractionType::MODAL_SUBMIT                     // 5
```

### Tempcord\Enums\ChannelType

Channel types.

```php
ChannelType::GUILD_TEXT          // 0
ChannelType::DM                  // 1
ChannelType::GUILD_VOICE         // 2
ChannelType::GROUP_DM            // 3
ChannelType::GUILD_CATEGORY      // 4
ChannelType::GUILD_NEWS          // 5
ChannelType::GUILD_STORE         // 6
ChannelType::GUILD_NEWS_THREAD   // 10
ChannelType::GUILD_PUBLIC_THREAD // 11
ChannelType::GUILD_PRIVATE_THREAD // 12
ChannelType::GUILD_STAGE_VOICE   // 13
ChannelType::GUILD_DIRECTORY     // 14
ChannelType::GUILD_FORUM         // 15
```

## Event Classes

### Tempcord\Events\MessageCreateEvent

Fired when a message is created.

```php
public readonly Message $message;
public readonly ?Guild $guild;
public readonly Channel $channel;
public readonly User $author;
```

### Tempcord\Events\MessageUpdateEvent

Fired when a message is updated.

```php
public readonly Message $message;
public readonly ?Message $oldMessage;
public readonly ?Guild $guild;
public readonly Channel $channel;
```

### Tempcord\Events\MessageDeleteEvent

Fired when a message is deleted.

```php
public readonly string $messageId;
public readonly string $channelId;
public readonly ?string $guildId;
```

### Tempcord\Events\GuildCreateEvent

Fired when the bot joins a guild.

```php
public readonly Guild $guild;
public readonly bool $unavailable;
```

### Tempcord\Events\GuildUpdateEvent

Fired when a guild is updated.

```php
public readonly Guild $guild;
public readonly ?Guild $oldGuild;
```

### Tempcord\Events\GuildDeleteEvent

Fired when the bot leaves a guild.

```php
public readonly string $guildId;
public readonly bool $unavailable;
```

### Tempcord\Events\GuildMemberAddEvent

Fired when a member joins a guild.

```php
public readonly Member $member;
public readonly Guild $guild;
```

### Tempcord\Events\GuildMemberUpdateEvent

Fired when a member is updated.

```php
public readonly Member $member;
public readonly ?Member $oldMember;
public readonly Guild $guild;
```

### Tempcord\Events\GuildMemberRemoveEvent

Fired when a member leaves a guild.

```php
public readonly User $user;
public readonly string $guildId;
```

## Interface Classes

### Tempcord\Middleware\MiddlewareInterface

Interface for middleware classes.

```php
interface MiddlewareInterface
{
    public function handle(Interaction $interaction, callable $next): mixed;
}
```

### Tempcord\Events\EventListenerInterface

Interface for event listeners.

```php
interface EventListenerInterface
{
    public function handle(object $event): void;
}
```

## Utility Classes

### Tempcord\Utils\EmbedBuilder

Builder for Discord embeds.

```php
$embed = EmbedBuilder::create()
    ->setTitle('Hello World')
    ->setDescription('This is an embed')
    ->setColor(0x00ff00)
    ->addField('Field 1', 'Value 1', true)
    ->addField('Field 2', 'Value 2', true)
    ->setFooter('Footer text', 'https://example.com/icon.png')
    ->setTimestamp()
    ->build();
```

#### Methods

```php
public static function create(): self
public function setTitle(?string $title): self
public function setDescription(?string $description): self
public function setUrl(?string $url): self
public function setTimestamp(?DateTime $timestamp = null): self
public function setColor(?int $color): self
public function setFooter(?string $text, ?string $iconUrl = null): self
public function setImage(?string $url): self
public function setThumbnail(?string $url): self
public function setAuthor(?string $name, ?string $url = null, ?string $iconUrl = null): self
public function addField(string $name, string $value, bool $inline = false): self
public function setFields(array $fields): self
public function build(): array
```

### Tempcord\Utils\ComponentBuilder

Builder for Discord components.

```php
$components = ComponentBuilder::create()
    ->addButton('primary', 'click_me', 'Click Me!')
    ->addButton('secondary', 'cancel', 'Cancel')
    ->addSelectMenu('select_option', 'Choose an option', [
        ['label' => 'Option 1', 'value' => 'opt1'],
        ['label' => 'Option 2', 'value' => 'opt2'],
    ])
    ->build();
```

#### Methods

```php
public static function create(): self
public function addButton(string $style, string $customId, string $label, ?string $emoji = null, bool $disabled = false): self
public function addLinkButton(string $url, string $label, ?string $emoji = null, bool $disabled = false): self
public function addSelectMenu(string $customId, string $placeholder, array $options, int $minValues = 1, int $maxValues = 1): self
public function addTextInput(string $customId, string $label, string $style = 'short', bool $required = true): self
public function newRow(): self
public function build(): array
```

### Tempcord\Utils\PermissionCalculator

Utility for permission calculations.

```php
$calculator = new PermissionCalculator();

// Check if user has permission
$hasPermission = $calculator->hasPermission($member, Permission::MANAGE_MESSAGES);

// Calculate effective permissions
$permissions = $calculator->calculatePermissions($member, $channel);

// Check multiple permissions
$hasAll = $calculator->hasAllPermissions($member, [
    Permission::SEND_MESSAGES,
    Permission::EMBED_LINKS,
]);
```

#### Methods

```php
public function hasPermission(Member $member, Permission $permission, ?Channel $channel = null): bool
public function hasAllPermissions(Member $member, array $permissions, ?Channel $channel = null): bool
public function hasAnyPermission(Member $member, array $permissions, ?Channel $channel = null): bool
public function calculatePermissions(Member $member, ?Channel $channel = null): array
public function isAdmin(Member $member): bool
```

## Helper Functions

### Global Helpers

```php
// Configuration
config(string $key, mixed $default = null): mixed

// Logging
logger(): LoggerInterface

// Cache
cache(): CacheInterface

// Queue
queue(): QueueInterface

// Application paths
app_path(string $path = ''): string
base_path(string $path = ''): string
config_path(string $path = ''): string
storage_path(string $path = ''): string
database_path(string $path = ''): string

// Environment
env(string $key, mixed $default = null): mixed

// Time
now(): DateTime
today(): DateTime

// Strings
str_random(int $length = 16): string
str_slug(string $title, string $separator = '-'): string

// Arrays
array_get(array $array, string $key, mixed $default = null): mixed
array_set(array &$array, string $key, mixed $value): void
array_forget(array &$array, string $key): void

// Validation
validate(array $data, array $rules): array

// Response helpers
response(): ResponseFactory
redirect(string $to = null): RedirectResponse

// Discord helpers
discord(): DiscordClient
bot(): BotInstance
```

## Error Classes

### Tempcord\Exceptions\TempcordException

Base exception class.

### Tempcord\Exceptions\CommandException

Thrown when command execution fails.

### Tempcord\Exceptions\ValidationException

Thrown when validation fails.

### Tempcord\Exceptions\PermissionException

Thrown when permission check fails.

### Tempcord\Exceptions\RateLimitException

Thrown when rate limit is exceeded.

### Tempcord\Exceptions\ConfigurationException

Thrown when configuration is invalid.

## Constants

### Version Information

```php
Tempcord::VERSION           // Current version
Tempcord::DISCORD_API_VERSION // Discord API version
Tempcord::USER_AGENT        // HTTP User-Agent
```

### Limits

```php
Tempcord::MAX_EMBED_TITLE_LENGTH        // 256
Tempcord::MAX_EMBED_DESCRIPTION_LENGTH  // 4096
Tempcord::MAX_EMBED_FIELD_NAME_LENGTH   // 256
Tempcord::MAX_EMBED_FIELD_VALUE_LENGTH  // 1024
Tempcord::MAX_EMBED_FOOTER_LENGTH       // 2048
Tempcord::MAX_EMBED_AUTHOR_LENGTH       // 256
Tempcord::MAX_EMBED_FIELDS              // 25
Tempcord::MAX_EMBED_TOTAL_LENGTH        // 6000

Tempcord::MAX_MESSAGE_LENGTH            // 2000
Tempcord::MAX_COMMAND_NAME_LENGTH       // 32
Tempcord::MAX_COMMAND_DESCRIPTION_LENGTH // 100
Tempcord::MAX_OPTION_NAME_LENGTH        // 32
Tempcord::MAX_OPTION_DESCRIPTION_LENGTH // 100
Tempcord::MAX_CHOICE_NAME_LENGTH        // 100
Tempcord::MAX_CHOICE_VALUE_LENGTH       // 100

Tempcord::MAX_COMPONENTS_PER_ROW        // 5
Tempcord::MAX_ROWS_PER_MESSAGE          // 5
Tempcord::MAX_SELECT_OPTIONS            // 25
Tempcord::MAX_BUTTON_LABEL_LENGTH       // 80
Tempcord::MAX_SELECT_PLACEHOLDER_LENGTH // 150
```

## Type Definitions

### Command Handler

```php
type CommandHandler = callable(Interaction): void
```

### Event Handler

```php
type EventHandler = callable(object): void
```

### Middleware Handler

```php
type MiddlewareHandler = callable(Interaction, callable): mixed
```

### Option Choice

```php
type OptionChoice = [
    'name' => string,
    'value' => string|int|float,
    'name_localizations' => ?array,
]
```

### Embed Field

```php
type EmbedField = [
    'name' => string,
    'value' => string,
    'inline' => ?bool,
]
```

### Component

```php
type Component = [
    'type' => int,
    'custom_id' => ?string,
    'disabled' => ?bool,
    'style' => ?int,
    'label' => ?string,
    'emoji' => ?array,
    'url' => ?string,
    'options' => ?array,
    'placeholder' => ?string,
    'min_values' => ?int,
    'max_values' => ?int,
    'min_length' => ?int,
    'max_length' => ?int,
    'required' => ?bool,
    'value' => ?string,
]
```

This API reference provides comprehensive documentation for all public classes, methods, and interfaces in Tempcord. For more detailed examples and usage patterns, refer to the other documentation files and the examples directory.