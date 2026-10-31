<?php

namespace Tempcord\Tests\Unit;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Tempcord\Discord\Bitwise\Bitwise;
use Tempcord\Support\Cache\Cache;
use Tempcord\Support\Cache\CacheSubscriber;
use Tempcord\Runtime\Command\AllCommandExtension;
use Tempcord\Runtime\Registration\CommandBuilderFactory;
use Tempcord\Runtime\Component\ComponentExtension;
use Tempcord\Compile\Command\CommandsRegistry;
use Tempcord\Compile\Component\ComponentsRegistry;
use Tempcord\Compile\Event\EventsRegistry;
use Tempcord\Compile\Plugin\PluginsRegistry;
use Tempcord\Runtime\Command\ArgumentResolver;
use Tempcord\Runtime\Autocomplete\AutocompleteResolver;
use Tempcord\Runtime\Autocomplete\AutocompleteResponder;
use Tempcord\Runtime\Autocomplete\ChoiceFactory;
use Tempcord\Runtime\Command\CommandBinder;
use Tempcord\Runtime\Command\CommandDispatcher;
use Tempcord\Runtime\Middleware\MiddlewarePipeline;
use Tempcord\Runtime\Registration\CommandRegistrar;
use Tempcord\Runtime\Component\ComponentArgumentResolver;
use Tempcord\Runtime\Component\ComponentBinder;
use Tempcord\Runtime\Component\ComponentDispatcher;
use Tempcord\Runtime\Command\OptionValueResolver;
use Tempcord\Runtime\Plugin\PluginBooter;
use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempcord\Tests\Doubles\FakeDiscord;
use Tempcord\Tests\Doubles\RecordingHttp;
use Tempcord\Tests\Doubles\RecordingLogger;
use Tempest\Container\GenericContainer;
use Tempest\Log\Logger;
use Tempcord\Declare\Command\Command;
use Tempcord\Compile\Command\CommandCompiler;
use Tempcord\Compile\Command\Definitions\CommandDefinition;
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
                    new MiddlewarePipeline(new GenericContainer()),
                    $discord,
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
