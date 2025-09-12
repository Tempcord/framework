<?php

namespace Tempcord\Traits;

trait HasAttributes
{
    private array $attributes = [];

    private function setAttribute(string $name, mixed $value): self
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    private function getAttribute(string $name): mixed
    {
        return $this->attributes[$name] ?? null;
    }

    private function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes) && $this->attributes[$name] !== null;
    }

}