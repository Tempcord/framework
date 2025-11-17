<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use ReflectionClass;
use ReflectionException;
use Tempest\Container\Exceptions\InvokedCallableWasInvalid;
use Tempest\Container\GenericContainer;
use Tempest\Core\FrameworkKernel;
use Tempest\Core\Kernel;
use Tempest\Core\Kernel\LoadDiscoveryClasses;
use Tempest\Discovery\DiscoveryLocation;
use function Tempest\Support\Path\normalize;
use function Tempest\Support\Path\to_absolute_path;
use Tests\Fixtures\Commands\CommandWithoutName;

abstract class TestCase extends BaseTestCase
{
    protected string $root;

    protected Kernel $kernel;

    protected GenericContainer $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setupKernel();
    }


    /**
     * Returns an array of DiscoveryLocations that should be discovered only during testing
     * @param string|array $classNames
     * @return void
     * @throws InvokedCallableWasInvalid
     * @throws ReflectionException
     */
    protected function discover(string|array $classNames): void
    {
        if (!is_array($classNames)) {
            $classNames = [$classNames];
        }

        $discoveryLocations = [];

        foreach ($classNames as $className) {
            $reflection = new ReflectionClass($className);
            $filePath = $reflection->getFileName();

            $discoveryLocations = [];

            $fixturesPath = to_absolute_path($this->root, $filePath);

            $discoveryLocations[] = new DiscoveryLocation($className, $fixturesPath);
        }

        $this->kernel->discoveryLocations = $discoveryLocations;
        $this->container->invoke(LoadDiscoveryClasses::class);
    }

    protected function setupKernel(): self
    {
        // We force forward slashes for consistency even on Windows.
        $this->root ??= normalize(realpath(getcwd()));


        $this->kernel ??= FrameworkKernel::boot(
            root: $this->root,
        );


        /** @var GenericContainer $container */
        $container = $this->kernel->container;
        $this->container = $container;

        return $this;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->root, $this->container, $this->kernel);
    }

}
