<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# Middleware

Something that runs before a handler, and may decide it never runs.

```php
use Tempcord\Interfaces\Middleware;
```

## Methods

### `__invoke(CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction, callable $next): void`

