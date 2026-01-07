<?php

namespace Tempcord\Support\Responses;

use Carbon\Carbon;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Enums\MessageFlag;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\ComponentBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\EmbedBuilder;
use Tempcord\CommandInteraction;

class InteractionResponse
{
    private ?EmbedBuilder $currentEmbed = null;

    public function __construct(
        private readonly CommandInteraction $interaction,
        private InteractionCallbackBuilder $builder = new InteractionCallbackBuilder
    )
    {
        $this->builder->setType(InteractionCallbackType::CHANNEL_MESSAGE_WITH_SOURCE);
    }

    // ============ Convenience Methods (Shortcuts) ============

    public function success(): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setColor(0x00FF00); // Green
        return $this;
    }

    public function error(): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setColor(0xFF0000); // Red
        return $this;
    }

    public function warning(): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setColor(0xFFA500); // Orange
        $this->currentEmbed->setTitle("Warning");
        return $this;
    }

    // ============ Content Methods ============

    /**
     * Set content. If embed exists, sets description; otherwise sets message content.
     */
    public function content(string $content): self
    {
        if ($this->currentEmbed !== null) {
            $this->currentEmbed->setDescription($content);
        } else {
            $this->builder->setContent($content);
        }
        return $this;
    }

    /**
     * Set plain text content (no embed)
     */
    public function text(string $content): self
    {
        $this->builder->setContent($content);
        return $this;
    }

    // ============ Embed Properties ============

    public function title(string $title): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setTitle($title);
        return $this;
    }

    public function description(string $description): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setDescription($description);
        return $this;
    }

    public function color(int $color): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setColor($color);
        return $this;
    }

    public function url(string $url): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setUrl($url);
        return $this;
    }

    public function timestamp(?Carbon $timestamp = null): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setTimestamp($timestamp ?? Carbon::now());
        return $this;
    }

    // ============ Embed Components ============

    public function footer(string $text, ?string $iconUrl = null): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setFooter($text, $iconUrl);
        return $this;
    }

    public function image(string $url): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setImage($url);
        return $this;
    }

    public function thumbnail(string $url): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setThumbnail($url);
        return $this;
    }

    public function author(string $name, ?string $url = null, ?string $iconUrl = null): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->setAuthor($name, $url, $iconUrl);
        return $this;
    }

    public function field(string $name, string $value, bool $inline = false): self
    {
        $this->ensureEmbed();
        $this->currentEmbed->addField($name, $value, $inline);
        return $this;
    }

    // ============ Message Properties ============

    /**
     * Make message ephemeral (only visible to the user who triggered the interaction)
     */
    public function ephemeral(bool $ephemeral = true): self
    {
        if ($ephemeral) {
            $this->builder->setFlags(MessageFlag::EPHEMERAL->value);
        } else {
            $this->builder->setFlags(0);
        }
        return $this;
    }

    /**
     * Add Discord components (buttons, select menus, etc.)
     */
    public function components(ComponentBuilder $components): self
    {
        $this->builder->setComponents($components);
        return $this;
    }

    /**
     * Enable TTS (text-to-speech)
     */
    public function tts(bool $tts = true): self
    {
        $this->builder->setTts($tts);
        return $this;
    }

    // ============ Legacy Decorator Support ============

    /**
     * Apply a decorator
     */
    public function decorate(ResponseDecorator $with): self
    {
        $with->decorate($this, $this->interaction);
        return $this;
    }

    // ============ Internal Helpers ============

    /**
     * Ensure an embed exists for modification
     */
    private function ensureEmbed(): void
    {
        if ($this->currentEmbed === null) {
            $this->currentEmbed = new EmbedBuilder();
            $this->builder->addEmbed($this->currentEmbed);
        }
    }

    /**
     * Get the current embed (for decorators)
     */
    public function getCurrentEmbed(): EmbedBuilder
    {
        $this->ensureEmbed();
        return $this->currentEmbed;
    }

    /**
     * Get the builder (for decorators)
     */
    public function getBuilder(): InteractionCallbackBuilder
    {
        return $this->builder;
    }

    // ============ Send ============

    public function send(): void
    {
        $this->interaction->createInteractionResponse($this->builder);
    }
}