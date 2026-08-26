<?php

namespace Tempcord\Tools;

/**
 * The structured half of the output, so a website can build navigation and
 * search without parsing prose.
 */
final readonly class JsonWriter
{
    /**
     * @param array<string, list<Symbol>> $groups
     * @param list<array{title: string, slug: string}> $guides
     */
    public function render(array $groups, array $guides): string
    {
        $reference = [];

        foreach ($groups as $group => $symbols) {
            $reference[$group] = array_map(
                static fn(Symbol $symbol) => [
                    'name' => $symbol->name,
                    'fqcn' => $symbol->fqcn,
                    'kind' => $symbol->kind,
                    'target' => $symbol->target,
                    'summary' => $symbol->summary,
                    'slug' => $symbol->slug,
                    'parameters' => array_map(
                        static fn(Parameter $parameter) => [
                            'name' => $parameter->name,
                            'type' => $parameter->type,
                            'default' => $parameter->default,
                            'required' => $parameter->isRequired(),
                            'summary' => $parameter->summary,
                        ],
                        $symbol->parameters,
                    ),
                    'cases' => $symbol->cases,
                    'methods' => $symbol->methods,
                ],
                $symbols,
            );
        }

        return json_encode(
            ['guides' => $guides, 'reference' => $reference],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) . "\n";
    }
}
