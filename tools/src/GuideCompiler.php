<?php

namespace Tempcord\Tools;

use RuntimeException;

/**
 * Turns guide sources into publishable pages.
 *
 * Guides carry no code of their own. Every example is pulled from a file the
 * test suite already compiles and exercises, so an example that stops being
 * true breaks the build rather than quietly misleading a reader — which is
 * exactly how the documentation this replaces came to describe a middleware
 * layer that never existed.
 */
final readonly class GuideCompiler
{
    private const string PATTERN = '/^<!--\s*include:\s*(\S+?)(?:\s+lines=(\d+)-(\d+))?\s*-->$/m';

    public function __construct(
        private string $root,
    ) {}

    /**
     * @return list<string> every path a guide includes
     */
    public function includedPaths(string $source): array
    {
        preg_match_all(self::PATTERN, $source, $matches);

        return $matches[1];
    }

    public function compile(string $source): string
    {
        return preg_replace_callback(
            self::PATTERN,
            function (array $match): string {
                $path = $match[1];
                $absolute = $this->root . '/' . $path;

                if (!is_file($absolute)) {
                    throw new RuntimeException(
                        'Guide includes "' . $path . '", which does not exist. '
                        . 'Examples must come from real files so they cannot go stale.',
                    );
                }

                $code = rtrim((string) file_get_contents($absolute));

                if (isset($match[2], $match[3])) {
                    $lines = explode("\n", $code);
                    $code = implode("\n", array_slice($lines, (int) $match[2] - 1, (int) $match[3] - (int) $match[2] + 1));
                }

                return "```php\n" . $this->stripPreamble($code) . "\n```\n\n"
                    . '<small>From [`' . $path . '`](../../' . $path . ') — compiled and exercised by the test suite.</small>';
            },
            $source,
        ) ?? $source;
    }

    /**
     * The opening tag and namespace are noise in a documentation example; the
     * imports are not, since they tell a reader what to write.
     */
    private function stripPreamble(string $code): string
    {
        $code = preg_replace('/^<\?php\s*\n/', '', $code) ?? $code;
        $code = preg_replace('/^namespace [^;]+;\s*\n/m', '', $code) ?? $code;

        return trim($code);
    }
}
