<?php

namespace Tempcord\Support\Prompts;

final class PromptParser
{
    /**
     * Parse user input into prompt name and arguments.
     *
     * Examples:
     * - "/clear" -> name: "clear", arguments: []
     * - "/reboot --mode soft" -> name: "reboot", arguments: ["mode" => "soft"]
     * - "/reboot --mode=soft" -> name: "reboot", arguments: ["mode" => "soft"]
     * - "help" -> name: "help", arguments: []
     *
     * @param string $input User input
     * @return ParsedPrompt
     */
    public static function parse(string $input): ParsedPrompt
    {
        // Remove leading slash if present and trim
        $input = ltrim(trim($input), '/');

        if (empty($input)) {
            return new ParsedPrompt('', []);
        }

        // Split into tokens, handling quoted strings
        $tokens = self::tokenize($input);

        $name = array_shift($tokens) ?? '';
        $arguments = [];

        while ($token = array_shift($tokens)) {
            if (str_starts_with($token, '--')) {
                // Long option: --mode=soft or --mode soft
                $option = ltrim($token, '-');

                if (str_contains($option, '=')) {
                    [$key, $value] = explode('=', $option, 2);
                    $arguments[$key] = $value;
                } else {
                    // Next token is value, or true if no value (flag)
                    $value = array_shift($tokens);
                    $arguments[$option] = $value ?? true;
                }
            } elseif (str_starts_with($token, '-')) {
                // Short option: -m soft
                $option = ltrim($token, '-');
                $value = array_shift($tokens);
                $arguments[$option] = $value ?? true;
            } else {
                // Positional argument
                $arguments[] = $token;
            }
        }

        return new ParsedPrompt($name, $arguments);
    }

    /**
     * Tokenize input string, handling quoted strings.
     *
     * @param string $input
     * @return array<string>
     */
    private static function tokenize(string $input): array
    {
        // Match quoted strings or non-space sequences
        preg_match_all('/(?:"[^"]*"|\'[^\']*\'|[^\s]+)/', $input, $matches);

        // Remove quotes from matched strings
        return array_map(
            fn($token) => trim($token, '"\''),
            $matches[0]
        );
    }
}
