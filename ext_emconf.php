<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cache Guard',
    'description' => 'Prevents backend cache flushes of protected cache groups in production-like contexts',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'Wolfgang Klinger',
    'author_email' => 'wolfgang@wazum.com',
    'version' => '1.0.1',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.99.99',
            'typo3' => '12.4.0-14.3.99',
        ],
    ],
];
