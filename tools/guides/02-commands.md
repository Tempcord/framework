# Commands and options

## Subcommands

A class whose methods carry `#[Subcommand]` exposes each one as a subcommand. There is no
`__invoke` in this case — the methods are the handlers.

<!-- include: tests/Fixtures/ModerationCommand.php -->

That registers `/moderation kick`.

## Grouping subcommands

Adding `#[SubcommandGroup]` to the class nests everything one level deeper, which is how
Discord models `/command group subcommand`.

<!-- include: tests/Fixtures/MusicCommand.php -->

That registers `/music playlist play` and `/music playlist stop`. A method without
`#[Subcommand]` is ignored, so helpers can sit alongside handlers.

## Option types

The Discord option type is read from the parameter's PHP type:

| PHP type | Discord option type |
| --- | --- |
| `string` | STRING |
| `int` | INTEGER |
| `float` | NUMBER |
| `bool` | BOOLEAN |
| `Tempcord\Discord\Parts\User` | USER |
| `Tempcord\Discord\Parts\Channel` | CHANNEL |
| `Tempcord\Discord\Parts\Role` | ROLE |

A parameter typed `User`, `Channel` or `Role` is fetched from Discord before your handler
runs, so you receive the entity rather than an id.

An unsupported type fails at boot rather than on the first interaction that reaches it.

## Receiving the interaction

A parameter named `$interaction` receives the `CommandInteraction`, which is how you reply.
It needs no attribute.

## Constraining what users may send

Discord can enforce constraints before your handler is ever called, which is cheaper and
gives the user immediate feedback.

<!-- include: tests/Fixtures/ConstrainedCommand.php -->

Choices accept either shape. A map uses its keys as the labels users see; a list has no
labels of its own, so each value stands in as its own.

## Restricting who may use a command

`permissions` sets the default a member needs. An empty list leaves the command unrestricted.

<!-- include: tests/Fixtures/RestrictedCommand.php -->

Server administrators can override this per guild, so treat it as a default rather than a
security boundary.

## Scoping a command to one guild

`guildId` registers a command in a single guild instead of globally. Guild commands appear
immediately, which makes them useful while developing; global commands can take up to an
hour to propagate.

<!-- include: tests/Fixtures/GuildAlphaCommand.php -->

## Context menus

A command can also be something people reach for by right-clicking a user or a
message, rather than by typing. Say so with `type`, and give it a name — Discord
shows that name verbatim, so it is not derived from the class:

<!-- include: tests/Fixtures/ProfileContextMenu.php -->

There are no options on a context menu. What it was used on arrives beside them,
and the parameter's own type says which shape of it you want: `User` or
`GuildMember` for a user menu, `Message` for a message menu.

A member target is given back its user, which Discord leaves empty because it
sends the two halves separately.

A context menu needs no description, and giving one is refused — Discord has
nowhere to show it.
