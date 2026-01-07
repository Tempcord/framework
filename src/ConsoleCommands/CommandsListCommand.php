<?php

namespace Tempcord\ConsoleCommands;

use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Parts\User;
use Tempcord\Attributes\Commands\Command;
use Tempcord\Attributes\Commands\Option;
use Tempcord\Attributes\Commands\Subcommand;
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
            $this->header($item->reflector->getName(), $item->hasSubcommands ? "<style='dim fg-cyan'>// Subcommands:</style>" : null);
            $this->writeln();

            if ($item->hasSubcommands) {
                foreach ($item->subcommands as $subcommand) {
                    //Custom header for subcommands
                    $this->writeln(
                        str($item->reflector->getName() . "::" . $subcommand->reflector->getName() . '(...)')
                            ->wrap("<style='dim fg-gray'>//</style> <style='fg-gray bold'>", "</style>")
                    );

                    $this->renderCommand($subcommand);
                }
                continue;
            }

            $this->renderCommand($item);
        }

    }

    public function renderCommand(Command|Subcommand $item)
    {
        $parts = [];

        foreach ($item->options as $option) {
            $parts[] = '<style="fg-gray">' . $this->renderArgument($option) . '</style>';
        }

        $this
            ->keyValue('Name', $item->name)
            ->keyValue('Description', $item->description)
            ->writeln('Options:')
            ->writeln(implode(' ', $parts));
        $this->writeln();
    }

    private function renderArgument(Option|Subcommand $option): string
    {
        $formattedArgumentName = match ($option->type) {
            ApplicationCommandOptionType::USER => User::class,
            default => str($option->type->name)->lower(),
        };

        $formattedArgumentName = str($option->name)->wrap('<style="fg-blue">', '</style>')
            ->append(str($formattedArgumentName)->wrap('<style="fg-magenta">:typeOf<', '></style>'));

        return str()
            ->append(str('[')->wrap('<style="fg-gray dim">', '</style>'))
            ->append($formattedArgumentName)
            ->append(str(']')->wrap('<style="fg-gray dim">', '</style>'))
            ->toString();
    }

}