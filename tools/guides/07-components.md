# Components

Buttons, select menus and modals are answered the same way commands are: an attribute
names the custom id, and the framework routes the interaction to your method.

## Buttons

An invokable class carrying `#[Button]` handles one button. With no id given, the class
name carries it — `ReportButton` answers `report`.

<!-- include: tests/Fixtures/ReportButton.php -->

Ask for the interaction in whichever shape you need it: `ButtonInteraction` to reply,
`ComponentInteraction` to also read the custom id and component type, or the raw
`InteractionCreate` payload.

## Ids that carry state

Discord hands back exactly the string a component was built with, so anything the handler
needs to know — which team, which petition — has to travel inside the id. Name those
segments with `{placeholders}` and they arrive as parameters, cast to the parameter's type.

The attribute is repeatable and may sit on a method, so a family of related buttons lives
on one class:

<!-- include: tests/Fixtures/TournamentButtons.php -->

To build a matching id when you create the button, compile the same pattern:

```php
use Tempcord\Runtime\CustomId;

CustomId::compile('tournament.accept.{team}')->build(['team' => $team->id]);
```

A literal id always wins over a pattern that would also match it, so a single id can be
carved out of a family without worrying about declaration order.

## Select menus

`#[SelectMenu]` covers every select type — string, user, role, mentionable and channel —
since Discord reports them all alike. A `$values` parameter receives everything the user
picked, and `$value` the first pick, which is null when the menu was cleared.

<!-- include: tests/Fixtures/PunishmentSelectMenu.php -->

## Modals

`#[ModalSubmit]` answers a submitted modal. Beyond placeholders from the id, a parameter
named after a field's custom id receives what was typed into it. A field the user left out
is not supplied at all, so the parameter's own default applies.

<!-- include: tests/Fixtures/BanModal.php -->

## Unmatched ids

An interaction whose custom id nothing answers is ignored rather than reported: it is
normally a component left over from an older version of the bot, or one another bot owns.

A handler that throws is logged and contained, exactly as a command or a listener is.
