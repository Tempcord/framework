<?php

namespace Tempcord\Runtime;

use LogicException;

/**
 * A component's custom id, which may carry {placeholders}.
 *
 * Discord gives back exactly the string a component was built with, so state a
 * handler needs — which petition, which team — has to travel inside the id
 * itself. A pattern names those segments once, and both directions use it: the
 * dispatcher reads them out of an incoming id, and build() writes a concrete
 * id back out.
 */
final readonly class CustomId
{
    /**
     * @param list<string> $parameters the placeholder names, in the order they appear
     * @param string|null $regex null when the pattern has no placeholders and a
     *                           plain string comparison is enough
     */
    private function __construct(
        public string $pattern,
        public array $parameters,
        private ?string $regex,
    ) {}

    public static function compile(string $pattern): self
    {
        $segments = preg_split('/\{(\w+)\}/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            throw new LogicException('Could not read custom id pattern [' . $pattern . ']');
        }

        $parameters = [];
        $regex = '';

        foreach ($segments as $index => $segment) {
            // preg_split with DELIM_CAPTURE alternates literal, capture, literal...
            if ($index % 2 === 0) {
                $regex .= preg_quote($segment, '#');
                continue;
            }

            if (in_array($segment, $parameters, true)) {
                throw new LogicException(
                    'Custom id pattern [' . $pattern . '] declares {' . $segment . '} more than once',
                );
            }

            $parameters[] = $segment;

            /*
             * Lazy, so a placeholder stops at the literal that follows it; the
             * anchors still let a trailing one swallow the rest of the id.
             */
            $regex .= '(?P<' . $segment . '>.+?)';
        }

        return new self(
            pattern: $pattern,
            parameters: $parameters,
            regex: $parameters === [] ? null : '#\A' . $regex . '\z#s',
        );
    }

    public function isLiteral(): bool
    {
        return $this->regex === null;
    }

    /**
     * The placeholder values in the given id, or null when it does not match.
     * A literal pattern that matches yields an empty array.
     *
     * @return array<string, string>|null
     */
    public function match(string $customId): ?array
    {
        if ($this->regex === null) {
            return $customId === $this->pattern ? [] : null;
        }

        if (preg_match($this->regex, $customId, $matches) !== 1) {
            return null;
        }

        $values = [];

        foreach ($this->parameters as $parameter) {
            $values[$parameter] = $matches[$parameter];
        }

        return $values;
    }

    /**
     * The concrete custom id for these placeholder values, to hand to a button
     * or select menu being built.
     *
     * @param array<string, string|int|float|\BackedEnum> $values
     */
    public function build(array $values = []): string
    {
        $replacements = [];

        foreach ($this->parameters as $parameter) {
            if (!array_key_exists($parameter, $values)) {
                throw new LogicException(
                    'Custom id pattern [' . $this->pattern . '] needs a value for {' . $parameter . '}',
                );
            }

            $value = $values[$parameter];

            $replacements['{' . $parameter . '}'] = $value instanceof \BackedEnum
                ? (string) $value->value
                : (string) $value;
        }

        return strtr($this->pattern, $replacements);
    }
}
