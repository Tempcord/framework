# Middleware

A middleware runs before a handler and may decide it never runs. It is the answer to
"may this person do this", asked in one place instead of restated at the top of every
handler that needs it.

```php
use Tempcord\Interfaces\Middleware;

final readonly class ModerationOnly implements Middleware
{
    public function __invoke($interaction, callable $next): void
    {
        // check, then either call $next($interaction) or answer instead
    }
}
```

Calling `$next` lets the handler run. Answering the interaction and returning without
calling it stops there — which is the whole point for a guard: the handler is never
reached, so it cannot half-do the work it was asked for.

## Why not just `#[Command(permissions: ...)]`

Discord's own permissions are the right tool most of the time — they hide a command from
anyone who may not use it, before it is ever typed. They stop being enough in two places:

- They are a **default**. A guild administrator can rewrite them in Server Settings, so
  they are not something to hang an irreversible action on.
- They are scoped to the **whole command**. A command whose subcommands do not share an
  audience cannot be described with them at all.

That second case is the one to reach for middleware over:

```php
use Tempcord\Attributes\Command;
use Tempcord\Attributes\Subcommand;
use Tempcord\Discord\Interaction\CommandInteraction;

/**
 * One command, two audiences: anybody may make a suggestion, only moderation
 * may act on the ones that are in.
 *
 * Discord scopes a command's permissions to the whole command, so this is not
 * something #[Command(permissions: ...)] can describe at all.
 */
#[Command(description: 'Suggestions from the server')]
final class SuggestionCommand
{
    #[Subcommand(name: 'add', description: 'Suggest something.')]
    public function add(CommandInteraction $interaction): void {}

    #[Subcommand(
        name: 'close',
        description: 'Close a suggestion.',
        middleware: [ModerationOnly::class],
    )]
    public function close(CommandInteraction $interaction): void {}
}
```

<small>From [`tests/Fixtures/SuggestionCommand.php`](../../tests/Fixtures/SuggestionCommand.php) — compiled and exercised by the test suite.</small>

Everybody sees `/suggestion` and everybody may `add`. `close` refuses anyone who is not
moderation, at the moment they use it.

## Writing one

Two shapes are accepted, and which one you use decides how it is built.

A **class name** is built by the container, so the middleware may take whatever it needs
— the configuration holding a guild's roles, a clock, a repository:

```php
use Tempcord\Discord\Interaction\ButtonInteraction;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Interaction\ComponentInteraction;
use Tempcord\Discord\Interaction\ModalSubmitInteraction;
use Tempcord\Interfaces\Middleware;

/**
 * Lets only a member holding the moderation role through.
 *
 * Named by class rather than written inline, so the container builds it and it
 * can take the configuration that knows which role that is.
 */
final readonly class ModerationOnly implements Middleware
{
    public function __construct(
        private string $moderatorRole = '::moderator::',
    ) {}

    public function __invoke(
        CommandInteraction|ButtonInteraction|ComponentInteraction|ModalSubmitInteraction $interaction,
        callable $next,
    ): void {
        $roles = $interaction->interaction->member->roles ?? [];

        if (!in_array($this->moderatorRole, $roles, true)) {
            $interaction->reply('Only moderation may do that.', ephemeral: true);

            return;
        }

        $next($interaction);
    }
}
```

<small>From [`tests/Fixtures/ModerationOnly.php`](../../tests/Fixtures/ModerationOnly.php) — compiled and exercised by the test suite.</small>

An **object written inside the attribute** takes no dependencies, since the container is
not involved in reading attributes. That is the right shape for a check that only needs
its own arguments:

```php
use Tempcord\Attributes\Command;
use Tempcord\Discord\Enums\Permission;
use Tempcord\Middleware\RequiresPermissions;

/**
 * Middleware written as an object inside the attribute, which is the shape a
 * check that only needs its own arguments takes.
 */
#[Command(
    description: 'Guarded inline',
    middleware: [new RequiresPermissions([Permission::MANAGE_GUILD], 'Not for you.')],
)]
final class InlineGuardedCommand
{
    public function __invoke(): void {}
}
```

<small>From [`tests/Fixtures/InlineGuardedCommand.php`](../../tests/Fixtures/InlineGuardedCommand.php) — compiled and exercised by the test suite.</small>

Either way, the interaction arrives as whichever shape answered it — a `CommandInteraction`
for a command, a `ButtonInteraction` for a button, a `ComponentInteraction` for a select
menu, a `ModalSubmitInteraction` for a modal. All four carry the gateway event as
`$interaction->interaction` and can reply, which is all a guard needs, and they are one
union so the same guard can sit on a subcommand and on the button that does the same thing.

## Where it goes

On a command, on a subcommand group, on a subcommand, and on any component attribute:

```php
#[Command(name: 'petition', description: '…', middleware: [/* every handler under it */])]
#[SubcommandGroup(name: 'keys', description: '…', middleware: [/* every subcommand in the group */])]
#[Subcommand(name: 'close', description: '…', middleware: [/* this one */])]
#[Button(id: 'petition.accept.{petition}', middleware: [/* this button */])]
```

What is declared around a handler is flattened into one chain at discovery time, outermost
first: the command's, then the group's, then the subcommand's own. The first middleware
listed sees the interaction first and decides whether anything after it happens at all.

## What it costs

Nothing, until it is reached. Each middleware is built at the moment the chain gets to it,
so a refusal never constructs the ones behind it.

A command's options are resolved **inside** the chain rather than before it. Resolving an
option can cost a REST call — Discord sends the id of a `User` option, not the user — and a
command a middleware is about to refuse should not pay for one.

A middleware that throws is logged and contained, exactly as a handler that throws is. The
handler does not run.

## Deferring

Middleware runs before the handler, so nothing has been deferred yet and a refusal can
answer with an ordinary `reply(..., ephemeral: true)`. Keep the `defer()` inside the
handler, where the slow work is.

## What ships with the framework

`RequiresPermissions` checks the permissions Discord has already computed for the channel
the interaction came from — no roles are read back and nothing is cached. An administrator
holds everything by definition, and an interaction with no member behind it (a direct
message) holds nothing:

```php
use Tempcord\Discord\Enums\Permission;
use Tempcord\Middleware\RequiresPermissions;

#[Subcommand(
    name: 'panel',
    description: 'Publishes the panel.',
    middleware: [new RequiresPermissions([Permission::MANAGE_GUILD], 'Not for you.')],
)]
```

Anything that asks about **roles** rather than permissions belongs in your own bot: role
ids are a particular server's answer to who moderation is, and the framework has no
opinion about them.

## When a class is not a middleware

Naming a class that does not implement `Middleware` fails at discovery, which is start-up.
A guard that turns out not to be a guard should stop the bot booting — not surface the
first time somebody uses the thing it was meant to protect.
