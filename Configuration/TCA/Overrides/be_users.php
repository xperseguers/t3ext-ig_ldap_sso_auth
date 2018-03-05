<?php
defined('TYPO3_MODE') || die();

call_user_func(function () {
    $tempColumns = [
        'tx_igldapssoauth_dn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:be_users.tx_igldapssoauth_dn',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ]
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_igldapssoauth_dn');
});
