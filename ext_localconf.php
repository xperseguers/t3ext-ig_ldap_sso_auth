<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    // Configuration of authentication service
    $config = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get($_EXTKEY);
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();

    // SSO configuration
    if ($config['enableFESSO'] ?? false) {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
    }
    if ($config['enableBESSO'] ?? false) {
        $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['BE_fetchUserIfNoSession'] = 1;
    }

    if ($typo3Version < 13) {
        // Visually change the record icon for FE/BE users and groups
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Imaging\IconFactory::class]['overrideIconOverlay'][]
            = \Causal\IgLdapSsoAuth\Hooks\IconFactory::class;
    }

    if ($typo3Version < 12) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/class.db_list_extra.inc']['getTable'][]
            = \Causal\IgLdapSsoAuth\Hooks\DatabaseRecordListIconUtility::class;
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Lowlevel\Controller\ConfigurationController::class]['modifyBlindedConfigurationOptions'][] = \Causal\IgLdapSsoAuth\Hooks\BlindedConfigurationOptionsHook::class;

    // Service configuration
    $subTypesArr = [];
    $subTypes = '';
    if ($config['enableFELDAPAuthentication'] ?? false) {
        $subTypesArr[] = 'getUserFE';
        $subTypesArr[] = 'authUserFE';
    }
    if ($config['enableBELDAPAuthentication'] ?? false) {
        $subTypesArr[] = 'getUserBE';
        $subTypesArr[] = 'authUserBE';

        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/setup/mod/index.php']['modifyUserDataBeforeSave'][]
            = \Causal\IgLdapSsoAuth\Hooks\SetupModuleController::class . '->preprocessData';
    }
    if (is_array($subTypesArr)) {
        $subTypesArr = array_unique($subTypesArr);
        $subTypes = implode(',', $subTypesArr);
    }

    // Register hook for \TYPO3\CMS\Core\DataHandling\DataHandler
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \Causal\IgLdapSsoAuth\Hooks\DataHandler::class;

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
        'class' => \Causal\IgLdapSsoAuth\Backend\Form\Element\LdapSuggestElement::class,
    ];

    if ($typo3Version < 12) {
        // Register type converters
        \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\Causal\IgLdapSsoAuth\Property\TypeConverter\ConfigurationConverter::class);
    }

    if ($typo3Version < 13) {
        // User have save doc new button
        \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('options.saveDocNew.tx_igldapssoauth_config=1');
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][\Causal\IgLdapSsoAuth\Update\MigrateSchedulerTasks::class]
        = \Causal\IgLdapSsoAuth\Update\MigrateSchedulerTasks::class;
})('ig_ldap_sso_auth');
