<?php

$EM_CONF['ns_wp_migration'] = [
    'title' => 'WP Migration',
    'description' => 'Plug-n-play TYPO3 extension to migrate wordpress page and page data to your TYPO3 site.',
    'category' => 'be',
    'author' => 'T3: Navdeepsinh Jethwa',
    'author_email' => 'sanjay@nitsan.in',
    'author_company' => 'T3Planet // NITSAN',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.1.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-13.9.99',
            'news' => '11.0.0-12.9.99',
            'blog' => '11.0.2-13.9.9',
            'md_news_author' => '7.0.0-8.9.99',
            'ns_license' => '1.2.0-13.9.99',
            'rte-ckeditor-image'=>'11.0.2-13.9.99',
            'php' => '7.4.0 - 8.3.99'
        ],

        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'classmap' => ['Classes/', 'Library/']
    ]
];
