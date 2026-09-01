<?php

namespace Tempcord\Discord;

use Tempcord\Discord\Constants\Events;
use Tempcord\Discord\Discord;
use Tempcord\Discord\Extension\Extension;
use Tempcord\Discord\FilteredEventEmitter;
use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Evenement\EventEmitter;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Enums\ComponentKind;
use Tempcord\Registries\ComponentsRegistry;

/**
 * The counterpart of the command extension for message components.
 *
 * Buttons, select menus and modals all arrive as one gateway event, so this
 * narrows it to component interactions, resolves the custom id against the
 * registry, and emits under the key the matched handler is bound by.
 */
final class ComponentExtension extends EventEmitter implements Extension
{
    private FilteredEventEmitter $componentListener;

    public function __construct(
        private readonly ComponentsRegistry $registry,
    ) {}

    public function initialize(Discord $discord): void
    {
        $this->componentListener = new FilteredEventEmitter(
            $discord->gateway->events,
            Events::INTERACTION_CREATE,
            static fn(InteractionCreate $interaction) => ComponentKind::of($interaction) !== null,
        );

        $this->componentListener->on(
            Events::INTERACTION_CREATE,
            function (InteractionCreate $interaction): void {
                $this->dispatch($interaction);
            },
        );

        $this->componentListener->start();
    }

    /**
     * The event name a definition is bound under. The kind is part of it so a
     * button and a modal may share a custom id without colliding.
     */
    public static function keyFor(ComponentDefinition $definition): string
    {
        return $definition->kind->value . ':' . $definition->customId->pattern;
    }

    public function bind(ComponentDefinition $definition, callable $listener): void
    {
        $this->on(self::keyFor($definition), $listener);
    }

    private function dispatch(InteractionCreate $interaction): void
    {
        $kind = ComponentKind::of($interaction);
        $customId = $interaction->data->custom_id ?? null;

        if ($kind === null || $customId === null) {
            return;
        }

        $match = $this->registry->match($kind, $customId);

        /*
         * An unmatched id is normal rather than an error: a component from an
         * older version of the bot, or one another bot owns.
         */
        if ($match === null) {
            return;
        }

        $this->emit(self::keyFor($match->definition), [$interaction, $match->parameters]);
    }
}
