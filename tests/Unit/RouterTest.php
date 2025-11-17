<?php

use Ragnarok\Fenrir\Bitwise\Bitwise;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\Intent;
use Ragnarok\Fenrir\Enums\InteractionType;
use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;
use Ragnarok\Fenrir\Parts\ApplicationCommand;
use Ragnarok\Fenrir\Parts\InteractionData;
use Tempcord\Registries\CommandsRegistry;
use Tempcord\Registries\Router;
use Tests\Fixtures\Commands\SpyInvokableCommand;
use function Tempest\get;

describe('Router', function (): void {
    beforeEach(function () {
        $this->registry = get(CommandsRegistry::class);
        $this->router = get(Router::class);
    });

    it('registers and can handle by id', function (): void {
        $this->discover(SpyInvokableCommand::class);

        /** @var \Tempcord\Attributes\Commands\Command $command */
        $command = $this->registry->bucket->items->get('spy_invokable');

        $appCommand = new ApplicationCommand();
        $appCommand->id = 'app-1';

        $this->router->register($appCommand, $command);

        $interaction = new InteractionCreate();
        $interaction->type = InteractionType::APPLICATION_COMMAND;
        $interaction->data = new InteractionData();
        $interaction->data->id = 'app-1';

        expect($this->router->canHandle($interaction))->toBeTrue();
    });

    it('throws on handle for unknown id', function (): void {
        $discord = (new Discord('dummy'))->withGateway(Bitwise::from(Intent::GUILDS));

        $interaction = new InteractionCreate();
        $interaction->type = InteractionType::APPLICATION_COMMAND;
        $interaction->data = new InteractionData();
        $interaction->data->id = 'missing';

        $commandInteraction = new \Ragnarok\Fenrir\Interaction\CommandInteraction($interaction, $discord);

        expect(fn() => $this->router->handle($commandInteraction))
            ->toThrow(RuntimeException::class, 'Command "missing" not found in router');
    });
});

