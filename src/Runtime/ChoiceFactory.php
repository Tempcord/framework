<?php

namespace Tempcord\Runtime;

use Ragnarok\Fenrir\Parts\ApplicationCommandOptionChoice;

/**
 * Normalises whatever an Autocomplete returned into the choice list Discord
 * accepts.
 */
final readonly class ChoiceFactory
{
    /**
     * Discord rejects an autocomplete response carrying more choices than this.
     */
    public const int MAX_CHOICES = 25;

    /**
     * A bare scalar stands for a single suggestion. A list uses each entry as
     * its own label; a map uses its keys as labels. Choices built by hand are
     * passed through untouched.
     *
     * @return list<ApplicationCommandOptionChoice>
     */
    public function from(mixed $value): array
    {
        $choices = is_array($value) ? $value : [$value];
        $choices = array_slice($choices, 0, self::MAX_CHOICES, preserve_keys: true);

        $isList = array_is_list($choices);

        return array_map(
            static function (mixed $choice, int|string $label) use ($isList): ApplicationCommandOptionChoice {
                if ($choice instanceof ApplicationCommandOptionChoice) {
                    return $choice;
                }

                $applicationCommandOptionChoice = new ApplicationCommandOptionChoice();
                $applicationCommandOptionChoice->name = (string) ($isList ? $choice : $label);
                $applicationCommandOptionChoice->value = $choice;

                return $applicationCommandOptionChoice;
            },
            $choices,
            array_keys($choices),
        );
    }
}
