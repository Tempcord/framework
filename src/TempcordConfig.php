<?php

namespace Tempcord;

use CyberWolf\Discord\Bitwise\Bitwise;

final readonly class TempcordConfig
{
    /**
     * @param bool $cache whether to keep the guilds, channels, roles, members
     *        and voice states the gateway reports, so a handler can read them
     *        without an HTTP round trip. Only ever holds what the configured
     *        intents already deliver.
     * @param bool $chunkMembers whether to ask the gateway for the members a
     *        large guild leaves out of GUILD_CREATE. Without it the member
     *        cache is only ever a slice of a busy server. Needs the
     *        GUILD_MEMBERS intent, and is ignored without it.
     */
    public function __construct(
        public string  $token,
        public Bitwise $intents,
        public bool    $cache = true,
        public bool    $chunkMembers = true,
    )
    {
    }
}