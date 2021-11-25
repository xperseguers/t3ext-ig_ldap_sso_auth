<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    // Configuration of authentication service
    $EXT_CONFIG = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$_EXTKEY] ?? [];

    // SSO configuration
    if ((bool)$EXT_CONFIG['enableFESSO']) {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
    }
    if ((bool)$EXT_CONFIG['enableBESSO']) {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = 1;
    }

    // Visually change the record icon for FE/BE users and groups
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Imaging\IconFactory::class]['overrideIconOverlay'][] = \Causal\IgLdapSsoAuth\Hooks\IconFactory::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][] = \Causal\IgLdapSsoAuth\Hooks\DatabaseRecordListIconUtility::class;

    // Service configuration
    $subTypesArr = [];
    $subTypes = '';
    if ($EXT_CONFIG['enableFELDAPAuthentication']) {
        $subTypesArr[] = 'getUserFE';
        $subTypesArr[] = 'authUserFE';
        $subTypesArr[] = 'getGroupsFE';
    }
    if ($EXT_CONFIG['enableBELDAPAuthentication']) {
        $subTypesArr[] = 'getUserBE';
        $subTypesArr[] = 'authUserBE';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'][] = \Causal\IgLdapSsoAuth\Hooks\SetupModuleController::class . '->preprocessData';
    }
    if (is_array($subTypesArr)) {
        $subTypesArr = array_unique($subTypesArr);
        $subTypes = implode(',', $subTypesArr);
    }

    // Register hook for \TYPO3\CMS\Core\DataHandling\DataHandler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = \Causal\IgLdapSsoAuth\Hooks\DataHandler::class;

    // Register the import users Scheduler task
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Causal\IgLdapSsoAuth\Task\ImportUsers::class] = [
        'extension' => $_EXTKEY,
        'title' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.import_users.title',
        'description' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang.xlf:task.import_users.description',
        'additionalFields' => \Causal\IgLdapSsoAuth\Task\ImportUsersAdditionalFields::class
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        $_EXTKEY,
        'auth' /* sv type */,
        \Causal\IgLdapSsoAuth\Service\AuthenticationService::class, /* sv key */
        [
            'title' => 'Authentication service',
            'description' => 'Authentication service for LDAP and SSO environment.',

            'subtype' => $subTypes,

            'available' => true,
            'priority' => 80,
            'quality' => 80,

            'os' => '',
            'exec' => '',

            'className' => \Causal\IgLdapSsoAuth\Service\AuthenticationService::class,
        ]
    );

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1553520893] = [
        'nodeName' => 'ldapSuggest',
        'priority' => 40,
        'class' => \Causal\IgLdapSsoAuth\Form\Element\LdapSuggestElement::class,
    ];

    // Register type converters
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\Causal\IgLdapSsoAuth\Property\TypeConverter\ConfigurationConverter::class);

    // User have save doc new button
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_igldapssoauth_config=1');
})('ig_ldap_sso_auth');
