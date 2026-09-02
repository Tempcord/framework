# Scheduled tasks

Some of what a bot does is not a reply to anything: sweeping rows that have run their
course, lifting blocks that have expired, polling a service with no gateway event of its
own. `#[Scheduled]` declares an invokable class as work the bot does on a timer.

```php
use Tempcord\Attributes\Scheduled;

#[Scheduled(everySeconds: 10)]
final readonly class SweepTemporaryMessages
{
    public function __construct(private TempMessages $messages) {}

    public function __invoke(): void
    {
        $this->messages->sweep();
    }
}
```

That is the whole registration. The class is discovered like a command or a listener, is
built by the container so it may take whatever dependencies it needs, and is put on the
event loop before the gateway opens.

## What the framework guarantees

A timer is less forgiving than an event listener: it fires again whether or not the last
turn finished or threw, forever. Three things are handled so you do not have to write them
into every task.

- **A task that throws is logged and keeps its place.** Without that the exception travels
  into the event loop, and the usual result is that the timer is cancelled and nothing is
  ever swept again — silently, because the bot carries on answering commands.
- **A task is never started alongside itself.** If a turn is still running when the next
  one is due, that turn is skipped and the skip is logged. Otherwise a task slower than its
  own interval makes every following turn slower until nothing else gets a look in.
- **Each turn runs in a fiber**, so a task may `await` the REST API exactly as a command
  handler does.

## The first turn

The first turn comes after the interval, not at boot. A scheduled task is a repeating
chore; something that must happen once at startup — a reconciliation against what changed
while the bot was down — belongs in a plugin's `boot()`, where its ordering against
everything else is visible.

```php
final readonly class VoicePlugin implements Plugin
{
    public function __construct(private VoiceReconciler $reconciler) {}

    public function boot(Tempcord $tempcord): void
    {
        $this->reconciler->catchUp();
    }
}
```

## Choosing an interval

`everySeconds` is a float, so sub-second intervals are allowed, and zero is refused at
discovery — it asks the loop to run the task as fast as it can, which starves the gateway
heartbeat and drops the connection.

Prefer one cheap sweep that runs often over a clever one that runs rarely: a query over an
indexed `expires_at` costs almost nothing, and a task that runs every ten seconds needs no
reasoning about when it last ran or what it missed across a restart.
