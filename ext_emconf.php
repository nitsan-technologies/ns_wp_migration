<?php

$EM_CONF[$_EXTKEY] = [
    'title' => '[NITSAN] WP Migration',
    'description' => '',
    'category' => 'be',
    'author' => '',
    'author_email' => '',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '9.0.0-11.5.99',
            'blog' => '9.0.0-11.0.2'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
