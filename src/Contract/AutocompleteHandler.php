<?php

declare(strict_types=1);

namespace Tempcord\Contract;

use Ragnarok\Fenrir\Gateway\Events\InteractionCreate;

/**
 * Interface for autocomplete handler classes.
 *
 * Implement this interface to create type-safe autocomplete handlers.
 * Note: This interface is optional - autocomplete classes only need to implement __invoke().
 */
interface AutocompleteHandler
{
    /**
     * Handle autocomplete request and return choices.
     *
     * @param string $value Current input value from the user
     * @param InteractionCreate $interaction The autocomplete interaction event
     * @return array Array of choices. Supports multiple formats:
     *               - Simple: ['choice1', 'choice2']
     *               - Associative: ['Display Name' => 'value']
     *               - Formatted: [['name' => 'Display', 'value' => 'val']]
     */
    public function __invoke(string $value, InteractionCreate $interaction): array;
}
