<?php

namespace Tempcord\Support;

use Tempest\Console\Actions\ExecuteConsoleCommand;
use Tempest\Console\ConsoleConfig;
use Tempest\Console\ExitCodeWasInvalid;
use Tempest\Console\Input\ConsoleArgumentBag;
use Tempest\Container\Container;
use Tempest\Core\Composer;
use Tempest\Core\FrameworkKernel;
use Tempest\Core\Kernel;
use Tempest\Discovery\DiscoveryLocation;

final readonly class TempcordConsoleApplication
{
    public function __construct(
        private Container          $container,
        private ConsoleArgumentBag $argumentBag,
    )
    {
    }

    /** @param DiscoveryLocation[] $discoveryLocations */
    public static function boot(
        string  $name = 'Tempcord',
        ?string $root = null,
        array   $discoveryLocations = [],
    ): self
    {
        $root ??= getcwd();

        // Kernel
        $kernel = FrameworkKernel::boot(
            root: $root,
            discoveryLocations: $discoveryLocations,
        );

        $container = $kernel->container;

        $application = $container->get(__CLASS__);
        $composer = $container->get(Composer::class);

        // Application-specific config
        $consoleConfig = $container->get(ConsoleConfig::class);
        $consoleConfig->name = $name;
        //Reset all internal tempest commands
//        $consoleConfig->commands = [];

        return $application;
    }

    public function run(): void
    {
        $exitCode = $this->container->get(ExecuteConsoleCommand::class)($this->argumentBag->getCommandName());

        $exitCode = is_int($exitCode) ? $exitCode : $exitCode->value;

        if ($exitCode < 0 || $exitCode > 255) {
            throw new ExitCodeWasInvalid($exitCode);
        }

        $this->container->get(Kernel::class)->shutdown($exitCode);
    }


}