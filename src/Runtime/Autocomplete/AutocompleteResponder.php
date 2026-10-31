<?php

namespace Tempcord\Runtime\Autocomplete;

use Tempcord\Discord\Enums\InteractionCallbackType;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Compile\Command\Definitions\HandlerDefinition;
use Tempcord\Compile\Command\Definitions\OptionDefinition;
use Tempcord\Runtime\Concerns\ContainsFailures;
use Tempest\Log\Logger;

/**
 * Answers the autocomplete interactions Discord sends while a user is typing.
 *
 * Because handlers are compiled with the path each of their options sits at,
 * finding the focused one is a direct lookup rather than a walk back down the
 * subcommand tree.
 */
final readonly class AutocompleteResponder
{
    use ContainsFailures;

    public function __construct(
        private ChoiceFactory $choices,
        private AutocompleteResolver $resolver,
        private Logger $logger,
    ) {}

    public function respond(HandlerDefinition $handler, CommandInteraction $interaction): void
    {
        $focused = $this->focused($handler, $interaction);

        // Discord sends autocomplete interactions with nothing focused too.
        if ($focused === null) {
            return;
        }

        [$option, $structure] = $focused;
        $autocomplete = $option->autocomplete;

        if ($autocomplete === null) {
            return;
        }

        /*
         * Suggestions may come from a database or an API and so may await. When
         * they fail, Discord simply shows none.
         */
        $this->contained(
            'Autocomplete ' . $autocomplete->label(),
            function () use ($autocomplete, $interaction, $structure): void {
                $interaction->createInteractionResponse(
                    InteractionCallbackBuilder::new()
                        ->setChoices($this->choices->from(
                            $this->resolver->suggest($autocomplete, $interaction, $structure->value),
                        ))
                        ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT),
                );
            },
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
