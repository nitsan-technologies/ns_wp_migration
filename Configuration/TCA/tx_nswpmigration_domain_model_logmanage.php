<?php

/*
 * This file is part of the package t3g/ns_wp_migration.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

$ll = 'LLL:EXT:ns_wp_migration/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_log_manage',
        'label' => 'uid',
        'label_alt' => 'sys_language_uid',
        // Display Language after Label
        'label_alt_force' => 0,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l18n_parent',
        'transOrigDiffSourceField' => 'l18n_diffsource',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'default_sortby' => 'ORDER BY uid',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        'typeicon_classes' => [
            'default' => 'record-blog-tag'
        ],
        'searchFields' => 'uid',
    ],
    'columns' => [
        'pid' => [
            'label' => 'pid',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'crdate' => [
            'label' => 'crdate',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'tstamp' => [
            'label' => 'tstamp',
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'hidden' => [
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'default' => 0,
            ],
        ],
        'number_of_records' => [
            'label' => $ll . 'tx_log_manage.number_of_records',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ],
        ],
        'total_success' => [
            'label' => $ll . 'tx_log_manage.total_success',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'total_fails' => [
            'label' => $ll . 'tx_log_manage.total_fails',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'total_update' => [
            'label' => $ll . 'tx_log_manage.total_update',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'added_by' => [
            'label' => $ll . 'tx_log_manage.added_by',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        'redirect_json' => [
            'label' => $ll. 'tx_log_manage.added_by',
            'config' => [
                'type' => 'text'
            ]
         ],
        'records_log' => [
            'label' => $ll. 'tx_log_manage.records_log',
            'config' => [
                'type' => 'text'
            ]
         ],
        'created_date' => [
            'label' => $ll . 'tx_log_manage.created_date',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'required,trim',
            ]
        ],
        
    ],
    'palettes' => [
        'paletteCore' => [
            'showitem' => 'hidden,sys_language_uid,l18n_parent,l18n_diffsource',
            'canNotCollapse' => true,
        ],
    ],
];