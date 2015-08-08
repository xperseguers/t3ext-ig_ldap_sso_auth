<?php
defined('TYPO3_MODE') or die();

$tempColumns = array(
    'tx_igldapssoauth_dn' => array(
        'exclude' => 1,
        'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:be_users.tx_igldapssoauth_dn',
        'config' => array(
            'type' => 'input',
            'size' => 30,
        )
    ),
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('be_users', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes('be_users', 'tx_igldapssoauth_dn;;;;1-1-1');
