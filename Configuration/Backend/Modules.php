<?php

defined('TYPO3') or die();

return [
	// Extbase variant.
	'txigldapssoauthM1' => [
		'parent' => 'system',
		'access' => 'admin',
		'iconIdentifier' => 'ig_ldap_sso_auth_module',
		'path' => '/module/system/txigldapssoauthM1',
		'labels' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf',
		'extensionName' => 'IgLdapSsoAuth',
		'controllerActions' => [
			\Causal\IgLdapSsoAuth\Controller\ModuleController::class => [
				'index',
				'status',
				'search',
				'importFrontendUsers',
				'importBackendUsers',
				'importFrontendUserGroups',
				'importBackendUserGroups',
			],
		],
	],
];
