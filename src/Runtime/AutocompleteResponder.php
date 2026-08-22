<?php

namespace Tempcord\Runtime;

use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Definitions\OptionDefinition;
use Tempcord\Discord\InteractionCallbackBuilder;

/**
 * Answers the autocomplete interactions Discord sends while a user is typing.
 *
 * Because handlers are compiled with the path each of their options sits at,
 * finding the focused one is a direct lookup rather than a walk back down the
 * subcommand tree.
 */
final readonly class AutocompleteResponder
{
    public function __construct(
        private ChoiceFactory $choices,
    ) {}

    public function respond(HandlerDefinition $handler, CommandInteraction $interaction): void
    {
        $focused = $this->focused($handler, $interaction);

        // Discord sends autocomplete interactions with nothing focused too.
        if ($focused === null) {
            return;
        }

        [$option, $structure] = $focused;

        if ($option->autocomplete === null) {
            return;
        }

        $interaction->createInteractionResponse(
            InteractionCallbackBuilder::new()
                ->setChoices($this->choices->from(
                    $option->autocomplete->handle($interaction, $structure->value),
                ))
                ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT),
        );
    }

    /**
     * @return array{OptionDefinition, ApplicationCommandInteractionDataOptionStructure}|null
     */
    private function focused(HandlerDefinition $handler, CommandInteraction $interaction): ?array
    {
        foreach ($handler->options as $option) {
            $structure = $interaction->getOption($handler->pathTo($option));

            if ($structure !== null && ($structure->focused ?? false) === true) {
                return [$option, $structure];
            }
        }

        return null;
    }
}
