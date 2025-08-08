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
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-13.9.99',
            'news' => '11.0.0-12.9.99',
            'blog' => '11.0.2-13.9.9',
            'md_news_author' => '7.0.0-8.9.99',
            'ns_license' => '1.2.0-13.9.99',
            'rte_ckeditor_image'=>'11.0.2-13.9.99',
            'php' => '7.4.0 - 8.3.99'
        ],

        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'classmap' => ['Classes/', 'Library/']
    ]
];
