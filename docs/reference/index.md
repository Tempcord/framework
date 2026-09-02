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
- [Scheduled](attributes/scheduled.md) — Declares an invokable class as work the bot does on a timer.

## Autocomplete

- [Autocomplete](autocomplete/autocomplete.md)
- [ArrayAutocomplete](autocomplete/array-autocomplete.md)

## Cache

- [Cache](cache/cache.md) — What the gateway has told the bot about the guilds it is in.

## Components

- [CustomId](components/custom-id.md) — A component's custom id, which may carry {placeholders}.

## Configuration

- [TempcordConfig](configuration/tempcord-config.md)

## Enums

- [DiscordLocale](enums/discord-locale.md) — The locales Discord accepts for name and description localizations.

## Plugins

- [Plugin](plugins/plugin.md) — A package that extends a bot with its own behaviour.

