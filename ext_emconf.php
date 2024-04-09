<?php
$EM_CONF['ns_wp_migration'] = [
    'title' => '[Nitsan] WP Migration',
    'description' => 'Plug-n-play TYPO3 extension to migrate wordpress post and page data to your TYPO3 site. This extension includes features like Migrate post to EXT:news or EXT:blog, migrate categories, migrate tags, migrate media files etc.',
    'category' => 'be',
    'author' => 'T3: Navdeepsinh Jethwa',
    'author_email' => 'sanjay@nitsan.in',
    'author_company' => 'NITSAN Technologies Pvt Ltd',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-12.5.99',
            'news' => '11.0.0-11.4.99',
            'blog' => '11.0.2-12.0.2',
            'md_news_author' => '7.0.0-7.0.2',
            'rte_ckeditor_image' => '11.0.0-12.4.99',
            'ns_license' => '1.0.0-12.1.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'classmap' => ['Classes/', 'Library/']
    ]
];
