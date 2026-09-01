# Autocomplete

Autocomplete suggests values while the user is still typing, before they submit the command.

There are three ways to supply the suggestions, and they differ only in where the code lives.

## From the command itself

The lightest way: a method carrying `#[Autocomplete]`, naming the option it completes. No
extra class, and the command's own dependencies are already to hand.

<!-- include: tests/Fixtures/SelfCompletingCommand.php -->

The method takes whatever it asks for, in whatever order: the `CommandInteraction` where a
parameter is typed as one, and what has been typed so far everywhere else. It may take
neither.

## From a fixed list

`ArrayAutocomplete` filters a list by what has been typed, for the case where the values
never change.

<!-- include: tests/Fixtures/SearchCommand.php -->

## From a class of its own

When the same suggestions are wanted by more than one command, name a class implementing
`Autocomplete`. It is built by the container, so it may take whatever dependencies it needs.

<!-- include: tests/Fixtures/TrackAutocomplete.php -->

Point the option at it by name:

<!-- include: tests/Fixtures/InjectedSearchCommand.php -->

Writing `autocomplete: new TrackAutocomplete(...)` also works, but an object built inside an
attribute cannot be given anything the container holds — which is why naming the class is
usually what you want.

## What you may return

| Return | Result |
| --- | --- |
| A list | Each value is shown as its own label |
| A map | Keys are the labels users see, values are what your handler receives |
| A single scalar | One suggestion |
| `ApplicationCommandOptionChoice` objects | Passed through untouched |

Discord accepts at most 25 choices and rejects a response carrying more, so anything beyond
that is dropped before sending.

## Timing and failure

Discord expects an autocomplete response within about three seconds. Keep the work small,
and cache rather than querying on every keystroke.

Suggestions run inside a fiber, so they may await the REST API or a database. One that
throws is logged and contained: the user simply sees no suggestions, and the gateway carries
on.
