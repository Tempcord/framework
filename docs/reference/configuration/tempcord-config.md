<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# TempcordConfig

```php
use Tempcord\TempcordConfig;
```

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `token` | `string` | *required* |  |
| `intents` | `Bitwise` | *required* |  |
| `cache` | `bool` | `true` | whether to keep the guilds, channels, roles, members and voice states the gateway reports, so a handler can read them without an HTTP round trip. Only ever holds what the configured intents already deliver. |
| `chunkMembers` | `bool` | `true` | whether to ask the gateway for the members a large guild leaves out of GUILD_CREATE. Without it the member cache is only ever a slice of a busy server. Needs the GUILD_MEMBERS intent, and is ignored without it. |

