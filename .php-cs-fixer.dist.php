<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude('vendor')
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PER-CS2.0' => true,
        '@PER-CS1.0:risky' => true,
        // No trailing comma in multiline parameter lists: that syntax needs PHP >= 8.0, this project supports 7.4.
        'trailing_comma_in_multiline' => ['after_heredoc' => true, 'elements' => ['arguments', 'array_destructuring', 'arrays', 'match']],
    ])
    ->setFinder($finder)
;
