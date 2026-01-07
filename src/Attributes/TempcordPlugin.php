<?php

declare(strict_types=1);

namespace Tempcord\Attributes;

use Attribute;
use Tempcord\Plugins\Plugin;
use Tempest\Reflection\ClassReflector;

/**
 * Marks a class as a Tempcord plugin for auto-discovery.
 *
 * The class must implement the Plugin interface.
 *
 * @example
 * ```php
 * #[TempcordPlugin]
 * class CommonPlugin extends AbstractPlugin
 * {
 *     // name, version, description read from composer.json
 * }
 * ```
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class TempcordPlugin
{
    public ClassReflector $reflector;

    /**
     * @param bool $enabled Whether this plugin is enabled. Default: true
     */
    public function __construct(
        public bool $enabled = true,
    ) {}

    public function withReflector(ClassReflector $reflector): self
    {
        $clone = clone $this;
        $clone->reflector = $reflector;

        return $clone;
    }

    /**
     * Get the plugin class name.
     */
    public function getPluginClass(): string
    {
        return $this->reflector->getName();
    }

    /**
     * Check if the class implements the Plugin interface.
     */
    public function isValidPlugin(): bool
    {
        return $this->reflector->implementsInterface(Plugin::class);
    }
}
