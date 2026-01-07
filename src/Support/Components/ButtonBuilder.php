<?php

declare(strict_types=1);

namespace Tempcord\Support\Components;

use Tempcord\Enums\ButtonStyle;
use Tempcord\Enums\ComponentType;

final class ButtonBuilder
{
    private ButtonStyle $style = ButtonStyle::Primary;
    private ?string $label = null;
    private ?string $customId = null;
    private ?string $url = null;
    private ?string $emoji = null;
    private bool $disabled = false;

    private function __construct() {}

    /**
     * Create a primary (blurple) button
     */
    public static function primary(string $label, string $customId): self
    {
        return (new self())
            ->setStyle(ButtonStyle::Primary)
            ->setLabel($label)
            ->setCustomId($customId);
    }

    /**
     * Create a secondary (grey) button
     */
    public static function secondary(string $label, string $customId): self
    {
        return (new self())
            ->setStyle(ButtonStyle::Secondary)
            ->setLabel($label)
            ->setCustomId($customId);
    }

    /**
     * Create a success (green) button
     */
    public static function success(string $label, string $customId): self
    {
        return (new self())
            ->setStyle(ButtonStyle::Success)
            ->setLabel($label)
            ->setCustomId($customId);
    }

    /**
     * Create a danger (red) button
     */
    public static function danger(string $label, string $customId): self
    {
        return (new self())
            ->setStyle(ButtonStyle::Danger)
            ->setLabel($label)
            ->setCustomId($customId);
    }

    /**
     * Create a link button (navigates to URL)
     */
    public static function link(string $label, string $url): self
    {
        return (new self())
            ->setStyle(ButtonStyle::Link)
            ->setLabel($label)
            ->setUrl($url);
    }

    public function setStyle(ButtonStyle $style): self
    {
        $this->style = $style;
        return $this;
    }

    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function setCustomId(string $customId): self
    {
        $this->customId = $customId;
        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Set emoji (Unicode emoji or custom emoji format)
     * @param string $emoji Unicode emoji or custom emoji ID
     * @param string|null $name Custom emoji name (required for custom emojis)
     * @param bool $animated Whether the emoji is animated
     */
    public function setEmoji(string $emoji, ?string $name = null, bool $animated = false): self
    {
        // If it's a Unicode emoji, just store it
        if ($name === null && !is_numeric($emoji)) {
            $this->emoji = json_encode(['name' => $emoji]);
        } else {
            // Custom emoji
            $this->emoji = json_encode([
                'id' => $emoji,
                'name' => $name,
                'animated' => $animated,
            ]);
        }
        return $this;
    }

    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Build the button component array
     */
    public function build(): array
    {
        $button = [
            'type' => ComponentType::Button->value,
            'style' => $this->style->value,
            'disabled' => $this->disabled,
        ];

        if ($this->label !== null) {
            $button['label'] = $this->label;
        }

        if ($this->style === ButtonStyle::Link) {
            $button['url'] = $this->url;
        } else {
            $button['custom_id'] = $this->customId;
        }

        if ($this->emoji !== null) {
            $button['emoji'] = json_decode($this->emoji, true);
        }

        return $button;
    }
}
