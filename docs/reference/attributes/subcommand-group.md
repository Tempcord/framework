<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# SubcommandGroup

Groups every subcommand its class declares under one more level of nesting.

```php
use Tempcord\Attributes\SubcommandGroup;
```

**Applies to:** class

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `name` | `BackedEnum\|string` | *required* |  |
| `description` | `string` | *required* |  |
| `middleware` | `array` | `[]` | run after whatever the command declares, before the subcommand's own |

