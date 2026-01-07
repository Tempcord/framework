<?php

declare(strict_types=1);

namespace Tempcord;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Tempcord\Support\Responses\InteractionResponse;

/**
 * Wraps a modal submission interaction
 */
class ModalInteraction
{
    private array $fields = [];

    public function __construct(
        public readonly InteractionCreate $interaction,
        private readonly Discord $discord,
        private readonly TempcordConfig $config
    ) {
        $this->parseFields();
    }

    /**
     * Parse modal fields from the interaction data
     */
    private function parseFields(): void
    {
        $components = $this->interaction->data->components ?? [];

        foreach ($components as $actionRow) {
            foreach ($actionRow->components ?? [] as $component) {
                if (isset($component->custom_id, $component->value)) {
                    $this->fields[$component->custom_id] = $component->value;
                }
            }
        }
    }

    /**
     * Get the custom_id of the modal
     */
    public function getCustomId(): string
    {
        return $this->interaction->data->custom_id ?? '';
    }

    /**
     * Get all field values
     * @return array<string, string>
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * Get a specific field value by custom_id
     */
    public function getField(string $customId, ?string $default = null): ?string
    {
        return $this->fields[$customId] ?? $default;
    }

    /**
     * Check if a field exists and is not empty
     */
    public function hasField(string $customId): bool
    {
        return isset($this->fields[$customId]) && $this->fields[$customId] !== '';
    }

    /**
     * Get the user who submitted the modal
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
