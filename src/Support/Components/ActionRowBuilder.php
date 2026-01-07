<?php

declare(strict_types=1);

namespace Tempcord\Support\Components;

use Tempcord\Enums\ComponentType;

final class ActionRowBuilder
{
    private array $components = [];

    private function __construct() {}

    /**
     * Create a new action row
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a button to this row
     */
    public function addButton(ButtonBuilder $button): self
    {
        $this->components[] = $button->build();
        return $this;
    }

    /**
     * Add a select menu to this row (select menus take up entire row)
     */
    public function addSelectMenu(SelectMenuBuilder $menu): self
    {
        $this->components[] = $menu->build();
        return $this;
    }

    /**
     * Add multiple buttons at once
     * @param ButtonBuilder[] $buttons
     */
    public function addButtons(array $buttons): self
    {
        foreach ($buttons as $button) {
            $this->addButton($button);
        }
        return $this;
    }

    /**
     * Build the action row
     */
    public function build(): array
    {
        return [
            'type' => ComponentType::ActionRow->value,
            'components' => $this->components,
        ];
    }

    /**
     * Check if the row is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->components);
    }

    /**
     * Get number of components in this row
     */
    public function count(): int
    {
        return count($this->components);
    }
}
