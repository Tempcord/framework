<?php

namespace Tempcord\ConsoleCommands;

use React\EventLoop\Loop;
use Tempcord\Support\Prompts\InteractiveSession;
use Tempcord\Tempcord;
use Tempcord\TempcordConfig;
use Tempest\Console\Console;
use Tempest\Console\ConsoleCommand;
use Tempest\Log\Logger;
use function Tempest\get;

final readonly class InteractiveCommand
{
    public function __construct(
        private Tempcord       $tempcord,
        private Console        $console,
        private Logger         $logger,
        private TempcordConfig $config
    )
    {
    }

    #[ConsoleCommand(
        name: 'interactive',
        description: 'Start the bot in interactive mode with live command prompt',
        aliases: ['i']
    )]
    public function __invoke(): void
    {
        // Enable interactive mode FIRST before logger is used
        // This ensures ConsoleLogChannel returns empty handlers
        $session = get(InteractiveSession::class);
        $session->enableInteractiveMode();

        // Prepare Discord - logger won't have console handlers attached
        $discord = $this->tempcord->prepareDiscord($this->logger, $this->config);

        // Start interactive session with ReactPHP event loop
        $session->start(Loop::get(), $discord);
    }
}
