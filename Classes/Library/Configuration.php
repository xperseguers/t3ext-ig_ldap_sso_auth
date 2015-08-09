<?php
namespace Causal\IgLdapSsoAuth\Library;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Configuration for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Configuration
{

    const GROUP_MEMBERSHIP_FROM_GROUP = 1;
    const GROUP_MEMBERSHIP_FROM_MEMBER = 2;

    /**
     * @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration
     */
    protected static $configuration;

    protected static $mode;
    protected static $be = array();
    protected static $fe = array();
    protected static $ldap = array();
    protected static $domains = array();

    /**
     * Initializes the configuration class.
     *
     * @param string $typo3_mode
     * @param int $uid
     * @return void
     * @deprecated since 3.0, will be removed in 3.2, use initialize() instead
     */
    public static function init($typo3_mode = null, $uid)
    {
        GeneralUtility::logDeprecatedFunction();

        /** @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository */
        $configurationRepository = GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Domain\\Repository\\ConfigurationRepository');
        $configuration = $configurationRepository->findByUid($uid);
        static::initialize($typo3_mode, $configuration);
    }

    /**
     * Initializes the configuration class.
     *
     * @param string $mode TYPO3 mode, either 'be' or 'fe'
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     */
    public static function initialize($mode, \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration)
    {
        $globalConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
        if (!is_array($globalConfiguration)) {
            $globalConfiguration = array();
        }

        // Legacy configuration options
        unset($globalConfiguration['evaluateGroupsFromMembership']);

        static::$configuration = $configuration;

        // Default TYPO3_MODE is BE
        static::setMode($mode ?: TYPO3_MODE);

        // Select configuration from database, merge with extension configuration template and initialise class attributes.

        static::$domains = array();
        $domainUids = GeneralUtility::intExplode(',', $configuration->getDomains(), true);
        foreach ($domainUids as $domainUid) {
            $row = static::getDatabaseConnection()->exec_SELECTgetSingleRow('domainName', 'sys_domain', 'uid=' . intval($domainUid));
            static::$domains[] = $row['domainName'];
        }

        static::$be['LDAPAuthentication'] = (bool)$globalConfiguration['enableBELDAPAuthentication'];
        static::$be['SSOAuthentication'] = false;
        static::$be['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? (bool)$globalConfiguration['forceLowerCaseUsername'] : false;
        static::$be['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
        static::$be['IfUserExist'] = (bool)$globalConfiguration['TYPO3BEUserExist'];
        static::$be['IfGroupExist'] = (bool)$globalConfiguration['TYPO3BEGroupExist'];
        static::$be['BEfailsafe'] = (bool)$globalConfiguration['BEfailsafe'];
        static::$be['DeleteUserIfNoLDAPGroups'] = false;
        static::$be['DeleteUserIfNoTYPO3Groups'] = false;
        static::$be['GroupsNotSynchronize'] = (bool)$globalConfiguration['TYPO3BEGroupsNotSynchronize'];
        static::$be['requiredLDAPGroups'] = $configuration->getBackendGroupsRequired() ? $configuration->getBackendGroupsRequired() : array();
        static::$be['updateAdminAttribForGroups'] = $configuration->getBackendGroupsAdministrator() ? $configuration->getBackendGroupsAdministrator() : array();
        static::$be['assignGroups'] = $configuration->getBackendGroupsAssigned() ? $configuration->getBackendGroupsAssigned() : array();
        static::$be['keepTYPO3Groups'] = (bool)$globalConfiguration['keepBEGroups'];
        static::$be['users']['basedn'] = $configuration->getBackendUsersBaseDn();
        static::$be['users']['filter'] = $configuration->getBackendUsersFilter();
        static::$be['users']['mapping'] = static::makeUserMapping($configuration->getBackendUsersMapping(), $configuration->getBackendUsersFilter());
        static::$be['groups']['basedn'] = $configuration->getBackendGroupsBaseDn();
        static::$be['groups']['filter'] = $configuration->getBackendGroupsFilter();
        static::$be['groups']['mapping'] = static::makeGroupMapping($configuration->getBackendGroupsMapping());

        static::$fe['LDAPAuthentication'] = (bool)$globalConfiguration['enableFELDAPAuthentication'];
        static::$fe['SSOAuthentication'] = (bool)$globalConfiguration['enableFESSO'];
        static::$fe['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? (bool)$globalConfiguration['forceLowerCaseUsername'] : false;
        static::$fe['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
        static::$fe['IfUserExist'] = (bool)$globalConfiguration['TYPO3FEUserExist'];
        static::$fe['IfGroupExist'] = (bool)$globalConfiguration['TYPO3FEGroupExist'];
        static::$fe['BEfailsafe'] = false;
        static::$fe['updateAdminAttribForGroups'] = array();
        static::$fe['DeleteUserIfNoTYPO3Groups'] = (bool)$globalConfiguration['TYPO3FEDeleteUserIfNoTYPO3Groups'];
        static::$fe['DeleteUserIfNoLDAPGroups'] = (bool)$globalConfiguration['TYPO3FEDeleteUserIfNoLDAPGroups'];
        static::$fe['GroupsNotSynchronize'] = (bool)$globalConfiguration['TYPO3FEGroupsNotSynchronize'];
        static::$fe['assignGroups'] = $configuration->getFrontendGroupsAssigned() ? $configuration->getFrontendGroupsAssigned() : array();
        static::$fe['keepTYPO3Groups'] = (bool)$globalConfiguration['keepFEGroups'];
        static::$fe['requiredLDAPGroups'] = $configuration->getFrontendGroupsRequired() ? $configuration->getFrontendGroupsRequired() : array();
        static::$fe['users']['basedn'] = $configuration->getFrontendUsersBaseDn();
        static::$fe['users']['filter'] = $configuration->getFrontendUsersFilter();
        static::$fe['users']['mapping'] = static::makeUserMapping($configuration->getFrontendUsersMapping(), $configuration->getFrontendUsersFilter());
        static::$fe['groups']['basedn'] = $configuration->getFrontendGroupsBaseDn();
        static::$fe['groups']['filter'] = $configuration->getFrontendGroupsFilter();
        static::$fe['groups']['mapping'] = static::makeGroupMapping($configuration->getFrontendGroupsMapping());

        static::$ldap['server'] = $configuration->getLdapServer();
        static::$ldap['charset'] = $configuration->getLdapCharset() ? $configuration->getLdapCharset() : 'utf-8';
        static::$ldap['protocol'] = $configuration->getLdapProtocol();
        static::$ldap['host'] = $configuration->getLdapHost();
        static::$ldap['port'] = $configuration->getLdapPort();
        static::$ldap['tls'] = $configuration->isLdapTls();
        static::$ldap['binddn'] = $configuration->getLdapBindDn();
        static::$ldap['password'] = $configuration->getLdapPassword();
    }

    /**
     * Returns true if configuration has been initialized, otherwise false.
     *
     * @return bool
     */
    public static function isInitialized()
    {
        return static::$mode !== null;
    }

    /**
     * Returns true if this configuration is enabled for current host.
     *
     * @return bool
     */
    public static function isEnabledForCurrentHost()
    {
        static $host = null;
        if ($host === null && count(static::$domains) > 0) {
            $host = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        }
        return count(static::$domains) === 0 || in_array($host, static::$domains);
    }

    /**
     * Returns the list of domains.
     *
     * @return array
     */
    public static function getDomains()
    {
        return static::$domains;
    }

    /**
     * Makes the user mapping.
     *
     * @param string $mapping
     * @param string $filter
     * @return array
     */
    protected static function makeUserMapping($mapping = '', $filter = '')
    {
        // Default fields : username, tx_igldapssoauth_dn

        $userMapping = static::parseMapping($mapping);
        $userMapping['username'] = '<' . static::getUsernameAttribute($filter) . '>';
        $userMapping['tx_igldapssoauth_dn'] = '<dn>';
        $userMapping['tx_igldapssoauth_id'] = static::getUid();

        return $userMapping;
    }

    /**
     * Makes a group mapping.
     *
     * @param string $mapping
     * @return array
     */
    protected static function makeGroupMapping($mapping = '')
    {
        // Default fields : title, tx_igldapssoauth_dn

        $groupMapping = static::parseMapping($mapping);
        if (!isset($groupMapping['title'])) {
            $groupMapping['title'] = '<dn>';
        }
        $groupMapping['tx_igldapssoauth_dn'] = '<dn>';

        return $groupMapping;
    }

    /**
     * Makes a mapping.
     *
     * @param string $mapping
     * @return array
     * @deprecated since 3.0, will be removed in 3.2, use parseMapping() instead
     */
    public static function make_mapping($mapping = '')
    {
        GeneralUtility::logDeprecatedFunction();
        return static::parseMapping($mapping);
    }

    /**
     * Parses a mapping definition.
     *
     * @param string $mapping
     * @return array
     */
    public static function parseMapping($mapping = '')
    {
        $setup = \Causal\IgLdapSsoAuth\Utility\TypoScriptUtility::loadTypoScript($mapping);

        // Remove partial definitions
        $keys = array_keys($setup);
        foreach ($keys as $key) {
            if (substr($key, -1) !== '.') {
                if (empty($setup[$key])) {
                    unset($setup[$key]);
                }
            }
        }

        return $setup;
    }

    /**
     * Gets the Pid to use.
     *
     * @param array $mapping
     * @return int|null
     * @deprecated since 3.0, will be removed in 3.2, use getPid() instead
     */
    public static function get_pid($mapping = array())
    {
        GeneralUtility::logDeprecatedFunction();
        if (!$mapping) {
            return null;
        }

        if (isset($mapping['pid'])) {
            return is_numeric($mapping['pid']) ? intval($mapping['pid']) : 0;
        }

        return 0;
    }

    /**
     * Returns the pid (storage folder) to use.
     *
     * @param array $mapping
     * @return int|null
     */
    public static function getPid($mapping = array())
    {
        if (!$mapping) {
            return null;
        }
        if (isset($mapping['pid'])) {
            return is_numeric($mapping['pid']) ? intval($mapping['pid']) : 0;
        }
        return null;
    }

    /**
     * Returns the LDAP attribute holding the username.
     *
     * @param string $filter
     * @return string
     * @deprecated since 3.0, will be removed in 3.2, use getUsernameAttribute() instead
     */
    public static function get_username_attribute($filter = null)
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getUsernameAttribute();
    }

    /**
     * Returns the LDAP attribute holding the username.
     *
     * @param string $filter
     * @return string
     */
    public static function getUsernameAttribute($filter = null)
    {
        if ($filter && preg_match('/(\\w*)=\\{USERNAME\\}/', $filter, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Gets the LDAP configuration.
     *
     * @return array
     */
    public static function getLdapConfiguration()
    {
        return static::$ldap;
    }

    /**
     * Gets the Frontend configuration.
     *
     * @return array
     * @deprecated since 3.0, will be removed in 3.2, use getFrontendConfiguration() instead
     */
    public static function getFeConfiguration()
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getFrontendConfiguration();
    }

    /**
     * Gets the Frontend configuration.
     *
     * @return array
     */
    public static function getFrontendConfiguration()
    {
        return static::$fe;
    }

    /**
     * Gets the Backend configuration.
     *
     * @return array
     * @deprecated since 3.0, will be removed in 3.2, use getBackendConfiguration() instead
     */
    public static function getBeConfiguration()
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getBackendConfiguration();
    }

    /**
     * Gets the Backend configuration.
     *
     * @return array
     */
    public static function getBackendConfiguration()
    {
        return static::$be;
    }

    /**
     * Gets the TYPO3 mode.
     *
     * @return string
     * @deprecated since 3.0, will be removed in 3.2, use getMode() instead
     */
    public static function getTypo3Mode()
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getMode();
    }

    /**
     * Sets the TYPO3 mode.
     *
     * @param string $typo3_mode
     * @return void
     * @deprecated since 3.0, will be removed in 3.2, use setMode() instead
     */
    public static function setTypo3Mode($typo3_mode)
    {
        GeneralUtility::logDeprecatedFunction();
        static::setMode($typo3_mode);
    }

    /**
     * Gets the TYPO3 mode.
     *
     * @return string Either 'be' or 'fe'
     */
    public static function getMode()
    {
        return static::$mode;
    }

    /**
     * Sets the TYPO3 mode.
     *
     * @param string $mode TYPO3 mode, either 'be' or 'fe'
     * @return void
     * @throws \UnexpectedValueException
     */
    public static function setMode($mode)
    {
        $mode = strtolower($mode);
        if (!GeneralUtility::inList('be,fe', $mode)) {
            throw new \UnexpectedValueException('$mode must be either "be" or "fe"', 1425123719);
        }
        static::$mode = $mode;
    }

    /**
     * Gets the uid.
     *
     * @return int
     */
    public static function getUid()
    {
        return static::$configuration->getUid();
    }

    /**
     * Gets the name.
     *
     * @return mixed
     * @deprecated since 3.0, will be removed in 3.2
     */
    public static function getName()
    {
        GeneralUtility::logDeprecatedFunction();
        return self::$configuration->getName();
    }

    /**
     * Returns the configuration value of a given feature or false if
     * the corresponding feature is disabled.
     *
     * @param string $feature
     * @return mixed|false
     * @deprecated since 3.0, will be removed in 3.2, use getValue() instead
     */
    public static function is_enable($feature = null)
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getValue($feature);
    }

    /**
     * Returns the configuration value of a given feature or false if
     * the corresponding feature is disabled.
     *
     * @param string $feature
     * @return mixed|false
     */
    public static function getValue($feature)
    {
        $config = (static::$mode === 'be')
            ? static::getBackendConfiguration()
            : static::getFrontendConfiguration();

        return (isset($config[$feature]) ? $config[$feature] : false);
    }

    /**
     * Returns the list of LDAP attributes used by a mapping configuration.
     *
     * @param array|string $mapping
     * @return array
     * @deprecated since 3.0, will be removed in 3.2, use getLdapAttributes() instead
     */
    public static function get_ldap_attributes($mapping = array())
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getLdapAttributes($mapping);
    }

    /**
     * Returns the list of LDAP attributes used by a mapping configuration.
     *
     * @param array|string $mapping
     * @return array
     */
    public static function getLdapAttributes($mapping = array())
    {
        $ldapAttributes = array();
        if (is_array($mapping)) {
            foreach ($mapping as $field => $attribute) {
                if (substr($field, -1) === '.') {
                    // This is a TypoScript configuration
                    continue;
                }
                if (preg_match_all('/<(.+?)>/', $attribute, $matches)) {
                    foreach ($matches[1] as $matchedAttribute) {
                        $ldapAttributes[] = strtolower($matchedAttribute);
                    }
                }
            }
        }

        return array_values(array_unique($ldapAttributes));
    }

    /**
     * Returns true if the mapping contains some extended construct such
     * as parameters for a hook or TypoScript.
     *
     * @param array|string $mapping
     * @return bool
     */
    public static function hasExtendedMapping($mapping = array())
    {
        // Shortcut: if hooks are registered, take for granted extended syntax will be used
        $extended = is_array($mapping)
            && (
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraDataProcessing'])
                || is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'])
                || is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['getGroupProcessing'])
            );

        if (is_array($mapping) && !$extended) {
            foreach ($mapping as $field => $attribute) {
                if (substr($field, -1) === '.') {
                    $extended = true;
                    break;
                }
            }
        }

        return $extended;
    }

    /**
     * Returns the type of server.
     *
     * @param int $uid
     * @return string
     * @deprecated since 3.0, will be removed in 3.2, use getServerName() instead
     */
    public static function get_server_name($uid = null)
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getServerType($uid);
    }

    /**
     * Returns the type of server.
     *
     * @param int $type
     * @return string
     */
    public static function getServerType($type = null)
    {
        switch ($type) {
            case 0:
                $server = 'OpenLDAP';
                break;
            case 1:
                $server = 'Active Directory / Novell eDirectory';
                break;
            default:
                $server = null;
                break;
        }

        return $server;
    }

    /**
     * Replaces following markers with a wildcard in a LDAP filter:
     * - {USERNAME}
     * - {USERDN}
     * - {USERUID}
     *
     * @param string $filter
     * @return string
     * @deprecated since 3.0, will be removed in 3.2, use replaceFilterMarkers() instead
     */
    public static function replace_filter_markers($filter)
    {
        GeneralUtility::logDeprecatedFunction();
        return static::replaceFilterMarkers($filter);
    }

    /**
     * Replaces following markers with a wildcard in a LDAP filter:
     * - {USERNAME}
     * - {USERDN}
     * - {USERUID}
     *
     * @param string $filter
     * @return string
     */
    public static function replaceFilterMarkers($filter)
    {
        $filter = str_replace(array('{USERNAME}', '{USERDN}', '{USERUID}'), '*', $filter);
        return $filter;
    }

    /**
     * Gets the extension configuration array from table tx_igldapssoauth_config.
     *
     * @param int $uid
     * @return array
     */
    protected static function select($uid = 0)
    {
        $config = static::getDatabaseConnection()->exec_SELECTgetRows(
            '*',
            'tx_igldapssoauth_config',
            'deleted=0 AND hidden=0 AND uid=' . intval($uid)
        );

        return count($config) == 1 ? $config[0] : array();
    }

    /**
     * Returns the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
