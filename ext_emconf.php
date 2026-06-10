<?php

declare(strict_types=1);

$EM_CONF[$_EXTKEY] = [
    'title' => 'Cache Flush Lock',
    'description' => 'Prevents backend cache flushes of protected cache groups in production-like contexts',
    'category' => 'misc',
    'state' => 'stable',
    'author' => 'Wolfgang Klinger',
    'author_email' => 'wolfgang@wazum.com',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'php' => '8.2.0-8.99.99',
            'typo3' => '13.4.0-14.3.99',
        ],
    ],
];
