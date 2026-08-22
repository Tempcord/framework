<?php

namespace Tempcord\Plugins;

use Tempcord\Tempcord;

/**
 * A package that extends a bot with its own behaviour.
 *
 * Commands and events need nothing from this interface: Tempest discovers any
 * installed package that depends on it, so a plugin's #[Command] and #[Event]
 * classes are picked up the same way an application's own are.
 *
 * What discovery cannot do is give a plugin a moment to act — to register a
 * Fenrir extension, start a timer, or open a connection of its own. That is
 * what this is for. Implementations are constructed by the container, so a
 * plugin may take whatever dependencies it needs.
 */
interface Plugin
{
    /**
     * Called once, after commands and events are bound and before the gateway
     * opens.
     *
     * Anything that must exist before the first event arrives belongs here.
     * Throwing is reported and skips only this plugin, so one broken plugin
     * does not stop a bot from starting.
     */
    public function boot(Tempcord $tempcord): void;
}
