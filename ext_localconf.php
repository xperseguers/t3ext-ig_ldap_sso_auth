<?php

if (!defined ('TYPO3_MODE')) 	die ('Access denied.');

//$TYPO3_CONF_VARS['SVCONF']['auth'];

// iglib class require
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_auth.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_config.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_ldap_group.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_ldap_user.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_ldap.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_typo3_group.php');
require_once(t3lib_extMgm::extPath($_EXTKEY).'lib/class.tx_igldapssoauth_typo3_user.php');
require_once(PATH_iglib.'class.iglib_ldap.php');
require_once(PATH_iglib.'class.iglib_db.php');
require_once(PATH_iglib.'CAS/CAS.php');

// Configuration of authentication service.
$EXT_CONFIG = unserialize($TYPO3_CONF_VARS['EXT']['extConf']['ig_ldap_sso_auth']);

// SSO configuration

//extend tslib_feUserAuth
if ($EXT_CONFIG['enableFECASAuthentication']) {
    //$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/class.tslib_feuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY)."sv1/class.ux_tslib_feuserauth.php";
   $TYPO3_CONF_VARS['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = 1;
}

//extend t3lib_beUserAuth
//if ($EXT_CONFIG['enableBECASAuthentication']) {
    //$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_beuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY)."sv1/class.ux_t3lib_beuserauth.php";
    //$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = 1;
//}

// Service configuration
//$TYPO3_CONF_VARS['SVCONF']['auth']['tx_igldapssoauth_sv1']['Test'] = 'ALLO';

if ($EXT_CONFIG['enableFELDAPAuthentication'] && !$EXT_CONFIG['enableBELDAPAuthentication']) {
	$subTypes = 'getUserFE,authUserFE,getGroupsFE';
}

if (!$EXT_CONFIG['enableFELDAPAuthentication'] && $EXT_CONFIG['enableBELDAPAuthentication']) {
	$subTypes = 'getUserBE,authUserBE';
	$TYPO3_CONF_VARS['BE']['loginSecurityLevel'] = 'normal';
}

if ($EXT_CONFIG['enableFELDAPAuthentication'] && $EXT_CONFIG['enableBELDAPAuthentication']) {
	$subTypes = 'getUserFE,authUserFE,getGroupsFE,getUserBE,authUserBE';
	$TYPO3_CONF_VARS['BE']['loginSecurityLevel'] = 'normal';
}

t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_igldapssoauth_sv1' /* sv key */,
		array(

			'title' => 'Authentication service',
			'description' => 'Authentication service for LDAP and SSO environement.',

			'subtype' => $subTypes,

			'available' => TRUE,
			'priority' => 50,
			'quality' => 50,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY).'sv1/class.tx_igldapssoauth_sv1.php',
			'className' => 'tx_igldapssoauth_sv1',
		)
	);

// User have save doc new bouton
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tx_igldapssoauth_config=1');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_igldapssoauth_pi1.php','_pi1','list_type',1);

?>