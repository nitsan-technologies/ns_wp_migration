<?php

// return [
//     'imports' => [
//         '@nitsan/ns-wp-migration-jquery/' => 'EXT:ns_wp_migration/Resources/Public/JavaScript/Jquery.js',
//         '@nitsan/ns-wp-migration-main/' => 'EXT:ns_wp_migration/Resources/Public/JavaScript/Main.js',
//         '@nitsan/ns-wp-migration-datatable/' => 'EXT:ns_wp_migration/Resources/Public/JavaScript/Datatables.js',
//     ],
// ];


return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@nitsan/ns-wp-migration/' => 'EXT:ns_wp_migration/Resources/Public/JavaScript/',
    ],
];
