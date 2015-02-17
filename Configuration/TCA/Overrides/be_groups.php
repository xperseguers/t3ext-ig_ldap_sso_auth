<?php
defined('TYPO3_MODE') or die();

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

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_groups', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_groups', 'tx_igldapssoauth_dn;;;;1-1-1');
