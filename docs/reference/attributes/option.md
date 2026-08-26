<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# Option

Declares a method parameter as a user-supplied command option.

```php
use Tempcord\Attributes\Option;
```

**Applies to:** parameter

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `description` | `string` | *required* | shown beneath the option in Discord's picker |
| `name` | `?string` | `null` | defaults to the parameter's own name |
| `autocomplete` | `?Autocomplete` | `null` | suggests values as the user types; mutually exclusive with choices |
| `choices` | `array` | `[]` | the only values Discord will accept. A map uses its keys as the labels users see; a list shows each value as its own label. Mutually exclusive with autocomplete. |
| `minValue` | `int\|float\|null` | `null` | smallest accepted number |
| `maxValue` | `int\|float\|null` | `null` | largest accepted number |
| `minLength` | `?int` | `null` | shortest accepted string |
| `maxLength` | `?int` | `null` | longest accepted string |
| `channelTypes` | `array` | `[]` | restricts which channels may be picked, for a Channel option |

