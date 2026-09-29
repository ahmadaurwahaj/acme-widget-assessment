<?php

declare(strict_types=1);

$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/src', __DIR__ . '/tests', __DIR__ . '/config', __DIR__ . '/public']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS3x0' => true,
        '@PHP8x4Migration' => true,
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'ordered_imports' => ['imports_order' => ['class', 'function', 'const']],
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
