<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$config = (new Config())
    ->setFinder(
        Finder::create()
            ->in(__DIR__ . '/src')
            ->in(__DIR__ . '/bin')
            ->in(__DIR__ . '/tests')
            ->append([
                __FILE__,
            ])
            ->exclude([
                'Fixtures',
            ]),
    )
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setCacheFile(__DIR__ . '/' . basename(__FILE__) . '.cache')
    ->setRules([
        '@PSR12' => true,
    ]);

return $config;
