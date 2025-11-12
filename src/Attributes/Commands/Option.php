<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use LogicException;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\Role;
use Ragnarok\Fenrir\Parts\User;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use RuntimeException;
use Tempcord\Interfaces\Autocomplete;
use Tempcord\Tempcord;
use Tempcord\Traits\HasAttributes;
use Tempest\Reflection\ParameterReflector;
use Throwable;
use function React\Async\await;
use function Tempest\get;
use function Tempest\Support\str;

#[Attribute(Attribute::TARGET_PARAMETER)]
final class Option
{
    use HasAttributes;

    public ParameterReflector $reflector;

    public string $name {
        get {
            if ($this->hasAttribute('name')) {
                return $this->getAttribute('name');
            }

            return str($this->reflector->getName())->toString();
        }
    }

    public ApplicationCommandOptionType $type {
        get {
            if (!$this->reflector->getReflection()->hasType()) {
                throw new LogicException('Command option does not have type');
            }

            $type = $this->reflector->getType();

            //array, bool, callable, float, int, null, object, false, iterable, mixed, never, true, void,
            return match ($type->getName()) {
                'string' => ApplicationCommandOptionType::STRING,
                'int' => ApplicationCommandOptionType::INTEGER,
                'float' => ApplicationCommandOptionType::NUMBER,
                'bool' => ApplicationCommandOptionType::BOOLEAN,
                User::class => ApplicationCommandOptionType::USER,
                Channel::class => ApplicationCommandOptionType::CHANNEL,
                Role::class => ApplicationCommandOptionType::ROLE,
                //@todo Add more options: Mentionable
                default => throw new LogicException('Command option type not supported'),
                //@todo maybe add some DTO mapper!? To map modal data to an object
            };
        }
    }

    public bool $isRequired {
        get {
            return !$this->reflector->isOptional();
        }
    }

    public CommandOptionBuilder $build {
        get {
            return CommandOptionBuilder::new()
                ->setName($this->name)
                ->setDescription($this->description)
                ->setRequired($this->isRequired)
                ->setType($this->type)
                ->setAutoComplete($this->autocomplete !== null);
        }
    }

    public function __construct(
        //@todo Add more options for building the Option (from DiscordPHP)
        public string        $description,
        ?string              $name = null,
        public ?Autocomplete $autocomplete = null,
    )
    {
        $this->setAttribute('name', $name);
    }

    /**
     * @param ApplicationCommandInteractionDataOptionStructure|null $option
     * @param CommandInteraction $interaction
     * @return mixed
     * @throws Throwable
     */
    public function mapValue(?ApplicationCommandInteractionDataOptionStructure $option, CommandInteraction $interaction): mixed
    {
        if (!$option) {
            return null;
        }

        $tempcord = get(Tempcord::class);


        if ($option->type === ApplicationCommandOptionType::USER) {
            await($tempcord->discord->rest->user->get($option->value));
        }

        return match ($option->type) {
            ApplicationCommandOptionType::USER => await($tempcord->discord->rest->user->get($option->value)),
            ApplicationCommandOptionType::CHANNEL => await($tempcord->discord->rest->channel->get($option->value)),
            ApplicationCommandOptionType::ROLE => await($tempcord->discord->rest->guild->getRole($interaction->interaction->guild_id, $option->value)),
            //@todo need a proxy object that will proxy all props to Channel or User
            ApplicationCommandOptionType::MENTIONABLE => throw new RuntimeException('Not implemented'),
            default => $option->value,
        };
    }
}