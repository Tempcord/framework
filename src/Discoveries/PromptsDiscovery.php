<?php

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Prompts\Prompt;
use Tempcord\Registries\PromptsRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class PromptsDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly PromptsRegistry $registry,
    )
    {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        /** @var Prompt $prompt */
        foreach ($class->getAttributes(Prompt::class) as $prompt) {
            $this->discoveryItems->add($location, $prompt->withReflector($class));
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $prompt) {
            $this->registry->bucket->add($prompt);
        }
    }
}
