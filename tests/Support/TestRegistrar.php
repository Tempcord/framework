<?php

namespace Tests\Support;

use Tempcord\Interfaces\CommandRegistrar;
use Tempcord\Interfaces\Thenable;

class TestRegistrar implements CommandRegistrar
{
    public array $globalCalls = [];
    public array $guildCalls = [];

    public function registerGlobal(string $applicationId, \Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder $builder): Thenable
    {
        $this->globalCalls[] = [$applicationId, $builder->getName()];
        return new ImmediateThenable((object)['id' => 'global-1']);
    }

    public function registerGuild(string $applicationId, string $guildId, \Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder $builder): Thenable
    {
        $this->guildCalls[] = [$applicationId, $guildId, $builder->getName()];
        return new ImmediateThenable((object)['id' => 'guild-1']);
    }
}

