<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Discord\Bitwise\Bitwise;
use Tempcord\Cache\Cache;
use Tempcord\Cache\CacheSubscriber;
use Tempcord\Discord\AllCommandExtension;
use Tempcord\Discord\CommandBuilderFactory;
use Tempcord\Discord\ComponentExtension;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\ComponentsRegistry;
use Tempcord\Registries\EventsRegistry;
use Tempcord\Registries\PluginsRegistry;
use Tempcord\Runtime\ArgumentResolver;
use Tempcord\Runtime\AutocompleteResolver;
use Tempcord\Runtime\AutocompleteResponder;
use Tempcord\Runtime\ChoiceFactory;
use Tempcord\Runtime\CommandBinder;
use Tempcord\Runtime\CommandDispatcher;
use Tempcord\Runtime\MiddlewarePipeline;
use Tempcord\Runtime\CommandRegistrar;
use Tempcord\Runtime\ComponentArgumentResolver;
use Tempcord\Runtime\ComponentBinder;
use Tempcord\Runtime\ComponentDispatcher;
use Tempcord\Runtime\OptionValueResolver;
use Tempcord\Runtime\PluginBooter;
use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempest\Container\GenericContainer;
use Tempest\Log\Logger;
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
        ?ComponentsRegistry $components = null,
        ?EventsRegistry $events = null,
        ?PluginsRegistry $plugins = null,
        ?RecordingHttp $http = null,
        ?RecordingLogger $logger = null,
        ?Cache $cache = null,
    ): Tempcord {
        $http ??= new RecordingHttp();
        $discord ??= new FakeDiscord($http);
        $components ??= new ComponentsRegistry();
        $logger ??= new RecordingLogger();

        $container = new GenericContainer();
        $container->singleton(Logger::class, $logger);

        return new Tempcord(
            discord: $discord,
            commandsRegistry: $commands ?? new CommandsRegistry(),
            componentsRegistry: $components,
            eventsRegistry: $events ?? new EventsRegistry($container),
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
                    new MiddlewarePipeline(new GenericContainer()),
                ),
                new AutocompleteResponder(
                    new ChoiceFactory(),
                    new AutocompleteResolver($container),
                    $logger,
                ),
            ),
            componentBinder: new ComponentBinder(
                new ComponentExtension($components),
                new ComponentDispatcher(
                    new ComponentArgumentResolver($discord),
                    new GenericContainer(),
                    new RecordingLogger(),
                ),
            ),
            pluginBooter: new PluginBooter($logger),
            cacheSubscriber: $cache === null
                ? null
                : new CacheSubscriber($cache, new TempcordConfig('::token::', new Bitwise())),
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
