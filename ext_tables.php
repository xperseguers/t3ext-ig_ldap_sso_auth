<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    if ($typo3Version < 12) {
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
})('ig_ldap_sso_auth');
