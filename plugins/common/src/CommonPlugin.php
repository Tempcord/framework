<?php

declare(strict_types=1);

namespace Tempcord\Common;

use Ragnarok\Fenrir\Discord;
use Tempcord\Attributes\TempcordPlugin;
use Tempcord\Common\Middleware\MaintenanceModeMiddleware;
use Tempcord\Plugins\AbstractPlugin;
use Tempest\Container\Container;

/**
 * Common plugin providing essential middleware, tasks, and utilities
 * that every Discord bot developer typically needs.
 *
 * Name, version, and description are read from composer.json.
 */
#[TempcordPlugin(priority: 50)]
class CommonPlugin extends AbstractPlugin
{
    public function register(Container $container): void
    {
        // Register plugin configuration if needed
        $container->singleton(CommonConfig::class, fn() => new CommonConfig());
    }

    public function boot(Discord $discord): void
    {
        // Plugin-specific Discord setup can go here
    }

    public function middleware(): array
    {
        // Global middleware applied to all commands
        // Bot developers can override by not including this plugin
        return [
            MaintenanceModeMiddleware::class,
        ];
    }

    public function discoveryNamespaces(): array
    {
        return [
            'Tempcord\\Common\\',
        ];
    }
}
