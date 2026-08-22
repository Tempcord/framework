<?php

declare(strict_types=1);

use Tempcord\Tools\DocsGenerator;

require __DIR__ . '/../vendor/autoload.php';

$root = dirname(__DIR__);
$output = $root . '/docs';

$generator = new DocsGenerator($root);
$files = $generator->generate();

$generator->write($output);

echo 'Wrote ' . count($files) . " files to docs/\n";

foreach (array_keys($files) as $path) {
    echo '  ' . $path . "\n";
}
