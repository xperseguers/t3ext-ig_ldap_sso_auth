<?php
defined('TYPO3_MODE') || die();

call_user_func(function () {
    // Register additional sprite icons

    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'extensions-ig_ldap_sso_auth-overlay-ldap-record',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/overlay-ldap-record.png',
        ]
    );
    unset($iconRegistry);

    if (TYPO3_MODE === 'BE') {
        $icon = 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/module-ldap.png';

        // Add BE module on top of system main module
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            'Causal.ig_ldap_sso_auth',
            'system',
            'txigldapssoauthM1',
            'top',
            [
                'Module' => implode(',', [
                    'index',
                    'status',
                    'search', 'updateSearchAjax', 'searchAjax',
                    'importFrontendUsers', 'importBackendUsers', 'importUsersAjax',
                    'importFrontendUserGroups', 'importBackendUserGroups', 'importUserGroupsAjax',
                ]),
            ],
            [
                'access' => 'admin',
                'icon' => $icon,
                'labels' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf'
            ]

        );
    }
    // Initialize "context sensitive help" (csh)
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
        'tx_igldapssoauth_config', 'EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_csh_db.xlf'
    );
});