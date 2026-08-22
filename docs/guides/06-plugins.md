# Plugins

A plugin is an ordinary Composer package that adds commands, events or behaviour to a bot.

## Commands and events need no plugin API

Tempest discovers any installed package that depends on a `tempest/*` package, and Tempcord's
discovery does not care whether a class came from your application or from a package. So a
package that ships this:

```php
#[Command(description: 'Show scheduled tasks')]
final class TasksCommand
{
    public function __invoke(CommandInteraction $interaction): void
    {
        // ...
    }
}
```

is registered by the bot that installs it, with no registration step. The same is true of
`#[Event]` listeners.

If your package does not depend on anything from Tempest, opt in through composer instead:

```json
{
  "extra": {
    "tempest": {
      "can-discover": true
    }
  }
}
```

## When a plugin needs to act

Discovery covers declarations. What it cannot do is give a package a moment to *run* — to
register a Fenrir extension, start a timer, or open a connection. That is what `Plugin` is
for.

```php
use Tempcord\Plugins\Plugin;
use Tempcord\Tempcord;

final class RecordingPlugin implements Plugin
{
    /** @var list<Tempcord> */
    public static array $booted = [];

    public function boot(Tempcord $tempcord): void
    {
        self::$booted[] = $tempcord;
    }
}
```

<small>From [`tests/Fixtures/RecordingPlugin.php`](../../tests/Fixtures/RecordingPlugin.php) — compiled and exercised by the test suite.</small>

`boot()` is called once, after commands and events are bound and before the gateway opens, so
anything that must exist before the first event arrives belongs there.

Plugins are built by the container, so a plugin may take whatever it needs:

```php
final readonly class TasksPlugin implements Plugin
{
    public function __construct(
        private Registry $tasks,
    ) {}

    public function boot(Tempcord $tempcord): void
    {
        $tempcord->discord->registerExtension($this->tasks);
    }
}
```

No registration is needed for the plugin class either — implementing the interface is enough.

## Failure

A plugin that throws while booting is logged and reported, and the bot carries on without it.
One broken plugin does not stop the others, or the bot.

## Writing one

```json
{
  "name": "vendor/my-plugin",
  "require": {
    "tempcord/framework": "^0.6"
  },
  "autoload": {
    "psr-4": {
      "Vendor\\MyPlugin\\": "src/"
    }
  }
}
```

Requiring `tempcord/framework` is enough for discovery, since the framework itself depends on
Tempest.
