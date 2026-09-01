<?php

namespace Tempcord\Cache;

use CyberWolf\Discord\Parts\Channel;
use CyberWolf\Discord\Parts\Guild;
use CyberWolf\Discord\Parts\GuildMember;
use CyberWolf\Discord\Parts\Role;
use CyberWolf\Discord\Parts\VoiceState;

/**
 * One guild's cached state.
 *
 * Internal to the cache: everything here is reachable through Cache, which owns
 * the cross-guild indexes that make a lookup by channel id alone possible.
 *
 * @internal
 */
final class GuildState
{
    /** @var array<string, Channel> */
    public array $channels = [];

    /** @var array<string, Role> */
    public array $roles = [];

    /** @var array<string, GuildMember> */
    public array $members = [];

    /** @var array<string, VoiceState> keyed by user id */
    public array $voiceStates = [];

    public function __construct(
        public Guild $guild,
    ) {}
}
