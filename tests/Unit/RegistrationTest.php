<?php

namespace {
    use Evenement\EventEmitterInterface;
    use Ragnarok\Fenrir\Constants\Events;
    use Ragnarok\Fenrir\Enums\InteractionType;
    use Ragnarok\Fenrir\Gateway\Events\Ready;
    use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
    use Tempcord\Registries\CommandsRegistry;
    use Tempcord\Interfaces\EventBus;
    use Tempcord\Interfaces\CommandRegistrar;
    use Tempcord\Interfaces\Thenable;
    use Tests\Fixtures\Commands\SpyInvokableCommand;
    use Tests\Fixtures\Commands\SpyGuildInvokableCommand;
    use function Tempest\get;

    // Minimal Evenement emitter for tests
    class TestEmitter implements EventEmitterInterface
    {
        private array $once = [];
        private array $on = [];

        public function on($event, callable $listener): void { $this->on[$event][] = $listener; }
        public function once($event, callable $listener): void { $this->once[$event][] = $listener; }
        public function removeListener($event, callable $listener): void {}
        public function removeAllListeners($event = null): void {}
        public function listeners($event = null): array { return []; }
        public function emit($event, array $arguments = []): void {
            foreach ($this->on[$event] ?? [] as $l) { $l(...$arguments); }
            foreach ($this->once[$event] ?? [] as $l) { $l(...$arguments); }
            $this->once[$event] = [];
        }
    }

    class TestEventBus implements EventBus
    {
        public function __construct(private EventEmitterInterface $emitter)
        {
        }

        public function getEmitter(): EventEmitterInterface { return $this->emitter; }
        public function on(string $event, callable $listener): void { $this->emitter->on($event, $listener); }
        public function once(string $event, callable $listener): void { $this->emitter->once($event, $listener); }
        public function emit(string $event, array $arguments = []): void { $this->emitter->emit($event, $arguments); }
    }

    class ImmediateThenable implements Thenable
    {
        public function __construct(private $result) {}
        public function then(callable $onFulfilled): void { $onFulfilled($this->result); }
    }

    class TestRegistrar implements CommandRegistrar
    {
        public array $globalCalls = [];
        public array $guildCalls = [];

        public function registerGlobal(string $applicationId, \Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder $builder): Thenable
        {
            $this->globalCalls[] = [$applicationId, $builder->getName()];
            return new ImmediateThenable((object)['id' => 'global-1']);
        }

        public function registerGuild(string $applicationId, string $guildId, \Ragnarok\Fenrir\Rest\Helpers\Command\CommandBuilder $builder): Thenable
        {
            $this->guildCalls[] = [$applicationId, $guildId, $builder->getName()];
            return new ImmediateThenable((object)['id' => 'guild-1']);
        }
    }

    describe('Command registration', function (): void {
        beforeEach(function () {
            $this->registry = get(CommandsRegistry::class);
        });

        it('registers a global command on READY and invokes handler on INTERACTION_CREATE', function (): void {
            $this->discover(SpyInvokableCommand::class);

            $bus = new TestEventBus(new TestEmitter());
            $registrar = new TestRegistrar();
            $this->registry->setEventBus($bus);
            $this->registry->setCommandRegistrar($registrar);
            // Initialize with a real Discord using a dummy token; adapters will be used so no network interaction
            $this->registry->initialize(new \Ragnarok\Fenrir\Discord('dummy-token'));

            // Emit READY
            $bus->emit(Events::READY, [(object)['user' => (object)['id' => 'app-123']]]);

            // Assert REST createApplicationCommand was called
            expect($registrar->globalCalls)->toHaveCount(1)
                ->and($registrar->globalCalls[0][0])->toBe('app-123')
                ->and($registrar->globalCalls[0][1])->toBe('spy_invokable');

            // Emit INTERACTION_CREATE with returned command id
            $bus->emit(Events::INTERACTION_CREATE, [
                (object) [
                    'type' => InteractionType::APPLICATION_COMMAND,
                    'data' => (object)['id' => 'global-1']
                ]
            ]);

            expect(SpyInvokableCommand::$invoked)->toBeTrue();
        });

        it('registers a guild command on READY and invokes handler on INTERACTION_CREATE', function (): void {
            $this->discover(SpyGuildInvokableCommand::class);

            $bus = new TestEventBus(new TestEmitter());
            $registrar = new TestRegistrar();
            $this->registry->setEventBus($bus);
            $this->registry->setCommandRegistrar($registrar);
            $this->registry->initialize(new \Ragnarok\Fenrir\Discord('dummy-token'));

            $bus->emit(Events::READY, [(object)['user' => (object)['id' => 'app-456']]]);

            expect($registrar->guildCalls)->toHaveCount(1)
                ->and($registrar->guildCalls[0][0])->toBe('app-456')
                ->and($registrar->guildCalls[0][1])->toBe('42')
                ->and($registrar->guildCalls[0][2])->toBe('spy_guild_invokable');

            $bus->emit(Events::INTERACTION_CREATE, [
                (object) [
                    'type' => InteractionType::APPLICATION_COMMAND,
                    'data' => (object)['id' => 'guild-1']
                ]
            ]);

            expect(SpyGuildInvokableCommand::$invoked)->toBeTrue();
        });

        it('treats a command with subcommands and no own handler as a group', function (): void {
            \Tests\Fixtures\Commands\ParentWithSubcommandsCommand::$invoked = false;
            $this->discover(\Tests\Fixtures\Commands\ParentWithSubcommandsCommand::class);

            $bus = new TestEventBus(new TestEmitter());
            $registrar = new TestRegistrar();
            $this->registry->setEventBus($bus);
            $this->registry->setCommandRegistrar($registrar);
            $this->registry->initialize(new \Ragnarok\Fenrir\Discord('dummy-token'));

            // Emit READY and ensure the command is registered
            $bus->emit(Events::READY, [(object)['user' => (object)['id' => 'app-789']]]);

            expect($registrar->globalCalls)->toHaveCount(1)
                ->and($registrar->globalCalls[0][0])->toBe('app-789')
                ->and($registrar->globalCalls[0][1])->toBe('parent_with_subcommands');

            // Emit INTERACTION_CREATE for the registered command id; since the parent has no handler,
            // it should be treated as a group and no handler should be invoked
            $bus->emit(Events::INTERACTION_CREATE, [
                (object) [
                    'type' => InteractionType::APPLICATION_COMMAND,
                    'data' => (object)['id' => 'global-1']
                ]
            ]);

            expect(\Tests\Fixtures\Commands\ParentWithSubcommandsCommand::$invoked)->toBeFalse();
        });
    });
}