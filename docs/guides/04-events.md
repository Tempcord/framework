# Events

Events let a bot react to what happens on the gateway rather than to a command.

## Listening

An invokable class carrying `#[Event]` becomes a listener. The event name is the gateway
event you want; the payload arrives as the single argument.

```php
use Tempcord\Attributes\Event;

#[Event(name: 'READY')]
final class ReadyListener
{
    /** @var list<object> */
    public static array $received = [];

    public function __invoke(object $payload): void
    {
        self::$received[] = $payload;
    }
}
```

<small>From [`tests/Fixtures/ReadyListener.php`](../../tests/Fixtures/ReadyListener.php) — compiled and exercised by the test suite.</small>

The class is resolved from the container, so a listener may take constructor dependencies.

## Intents

Discord only sends events your bot subscribed to. Intents are set in your configuration:

```php
use CyberWolf\Discord\Bitwise\Bitwise;
use CyberWolf\Discord\Enums\Intent;

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
