<?php

namespace Tempcord\Runtime\Command;

use Tempcord\Compile\Concerns\ReadsParameterTypes;
use Tempcord\Discord\Discord;
use Tempcord\Discord\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use BackedEnum;
use RuntimeException;
use Tempest\Reflection\ParameterReflector;
use Throwable;
use function React\Async\await;

/**
 * Turns the raw value Discord sends for an option into the PHP value the
 * handler's parameter is typed for, fetching the entity where the option only
 * carries an id.
 *
 * Discord is a constructor dependency rather than something reached for through
 * the container, so resolution can be exercised without a gateway.
 */
final readonly class OptionValueResolver
{
    use ReadsParameterTypes;

    public function __construct(
        private Discord $discord,
    ) {}

    /**
     * @throws Throwable
     */
    public function resolve(
        ?ApplicationCommandInteractionDataOptionStructure $option,
        CommandInteraction $interaction,
        ?ParameterReflector $parameter = null,
    ): mixed {
        if ($option === null) {
            return null;
        }

        $type = $parameter === null ? null : $this->parameterType($parameter);

        if (self::isBackedEnum($type)) {
            return $this->toEnum($option, $type);
        }

        return match ($option->type) {
            ApplicationCommandOptionType::USER => await(
                $this->discord->rest->user->get($option->value),
            ),
            ApplicationCommandOptionType::CHANNEL => await(
                $this->discord->rest->channel->get($option->value),
            ),
            ApplicationCommandOptionType::ROLE => await(
                $this->discord->rest->guild->getRole(
                    $interaction->interaction->guild_id,
                    $option->value,
                ),
            ),
            //@todo needs a proxy object that forwards to whichever of Channel or User was mentioned
            ApplicationCommandOptionType::MENTIONABLE => throw new RuntimeException(
                'Mentionable options are not supported yet',
            ),
            default => $option->value,
        };
    }

    /**
     * Discord validates a choice against the list it was given, so a value that
     * is not a case can only come from a client that made one up. Saying which
     * option and which value beats a ValueError from deep inside from().
     *
     * @param class-string<BackedEnum> $enum
     */
    private function toEnum(
        ApplicationCommandInteractionDataOptionStructure $option,
        string $enum,
    ): BackedEnum {
        $backing = new \ReflectionEnum($enum)->getBackingType()?->getName();
        $value = $backing === 'int' ? (int) $option->value : (string) $option->value;

        return $enum::tryFrom($value) ?? throw new RuntimeException(
            'Option [' . $option->name . '] was sent "' . $option->value . '", which is not a case of ' . $enum,
        );
    }
}
