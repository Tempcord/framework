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
        $this->isMentionable($this->from);

        return "<@{$this->from->id}>";
    }

    private function isMentionable(mixed $from): void
    {
        if (
            !$from instanceof User &&
            !$from instanceof GuildMember
        ) {
            throw new \InvalidArgumentException('Can not mention this object. [' . $from::class . ']');
        }
    }

}