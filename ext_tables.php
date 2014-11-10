<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

// Init table configuration array for tx_igldapssoauth_config.
$GLOBALS['TCA']['tx_igldapssoauth_config'] = array(
	'ctrl' => array(
		'title' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config',
		'label' => 'name',
		'sortby' => 'sorting',
		'adminOnly' => 1,
		'rootLevel' => 1,
		'dividers2tabs'=> TRUE,
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
		),
		'requestUpdate' => 'ldap_server',
		'dynamicConfigFile' => t3lib_extMgm::extPath($_EXTKEY) . 'Configuration/TCA/Config.php',
		'iconfile' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/icon_tx_igldapssoauth_config.png',
	),
);

// Add fields tx_igldapssoauth_dn to be_groups TCA.
$tempColumns = array(
	'tx_igldapssoauth_dn' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups.tx_igldapssoauth_dn',
		'config' => array(
			'type' => 'input',
			'size' => 30,
		)
	),
);

if (version_compare(TYPO3_version, '6.1.0', '<')) {
	t3lib_div::loadTCA('be_groups');
}
t3lib_extMgm::addTCAcolumns('be_groups', $tempColumns);
t3lib_extMgm::addToAllTCAtypes('be_groups', 'tx_igldapssoauth_dn;;;;1-1-1');

//// Add fields tx_igldapssoauth_dn to be_users TCA.
//$tempColumns = array(
//	'tx_igldapssoauth_dn' => array(
//		'exclude' => 1,
//		'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:be_users.tx_igldapssoauth_dn',
//		'config' => array(
//			'type' => 'input',
//			'size' => '30',
//		)
//	),
//);
//
//if (version_compare(TYPO3_version, '6.1.0', '<')) {
//	t3lib_div::loadTCA('be_users');
//}
//t3lib_extMgm::addTCAcolumns('be_users', $tempColumns);
//t3lib_extMgm::addToAllTCAtypes('be_users', 'tx_igldapssoauth_dn;;;;1-1-1');

// Add fields tx_igldapssoauth_dn to fe_groups TCA.
$tempColumns = array(
	'tx_igldapssoauth_dn' => array(
		'exclude' => 1,
		'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_groups.tx_igldapssoauth_dn',
		'config' => array(
			'type' => 'input',
			'size' => '30',
		)
	),
);

if (version_compare(TYPO3_version, '6.1.0', '<')) {
	t3lib_div::loadTCA('fe_groups');
}
t3lib_extMgm::addTCAcolumns('fe_groups', $tempColumns);
t3lib_extMgm::addToAllTCAtypes('fe_groups', 'tx_igldapssoauth_dn');

//// Add fields tx_igldapssoauth_dn to fe_users TCA.
//$tempColumns = array(
//	'tx_igldapssoauth_dn' => Array (
//		'exclude' => 1,
//		'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:fe_users.tx_igldapssoauth_dn',
//		'config' => array(
//			'type' => 'input',
//			'size' => '30',
//		)
//	),
//);
//
//if (version_compare(TYPO3_version, '6.1.0', '<')) {
//	t3lib_div::loadTCA('fe_users');
//}
//t3lib_extMgm::addTCAcolumns('fe_users', $tempColumns);
//t3lib_extMgm::addToAllTCAtypes('fe_users', 'tx_igldapssoauth_dn;;;;1-1-1');

$icons = array(
	'overlay-ldap-record' => t3lib_extMgm::extRelPath($_EXTKEY) . 'Resources/Public/Icons/overlay-ldap-record.png',
);
t3lib_SpriteManager::addSingleIcons($icons, $_EXTKEY);

$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'][] = 'is_ldap_record';
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames']['is_ldap_record'] = 'extensions-' . $_EXTKEY . '-overlay-ldap-record';

// Alter users TCA
$GLOBALS['EXT_CONFIG']['enableBELDAPAuthentication'] ? $GLOBALS['TCA']['be_users']['columns']['password']['config']['eval'] = 'md5,password' : null;
$GLOBALS['EXT_CONFIG']['enableFELDAPAuthentication'] ? $GLOBALS['TCA']['fe_users']['columns']['password']['config']['eval'] = 'password': null;
// Load TCA.


// Add BE module on top of tools main module
if (TYPO3_MODE === 'BE') {
	t3lib_extMgm::addModule('tools', 'txigldapssoauthM1', 'top', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}

// Initialize "context sensitive help" (csh)
t3lib_extMgm::addLLrefForTCAdescr('tx_igldapssoauth_config', 'EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_csh_db.xml');
