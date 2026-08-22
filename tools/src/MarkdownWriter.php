<?php

namespace Tempcord\Tools;

final readonly class MarkdownWriter
{
    private const string BANNER =
        "<!-- Generated from the source by `composer docs`. Do not edit by hand. -->\n\n";

    public function render(Symbol $symbol): string
    {
        $out = self::BANNER . '# ' . $symbol->name . "\n\n";

        if ($symbol->summary !== '') {
            $out .= $symbol->summary . "\n\n";
        }

        $out .= "```php\nuse " . $symbol->fqcn . ";\n```\n\n";

        if ($symbol->target !== null) {
            $out .= '**Applies to:** ' . $symbol->target . "\n\n";
        }

        if ($symbol->parameters !== []) {
            $out .= "## Parameters\n\n";
            $out .= "| Name | Type | Default | Description |\n";
            $out .= "| --- | --- | --- | --- |\n";

            foreach ($symbol->parameters as $parameter) {
                $out .= sprintf(
                    "| `%s` | `%s` | %s | %s |\n",
                    $parameter->name,
                    $this->escape($parameter->type),
                    $parameter->isRequired() ? '*required*' : '`' . $this->escape($parameter->default) . '`',
                    $this->escape($parameter->summary),
                );
            }

            $out .= "\n";
        }

        if ($symbol->methods !== []) {
            $out .= "## Methods\n\n";

            foreach ($symbol->methods as $method) {
                $out .= '### `' . $method['signature'] . "`\n\n";

                if ($method['summary'] !== '') {
                    $out .= $method['summary'] . "\n\n";
                }
            }
        }

        if ($symbol->cases !== []) {
            $out .= "## Cases\n\n| Case | Value |\n| --- | --- |\n";

            foreach ($symbol->cases as $case) {
                $out .= sprintf("| `%s` | `%s` |\n", $case['name'], $case['value']);
            }

            $out .= "\n";
        }

        return $out;
    }

    /**
     * @param array<string, list<Symbol>> $groups
     */
    public function renderIndex(array $groups): string
    {
        $out = self::BANNER . "# API reference\n\n";

        foreach ($groups as $group => $symbols) {
            $out .= '## ' . ucfirst($group) . "\n\n";

            foreach ($symbols as $symbol) {
                $out .= sprintf(
                    "- [%s](%s.md)%s\n",
                    $symbol->name,
                    str_replace('reference/', '', $symbol->slug),
                    $symbol->summary === '' ? '' : ' — ' . $this->escape($symbol->summary),
                );
            }

            $out .= "\n";
        }

        return $out;
    }

    /**
     * @param array<string, list<Symbol>> $groups
     * @param list<array{title: string, slug: string}> $guides
     */
    public function renderLanding(array $groups, array $guides): string
    {
        $out = self::BANNER . "# Tempcord documentation\n\n"
            . "Build Discord bots with PHP, on top of [Tempest](https://tempestphp.com).\n\n"
            . "## Guides\n\n";

        foreach ($guides as $guide) {
            $out .= sprintf("- [%s](%s.md)\n", $guide['title'], $guide['slug']);
        }

        $out .= "\n## Reference\n\n"
            . "Generated from the source, so it describes what the framework actually does.\n\n";

        foreach ($groups as $group => $symbols) {
            $out .= '**' . ucfirst($group) . "** — ";
            $out .= implode(', ', array_map(
                static fn(Symbol $symbol) => '[' . $symbol->name . '](' . $symbol->slug . '.md)',
                $symbols,
            ));
            $out .= "\n\n";
        }

        return $out;
    }

    /**
     * Pipes would break out of the table cell they sit in.
     */
    private function escape(string $text): string
    {
        return str_replace('|', '\\|', $text);
    }
}
