<?php

namespace Tempcord\Runtime;

use CyberWolf\Discord\Enums\InteractionCallbackType;
use CyberWolf\Discord\Interaction\CommandInteraction;
use CyberWolf\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use Tempcord\Definitions\HandlerDefinition;
use Tempcord\Definitions\OptionDefinition;
use Tempcord\Discord\InteractionCallbackBuilder;
use Tempest\Log\Logger;
use Throwable;

use function React\Async\async;

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
         * As with a command: suggestions may come from a database or an API and
         * so may await, and one that throws must not travel up into the gateway
         * and take the connection with it. Discord simply shows no suggestions.
         */
        async(function () use ($autocomplete, $interaction, $structure): void {
            try {
                $interaction->createInteractionResponse(
                    InteractionCallbackBuilder::new()
                        ->setChoices($this->choices->from(
                            $this->resolver->suggest($autocomplete, $interaction, $structure->value),
                        ))
                        ->setType(InteractionCallbackType::APPLICATION_COMMAND_AUTOCOMPLETE_RESULT),
                );
            } catch (Throwable $throwable) {
                $this->logger->error(
                    'Autocomplete ' . $autocomplete->label() . ' failed: ' . $throwable->getMessage(),
                    ['exception' => $throwable],
                );
            }
        })();
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
