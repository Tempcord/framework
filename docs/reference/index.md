<!-- Generated from the source by `composer docs`. Do not edit by hand. -->

# API reference

## Attributes

- [Command](attributes/command.md) — Declares a class as a Discord application command.
- [SubcommandGroup](attributes/subcommand-group.md) — Groups every subcommand its class declares under one more level of nesting.
- [Subcommand](attributes/subcommand.md) — Declares a public method as a subcommand of the command its class declares.
- [Option](attributes/option.md) — Declares a method parameter as a user-supplied command option.
- [Event](attributes/event.md) — Declares an invokable class as a listener for a Discord gateway event.
- [Autocomplete](attributes/autocomplete.md) — Declares a method as the source of suggestions for one of the command's options.
- [Button](attributes/button.md) — Declares a class or method as the handler for a button press.
- [SelectMenu](attributes/select-menu.md) — Declares a class or method as the handler for a select menu choice.
- [ModalSubmit](attributes/modal-submit.md) — Declares a class or method as the handler for a submitted modal.

## Options

- [Choosable](options/choosable.md) — An enum that says how each of its cases should read in Discord.

## Autocomplete

- [Autocomplete](autocomplete/autocomplete.md)
- [ArrayAutocomplete](autocomplete/array-autocomplete.md)

## Cache

- [Cache](cache/cache.md) — What the gateway has told the bot about the guilds it is in.

## Components

- [CustomId](components/custom-id.md) — A component's custom id, which may carry {placeholders}.

## Configuration

- [TempcordConfig](configuration/tempcord-config.md)

## Messaging

- [DirectMessage](messaging/direct-message.md) — Writes to a member privately, on a best-effort basis.

## Enums

- [DiscordLocale](enums/discord-locale.md) — The locales Discord accepts for name and description localizations.

## Plugins

- [Plugin](plugins/plugin.md) — A package that extends a bot with its own behaviour.

## Middleware

- [Middleware](middleware/middleware.md) — Something that runs before a handler, and may decide it never runs.
- [RequiresPermissions](middleware/requires-permissions.md) — Refuses anyone whose permissions in the channel fall short.

