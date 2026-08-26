<?php

namespace Tempcord\Discord;

use CyberWolf\Discord\Constants\Events;
use CyberWolf\Discord\Discord;
use CyberWolf\Discord\Enums\InteractionType;
use CyberWolf\Discord\FilteredEventEmitter;
use CyberWolf\Discord\Gateway\Events\InteractionCreate;
use CyberWolf\Discord\Interaction\CommandInteraction;

/**
 * Fenrir's command extension, widened to also carry autocomplete interactions.
 *
 * Both kinds are emitted under the interaction's full dotted name — "ping",
 * "music.playlist.play" — with autocomplete suffixed, which is exactly the
 * path a compiled HandlerDefinition is keyed by.
 */
final class AllCommandExtension extends \CyberWolf\Discord\Command\AllCommandExtension
{
    public const string AUTOCOMPLETE_SUFFIX = '.autocomplete';

    public function initialize(Discord $discord): void
    {
        $this->commandListener = new FilteredEventEmitter(
            $discord->gateway->events,
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interactionCreate) => isset($interactionCreate->type)
                && in_array($interactionCreate->type, [
                    InteractionType::APPLICATION_COMMAND,
                    InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE,
                ], true)
                && $this->emitInteraction($interactionCreate),
        );

        $this->commandListener->on(
            Events::INTERACTION_CREATE,
            function (InteractionCreate $interaction) use ($discord): void {
                $name = $this->getFullNameByInteraction($interaction);

                if ($interaction->type === InteractionType::APPLICATION_COMMAND_AUTOCOMPLETE) {
                    $name .= self::AUTOCOMPLETE_SUFFIX;
                }

                $this->emit($name, [new CommandInteraction($interaction, $discord)]);
            },
        );

        $this->commandListener->start();
    }

    public function bind(string $command, callable $listener, callable $autocomplete): void
    {
        $this->on($command, $listener);
        $this->on($command . self::AUTOCOMPLETE_SUFFIX, $autocomplete);
    }
}
