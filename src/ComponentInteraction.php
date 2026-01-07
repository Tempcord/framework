<?php

declare(strict_types=1);

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\Support\Components\ModalBuilder;
use Tempcord\Support\Responses\InteractionResponse;

/**
 * Wraps a component interaction (button click or select menu selection)
 */
class ComponentInteraction
{
    public function __construct(
        public readonly InteractionCreate $interaction,
        private readonly Discord $discord,
        private readonly TempcordConfig $config
    ) {}

    /**
     * Get the custom_id of the component
     */
    public function getCustomId(): string
    {
        return $this->interaction->data->custom_id ?? '';
    }

    /**
     * Get selected values (for select menus)
     * @return array<string>
     */
    public function getValues(): array
    {
        return $this->interaction->data->values ?? [];
    }

    /**
     * Get the first selected value
     */
    public function getValue(): ?string
    {
        return $this->getValues()[0] ?? null;
    }

    /**
     * Get the component type
     */
    public function getComponentType(): int
    {
        return $this->interaction->data->component_type ?? 0;
    }

    /**
     * Get the user who triggered the interaction
     */
    public function getUser(): object
    {
        return $this->interaction->member?->user ?? $this->interaction->user;
    }

    /**
     * Get the user ID
     */
    public function getUserId(): string
    {
        return $this->getUser()->id;
    }

    /**
     * Get the guild ID (null if in DM)
     */
    public function getGuildId(): ?string
    {
        return $this->interaction->guild_id;
    }

    /**
     * Get the channel ID
     */
    public function getChannelId(): string
    {
        return $this->interaction->channel_id;
    }

    /**
     * Get the message that contains the component
     */
    public function getMessage(): ?object
    {
        return $this->interaction->message;
    }

    /**
     * Get the message ID
     */
    public function getMessageId(): ?string
    {
        return $this->interaction->message?->id;
    }

    /**
     * Create a response builder
     */
    public function respond(InteractionCallbackBuilder $with = new InteractionCallbackBuilder()): InteractionResponse
    {
        $response = new InteractionResponse(
            interaction: $this->asCommandInteraction(),
            builder: $with
        );

        $this->config->branding?->decorate($response, $this->asCommandInteraction());

        return $response;
    }

    /**
     * Update the original message (replaces content and components)
     */
    public function update(): InteractionResponse
    {
        $builder = new InteractionCallbackBuilder();
        $builder->setType(InteractionCallbackType::UPDATE_MESSAGE);

        return new InteractionResponse(
            interaction: $this->asCommandInteraction(),
            builder: $builder
        );
    }

    /**
     * Defer the response (shows "thinking..." indicator)
     */
    public function defer(bool $ephemeral = false): void
    {
        $builder = new InteractionCallbackBuilder();
        $builder->setType(InteractionCallbackType::DEFERRED_CHANNEL_MESSAGE_WITH_SOURCE);

        if ($ephemeral) {
            $builder->setFlags(64); // EPHEMERAL flag
        }

        $this->createInteractionResponse($builder);
    }

    /**
     * Defer and update the message (no visible loading state)
     */
    public function deferUpdate(): void
    {
        $builder = new InteractionCallbackBuilder();
        $builder->setType(InteractionCallbackType::DEFERRED_UPDATE_MESSAGE);

        $this->createInteractionResponse($builder);
    }

    /**
     * Open a modal dialog
     */
    public function openModal(ModalBuilder $modal): void
    {
        $builder = new InteractionCallbackBuilder();
        $builder->setType(InteractionCallbackType::MODAL);

        // Set modal data
        $modalData = $modal->build();
        $builder->setCustomId($modalData['custom_id']);
        $builder->setTitle($modalData['title']);
        $builder->setComponents($modalData['components']);

        $this->createInteractionResponse($builder);
    }

    /**
     * Send the interaction response
     */
    public function createInteractionResponse(InteractionCallbackBuilder $builder): void
    {
        $this->discord->rest->webhook->createInteractionResponse(
            $this->interaction->id,
            $this->interaction->token,
            $builder
        );
    }

    /**
     * Convert to CommandInteraction for compatibility
     */
    private function asCommandInteraction(): CommandInteraction
    {
        return new CommandInteraction($this->interaction, $this->discord, $this->config);
    }
}
