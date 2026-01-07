<?php

declare(strict_types=1);

namespace Tempcord\Support\Components;

use Tempcord\Enums\SelectMenuType;

final class SelectMenuBuilder
{
    private SelectMenuType $type = SelectMenuType::StringSelect;
    private string $customId;
    private ?string $placeholder = null;
    private int $minValues = 1;
    private int $maxValues = 1;
    private bool $disabled = false;
    private array $options = [];
    private array $channelTypes = [];

    private function __construct(string $customId)
    {
        $this->customId = $customId;
    }

    /**
     * Create a string select menu with predefined options
     */
    public static function string(string $customId): self
    {
        return (new self($customId))->setType(SelectMenuType::StringSelect);
    }

    /**
     * Create a user select menu
     */
    public static function user(string $customId): self
    {
        return (new self($customId))->setType(SelectMenuType::UserSelect);
    }

    /**
     * Create a role select menu
     */
    public static function role(string $customId): self
    {
        return (new self($customId))->setType(SelectMenuType::RoleSelect);
    }

    /**
     * Create a mentionable (user + role) select menu
     */
    public static function mentionable(string $customId): self
    {
        return (new self($customId))->setType(SelectMenuType::MentionableSelect);
    }

    /**
     * Create a channel select menu
     */
    public static function channel(string $customId): self
    {
        return (new self($customId))->setType(SelectMenuType::ChannelSelect);
    }

    public function setType(SelectMenuType $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function minValues(int $min): self
    {
        $this->minValues = $min;
        return $this;
    }

    public function maxValues(int $max): self
    {
        $this->maxValues = $max;
        return $this;
    }

    public function disabled(bool $disabled = true): self
    {
        $this->disabled = $disabled;
        return $this;
    }

    /**
     * Add an option to a string select menu
     */
    public function addOption(
        string $label,
        string $value,
        ?string $description = null,
        ?string $emoji = null,
        bool $default = false
    ): self {
        $option = [
            'label' => $label,
            'value' => $value,
            'default' => $default,
        ];

        if ($description !== null) {
            $option['description'] = $description;
        }

        if ($emoji !== null) {
            // Simple Unicode emoji support
            $option['emoji'] = ['name' => $emoji];
        }

        $this->options[] = $option;
        return $this;
    }

    /**
     * Set allowed channel types for channel select
     * @param array<int> $types Channel type integers
     */
    public function channelTypes(array $types): self
    {
        $this->channelTypes = $types;
        return $this;
    }

    /**
     * Build the select menu component array
     */
    public function build(): array
    {
        $menu = [
            'type' => $this->type->value,
            'custom_id' => $this->customId,
            'min_values' => $this->minValues,
            'max_values' => $this->maxValues,
            'disabled' => $this->disabled,
        ];

        if ($this->placeholder !== null) {
            $menu['placeholder'] = $this->placeholder;
        }

        if ($this->type === SelectMenuType::StringSelect && !empty($this->options)) {
            $menu['options'] = $this->options;
        }

        if ($this->type === SelectMenuType::ChannelSelect && !empty($this->channelTypes)) {
            $menu['channel_types'] = $this->channelTypes;
        }

        return $menu;
    }
}
