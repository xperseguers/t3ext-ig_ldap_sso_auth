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
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Configuration {

	const GROUP_MEMBERSHIP_FROM_GROUP = 1;
	const GROUP_MEMBERSHIP_FROM_MEMBER = 2;

	/**
	 * @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration
	 */
	static protected $configuration;

	static protected $mode;
	static protected $be = array();
	static protected $fe = array();
	static protected $ldap = array();
	static protected $domains = array();

	/**
	 * Initializes the configuration class.
	 *
	 * @param string $typo3_mode
	 * @param int $uid
	 * @return void
	 * @deprecated since 3.0, will be removed in 3.2, use initialize() instead
	 */
	static public function init($typo3_mode = NULL, $uid) {
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
	static public function initialize($mode, \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration) {
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
		$domainUids = GeneralUtility::intExplode(',', $configuration->getDomains(), TRUE);
		foreach ($domainUids as $domainUid) {
			$row = static::getDatabaseConnection()->exec_SELECTgetSingleRow('domainName', 'sys_domain', 'uid=' . intval($domainUid));
			static::$domains[] = $row['domainName'];
		}

		static::$be['LDAPAuthentication'] = (bool)$globalConfiguration['enableBELDAPAuthentication'];
		static::$be['SSOAuthentication'] = FALSE;
		static::$be['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? (bool)$globalConfiguration['forceLowerCaseUsername'] : FALSE;
		static::$be['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
		static::$be['IfUserExist'] = (bool)$globalConfiguration['TYPO3BEUserExist'];
		static::$be['IfGroupExist'] = (bool)$globalConfiguration['TYPO3BEGroupExist'];
		static::$be['BEfailsafe'] = (bool)$globalConfiguration['BEfailsafe'];
		static::$be['DeleteUserIfNoLDAPGroups'] = FALSE;
		static::$be['DeleteUserIfNoTYPO3Groups'] = FALSE;
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
		static::$fe['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? (bool)$globalConfiguration['forceLowerCaseUsername'] : FALSE;
		static::$fe['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
		static::$fe['IfUserExist'] = (bool)$globalConfiguration['TYPO3FEUserExist'];
		static::$fe['IfGroupExist'] = (bool)$globalConfiguration['TYPO3FEGroupExist'];
		static::$fe['BEfailsafe'] = FALSE;
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
	 * Returns TRUE if configuration has been initialized, otherwise FALSE.
	 *
	 * @return bool
	 */
	static public function isInitialized() {
		return static::$mode !== NULL;
	}

	/**
	 * Returns TRUE if this configuration is enabled for current host.
	 *
	 * @return bool
	 */
	static public function isEnabledForCurrentHost() {
		static $host = NULL;
		if ($host === NULL && count(static::$domains) > 0) {
			$host = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
		}
		return count(static::$domains) === 0 || in_array($host, static::$domains);
	}

	/**
	 * Returns the list of domains.
	 *
	 * @return array
	 */
	static public function getDomains() {
		return static::$domains;
	}

	/**
	 * Makes the user mapping.
	 *
	 * @param string $mapping
	 * @param string $filter
	 * @return array
	 */
	static protected function makeUserMapping($mapping = '', $filter = '') {
		// Default fields : username, tx_igldapssoauth_dn

		$userMapping = static::makeMapping($mapping);
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
	static protected function makeGroupMapping($mapping = '') {
		// Default fields : title, tx_igldapssoauth_dn

		$groupMapping = static::makeMapping($mapping);
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
	 * @deprecated since 3.0, will be removed in 3.2, use makeMapping() instead
	 */
	static public function make_mapping($mapping = '') {
		GeneralUtility::logDeprecatedFunction();
		return static::makeMapping($mapping);
	}

	/**
	 * Makes a mapping.
	 *
	 * @param string $mapping
	 * @return array
	 */
	static public function makeMapping($mapping = '') {
		$mappingConfiguration = array();
		$mapping = GeneralUtility::trimExplode(LF, $mapping, TRUE);

		foreach ($mapping as $field) {
			// We do not use GeneralUtility::trimExplode() here to keep possible spaces
			// around "=" if used within a mapping value
			$fieldMapping = explode('=', $field, 2);
			if (!empty($fieldMapping[1])) {
				$key = trim($fieldMapping[0]);
				$value = trim($fieldMapping[1]);
				$mappingConfiguration[$key] = $value;
			}
		}

		return $mappingConfiguration;
	}

	/**
	 * Gets the Pid to use.
	 *
	 * @param array $mapping
	 * @return int|NULL
	 * @deprecated since 3.0, will be removed in 3.2, use getPid() instead
	 */
	static public function get_pid($mapping = array()) {
		GeneralUtility::logDeprecatedFunction();
		if (!$mapping) {
			return NULL;
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
	 * @return int|NULL
	 */
	static public function getPid($mapping = array()) {
		if (!$mapping) {
			return NULL;
		}
		if (isset($mapping['pid'])) {
			return is_numeric($mapping['pid']) ? intval($mapping['pid']) : 0;
		}
		return NULL;
	}

	/**
	 * Returns the LDAP attribute holding the username.
	 *
	 * @param string $filter
	 * @return string
	 * @deprecated since 3.0, will be removed in 3.2, use getUsernameAttribute() instead
	 */
	static public function get_username_attribute($filter = NULL) {
		GeneralUtility::logDeprecatedFunction();
		return static::getUsernameAttribute();
	}

	/**
	 * Returns the LDAP attribute holding the username.
	 *
	 * @param string $filter
	 * @return string
	 */
	static public function getUsernameAttribute($filter = NULL) {
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
	static public function getLdapConfiguration() {
		return static::$ldap;
	}

	/**
	 * Gets the Frontend configuration.
	 *
	 * @return array
	 * @deprecated since 3.0, will be removed in 3.2, use getFrontendConfiguration() instead
	 */
	static public function getFeConfiguration() {
		GeneralUtility::logDeprecatedFunction();
		return static::getFrontendConfiguration();
	}

	/**
	 * Gets the Frontend configuration.
	 *
	 * @return array
	 */
	static public function getFrontendConfiguration() {
		return static::$fe;
	}

	/**
	 * Gets the Backend configuration.
	 *
	 * @return array
	 * @deprecated since 3.0, will be removed in 3.2, use getBackendConfiguration() instead
	 */
	static public function getBeConfiguration() {
		GeneralUtility::logDeprecatedFunction();
		return static::getBackendConfiguration();
	}

	/**
	 * Gets the Backend configuration.
	 *
	 * @return array
	 */
	static public function getBackendConfiguration() {
		return static::$be;
	}

	/**
	 * Gets the TYPO3 mode.
	 *
	 * @return string
	 * @deprecated since 3.0, will be removed in 3.2, use getMode() instead
	 */
	static public function getTypo3Mode() {
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
	static public function setTypo3Mode($typo3_mode) {
		GeneralUtility::logDeprecatedFunction();
		static::setMode($typo3_mode);
	}

	/**
	 * Gets the TYPO3 mode.
	 *
	 * @return string Either 'be' or 'fe'
	 */
	static public function getMode() {
		return static::$mode;
	}

	/**
	 * Sets the TYPO3 mode.
	 *
	 * @param string $mode TYPO3 mode, either 'be' or 'fe'
	 * @return void
	 * @throws \RuntimeException
	 */
	static public function setMode($mode) {
		$mode = strtolower($mode);
		if (!GeneralUtility::inList('be,fe', $mode)) {
			throw new \RuntimeException('$mode must be either "be" or "fe"', 1425123719);
		}
		static::$mode = $mode;
	}

	/**
	 * Gets the uid.
	 *
	 * @return int
	 */
	static public function getUid() {
		return static::$configuration->getUid();
	}

	/**
	 * Gets the name.
	 *
	 * @return mixed
	 * @deprecated since 3.0, will be removed in 3.2
	 */
	static public function getName() {
		GeneralUtility::logDeprecatedFunction();
		return self::$configuration->getName();
	}

	/**
	 * Returns the configuration value of a given feature or FALSE if
	 * the corresponding feature is disabled.
	 *
	 * @param string $feature
	 * @return mixed|FALSE
	 * @deprecated since 3.0, will be removed in 3.2, use getValue() instead
	 */
	static public function is_enable($feature = NULL) {
		GeneralUtility::logDeprecatedFunction();
		return static::getValue($feature);
	}

	/**
	 * Returns the configuration value of a given feature or FALSE if
	 * the corresponding feature is disabled.
	 *
	 * @param string $feature
	 * @return mixed|FALSE
	 */
	static public function getValue($feature) {
		$config = (static::$mode === 'be')
			? static::getBackendConfiguration()
			: static::getFrontendConfiguration();

		return (isset($config[$feature]) ? $config[$feature] : FALSE);
	}

	/**
	 * Returns the list of LDAP attributes used by a mapping configuration.
	 *
	 * @param array|string $mapping
	 * @return array
	 * @deprecated since 3.0, will be removed in 3.2, use getLdapAttributes() instead
	 */
	static public function get_ldap_attributes($mapping = array()) {
		GeneralUtility::logDeprecatedFunction();
		return static::getLdapAttributes($mapping);
	}

	/**
	 * Returns the list of LDAP attributes used by a mapping configuration.
	 *
	 * @param array|string $mapping
	 * @return array
	 */
	static public function getLdapAttributes($mapping = array()) {
		$ldapAttributes = array();
		if (is_array($mapping)) {
			foreach ($mapping as $attribute) {
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
	 * Returns the type of server.
	 *
	 * @param int $uid
	 * @return string
	 * @deprecated since 3.0, will be removed in 3.2, use getServerName() instead
	 */
	static public function get_server_name($uid = NULL) {
		GeneralUtility::logDeprecatedFunction();
		return static::getServerType($uid);
	}

	/**
	 * Returns the type of server.
	 *
	 * @param int $type
	 * @return string
	 */
	static public function getServerType($type = NULL) {
		switch ($type) {
			case 0:
				$server = 'OpenLDAP';
				break;
			case 1:
				$server = 'Active Directory / Novell eDirectory';
				break;
			default:
				$server = NULL;
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
	static public function replace_filter_markers($filter) {
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
	static public function replaceFilterMarkers($filter) {
		$filter = str_replace(array('{USERNAME}', '{USERDN}', '{USERUID}'), '*', $filter);
		return $filter;
	}

	/**
	 * Gets the extension configuration array from table tx_igldapssoauth_config.
	 *
	 * @param int $uid
	 * @return array
	 */
	static protected function select($uid = 0) {
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
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
