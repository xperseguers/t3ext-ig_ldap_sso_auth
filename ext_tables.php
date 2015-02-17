<?php
defined('TYPO3_MODE') or die();

$icons = array(
	'overlay-ldap-record' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/overlay-ldap-record.png',
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $_EXTKEY);

$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'][] = 'is_ldap_record';
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames']['is_ldap_record'] = 'extensions-' . $_EXTKEY . '-overlay-ldap-record';

// Add BE module on top of tools main module
if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule('tools', 'txigldapssoauthM1', 'top', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'mod1/');
}

// Initialize "context sensitive help" (csh)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_igldapssoauth_config', 'EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_csh_db.xml');
