<?php

namespace Tempcord\Attributes;

use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Subcommand;
use function Tempest\Support\Arr\is_empty;

/** @extends Command */
trait HasSubcommands
{
    public bool $hasSubcommands {
        get => !is_empty($this->subcommands);
    }

    /** @var array<Subcommand> */
    public array $subcommands {
        get {
            $subcommands = [];
            foreach ($this->reflector->getPublicMethods() as $publicMethod) {
                if ($publicMethod->hasAttribute(Subcommand::class)) {
                    $subcommand = $publicMethod->getAttribute(Subcommand::class)
                        ?->withReflector($publicMethod);
                    $subcommands[$subcommand->name] = $subcommand;
                }
            }
            return $subcommands;
        }
    }

    public function getSubcommand(string $name): ?Subcommand
    {
        return $this->subcommands[$name] ?? null;
    }

}