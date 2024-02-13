<?php
$EM_CONF['ns_wp_migration'] = [
    'title' => '[Nitsan] WP Migration',
    'description' => 'Plug-n-play TYPO3 extension to migrate wordpress post and page data to your TYPO3 site. This extension includes features like Migrate post to EXT:news or EXT:blog, migrate categories, migrate tags, migrate media files etc.',
    'category' => 'be',
    'author' => 'Navdeepsinh Jethwa',
    'author_email' => 'sanjay@nitsan.in',
    'author_company' => 'NITSAN Technologies Pvt Ltd',
    'state' => 'stable',
    'clearCacheOnLoad' => 0,
    'version' => '11.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '11.5.0-11.5.99',
            'news' => '11.0.0-11.4.1',
            'blog' => '11.0.2-12.0.2',
            'md_news_author' => '7.0.0-7.0.2',
            'ns_basetheme'=> '11.5.8-11.5.10'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    'autoload' => [
        'classmap' => ['Classes/']
    ]
];
