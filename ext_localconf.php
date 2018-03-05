<?php
defined('TYPO3_MODE') || die();

call_user_func(function () {
    // Configuration of authentication service.
    $EXT_CONFIG = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ig_ldap_sso_auth'];

    // SSO configuration
    if ((bool)$EXT_CONFIG['enableFESSO']) {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
    }
    if ((bool)$EXT_CONFIG['enableBESSO']) {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = 1;
    }

    // Visually change the record icon for FE/BE users and groups
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Imaging\IconFactory::class]['overrideIconOverlay'][1518110608]
        = \Causal\IgLdapSsoAuth\Hooks\IconFactory::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][1518110608]
        = \Causal\IgLdapSsoAuth\Hooks\DatabaseRecordListIconUtility::class;

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

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'][1518110608]
            = \Causal\IgLdapSsoAuth\Hooks\SetupModuleController::class . '->preprocessData';
    }
    if (is_array($subTypesArr)) {
        $subTypesArr = array_unique($subTypesArr);
        $subTypes = implode(',', $subTypesArr);
    }

    // Register hook for \TYPO3\CMS\Core\DataHandling\DataHandler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][1518110608]
        = \Causal\IgLdapSsoAuth\Hooks\DataHandler::class;

    // Register the import users Scheduler task
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\Causal\IgLdapSsoAuth\Task\ImportUsers::class] = [
        'extension' => 'ig_ldap_sso_auth',
        'title' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.title',
        'description' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.description',
        'additionalFields' => \Causal\IgLdapSsoAuth\Task\ImportUsersAdditionalFields::class
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addService(
        'ig_ldap_sso_auth',
        // sv type
        'auth',
        // sv key
        \Causal\IgLdapSsoAuth\Service\AuthenticationService::class,
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

    // Register type converters
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(
        \Causal\IgLdapSsoAuth\Property\TypeConverter\ConfigurationConverter::class
    );

    // User have save doc new button
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig(
        'options.saveDocNew.tx_igldapssoauth_config=1'
    );
});
