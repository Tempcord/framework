<?php

namespace Tempcord\Interfaces;

/**
 * An enum that says how each of its cases should read in Discord.
 *
 * An enum typed command option offers every case as a choice, and without this
 * the case name is what a member sees — which is a PHP identifier, in English,
 * and rarely what a bot wants shown. Implement this to name them properly.
 *
 *     enum Platform: string implements Choosable
 *     {
 *         case PC = 'PC';
 *         case PlayStation = 'PS4';
 *
 *         public function label(): string
 *         {
 *             return match ($this) {
 *                 self::PC => 'PC',
 *                 self::PlayStation => 'PlayStation',
 *             };
 *         }
 *     }
 */
interface Choosable
{
    /**
     * What a member reads when picking this case.
     */
    public function label(): string;
}
