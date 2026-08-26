<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use CyberWolf\Discord\Bitwise\Bitwise;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Runtime\PluginBooter;
use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempest\Container\GenericContainer;
use Tempcord\Attributes\Command;
use Tempcord\Compiler\CommandCompiler;
use Tempcord\Definitions\CommandDefinition;
use Tempest\Reflection\ClassReflector;

abstract class TestCase extends BaseTestCase
{
    /**
     * A bot wired the way the container wires one, but with a Discord that
     * neither opens a socket nor reaches the network.
     */
    protected function tempcord(
        ?FakeDiscord $discord = null,
        ?CommandsRegistry $commands = null,
        ?EventsRegistry $events = null,
        ?PluginsRegistry $plugins = null,
        ?RecordingHttp $http = null,
        ?RecordingLogger $logger = null,
    ): Tempcord {
        $http ??= new RecordingHttp();
        $discord ??= new FakeDiscord($http);

        return new Tempcord(
            discord: $discord,
            commandsRegistry: $commands ?? new CommandsRegistry(),
            eventsRegistry: $events ?? new EventsRegistry(new GenericContainer()),
            pluginsRegistry: $plugins ?? new PluginsRegistry(),
            registrar: new CommandRegistrar(
                new CommandBuilderFactory(),
                new TempcordConfig('::token::', new Bitwise()),
                new RecordingLogger(),
                $http,
            ),
            binder: new CommandBinder(
                new AllCommandExtension(),
                new CommandDispatcher(
                    new ArgumentResolver(new OptionValueResolver($discord)),
                    new GenericContainer(),
                    new RecordingLogger(),
                ),
                new AutocompleteResponder(new ChoiceFactory()),
            ),
            pluginBooter: new PluginBooter($logger ?? new RecordingLogger()),
        );
    }

    /**
     * Compiles a fixture the same way CommandsDiscovery does.
     */
    protected function definition(string $class): CommandDefinition
    {
        $reflector = new ClassReflector($class);

        /** @var Command $attribute */
        $attribute = $reflector->getAttribute(Command::class);

        return new CommandCompiler()->compile($reflector, $attribute);
    }
}
