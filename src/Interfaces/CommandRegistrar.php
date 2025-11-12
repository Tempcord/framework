<?php

namespace Tempcord\Interfaces;

use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;

interface CommandRegistrar
{
    /** Register a global application command and return a Thenable that resolves to an object with an id */
    public function registerGlobal(string $applicationId, CommandBuilder $builder): Thenable;

    /** Register a guild-scoped application command and return a Thenable that resolves to an object with an id */
    public function registerGuild(string $applicationId, string $guildId, CommandBuilder $builder): Thenable;
}