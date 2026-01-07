<?php

namespace Tempcord\Support;

use Ragnarok\Fenrir\Parts\GuildMember;
use Ragnarok\Fenrir\Parts\User;

class DiscordObjectFactory
{
    private mixed $from;

    public function withData(mixed $data): self
    {
        $this->from = $data;

        return clone $this;
    }

    public function toMention(): string
    {
        $mentionable = $this->getMentionable($this->from);

        return "<@{$mentionable->id}>";
    }

    private function getMentionable(GuildMember|User $from): User
    {
        return $from instanceof GuildMember ? $from->user : $from;

    }

}