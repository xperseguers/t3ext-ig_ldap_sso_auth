<?php
defined('TYPO3_MODE') || die();

call_user_func(function () {
    $tempColumns = [
        'tx_igldapssoauth_dn' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:fe_groups.tx_igldapssoauth_dn',
            'config' => [
                'type' => 'input',
                'size' => 30,
            ]
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('fe_groups', $tempColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('fe_groups', 'tx_igldapssoauth_dn');
});
