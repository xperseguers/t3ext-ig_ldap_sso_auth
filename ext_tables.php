<?php
defined('TYPO3') || die();

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
})('ig_ldap_sso_auth');
