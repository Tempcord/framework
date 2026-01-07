<?php

declare(strict_types=1);

namespace Tempcord\Support\Components;

use Tempcord\Enums\ComponentType;

final class ModalBuilder
{
    private string $customId;
    private string $title;
    private array $components = [];

    private function __construct(string $customId, string $title)
    {
        $this->customId = $customId;
        $this->title = $title;
    }

    /**
     * Create a new modal
     */
    public static function create(string $customId, string $title): self
    {
        return new self($customId, $title);
    }

    /**
     * Add a short text input
     */
    public function shortInput(
        string $customId,
        string $label,
        ?string $placeholder = null,
        ?string $value = null,
        bool $required = true,
        ?int $minLength = null,
        ?int $maxLength = null
    ): self {
        $input = TextInputBuilder::short($customId, $label)
            ->required($required);

        if ($placeholder !== null) {
            $input->placeholder($placeholder);
        }

        if ($value !== null) {
            $input->value($value);
        }

        if ($minLength !== null) {
            $input->minLength($minLength);
        }

        if ($maxLength !== null) {
            $input->maxLength($maxLength);
        }

        return $this->addInput($input);
    }

    /**
     * Add a paragraph text input
     */
    public function paragraphInput(
        string $customId,
        string $label,
        ?string $placeholder = null,
        ?string $value = null,
        bool $required = true,
        ?int $minLength = null,
        ?int $maxLength = null
    ): self {
        $input = TextInputBuilder::paragraph($customId, $label)
            ->required($required);

        if ($placeholder !== null) {
            $input->placeholder($placeholder);
        }

        if ($value !== null) {
            $input->value($value);
        }

        if ($minLength !== null) {
            $input->minLength($minLength);
        }

        if ($maxLength !== null) {
            $input->maxLength($maxLength);
        }

        return $this->addInput($input);
    }

    /**
     * Add a text input builder
     */
    public function addInput(TextInputBuilder $input): self
    {
        // Modal inputs must be wrapped in action rows
        $this->components[] = [
            'type' => ComponentType::ActionRow->value,
            'components' => [$input->build()],
        ];
        return $this;
    }

    /**
     * Build the modal data
     */
    public function build(): array
    {
        return [
            'custom_id' => $this->customId,
            'title' => $this->title,
            'components' => $this->components,
        ];
    }

    public function getCustomId(): string
    {
        return $this->customId;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
