<?php

namespace Tempcord\Plugins;

/**
 * @mixin IsPlugin
 */
interface Plugin
{
    public string $name {
        get;
    }

    public string $version {
        get;
    }

    public function register(): void;

    public function boot(): void;

}