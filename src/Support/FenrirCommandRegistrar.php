<?php

namespace Tempcord\Support;

use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder;
use Tempcord\Interfaces\CommandRegistrar;
use Tempcord\Interfaces\Thenable;

class FenrirCommandRegistrar implements CommandRegistrar
{
    public function __construct(private Discord $discord)
    {
    }

    public function registerGlobal(string $applicationId, CommandBuilder $builder): Thenable
    {
        $promise = $this->discord->rest->globalCommand->createApplicationCommand($applicationId, $builder);
        return new FenrirPromiseAdapter($promise);
    }

    public function registerGuild(string $applicationId, string $guildId, CommandBuilder $builder): Thenable
    {
        $promise = $this->discord->rest->guildCommand->createApplicationCommand($applicationId, $guildId, $builder);
        return new FenrirPromiseAdapter($promise);
    }
}