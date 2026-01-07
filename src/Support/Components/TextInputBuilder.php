<?php

declare(strict_types=1);

namespace Tempcord\Support\Components;

use Tempcord\Enums\ComponentType;
use Tempcord\Enums\TextInputStyle;

final class TextInputBuilder
{
    private TextInputStyle $style = TextInputStyle::Short;
    private string $customId;
    private string $label;
    private ?string $placeholder = null;
    private ?string $value = null;
    private ?int $minLength = null;
    private ?int $maxLength = null;
    private bool $required = true;

    private function __construct(string $customId, string $label)
    {
        $this->customId = $customId;
        $this->label = $label;
    }

    /**
     * Create a short (single-line) text input
     */
    public static function short(string $customId, string $label): self
    {
        return (new self($customId, $label))->setStyle(TextInputStyle::Short);
    }

    /**
     * Create a paragraph (multi-line) text input
     */
    public static function paragraph(string $customId, string $label): self
    {
        return (new self($customId, $label))->setStyle(TextInputStyle::Paragraph);
    }

    public function setStyle(TextInputStyle $style): self
    {
        $this->style = $style;
        return $this;
    }

    public function placeholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function value(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function minLength(int $length): self
    {
        $this->minLength = $length;
        return $this;
    }

    public function maxLength(int $length): self
    {
        $this->maxLength = $length;
        return $this;
    }

    public function required(bool $required = true): self
    {
        $this->required = $required;
        return $this;
    }

    public function optional(): self
    {
        return $this->required(false);
    }

    /**
     * Build the text input component array
     */
    public function build(): array
    {
        $input = [
            'type' => ComponentType::TextInput->value,
            'style' => $this->style->value,
            'custom_id' => $this->customId,
            'label' => $this->label,
            'required' => $this->required,
        ];

        if ($this->placeholder !== null) {
            $input['placeholder'] = $this->placeholder;
        }

        if ($this->value !== null) {
            $input['value'] = $this->value;
        }

        if ($this->minLength !== null) {
            $input['min_length'] = $this->minLength;
        }

        if ($this->maxLength !== null) {
            $input['max_length'] = $this->maxLength;
        }

        return $input;
    }
}
