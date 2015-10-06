<?php
defined('TYPO3_MODE') || die();

$icons = array(
    'overlay-ldap-record' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath($_EXTKEY) . 'Resources/Public/Icons/overlay-ldap-record.png',
);
\TYPO3\CMS\Backend\Sprite\SpriteManager::addSingleIcons($icons, $_EXTKEY);

$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayPriorities'][] = 'is_ldap_record';
$GLOBALS['TBE_STYLES']['spriteIconApi']['spriteIconRecordOverlayNames']['is_ldap_record'] = 'extensions-' . $_EXTKEY . '-overlay-ldap-record';

if (TYPO3_MODE === 'BE') {
    if (version_compare(TYPO3_version, '7.0', '<')) {
        $icon = 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-ldap-62.png';
    } else {
        $icon = 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/module-ldap.png';
    }

    // Add BE module on top of system main module
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'Causal.' . $_EXTKEY,
        'system',
        'txigldapssoauthM1',
        'top',
        array(
            'Module' => implode(',', array(
                'index',
                'status',
                'search', 'updateSearchAjax', 'searchAjax',
                'importFrontendUsers', 'importBackendUsers', 'importUsersAjax',
                'importFrontendUserGroups', 'importBackendUserGroups', 'importUserGroupsAjax',
            )),
        ), array(
            'access' => 'admin',
            'icon' => $icon,
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf'
        )
    );
}

// Initialize "context sensitive help" (csh)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr('tx_igldapssoauth_config', 'EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_csh_db.xlf');
