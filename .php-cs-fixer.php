<?php

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
;

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER-CS' => true,

        // Remove extra blank lines in common places
        'no_extra_blank_lines' => [
            'tokens' => [
                'extra',
                'throw',
                'use',
                'use_trait',
                'continue',
                'break',
                'return',
                'parenthesis_brace_block',
                'curly_brace_block',
                'square_brace_block',
            ],
        ],

        // No blank line after opening PHP tag
        'no_blank_lines_after_phpdoc' => true,

        // Remove multiple blank lines
        'no_multiple_statements_per_line' => false,

        // Remove trailing whitespace
        'no_trailing_whitespace' => true,

        // Ensure single blank line at EOF
        'single_blank_line_at_eof' => true,

        // No whitespace in blank line
        'no_whitespace_in_blank_line' => true,

        // Compact class elements spacing
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
                'property' => 'one',
                'const' => 'one',
            ],
        ],

        // risky
        'declare_strict_types' => true,
        'no_unused_imports' => true,
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setIndent("\t");