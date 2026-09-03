<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# Subcommand

Declares a public method as a subcommand of the command its class declares.

```php
use Tempcord\Attributes\Subcommand;
```

**Applies to:** method

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `BackedEnum\|string` | *required* |  |
| `description` | `string` | *required* |  |
| `middleware` | `array` | `[]` | run after whatever the command and the group around it declare |

