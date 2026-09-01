<?php

namespace Tempcord\Runtime;

use Tempcord\Discord\Discord;
use Tempcord\Discord\Enums\ApplicationCommandOptionType;
use Tempcord\Discord\Interaction\CommandInteraction;
use Tempcord\Discord\Parts\ApplicationCommandInteractionDataOptionStructure;
use RuntimeException;
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
    public function __construct(
        private Discord $discord,
    ) {}

    /**
     * @throws Throwable
     */
    public function resolve(
        ?ApplicationCommandInteractionDataOptionStructure $option,
        CommandInteraction $interaction,
    ): mixed {
        if ($option === null) {
            return null;
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
}
