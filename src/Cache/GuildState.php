<?php

namespace Tempcord\Cache;

use Tempcord\Discord\Parts\Channel;
use Tempcord\Discord\Parts\Guild;
use Tempcord\Discord\Parts\GuildMember;
use Tempcord\Discord\Parts\Role;
use Tempcord\Discord\Parts\VoiceState;

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
