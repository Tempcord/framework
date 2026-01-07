<?php

declare(strict_types=1);

namespace Tempcord\Registries;

use Ragnarok\Fenrir\Constants\Events;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\Extension\Extension;
use Ragnarok\Fenrir\FilteredEventEmitter;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Tempcord\Attributes\Components\Button;
use Tempcord\Attributes\Components\Modal;
use Tempcord\Attributes\Components\SelectMenu;
use Tempcord\ComponentInteraction;
use Tempcord\Middleware\CommandMiddleware;
use Tempcord\Middleware\MiddlewarePipeline;
use Tempcord\ModalInteraction;
use Tempcord\TempcordConfig;
use Tempest\Container\Container;
use Tempest\Container\Singleton;
use Tempest\Log\Logger;

use function Tempest\get;
use function Tempest\invoke;

#[Singleton]
class ComponentsRegistry implements Extension
{
    private Discord $discord;

    /** @var array<Button> */
    private array $buttons = [];

    /** @var array<SelectMenu> */
    private array $selectMenus = [];

    /** @var array<Modal> */
    private array $modals = [];

    public function __construct(
        private readonly TempcordConfig $config,
        private readonly Container $container
    ) {}

    public function registerButton(Button $button): void
    {
        $this->buttons[] = $button;
    }

    public function registerSelectMenu(SelectMenu $selectMenu): void
    {
        $this->selectMenus[] = $selectMenu;
    }

    public function registerModal(Modal $modal): void
    {
        $this->modals[] = $modal;
    }

    /** @return array<Button> */
    public function getButtons(): array
    {
        return $this->buttons;
    }

    /** @return array<SelectMenu> */
    public function getSelectMenus(): array
    {
        return $this->selectMenus;
    }

    /** @return array<Modal> */
    public function getModals(): array
    {
        return $this->modals;
    }

    public function initialize(Discord $discord): void
    {
        $this->discord = $discord;

        // Listen for MESSAGE_COMPONENT interactions (buttons, select menus)
        $componentListener = new FilteredEventEmitter(
            $discord->gateway->events,
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interaction) => $interaction?->type === InteractionType::MESSAGE_COMPONENT
        );

        $componentListener->on(
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interaction) => $this->handleComponentInteraction($interaction)
        );

        $componentListener->start();

        // Listen for MODAL_SUBMIT interactions
        $modalListener = new FilteredEventEmitter(
            $discord->gateway->events,
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interaction) => $interaction?->type === InteractionType::MODAL_SUBMIT
        );

        $modalListener->on(
            Events::INTERACTION_CREATE,
            fn(InteractionCreate $interaction) => $this->handleModalInteraction($interaction)
        );

        $modalListener->start();

        $logger = get(Logger::class);
        $logger->info('Components registry initialized', [
            'buttons' => count($this->buttons),
            'selectMenus' => count($this->selectMenus),
            'modals' => count($this->modals),
        ]);
    }

    private function handleComponentInteraction(InteractionCreate $interactionCreate): void
    {
        $customId = $interactionCreate->data->custom_id ?? '';
        $componentType = $interactionCreate->data->component_type ?? 0;
        $logger = get(Logger::class);

        $logger->debug('Component interaction received', [
            'custom_id' => $customId,
            'component_type' => $componentType,
        ]);

        $interaction = new ComponentInteraction(
            $interactionCreate,
            $this->discord,
            $this->config
        );

        // Try to find a matching button handler
        if ($componentType === 2) { // Button
            foreach ($this->buttons as $button) {
                if ($button->matches($customId)) {
                    $this->invokeHandler($button, $interaction, $button->extractParams($customId));
                    return;
                }
            }
        }

        // Try to find a matching select menu handler
        if ($componentType >= 3 && $componentType <= 8) { // Select menus
            foreach ($this->selectMenus as $selectMenu) {
                if ($selectMenu->matches($customId)) {
                    $this->invokeHandler($selectMenu, $interaction, $selectMenu->extractParams($customId));
                    return;
                }
            }
        }

        $logger->warning('No handler found for component', [
            'custom_id' => $customId,
            'component_type' => $componentType,
        ]);
    }

    private function handleModalInteraction(InteractionCreate $interactionCreate): void
    {
        $customId = $interactionCreate->data->custom_id ?? '';
        $logger = get(Logger::class);

        $logger->debug('Modal submission received', [
            'custom_id' => $customId,
        ]);

        $interaction = new ModalInteraction(
            $interactionCreate,
            $this->discord,
            $this->config
        );

        foreach ($this->modals as $modal) {
            if ($modal->matches($customId)) {
                $this->invokeModalHandler($modal, $interaction, $modal->extractParams($customId));
                return;
            }
        }

        $logger->warning('No handler found for modal', [
            'custom_id' => $customId,
        ]);
    }

    private function invokeHandler(
        Button|SelectMenu $handler,
        ComponentInteraction $interaction,
        array $params
    ): void {
        $logger = get(Logger::class);

        try {
            $executeHandler = function (ComponentInteraction $int) use ($handler, $params): mixed {
                $instance = get($handler->reflector->getDeclaringClass()->getName());

                $args = [
                    'interaction' => $int,
                    'params' => $params,
                ];

                // For select menus, also pass values
                if ($handler instanceof SelectMenu) {
                    $args['values'] = $int->getValues();
                    $args['value'] = $int->getValue();
                }

                return invoke($handler->reflector, $instance, ...$args);
            };

            // Build middleware stack
            $middleware = array_merge(
                $this->config->globalMiddleware,
                $handler->middleware
            );

            if (!empty($middleware)) {
                $pipeline = new MiddlewarePipeline($this->container);

                // We need to adapt the middleware for ComponentInteraction
                // For now, skip middleware that requires CommandInteraction
                $response = $executeHandler($interaction);
            } else {
                $response = $executeHandler($interaction);
            }

            // Auto-send if response is InteractionResponse
            if ($response instanceof \Tempcord\Support\Responses\InteractionResponse) {
                $response->send();
            }
        } catch (\Throwable $e) {
            $logger->error('Component handler failed', [
                'custom_id' => $handler->customId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Try to send error response
            try {
                $interaction->respond()
                    ->error()
                    ->content('An error occurred while processing your request.')
                    ->ephemeral()
                    ->send();
            } catch (\Throwable) {
                // Ignore if we can't respond
            }
        }
    }

    private function invokeModalHandler(
        Modal $handler,
        ModalInteraction $interaction,
        array $params
    ): void {
        $logger = get(Logger::class);

        try {
            $instance = get($handler->reflector->getDeclaringClass()->getName());

            $args = [
                'interaction' => $interaction,
                'params' => $params,
                'fields' => $interaction->getFields(),
            ];

            // Add individual fields as named parameters
            foreach ($interaction->getFields() as $fieldId => $value) {
                $args[$fieldId] = $value;
            }

            $response = invoke($handler->reflector, $instance, ...$args);

            if ($response instanceof \Tempcord\Support\Responses\InteractionResponse) {
                $response->send();
            }
        } catch (\Throwable $e) {
            $logger->error('Modal handler failed', [
                'custom_id' => $handler->customId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            try {
                $interaction->respond()
                    ->error()
                    ->content('An error occurred while processing your submission.')
                    ->ephemeral()
                    ->send();
            } catch (\Throwable) {
                // Ignore if we can't respond
            }
        }
    }
}
