# Events

Events let a bot react to what happens on the gateway rather than to a command.

## Listening

An invokable class carrying `#[Event]` becomes a listener. The event name is the gateway
event you want; the payload arrives as the single argument.

<!-- include: tests/Fixtures/ReadyListener.php -->

The class is resolved from the container, so a listener may take constructor dependencies.

## Intents

Discord only sends events your bot subscribed to. Intents are set in your configuration:

```php
use Tempcord\Discord\Bitwise\Bitwise;
use Tempcord\Discord\Enums\Intent;

return new TempcordConfig(
    token: env('DISCORD_TOKEN'),
    intents: Bitwise::from(
        Intent::GUILDS,
        Intent::GUILD_MESSAGES,
        Intent::MESSAGE_CONTENT,
    ),
);
```

A listener for an event you have no intent for is registered and simply never fires, which is
a common reason for a listener that appears to do nothing.

`MESSAGE_CONTENT`, `GUILD_MEMBERS` and `GUILD_PRESENCES` are privileged: they must also be
enabled in the Discord developer portal, and above a hundred guilds they need approval.

## Errors

A listener that throws is logged and contained; the gateway connection carries on rather than
the bot falling over.
