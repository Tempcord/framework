<?php

namespace Tempcord\Traits;

use BackedEnum;

trait HasAttributes
{
    private array $attributes = [];

    private function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    private function getAttribute(string $name, mixed $default = null): mixed
    {
        return $this->attributes[$name] ?? $default;
    }

    private function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes) && $this->attributes[$name] !== null;
    }

    private function rememberAttribute(string $name, mixed $value): mixed
    {
        if (!$this->hasAttribute($name)) {
            $this->setAttribute($name, is_callable($value) ? $value() : $value);
        }

        $value = $this->getAttribute($name);

        return $value instanceof BackedEnum ? $value->value : $value;
    }

}