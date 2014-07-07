<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Configuration of authentication service.
$EXT_CONFIG = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['ig_ldap_sso_auth']);

// SSO configuration

//extend tslib_feUserAuth
if ($EXT_CONFIG['enableFECASAuthentication']) {
	// iglib class require
	require_once(t3lib_extMgm::extPath($_EXTKEY) . 'Classes/CAS/CAS.php');

	if($EXT_CONFIG['enableFetchUserIfNoSession']){
		$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
	}
	else{
		$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = 1;
	}
}

// Visually change the record icon for FE/BE users and groups
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_iconworks.php']['overrideIconOverlay'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/DatabaseRecordListIconUtility.php:Tx_IgLdapSsoAuth_Hooks_DatabaseRecordListIconUtility';
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/DatabaseRecordListIconUtility.php:Tx_IgLdapSsoAuth_Hooks_DatabaseRecordListIconUtility';

//extend t3lib_beUserAuth
//if ($EXT_CONFIG['enableBECASAuthentication']) {
    //$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_beuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY)."sv1/class.ux_t3lib_beuserauth.php";
    //$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = 1;
//}

// Service configuration
if ($EXT_CONFIG['enableFELDAPAuthentication']) {
	$subTypesArr[] = 'getUserFE';
	$subTypesArr[] ='authUserFE';
	$subTypesArr[] ='getGroupsFE';
}
if ($EXT_CONFIG['enableBELDAPAuthentication']) {
	$subTypesArr[] = 'getUserBE';
	$subTypesArr[] = 'authUserBE';

	$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'][] = 'EXT:' . $_EXTKEY . '/Classes/Hooks/SetupModuleController.php:Tx_IgLdapSsoAuth_Hooks_SetupModuleController->preprocessData';
}
if ($EXT_CONFIG['enableFECASAuthentication']) {
	$subTypesArr[] = 'getUserFE';
	$subTypesArr[] ='authUserFE';
}
if (is_array($subTypesArr)) {
	$subTypesArr = array_unique($subTypesArr);
	$subTypes = implode(',',$subTypesArr);
}

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_igldapssoauth_scheduler_synchroniseusers'] = array(
	'extension'   => $_EXTKEY,
	'title'       => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:synchro.name',
	'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xml:synchro.description'
);

t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_igldapssoauth_sv1' /* sv key */,
	array(
		'title' => 'Authentication service',
		'description' => 'Authentication service for LDAP and SSO environement.',

		'subtype' => $subTypes,

		'available' => TRUE,
		'priority' => 80,
		'quality' => 80,

		'os' => '',
		'exec' => '',

		'classFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Service/Sv1.php',
		'className' => 'tx_igldapssoauth_sv1',
	)
);

// User have save doc new bouton
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tx_igldapssoauth_config=1');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_igldapssoauth_pi1.php','_pi1','list_type', 1);
