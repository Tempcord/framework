<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# CustomId

A component's custom id, which may carry {placeholders}.

```php
use Tempcord\Runtime\CustomId;
```

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `pattern` | `string` | *required* |  |
| `parameters` | `array` | *required* | the placeholder names, in the order they appear |
| `regex` | `?string` | *required* | null when the pattern has no placeholders and a plain string comparison is enough |

