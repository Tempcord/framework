<?php

namespace Tempcord\Attributes\Commands;

use Attribute;
use Closure;
use LogicException;
use Ragnarok\Fenrir\Discord;
use Ragnarok\Fenrir\Enums\ApplicationCommandOptionType;
use Ragnarok\Fenrir\Interaction\CommandInteraction;
use Ragnarok\Fenrir\Parts\ApplicationCommandInteractionDataOptionStructure;
use Ragnarok\Fenrir\Parts\Channel;
use Ragnarok\Fenrir\Parts\GuildMember;
use Ragnarok\Fenrir\Parts\Role;
use Ragnarok\Fenrir\Parts\User;
use Ragnarok\Fenrir\Rest\Helpers\Command\CommandOptionBuilder;
use RuntimeException;
use Tempcord\Interfaces\Autocomplete;
use Tempcord\Support\Traits\HasAttributes;
use Tempcord\Tempcord;
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


            return match ($type->getName()) {
                //@TODO: maybe add some DTO mapper!? To map modal data to an object
                'int' => ApplicationCommandOptionType::INTEGER,
                'float' => ApplicationCommandOptionType::NUMBER,
                'bool' => ApplicationCommandOptionType::BOOLEAN,
                User::class, GuildMember::class => ApplicationCommandOptionType::USER,
                Channel::class => ApplicationCommandOptionType::CHANNEL,
                Role::class => ApplicationCommandOptionType::ROLE,
                default => ApplicationCommandOptionType::STRING,
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
        public string   $description,
        ?string         $name = null,
        public ?Closure $autocomplete = null,
    )
    {
        $this->setAttribute('name', $name);
    }

    public function withReflector(ParameterReflector $reflector): Option
    {
        $this->reflector = $reflector;
        return $this;

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

        $resolved = $interaction->interaction->data->resolved;
        $logger = get(\Tempest\Log\Logger::class);

        if ($this->reflector->getType()->equals(GuildMember::class)) {
            if (isset($resolved?->members[$option->value])) {
                return $resolved->members[$option->value];
            }
            $discord = get(Discord::class);
            return await($discord->rest->guild->getMember($interaction->interaction->guild_id, $option->value));
        }

        return match ($option->type) {
            ApplicationCommandOptionType::USER => $resolved?->users[$option->value] ?? (function () use ($option) {
                $discord = get(Discord::class);
                return await($discord->rest->user->get($option->value));
            })(),
            ApplicationCommandOptionType::CHANNEL => $resolved?->channels[$option->value] ?? (function () use ($option) {
                $discord = get(Discord::class);
                return await($discord->rest->channel->get($option->value));
            })(),
            ApplicationCommandOptionType::ROLE => $resolved?->roles[$option->value] ?? (function () use ($option, $interaction) {
                $discord = get(Discord::class);
                return await($discord->rest->guild->getRole($interaction->interaction->guild_id, $option->value));
            })(),
            default => $option->value,
        };
    }
}