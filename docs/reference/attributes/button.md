<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# Button

Declares a class or method as the handler for a button press.

```php
use Tempcord\Attributes\Button;
```

**Applies to:** class, method

## Parameters

| Name | Type | Default | Description |
| --- | --- | --- | --- |
| `id` | `BackedEnum\|string\|null` | `null` | the button's custom id. It may carry {placeholders}, as in "tournament.accept.{team}", which are matched out of the incoming id and passed to same-named parameters. Defaults to the class name with a Button prefix or suffix stripped and the rest snake_cased. |
| `middleware` | `array` | `[]` | run in the order given, outermost first; any of them may answer instead of letting the handler run |

