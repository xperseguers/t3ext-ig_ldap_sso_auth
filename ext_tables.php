<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    // Register additional sprite icons
    /** @var \TYPO3\CMS\Core\Imaging\IconRegistry $iconRegistry */
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon('extensions-ig_ldap_sso_auth-overlay-ldap-record',
        \TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider::class,
        [
            'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/overlay-ldap-record.png',
        ]
    );
    unset($iconRegistry);

    if (TYPO3_MODE === 'BE') {
        // Add BE module on top of system main module
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
            $_EXTKEY,
            'system',
            'txigldapssoauthM1',
            'top',
            [
                \Causal\IgLdapSsoAuth\Controller\ModuleController::class => implode(',', [
                    'index',
                    'status',
                    'search',
                    'importFrontendUsers', 'importBackendUsers',
                    'importFrontendUserGroups', 'importBackendUserGroups',
                ]),
            ], [
                'access' => 'admin',
                'icon' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-ldap.png',
                'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf'
            ]
        );
    }

    // Initialize "context sensitive help" (csh)
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_igldapssoauth_config', 'EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_csh_db.xlf');
})('ig_ldap_sso_auth');
