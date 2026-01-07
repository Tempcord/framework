<?php

declare(strict_types=1);

namespace Tempcord\Plugins;

use Ragnarok\Fenrir\Discord;
use Tempest\Container\Container;

/**
 * Base class for Tempcord plugins with sensible defaults.
 */
abstract class AbstractPlugin implements Plugin
{
    public function register(Container $container): void
    {
        // Override to register services
    }

    public function boot(Discord $discord): void
    {
        // Override to set up Discord-specific features
    }

    public function middleware(): array
    {
        return [];
    }

    public function discoveryNamespaces(): array
    {
        return [];
    }
}
