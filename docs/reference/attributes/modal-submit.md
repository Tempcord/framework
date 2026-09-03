<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# ModalSubmit

Declares a class or method as the handler for a submitted modal.

```php
use Tempcord\Attributes\ModalSubmit;
```

**Applies to:** class, method

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `BackedEnum\|string\|null` | `null` | the modal's custom id, which may carry {placeholders}. Defaults to the class name with a ModalSubmit or Modal prefix or suffix stripped and the rest snake_cased. |
| `middleware` | `array` | `[]` | run in the order given, outermost first; any of them may answer instead of letting the handler run |

