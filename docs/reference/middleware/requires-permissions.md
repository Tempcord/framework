<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# RequiresPermissions

Refuses anyone whose permissions in the channel fall short.

```php
use Tempcord\Middleware\RequiresPermissions;
```

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `permissions` | `array` | *required* | every one of which the caller must hold; an administrator holds all of them by definition |
| `refusal` | `string` | `'You are not allowed to use this command.'` | what the caller is told, ephemerally |

