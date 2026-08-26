<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# Command

Declares a class as a Discord application command.

```php
use Tempcord\Attributes\Command;
```

**Applies to:** class, method

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `BackedEnum\|string\|null` | `null` | defaults to the class name, with a Command prefix or suffix stripped and the rest snake_cased |
| `description` | `?string` | `null` |  |
| `guildId` | `string\|int\|null` | `null` |  |
| `isNsfw` | `bool` | `false` |  |
| `permissions` | `array` | `[]` | the permissions a member needs by default; an empty list leaves the command unrestricted |
| `directMessage` | `bool` | `true` |  |
| `type` | `ApplicationCommandTypes` | `ApplicationCommandTypes::CHAT_INPUT` |  |
| `translationKey` | `?string` | `null` | the catalog key this command's translations live under. Keys for everything beneath it are derived from position, so "commands.music" gives commands.music.description for the command, commands.music.playlist.play.description for a subcommand, and commands.music.playlist.play.title.description for its option. |

