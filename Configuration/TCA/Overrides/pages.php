<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

$tempColumns = [
    'tx_bgmhreflang_1' => [
        'exclude' => 0,
        'l10n_mode' => 'exclude',
        'label' => 'LLL:EXT:bgm_hreflang/Resources/Private/Language/Backend.xlf:pages.tx_bgmhreflang_1',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'foreign_table' => 'pages',
            'MM' => 'tx_bgmhreflang_page_page_mm',
            'MM_match_fields' => [
                'tablenames' => 'pages',
            ],
            'MM_insert_fields' => [
                'tablenames' => 'pages',
            ],
            'size' => 6,
            'autoSizeMax' => 30,
            'minitems' => 0,
            'maxitems' => 9999,
            'suggestOptions' => [
                'default' => [
                    'searchWholePhrase' => 1,
                ],
            ],
        ],
    ],
    'tx_bgmhreflang_2' => [
        'exclude' => 0,
        'l10n_mode' => 'exclude',
        'label' => 'LLL:EXT:bgm_hreflang/Resources/Private/Language/Backend.xlf:pages.tx_bgmhreflang_2',
        'config' => [
            'type' => 'group',
            'internal_type' => 'db',
            'allowed' => 'pages',
            'foreign_table' => 'pages',
            'MM' => 'tx_bgmhreflang_page_page_mm',
            'MM_match_fields' => [
                'tablenames' => 'pages',
            ],
            'MM_insert_fields' => [
                'tablenames' => 'pages',
            ],
            'MM_opposite_field' => 'tx_bgmhreflang_1',
            'size' => 6,
            'autoSizeMax' => 30,
            'minitems' => 0,
            'maxitems' => 9999,
            'fieldControl' => [
                'elementBrowser' => [
                    'disabled' => true,
                ],
            ],
        ],
    ],
    'tx_bgmhreflang_list' => [
        'exclude' => 0,
        'l10n_mode' => 'exclude',
        'label' => 'LLL:EXT:bgm_hreflang/Resources/Private/Language/Backend.xlf:pages.tx_bgmhreflang_list',
        'config' => [
            'type' => 'none',
            'renderType' => 'bgmhreflangList',
        ],
    ],
];
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('pages', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addFieldsToPalette('pages', 'tx_bgmhreflang', 'tx_bgmhreflang_1,--linebreak--,tx_bgmhreflang_2,--linebreak--,tx_bgmhreflang_list');
$GLOBALS['TCA']['pages']['palettes']['tx_bgmhreflang']['canNotCollapse'] = 1;
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('pages', '--palette--;LLL:EXT:bgm_hreflang/Resources/Private/Language/Backend.xlf:pages.palette.tx_bgmhreflang;tx_bgmhreflang;;', '', 'after:lastUpdated');

$GLOBALS['TCA']['pages']['ctrl']['setToDefaultOnCopy'] = ($GLOBALS['TCA']['pages']['ctrl']['setToDefaultOnCopy'] ? $GLOBALS['TCA']['pages']['ctrl']['setToDefaultOnCopy'] . ',' : '') . 'tx_bgmhreflang_1,tx_bgmhreflang_2';
