<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# Autocomplete

Declares a method as the source of suggestions for one of the command's options.

```php
use Tempcord\Attributes\Autocomplete;
```

**Applies to:** method

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `option` | `BackedEnum\|string` | *required* | the name of the option this completes, as Discord knows it — the parameter's name, or whatever #[Option(name: ...)] renamed it to |

