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

use Causal\IgLdapSsoAuth\Domain\Repository\Typo3GroupRepository;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3UserRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Authentication for the 'ig_ldap_sso_auth' extension.
 *
 * @author      Michael Gagnon <mgagnon@infoglobe.ca>
 */
class Authentication
{
    protected static $config;
    protected static $lastAuthenticationDiagnostic;

    /**
     * Temporary storage for LDAP groups (should be removed after some refactoring).
     *
     * @var array|null
     */
    protected static $ldapGroups = null;

    /**
     * @var \Causal\IgLdapSsoAuth\Service\AuthenticationService
     */
    protected static $authenticationService;

    /**
     * Sets the authentication service.
     *
     * @param \Causal\IgLdapSsoAuth\Service\AuthenticationService $authenticationService
     * @return void
     */
    public static function setAuthenticationService(\Causal\IgLdapSsoAuth\Service\AuthenticationService $authenticationService)
    {
        static::$authenticationService = $authenticationService;
    }

    /**
     * Initializes the configuration based on current TYPO3 mode (BE/FE) and
     * returns it afterwards.
     *
     * @return array The corresponding configuration (BE/FE)
     */
    public static function initializeConfiguration()
    {
        if (Configuration::getMode() === 'be') {
            static::$config = Configuration::getBackendConfiguration();
        } else {
            static::$config = Configuration::getFrontendConfiguration();
        }
        return static::$config;
    }

    /**
     * Authenticates using LDAP and returns a user record or false
     * if operation fails.
     *
     * @param string $username
     * @param string $password
     * @return bool|array true or array of user info on success, otherwise false
     * @throws \Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException when LDAP extension for PHP is not available
     */
    public static function ldapAuthenticate($username, $password = null)
    {
        static::$lastAuthenticationDiagnostic = '';

        if ($username && Configuration::getValue('forceLowerCaseUsername')) {
            // Possible enhancement: use \TYPO3\CMS\Core\Charset\CharsetConverter::conv_case instead
            $username = strtolower($username);
        }

        $ldapInstance = Ldap::getInstance();

        // Valid user only if username and connect to LDAP server.
        if ($username && $ldapInstance->connect(Configuration::getLdapConfiguration())) {
            // Set extension configuration from TYPO3 mode (BE/FE).
            static::initializeConfiguration();

            // Valid user from LDAP server
            if ($userdn = $ldapInstance->validateUser($username, $password, static::$config['users']['basedn'], static::$config['users']['filter'])) {
                static::getLogger()->info(sprintf('Successfully authenticated' . ($password === null ? ' SSO' : '') . ' user "%s" with LDAP', $username));

                if ($userdn === true) {
                    return true;
                }
                return static::synchroniseUser($userdn, $username);
            } else {
                static::$lastAuthenticationDiagnostic = $ldapInstance->getLastBindDiagnostic();
                if (!empty(static::$lastAuthenticationDiagnostic)) {
                    static::getLogger()->notice(static::$lastAuthenticationDiagnostic);
                }
            }

            // LDAP authentication failed.
            $ldapInstance->disconnect();

            // This is a notice because it is fine to fallback to standard TYPO3 authentication
            static::getLogger()->notice(sprintf('Could not authenticate user "%s" with LDAP', $username));

            return false;
        }

        // LDAP authentication failed.
        static::getLogger()->warning('Cannot connect to LDAP or username is empty', ['username' => $username]);
        $ldapInstance->disconnect();
        return false;
    }

    /**
     * Returns the last static::ldap_auth() diagnostic (may be empty).
     *
     * @return string
     */
    public static function getLastAuthenticationDiagnostic()
    {
        return static::$lastAuthenticationDiagnostic;
    }

    /**
     * Synchronizes a user.
     *
     * @param string $userdn
     * @param $username
     * @return array|false
     */
    public static function synchroniseUser($userdn, $username = null)
    {
        // User is valid. Get it from DN.
        $ldapUser = static::getLdapUser($userdn);

        if ($ldapUser === null) {
            return false;
        }

        if (!$username) {
            $userAttribute = Configuration::getUsernameAttribute(static::$config['users']['filter']);
            $username = $ldapUser[$userAttribute][0];
        }
        // Get user pid from user mapping.
        $typo3_users_pid = Configuration::getPid(static::$config['users']['mapping']);

        // Get TYPO3 user from username, DN and pid.
        $typo3_user = static::getTypo3User($username, $userdn, $typo3_users_pid);
        if ($typo3_user === null) {
            // Non-existing local users are not allowed to authenticate
            return false;
        }

        // Get LDAP and TYPO3 user groups for user
        // First reset the LDAP groups
        static::$ldapGroups = null;
        $typo3_groups = static::getUserGroups($ldapUser);
        if ($typo3_groups === null) {
            // Required LDAP groups are missing
            static::$lastAuthenticationDiagnostic = 'Missing required LDAP groups.';
            return false;
        }

        if (Configuration::getValue('IfUserExist') && empty($typo3_user['uid'])) {
            return false;
            // User does not exist in TYPO3.
        } elseif (empty($typo3_user['uid']) && (!empty($typo3_groups) || !Configuration::getValue('DeleteUserIfNoTYPO3Groups'))) {
            // Insert new user: use TCA configuration to override default values
            $userTable = static::$authenticationService->authInfo['db_user']['table'];
            if (is_array($GLOBALS['TCA'][$userTable]['columns'])) {
                foreach ($GLOBALS['TCA'][$userTable]['columns'] as $column => $columnConfig) {
                    if (isset($columnConfig['config']['default'])) {
                        $defaultValue = $columnConfig['config']['default'];
                        $typo3_user[$column] = $defaultValue;
                    }
                }
            }

            $typo3_user['username'] = Typo3UserRepository::setUsername($typo3_user['username']);

            $typo3_user = Typo3UserRepository::add($userTable, $typo3_user);
        }

        if (!empty($typo3_user['uid'])) {
            // Let's restore deleted accounts since the only way to prevent an actual LDAP member
            // to authenticate is to set a "stop time" (endtime in DB) to the TYPO3 user record or
            // mark it as "disable"
            $typo3_user['deleted'] = 0;

            $typo3_user['password'] = Typo3UserRepository::setRandomPassword();

            if ((empty($typo3_groups) && Configuration::getValue('DeleteUserIfNoTYPO3Groups'))) {
                $typo3_user['deleted'] = 1;
                $typo3_user['endtime'] = $GLOBALS['EXEC_TIME'];
                static::getLogger()->debug('User record has been deleted because she has no associated TYPO3 groups.', $typo3_user);
            }
            // Delete user if no LDAP groups found.
            if (Configuration::getValue('DeleteUserIfNoLDAPGroups') && !static::$ldapGroups) {
                $typo3_user['deleted'] = 1;
                $typo3_user['endtime'] = $GLOBALS['EXEC_TIME'];
                static::getLogger()->debug('User record has been deleted because she has no LDAP groups.', $typo3_user);
            }
            // Set groups to user.
            $groupTable = static::$authenticationService->authInfo['db_groups']['table'];
            $typo3_user = Typo3UserRepository::setUserGroups($typo3_user, $typo3_groups, $groupTable);
            // Merge LDAP user with TYPO3 user from mapping.
            if ($typo3_user) {
                $typo3_user = static::merge($ldapUser, $typo3_user, static::$config['users']['mapping']);

                if (Configuration::getValue('forceLowerCaseUsername')) {
                    // Possible enhancement: use \TYPO3\CMS\Core\Charset\CharsetConverter::conv_case instead
                    $typo3_user['username'] = strtolower($typo3_user['username']);
                }

                // Update TYPO3 user.
                Typo3UserRepository::update(static::$authenticationService->authInfo['db_user']['table'], $typo3_user);

                $typo3_user['tx_igldapssoauth_from'] = 'LDAP';

                if ((bool)$typo3_user['deleted']) {
                    // User has been updated in TYPO3, but it should not be granted to get an actual session
                    $typo3_user = false;
                }
            }
        } else {
            $typo3_user = false;
        }
        return $typo3_user;
    }

    /**
     * Returns a LDAP user.
     *
     * @param string $dn
     * @return array
     */
    protected static function getLdapUser($dn = null)
    {
        // Restricting the list of returned attributes sometimes makes the ldap_search() method issue a PHP warning:
        //     Warning: ldap_search(): Array initialization wrong
        // so we just ask for every attribute ("true" below)!
        if (true || Configuration::hasExtendedMapping(static::$config['users']['mapping'])) {
            $attributes = [];
        } else {
            // Currently never called ever again due to the warning found sometimes (see above)
            $attributes = Configuration::getLdapAttributes(static::$config['users']['mapping']);
            if (strpos(static::$config['groups']['filter'], '{USERUID}') !== false) {
                $attributes[] = 'uid';
                $attributes = array_unique($attributes);
            }
        }

        $ldapInstance = Ldap::getInstance();
        $ldapInstance->connect(Configuration::getLdapConfiguration());

        $users = $ldapInstance->search(
            $dn,
            str_replace('{USERNAME}', '*', static::$config['users']['filter']),
            $attributes
        );

        $user = is_array($users[0]) ? $users[0] : null;

        $ldapInstance->disconnect();
        static::getLogger()->debug(sprintf('Retrieving LDAP user from DN "%s"', $dn), $user ?: []);

        return $user;
    }

    /**
     * Gets the LDAP and TYPO3 user groups for a given user.
     *
     * @param array $ldapUser LDAP user data
     * @param array $configuration LDAP configuration
     * @param string $groupTable Name of the group table (should normally be either "be_groups" or "fe_groups")
     * @return array|null Array of groups or null if required LDAP groups are missing
     * @throws \Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException
     */
    public static function getUserGroups(array $ldapUser, array $configuration = null, $groupTable = '')
    {
        if ($configuration === null) {
            $configuration = static::$config;
        }
        if (empty($groupTable)) {
            if (isset(static::$authenticationService)) {
                $groupTable = static::$authenticationService->authInfo['db_groups']['table'];
            } else {
                if (TYPO3_MODE === 'BE') {
                    $groupTable = 'be_groups';
                } else {
                    $groupTable = 'fe_groups';
                }
            }
        }

        // User is valid only if exist in TYPO3.
        // Get LDAP groups from LDAP user.
        $typo3_groups = [];
        $ldapGroups = static::getLdapGroups($ldapUser);
        unset($ldapGroups['count']);

        /** @var \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup[]|\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup[] $requiredLDAPGroups */
        $requiredLDAPGroups = Configuration::getValue('requiredLDAPGroups');

        if (count($ldapGroups) === 0) {
            if (count($requiredLDAPGroups) > 0) {
                return null;
            }
        } else {
            // Get pid from group mapping
            $typo3GroupPid = Configuration::getPid($configuration['groups']['mapping']);

            $typo3GroupsTemp = static::getTypo3Groups(
                $ldapGroups,
                $groupTable,
                $typo3GroupPid,
                $configuration['groups']['mapping']
            );

            if (count($requiredLDAPGroups) > 0) {
                $hasRequired = false;
                $groupUids = [];
                foreach ($typo3GroupsTemp as $typo3Group) {
                    $groupUids[] = $typo3Group['uid'];
                }
                foreach ($requiredLDAPGroups as $group) {
                    if (in_array($group->getUid(), $groupUids)) {
                        $hasRequired = true;
                        break;
                    }
                }
                if (!$hasRequired) {
                    return null;
                }
            }

            if (Configuration::getValue('IfGroupExist') && count($typo3GroupsTemp) === 0) {
                return [];
            }

            $i = 0;
            foreach ($typo3GroupsTemp as $typo3Group) {
                if (Configuration::getValue('GroupsNotSynchronize') && empty($typo3Group['uid'])) {
                    // Groups should not get synchronized and the current group is invalid
                    continue;
                }
                if (Configuration::getValue('GroupsNotSynchronize')) {
                    $typo3_groups[] = $typo3Group;
                } elseif (empty($typo3Group['uid'])) {
                    $newGroup = Typo3GroupRepository::add(
                        $groupTable,
                        $typo3Group
                    );

                    $typo3_group_merged = static::merge(
                        $ldapGroups[$i],
                        $newGroup,
                        $configuration['groups']['mapping']
                    );

                    Typo3GroupRepository::update(
                        $groupTable,
                        $typo3_group_merged
                    );

                    $typo3Group = Typo3GroupRepository::fetch(
                        $groupTable,
                        $typo3_group_merged['uid']
                    );
                    $typo3_groups[] = $typo3Group[0];
                } else {
                    // Restore group that may have been previously deleted
                    $typo3Group['deleted'] = 0;
                    $typo3_group_merged = static::merge(
                        $ldapGroups[$i],
                        $typo3Group,
                        $configuration['groups']['mapping']
                    );

                    Typo3GroupRepository::update(
                        $groupTable,
                        $typo3_group_merged
                    );

                    $typo3Group = Typo3GroupRepository::fetch(
                        $groupTable,
                        $typo3_group_merged['uid']
                    );
                    $typo3_groups[] = $typo3Group[0];
                }

                $i++;
            }
        }
        // Hook for processing the groups
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['getGroupsProcessing'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['getGroupsProcessing'] as $className) {
                /** @var $postProcessor \Causal\IgLdapSsoAuth\Utility\GetGroupsProcessorInterface */
                $postProcessor = GeneralUtility::makeInstance($className);
                if ($postProcessor instanceof \Causal\IgLdapSsoAuth\Utility\GetGroupsProcessorInterface) {
                    $postProcessor->getUserGroups($groupTable, $ldapUser, $typo3_groups);
                } else {
                    throw new \RuntimeException('Processor ' . get_class($postProcessor) . ' must implement the \\Causal\\IgLdapSsoAuth\\Utility\\GetGroupsProcessorInterface interface', 1431340191);
                }
            }
        }
        return $typo3_groups;
    }

    /**
     * Returns LDAP groups associated to a given user.
     *
     * @param array $ldapUser
     * @return array
     */
    protected static function getLdapGroups(array $ldapUser = [])
    {
        // Make sure configuration is properly initialized, there could be a change of context
        // (Backend/Frontend) between calls
        static::initializeConfiguration();

        // Get groups attributes from group mapping configuration.
        $ldapGroupAttributes = Configuration::getLdapAttributes(static::$config['groups']['mapping']);
        $ldapGroups = ['count' => 0];

        $ldapInstance = Ldap::getInstance();
        $ldapInstance->connect(Configuration::getLdapConfiguration());

        if (Configuration::getValue('evaluateGroupsFromMembership')) {
            // Get LDAP groups from membership attribute
            if ($membership = LdapGroup::getMembership($ldapUser, static::$config['users']['mapping'])) {
                $ldapGroups = LdapGroup::selectFromMembership(
                    $membership,
                    static::$config['groups']['basedn'],
                    static::$config['groups']['filter'],
                    $ldapGroupAttributes,
                    // If groups should not get synchronized, there is no need to actively check them
                    // against the LDAP server, simply accept every groups from $membership matching
                    // the baseDN for groups, because LDAP groups not existing locally will simply be
                    // skipped and not automatically created. This allows groups to be available on a
                    // different LDAP server (see https://forge.typo3.org/issues/64141):
                    !(bool)static::$config['GroupsNotSynchronize'],
                    $ldapInstance
                );
            }
        } else {
            // Get LDAP groups from DN of user.
            $ldapGroups = LdapGroup::selectFromUser(
                static::$config['groups']['basedn'],
                static::$config['groups']['filter'],
                $ldapUser['dn'],
                !empty($ldapUser['uid'][0]) ? $ldapUser['uid'][0] : '',
                $ldapGroupAttributes,
                $ldapInstance
            );
        }

        $ldapInstance->disconnect();

        static::getLogger()->debug(sprintf('Retrieving LDAP groups for user "%s"', $ldapUser['dn']), $ldapGroups);

        // Store for later usage and return
        static::$ldapGroups = $ldapGroups;
        return $ldapGroups;
    }

    /**
     * Returns a TYPO3 user.
     *
     * @param string $username
     * @param string $userDn
     * @param int|null $pid
     * @return array
     */
    protected static function getTypo3User($username, $userDn, $pid = null)
    {
        $user = null;

        $typo3_users = Typo3UserRepository::fetch(static::$authenticationService->authInfo['db_user']['table'], 0, $pid, $username, $userDn);
        if ($typo3_users) {
            // Check option "Users that are not already present in TYPO3 may not log on"
            // Note: there is no need to manually remove users with inactive starttime/endtime since
            //       this is catched by TYPO3 anyway
            if (Configuration::getValue('IfUserExist')) {
                // Ensure every returned record is active
                $numberOfUsers = count($typo3_users);
                for ($i = 0; $i < $numberOfUsers; $i++) {
                    if (!empty($typo3_users[$i]['deleted'])) {
                        // User is deleted => behave as if it did not exist at all!
                        // Note: if user is inactive (disable=1), this will be catched by TYPO3 automatically
                        //       but we need to manually remove it here otherwise this extension would silently
                        //       restore the deleted user (which is a design choice)
                        unset($typo3_users[$i]);
                    }
                }

                // Reset the array's indices
                $typo3_users = array_values($typo3_users);
            }

            // We want to return only first user in any case, if more than one are returned (e.g.,
            // same username/DN twice) actual authentication will fail anyway later on
            $user = is_array($typo3_users[0]) ? $typo3_users[0] : null;
        } elseif (!Configuration::getValue('IfUserExist')) {
            $user = Typo3UserRepository::create(static::$authenticationService->authInfo['db_user']['table']);

            $user['pid'] = (int)$pid;
            $user['crdate'] = $GLOBALS['EXEC_TIME'];
            $user['tstamp'] = $GLOBALS['EXEC_TIME'];
            $user['username'] = $username;
            $user['tx_igldapssoauth_dn'] = $userDn;
        }

        return $user;
    }

    /**
     * Returns TYPO3 groups associated to $ldapGroups or create
     * fresh records if they don't exist yet.
     *
     * @param array $ldapGroups
     * @param string $table
     * @param int|null $pid
     * @param array $mapping
     * @return array
     */
    public static function getTypo3Groups(array $ldapGroups = [], $table = null, $pid = null, array $mapping = [])
    {
        if (count($ldapGroups) === 0) {
            // Early return
            return [];
        }

        $typo3Groups = [];

        foreach ($ldapGroups as $ldapGroup) {
            $groupName = null;
            if (isset($mapping['title']) &&  preg_match('`<([^$]*)>`', $mapping['title'])) {
                $groupName = static::replaceLdapMarkers($mapping['title'], $ldapGroup);
            }
            $existingTypo3Groups = Typo3GroupRepository::fetch($table, 0, $pid, $ldapGroup['dn'], $groupName);

            if (count($existingTypo3Groups) > 0) {
                $typo3Group = $existingTypo3Groups[0];
            } else {
                $typo3Group = Typo3GroupRepository::create($table);
                $typo3Group['pid'] = (int)$pid;
                $typo3Group['crdate'] = $GLOBALS['EXEC_TIME'];
                $typo3Group['tstamp'] = $GLOBALS['EXEC_TIME'];
            }

            $typo3Groups[] = $typo3Group;
        }

        return $typo3Groups;
    }

    /**
     * Returns TYPO3 users associated to $ldap_users or create fresh records
     * if they don't exist yet.
     *
     * @param array $ldapUsers
     * @param array $mapping
     * @param string $table
     * @param int|null $pid
     * @return array
     */
    public static function getTypo3Users(array $ldapUsers = [], array $mapping = [], $table = null, $pid = null)
    {
        if (count($ldapUsers) === 0) {
            // Early return
            return [];
        }

        $typo3Users = [];

        foreach ($ldapUsers as $ldapUser) {
            $username = null;
            if (isset($mapping['username']) && preg_match('`<([^$]*)>`', $mapping['username'])) {
                $username = static::replaceLdapMarkers($mapping['username'], $ldapUser);
            }
            $existingTypo3Users = Typo3UserRepository::fetch($table, 0, $pid, $username, $ldapUser['dn']);

            if (count($existingTypo3Users) > 0) {
                $typo3User = $existingTypo3Users[0];
            } else {
                $typo3User = Typo3UserRepository::create($table);
                $typo3User['pid'] = (int)$pid;
                $typo3User['crdate'] = $GLOBALS['EXEC_TIME'];
                $typo3User['tstamp'] = $GLOBALS['EXEC_TIME'];
            }

            $typo3Users[] = $typo3User;
        }

        return $typo3Users;
    }

    /**
     * Merges a user from LDAP and from TYPO3.
     *
     * @param array $ldap
     * @param array $typo3
     * @param array $mapping Parsed mapping definition
     * @param bool $reportErrors
     * @return array
     * @see \Causal\IgLdapSsoAuth\Library\Configuration::parseMapping()
     */
    public static function merge(array $ldap = [], array $typo3 = [], array $mapping = [], $reportErrors = false)
    {
        $out = $typo3;
        $typoScriptKeys = [];

        // Process every field (except "usergroup" and "parentGroup") which is not a TypoScript definition
        foreach ($mapping as $field => $value) {
            if (substr($field, -1) !== '.') {
                if ($field !== 'usergroup' && $field !== 'parentGroup') {
                    try {
                        $out = static::mergeSimple($ldap, $out, $field, $value);
                    } catch (\UnexpectedValueException $uve) {
                        if ($reportErrors) {
                            $out['__errors'][] = $uve->getMessage();
                        }
                    }
                }
            } else {
                $typoScriptKeys[] = $field;
            }
        }

        if (count($typoScriptKeys) > 0) {
            $flattenedLdap = [];
            foreach ($ldap as $key => $value) {
                if (!is_numeric($key)) {
                    if (is_array($value)) {
                        unset($value['count']);
                        $value = implode(LF, $value);
                    }
                    $flattenedLdap[$key] = $value;
                }
            }

            $backupTSFE = $GLOBALS['TSFE'];

            // Advanced stdWrap methods require a valid $GLOBALS['TSFE'] => create the most lightweight one
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(
                \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class,
                $GLOBALS['TYPO3_CONF_VARS'],
                0,
                ''
            );
            $GLOBALS['TSFE']->initTemplate();

            /** @var $contentObj \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer */
            $contentObj = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
            $contentObj->start($flattenedLdap, '');

            // Process every TypoScript definition
            foreach ($typoScriptKeys as $typoScriptKey) {
                // Remove the trailing period to get corresponding field name
                $field = substr($typoScriptKey, 0, -1);
                $value = isset($out[$field]) ? $out[$field] : '';
                $value = $contentObj->stdWrap($value, $mapping[$typoScriptKey]);
                $out = static::mergeSimple([$field => $value], $out, $field, $value);
            }

            $GLOBALS['TSFE'] = $backupTSFE;
        }

        return $out;
    }

    /**
     * Merges a field from LDAP into a TYPO3 record.
     *
     * @param array $ldap
     * @param array $typo3
     * @param string $field
     * @param string $value
     * @return array Modified $typo3 array
     * @throws \UnexpectedValueException
     */
    protected static function mergeSimple(array $ldap, array $typo3, $field, $value)
    {
        // Standard marker or custom function
        if (preg_match('`{([^$]*)}`', $value, $matches)) {
            switch ($value) {
                case '{DATE}':
                    $mappedValue = $GLOBALS['EXEC_TIME'];
                    break;
                case '{RAND}':
                    $mappedValue = rand();
                    break;
                default:
                    $mappedValue = '';
                    $parameters = explode(';', $matches[1]);
                    $hookParameters = [];

                    foreach ($parameters as $parameter) {
                        list($parameterKey, $parameterValue) = explode('|', $parameter, 2);
                        $hookParameters[trim($parameterKey)] = $parameterValue;
                    }
                    if (empty($hookParameters['hookName'])) {
                        throw new \UnexpectedValueException(sprintf('Custom marker hook parameter "hookName" is undefined: %s', $matches[0]), 1430138379);
                    }
                    $hookName = $hookParameters['hookName'];
                    $ldapAttributes = Configuration::getLdapAttributes([$value]);
                    // hook for processing user information once inserted or updated in the database
                    if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField']) &&
                        !empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'][$hookName])
                    ) {
                        $_procObj = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'][$hookName]);
                        if (!is_callable([$_procObj, 'extraMerge'])) {
                            throw new \UnexpectedValueException(sprintf('Custom marker hook "%s" does not have a method "extraMerge"', $hookName), 1430140817);
                        }
                        $mappedValue = $_procObj->extraMerge($field, $typo3, $ldap, $ldapAttributes, $hookParameters);
                    }
                    break;
            }

            // LDAP attribute
        } elseif (preg_match('`<([^$]*)>`', $value, $attribute)) {
            if ($field === 'tx_igldapssoauth_dn' || ($field === 'title' && $value === '<dn>')) {
                $mappedValue = $ldap[strtolower($attribute[1])];
            } else {
                $mappedValue = static::replaceLdapMarkers($value, $ldap);
            }

            // Constant
        } else {
            $mappedValue = $value;
        }

        // If field exists in TYPO3, set it to the mapped value
        if (array_key_exists($field, $typo3)) {
            $typo3[$field] = $mappedValue;

            // Otherwise, it is some extra value, which we store in a special sub-array
            // This may be data that is meant to be mapped onto other database tables
        } else {
            if (!isset($typo3['__extraData'])) {
                $typo3['__extraData'] = [];
            }
            $typo3['__extraData'][$field] = $mappedValue;
        }

        return $typo3;
    }

    /**
     * Replaces all LDAP markers (e.g. <cn>) with their corresponding values
     * in the LDAP data array.
     *
     * If no matching value was found in the array the marker will be removed.
     *
     * @param string $markerString The string containing the markers that should be replaced
     * @param array $ldapData Array containing the LDAP data that should be used for replacement
     * @return string The string with the replaced / removed markers
     * @author Alexander Stehlik <alexander.stehlik.deleteme@gmail.com>
     */
    public static function replaceLdapMarkers($markerString, $ldapData)
    {
        preg_match_all('/<(.+?)>/', $markerString, $matches);

        foreach ($matches[0] as $index => $fullMatchedMarker) {
            $ldapProperty = strtolower($matches[1][$index]);

            if (isset($ldapData[$ldapProperty])) {
                $ldapValue = $ldapData[$ldapProperty];
                if (is_array($ldapValue)) {
                    $ldapValue = $ldapValue[0];
                }
                $markerString = str_replace($fullMatchedMarker, $ldapValue, $markerString);
            } else {
                $markerString = str_replace($fullMatchedMarker, '', $markerString);
            }
        }

        return $markerString;
    }

    /**
     * Returns an array of RDN's from a given DN.
     *
     * @param string $dn
     * @param int $limit
     * @return array
     */
    public static function getRelativeDistinguishedNames($dn, $limit = null)
    {
        // We want to extract RDN's by splitting on comma but we
        // make sure that any escaped comma (\,) will NOT be taken
        // into account thanks to a look-behind assertion in pattern
        $pattern = '#(?<!\\\\),#';

        $parts = $limit === null
            ? preg_split($pattern, $dn)
            : preg_split($pattern, $dn, $limit);

        return $parts;
    }

    /**
     * Returns a logger.
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected static function getLogger()
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
        }
        return $logger;
    }
}
