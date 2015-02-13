<?php
defined('TYPO3_MODE') or die();

// Configuration of authentication service.
$EXT_CONFIG = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);

// SSO configuration
if ((bool)$EXT_CONFIG['enableFESSO']) {
	$GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
}

// Visually change the record icon for FE/BE users and groups
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/DatabaseRecordListIconUtility.php:Tx_IgLdapSsoAuth_Hooks_DatabaseRecordListIconUtility';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/DatabaseRecordListIconUtility.php:Tx_IgLdapSsoAuth_Hooks_DatabaseRecordListIconUtility';

// Service configuration
$subTypesArr = array();
$subTypes = '';
if ($EXT_CONFIG['enableFELDAPAuthentication']) {
	$subTypesArr[] = 'getUserFE';
	$subTypesArr[] = 'authUserFE';
	$subTypesArr[] = 'getGroupsFE';
}
if ($EXT_CONFIG['enableBELDAPAuthentication']) {
	$subTypesArr[] = 'getUserBE';
	$subTypesArr[] = 'authUserBE';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/SetupModuleController.php:Tx_IgLdapSsoAuth_Hooks_SetupModuleController->preprocessData';
}
if (is_array($subTypesArr)) {
	$subTypesArr = array_unique($subTypesArr);
	$subTypes = implode(',', $subTypesArr);
}

// Register hook for \TYPO3\CMS\Core\DataHandling\DataHandler
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/DataHandler.php:Tx_IgLdapSsoAuth_Hooks_DataHandler';

// Register the import users Scheduler task
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['Tx_IgLdapSsoAuth_Task_ImportUsers'] = array(
	'extension'			=> $_EXTKEY,
	'title'				=> 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:task.import_users.title',
	'description'		=> 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:task.import_users.description',
	'additionalFields'	=> 'Tx_IgLdapSsoAuth_Task_ImportUsersAdditionalFields'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService($_EXTKEY,  'auth' /* sv type */,  'tx_igldapssoauth_sv1' /* sv key */,
	array(
		'title' => 'Authentication service',
		'description' => 'Authentication service for LDAP and SSO environment.',

		'subtype' => $subTypes,

		'available' => TRUE,
		'priority' => 80,
		'quality' => 80,

		'os' => '',
		'exec' => '',

		'className' => 'tx_igldapssoauth_sv1',
	)
);

// User have save doc new button
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_igldapssoauth_config=1');
