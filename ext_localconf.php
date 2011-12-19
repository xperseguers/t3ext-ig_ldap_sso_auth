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
	require_once(t3lib_extMgm::extPath($_EXTKEY) . 'framework/CAS/CAS.php');

	if($EXT_CONFIG['enableFetchUserIfNoSession']){
   		$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
	}
	else{
		$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['FE_alwaysFetchUser'] = 1;
	}
}

//extend t3lib_beUserAuth
//if ($EXT_CONFIG['enableBECASAuthentication']) {
    //$TYPO3_CONF_VARS['BE']['XCLASS']['t3lib/class.t3lib_beuserauth.php'] = t3lib_extMgm::extPath($_EXTKEY)."sv1/class.ux_t3lib_beuserauth.php";
    //$TYPO3_CONF_VARS['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = 1;
//}

// Service configuration
//$TYPO3_CONF_VARS['SVCONF']['auth']['tx_igldapssoauth_sv1']['Test'] = 'ALLO';
if($EXT_CONFIG['enableFELDAPAuthentication']){
	$subTypesArr[] = 'getUserFE';
	$subTypesArr[] ='authUserFE';
	$subTypesArr[] ='getGroupsFE';
}
if($EXT_CONFIG['enableBELDAPAuthentication']){
	$subTypesArr[] = 'getUserBE';
	$subTypesArr[] = 'authUserBE';
}
if($EXT_CONFIG['enableFECASAuthentication']){
	$subTypesArr[] = 'getUserFE';
	$subTypesArr[] ='authUserFE';
}
if(is_array($subTypesArr)){
	$subTypesArr = array_unique($subTypesArr);
	$subTypes = implode(',',$subTypesArr);
}



$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks']['tx_igldapssoauth_scheduler_synchroniseusers'] = array(
    'extension'        => $_EXTKEY,
    'title'            => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:synchro.name',
    'description'      => 'LLL:EXT:' . $_EXTKEY . '/locallang.xml:synchro.description'
);

t3lib_extMgm::addService($_EXTKEY,  'auth' /* sv type */,  'tx_igldapssoauth_sv1' /* sv key */,
		array(

			'title' => 'Authentication service',
			'description' => 'Authentication service for LDAP and SSO environement.',

			'subtype' => $subTypes,

			'available' => TRUE,
			'priority' => 100,
			'quality' => 100,

			'os' => '',
			'exec' => '',

			'classFile' => t3lib_extMgm::extPath($_EXTKEY) . 'sv1/class.tx_igldapssoauth_sv1.php',
			'className' => 'tx_igldapssoauth_sv1',
		)
	);

// User have save doc new bouton
t3lib_extMgm::addUserTSConfig('options.saveDocNew.tx_igldapssoauth_config=1');

t3lib_extMgm::addPItoST43($_EXTKEY,'pi1/class.tx_igldapssoauth_pi1.php','_pi1','list_type',1);

?>