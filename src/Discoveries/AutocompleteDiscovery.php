<?php

declare(strict_types=1);

namespace Tempcord\Discoveries;

use Tempcord\Attributes\Commands\Autocomplete;
use Tempcord\Registries\AutocompleteRegistry;
use Tempest\Discovery\Discovery;
use Tempest\Discovery\DiscoveryLocation;
use Tempest\Discovery\IsDiscovery;
use Tempest\Reflection\ClassReflector;

final class AutocompleteDiscovery implements Discovery
{
    use IsDiscovery;

    public function __construct(
        private readonly AutocompleteRegistry $registry,
    ) {
    }

    public function discover(DiscoveryLocation $location, ClassReflector $class): void
    {
        /** @var Autocomplete $autocomplete */
        foreach ($class->getAttributes(Autocomplete::class) as $autocomplete) {
            $this->discoveryItems->add($location, $autocomplete->withReflector($class));
        }
    }

    public function apply(): void
    {
        foreach ($this->discoveryItems as $autocomplete) {
            $this->registry->register($autocomplete);
        }
    }
}
