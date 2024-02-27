<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'array_syntax' => ['syntax' => 'short'],
        'phpdoc_annotation_without_dot' => false,
        'phpdoc_summary' => false,
        'yoda_style' => true,
        'single_trait_insert_per_statement' => false,
    ])
    ->setFinder($finder)
;
