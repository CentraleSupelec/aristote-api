<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude(['var', 'vendor'])
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        'no_leading_import_slash' => true,
        'global_namespace_import' => true,
    ])
    ->setFinder($finder)
;
