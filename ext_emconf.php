<?php

$EM_CONF['ns_wp_migration'] = [
    'title' => 'TYPO3 WordPress Migration Tool',
    'description' => 'Easily migrate your WordPress pages and data into a TYPO3 website with this plug-and-play backend extension. Simplifies migration for posts, authors, media, and more.',    
    'category' => 'be',
    'author' => 'Team T3Planet',
    'author_email' => 'info@t3planet.de',
    'author_company' => 'T3Planet',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.5.99',
            'news' => '11.0.0-11.4.99',
            'blog' => '11.0.2-12.0.2',
            'md_news_author' => '7.0.0-7.0.2',
            'rte_ckeditor_image' => '11.0.0-12.4.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'classmap' => ['Classes/', 'Library/']
    ]
];
