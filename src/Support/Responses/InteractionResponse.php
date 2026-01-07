<?php

namespace Tempcord\Support\Responses;

use Carbon\Carbon;
use Ragnarok\Fenrir\Enums\InteractionCallbackType;
use Ragnarok\Fenrir\Enums\MessageFlag;
use Ragnarok\Fenrir\Interaction\Helpers\InteractionCallbackBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\ComponentBuilder;
use Ragnarok\Fenrir\Rest\Helpers\Channel\EmbedBuilder;
use Tempcord\CommandInteraction;
use Tempcord\Enums\ButtonStyle;
use Tempcord\Support\Components\ActionRowBuilder;
use Tempcord\Support\Components\ButtonBuilder;
use Tempcord\Support\Components\SelectMenuBuilder;

class InteractionResponse
{
    private ?EmbedBuilder $currentEmbed = null;

    /** @var array<array> Raw component rows */
    private array $componentRows = [];

    /** @var ActionRowBuilder|null Current action row being built */
    private ?ActionRowBuilder $currentRow = null;

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
     * Add Discord components (buttons, select menus, etc.) using Fenrir's ComponentBuilder
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

    // ============ Component Builder Methods ============

    /**
     * Add a button to the response
     *
     * @param string $label Button text
     * @param string $customId Custom ID for the handler
     * @param ButtonStyle $style Button style (Primary, Secondary, Success, Danger)
     * @param bool $disabled Whether the button is disabled
     */
    public function button(
        string $label,
        string $customId,
        ButtonStyle $style = ButtonStyle::Primary,
        bool $disabled = false
    ): self {
        $this->ensureActionRow();

        $button = match ($style) {
            ButtonStyle::Primary => ButtonBuilder::primary($label, $customId),
            ButtonStyle::Secondary => ButtonBuilder::secondary($label, $customId),
            ButtonStyle::Success => ButtonBuilder::success($label, $customId),
            ButtonStyle::Danger => ButtonBuilder::danger($label, $customId),
            ButtonStyle::Link => ButtonBuilder::primary($label, $customId), // Link requires URL, use primary as fallback
        };

        if ($disabled) {
            $button->disabled();
        }

        $this->currentRow->addButton($button);
        return $this;
    }

    /**
     * Add a link button (navigates to URL)
     */
    public function linkButton(string $label, string $url, bool $disabled = false): self
    {
        $this->ensureActionRow();

        $button = ButtonBuilder::link($label, $url);
        if ($disabled) {
            $button->disabled();
        }

        $this->currentRow->addButton($button);
        return $this;
    }

    /**
     * Add a primary (blurple) button
     */
    public function primaryButton(string $label, string $customId, bool $disabled = false): self
    {
        return $this->button($label, $customId, ButtonStyle::Primary, $disabled);
    }

    /**
     * Add a secondary (grey) button
     */
    public function secondaryButton(string $label, string $customId, bool $disabled = false): self
    {
        return $this->button($label, $customId, ButtonStyle::Secondary, $disabled);
    }

    /**
     * Add a success (green) button
     */
    public function successButton(string $label, string $customId, bool $disabled = false): self
    {
        return $this->button($label, $customId, ButtonStyle::Success, $disabled);
    }

    /**
     * Add a danger (red) button
     */
    public function dangerButton(string $label, string $customId, bool $disabled = false): self
    {
        return $this->button($label, $customId, ButtonStyle::Danger, $disabled);
    }

    /**
     * Add a string select menu
     */
    public function selectMenu(SelectMenuBuilder $menu): self
    {
        // Select menus take up entire row
        $this->finalizeCurrentRow();
        $this->componentRows[] = [
            'type' => 1, // ACTION_ROW
            'components' => [$menu->build()],
        ];
        return $this;
    }

    /**
     * Add a pre-built action row
     */
    public function actionRow(ActionRowBuilder $row): self
    {
        $this->finalizeCurrentRow();
        $this->componentRows[] = $row->build();
        return $this;
    }

    /**
     * Start a new action row (for grouping buttons)
     */
    public function newRow(): self
    {
        $this->finalizeCurrentRow();
        $this->currentRow = ActionRowBuilder::create();
        return $this;
    }

    /**
     * Ensure there's an action row to add buttons to
     */
    private function ensureActionRow(): void
    {
        if ($this->currentRow === null) {
            $this->currentRow = ActionRowBuilder::create();
        }

        // Max 5 buttons per row
        if ($this->currentRow->count() >= 5) {
            $this->finalizeCurrentRow();
            $this->currentRow = ActionRowBuilder::create();
        }
    }

    /**
     * Finalize the current action row and add to rows
     */
    private function finalizeCurrentRow(): void
    {
        if ($this->currentRow !== null && !$this->currentRow->isEmpty()) {
            $this->componentRows[] = $this->currentRow->build();
        }
        $this->currentRow = null;
    }

    /**
     * Build and apply all components to the builder
     */
    private function applyComponents(): void
    {
        $this->finalizeCurrentRow();

        if (!empty($this->componentRows)) {
            // Use raw array components since we built them ourselves
            $this->builder->setRawComponents($this->componentRows);
        }
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
        $this->applyComponents();
        $this->interaction->createInteractionResponse($this->builder);
    }
}