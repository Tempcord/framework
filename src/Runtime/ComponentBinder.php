<?php

namespace Tempcord\Runtime;

use Tempcord\Discord\Gateway\Events\InteractionCreate;
use Tempcord\Definitions\ComponentDefinition;
use Tempcord\Discord\ComponentExtension;

/**
 * Binds each compiled component handler to the custom id it answers.
 */
final readonly class ComponentBinder
{
    public function __construct(
        public ComponentExtension $extension,
        private ComponentDispatcher $dispatcher,
    ) {}

    /**
     * @param list<ComponentDefinition> $components
     *
     * @return list<Outcome>
     */
    public function bindAll(array $components): array
    {
        $outcomes = [];

        foreach ($components as $component) {
            $this->extension->bind(
                $component,
                function (InteractionCreate $interaction, array $parameters) use ($component): void {
                    $this->dispatcher->dispatch($component, $interaction, $parameters);
                },
            );

            $outcomes[] = Outcome::success('Listening ' . $component->label() . '.');
        }

        return $outcomes;
    }
}
