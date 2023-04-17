<?php
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

namespace Causal\IgLdapSsoAuth\Library;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
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

    const SERVER_OPENLDAP = 0;
    const SERVER_ACTIVE_DIRECTORY = 1;

    /**
     * @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration
     */
    protected static $configuration;

    protected static $mode;
    protected static $be = [];
    protected static $fe = [];
    protected static $ldap = [];
    protected static $domains = [];

    /**
     * Initializes the configuration class.
     *
     * @param string $mode TYPO3 mode, either 'be' or 'fe'
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     */
    public static function initialize($mode, \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration)
    {
        $globalConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ig_ldap_sso_auth'] ?? [];

        // Legacy configuration options
        unset($globalConfiguration['evaluateGroupsFromMembership']);

        static::$configuration = $configuration;

        // Default TYPO3_MODE is BE
        static::setMode($mode ?: \Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode());

        // Select configuration from database, merge with extension configuration template and initialise class attributes.

        static::$domains = [];
        if (!empty(static::$domains)) {
            trigger_error(
                'LDAP configuration record uid=' . $configuration->getUid() . ' uses associated domains which are deprecated since TYPO3 v9.',
                E_USER_DEPRECATED
            );
        }

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $siteIdentifiers = GeneralUtility::trimExplode(',', $configuration->getSites(), true);
        foreach ($siteIdentifiers as $siteIdentifier) {
            try {
                $site = $siteFinder->getSiteByIdentifier($siteIdentifier);
                $domains = self::extractHostsFromSite($site);
                static::$domains = array_merge(static::$domains, $domains);
            } catch (SiteNotFoundException $e) {
                // Ignore invalid site identifiers.
            }
        }

        static::$domains = array_unique(static::$domains);

        static::$be['LDAPAuthentication'] = (bool)$globalConfiguration['enableBELDAPAuthentication'];
        static::$be['SSOAuthentication'] = (bool)$globalConfiguration['enableBESSO'];
        static::$be['SSOKeepDomainName'] = (bool)$globalConfiguration['keepBESSODomainName'];
        static::$be['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? (bool)$globalConfiguration['forceLowerCaseUsername'] : false;
        static::$be['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
        static::$be['IfUserExist'] = (bool)$globalConfiguration['TYPO3BEUserExist'];
        static::$be['IfGroupExist'] = (bool)$globalConfiguration['TYPO3BEGroupExist'];
        static::$be['BEfailsafe'] = (bool)$globalConfiguration['BEfailsafe'];
        static::$be['DeleteUserIfNoLDAPGroups'] = false;
        static::$be['DeleteUserIfNoTYPO3Groups'] = false;
        static::$be['GroupsNotSynchronize'] = (bool)$globalConfiguration['TYPO3BEGroupsNotSynchronize'];
        static::$be['requiredLDAPGroups'] = $configuration->getBackendGroupsRequired() ?: [];
        static::$be['updateAdminAttribForGroups'] = $configuration->getBackendGroupsAdministrator() ?: [];
        static::$be['assignGroups'] = $configuration->getBackendGroupsAssigned() ?: [];
        static::$be['keepTYPO3Groups'] = (bool)$globalConfiguration['keepBEGroups'];
        static::$be['users']['basedn'] = $configuration->getBackendUsersBaseDn();
        static::$be['users']['filter'] = $configuration->getBackendUsersFilter();
        static::$be['users']['mapping'] = static::makeUserMapping($configuration->getBackendUsersMapping(), $configuration->getBackendUsersFilter());
        static::$be['groups']['basedn'] = $configuration->getBackendGroupsBaseDn();
        static::$be['groups']['filter'] = $configuration->getBackendGroupsFilter();
        static::$be['groups']['mapping'] = static::makeGroupMapping($configuration->getBackendGroupsMapping());

        static::$fe['LDAPAuthentication'] = (bool)$globalConfiguration['enableFELDAPAuthentication'];
        static::$fe['SSOAuthentication'] = (bool)$globalConfiguration['enableFESSO'];
        static::$fe['SSOKeepDomainName'] = (bool)$globalConfiguration['keepFESSODomainName'];
        static::$fe['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? (bool)$globalConfiguration['forceLowerCaseUsername'] : false;
        static::$fe['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
        static::$fe['IfUserExist'] = (bool)$globalConfiguration['TYPO3FEUserExist'];
        static::$fe['IfGroupExist'] = (bool)$globalConfiguration['TYPO3FEGroupExist'];
        static::$fe['BEfailsafe'] = true;
        static::$fe['updateAdminAttribForGroups'] = [];
        static::$fe['DeleteUserIfNoTYPO3Groups'] = (bool)$globalConfiguration['TYPO3FEDeleteUserIfNoTYPO3Groups'];
        static::$fe['DeleteUserIfNoLDAPGroups'] = (bool)$globalConfiguration['TYPO3FEDeleteUserIfNoLDAPGroups'];
        static::$fe['GroupsNotSynchronize'] = (bool)$globalConfiguration['TYPO3FEGroupsNotSynchronize'];
        static::$fe['assignGroups'] = $configuration->getFrontendGroupsAssigned() ?: [];
        static::$fe['keepTYPO3Groups'] = (bool)$globalConfiguration['keepFEGroups'];
        static::$fe['requiredLDAPGroups'] = $configuration->getFrontendGroupsRequired() ?: [];
        static::$fe['users']['basedn'] = $configuration->getFrontendUsersBaseDn();
        static::$fe['users']['filter'] = $configuration->getFrontendUsersFilter();
        static::$fe['users']['mapping'] = static::makeUserMapping($configuration->getFrontendUsersMapping(), $configuration->getFrontendUsersFilter());
        static::$fe['groups']['basedn'] = $configuration->getFrontendGroupsBaseDn();
        static::$fe['groups']['filter'] = $configuration->getFrontendGroupsFilter();
        static::$fe['groups']['mapping'] = static::makeGroupMapping($configuration->getFrontendGroupsMapping());

        static::$ldap['server'] = $configuration->getLdapServer();
        static::$ldap['charset'] = $configuration->getLdapCharset() ?: 'utf-8';
        static::$ldap['host'] = $configuration->getLdapHost();
        static::$ldap['port'] = $configuration->getLdapPort();
        static::$ldap['tls'] = $configuration->isLdapTls();
        static::$ldap['tlsReqcert'] = $configuration->isLdapTlsReqcert();
        static::$ldap['ssl'] = $configuration->isLdapSsl();
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
     * @deprecated since TYPO3 v10
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
                if ($setup[$key] === '') {
                    unset($setup[$key]);
                }
            }
        }

        return $setup;
    }

    /**
     * Returns the pid (storage folder) to use.
     *
     * @param array $mapping
     * @return int|null
     */
    public static function getPid($mapping = [])
    {
        if (!$mapping) {
            return null;
        }
        if (isset($mapping['pid'])) {
            return is_numeric($mapping['pid']) ? (int)$mapping['pid'] : 0;
        }
        return null;
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
     */
    public static function getFrontendConfiguration()
    {
        return static::$fe;
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
     */
    public static function getLdapAttributes($mapping = [])
    {
        $ldapAttributes = [];
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
    public static function hasExtendedMapping($mapping = [])
    {
        if (!is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth'] ?? null)) {
            return false;
        }

        // Shortcut: if hooks are registered, take for granted extended syntax will be used
        $extended = is_array($mapping)
            && (
                is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraDataProcessing'])
                || is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'])
                || is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['getGroupsProcessing'])
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
     * @param int $type
     * @return string
     */
    public static function getServerType($type = null)
    {
        switch ($type) {
            case static::SERVER_OPENLDAP:
                $server = 'OpenLDAP';
                break;
            case static::SERVER_ACTIVE_DIRECTORY:
                $server = 'Active Directory';
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
     */
    public static function replaceFilterMarkers($filter)
    {
        $filter = str_replace(['{USERNAME}', '{USERDN}', '{USERUID}'], '*', $filter);
        return $filter;
    }

    /**
     * Gets the extension configuration array from table tx_igldapssoauth_config.
     *
     * @param int $uid
     * @return array
     */
    protected static function select(int $uid = 0): array
    {
        $config = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_igldapssoauth_config')
            ->select(
                ['*'],
                'tx_igldapssoauth_config',
                [
                    'uid' => $uid,
                ]
            )
            ->fetchAssociative();

        return !empty($config) ? $config : [];
    }

    /**
     * Extract relevant hosts from a given site configuration.
     * Respects base host and hosts for alternative site languages.
     *
     * @param Site $site
     * @return string[]
     */
    protected static function extractHostsFromSite(Site $site): array
    {
        $hosts = [];

        if ($site->getBase()->getHost() !== '') {
            $hosts[] = $site->getBase()->getHost();
        } else {
            $hosts[] = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        }

        // Allow domains from alternative languages.
        foreach ($site->getLanguages() as $siteLanguage) {
            if ($siteLanguage->getBase()->getHost() !== '') {
                $hosts[] = $siteLanguage->getBase()->getHost();
            }
        }

        return $hosts;
    }

}
