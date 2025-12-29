<?php

namespace Tempcord\ConsoleCommands;

use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Commands\Option;
use Tempcord\Tempcord;
use Tempest\Console\ConsoleCommand;
use Tempest\Console\HasConsole;
use Tempest\Console\Input\ConsoleArgumentDefinition;
use function Tempest\Support\str;

class CommandsListCommand
{
    use HasConsole;

    public function __construct(
        private Tempcord $tempcord,
    )
    {
    }

    #[ConsoleCommand('commands:list', 'List all commands')]
    public function __invoke(): void
    {
        foreach ($this->tempcord->commandsRegistry->bucket->items as $item) {
            $this->header($item->reflector->getName());
            $parts = [];
            foreach ($item->options as $option) {
                $parts[] = '<style="fg-gray">' . $this->renderArgument($option) . '</style>';
            }
            $this
                ->keyValue('Name', $item->name)
                ->keyValue('Description', $item->description)
                ->writeln('Options:')
                ->writeln(implode(' ', $parts));

        }

    }

    private function renderArgument(Option $option): string
    {

        $formattedArgumentName = match ($option->type) {
            ApplicationCommandOptionType::BOOLEAN => "--{$option->name}",
            default => $option->name,
        };

        $formattedArgumentName = str($formattedArgumentName)->wrap('<style="fg-blue">', '</style>');

        return str()
            ->append(str('[')->wrap('<style="fg-gray dim">', '</style>'))
            ->append($formattedArgumentName)
            ->append(str(']')->wrap('<style="fg-gray dim">', '</style>'))
            ->toString();
    }

}