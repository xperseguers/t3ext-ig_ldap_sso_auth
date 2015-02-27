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

	static protected $typo3_mode;
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
		$configuration = $configurationRepository->fetchByUid($uid);
		static::initialize($typo3_mode, $configuration);
	}

	/**
	 * Initializes the configuration class.
	 *
	 * @param string $typo3_mode
	 * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
	 */
	static public function initialize($typo3_mode, \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration) {
		$globalConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
		if (!is_array($globalConfiguration)) {
			$globalConfiguration = array();
		}

		// Legacy configuration options
		unset($globalConfiguration['evaluateGroupsFromMembership']);

		static::$configuration = $configuration;

		// Default TYPO3_MODE is BE
		static::$typo3_mode = $typo3_mode ? strtolower($typo3_mode) : strtolower(TYPO3_MODE);

		// Select configuration from database, merge with extension configuration template and initialise class attributes.

		static::$domains = array();
		$domainUids = GeneralUtility::intExplode(',', $configuration->getDomains(), TRUE);
		foreach ($domainUids as $domainUid) {
			$row = static::getDatabaseConnection()->exec_SELECTgetSingleRow('domainName', 'sys_domain', 'uid=' . intval($domainUid));
			static::$domains[] = $row['domainName'];
		}

		static::$be['LDAPAuthentication'] = $globalConfiguration['enableBELDAPAuthentication'];
		static::$be['SSOAuthentication'] = FALSE;
		static::$be['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? $globalConfiguration['forceLowerCaseUsername'] : 0;
		static::$be['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
		static::$be['IfUserExist'] = $globalConfiguration['TYPO3BEUserExist'];
		static::$be['IfGroupExist'] = $globalConfiguration['TYPO3BEGroupExist'];
		static::$be['BEfailsafe'] = $globalConfiguration['BEfailsafe'];
		static::$be['DeleteUserIfNoLDAPGroups'] = 0;
		static::$be['DeleteUserIfNoTYPO3Groups'] = 0;
		static::$be['GroupsNotSynchronize'] = $globalConfiguration['TYPO3BEGroupsNotSynchronize'];
		static::$be['requiredLDAPGroups'] = $configuration->getBackendGroupsRequired() ? $configuration->getBackendGroupsRequired() : 0;
		static::$be['updateAdminAttribForGroups'] = $configuration->getBackendGroupsAdministrator() ? $configuration->getBackendGroupsAdministrator() : 0;
		static::$be['assignGroups'] = $configuration->getBackendGroupsAssigned() ? $configuration->getBackendGroupsAssigned() : 0;
		static::$be['keepTYPO3Groups'] = $globalConfiguration['keepBEGroups'];
		static::$be['users']['basedn'] = explode('||', $configuration->getBackendUsersBaseDn());
		static::$be['users']['filter'] = $configuration->getBackendUsersFilter();
		static::$be['users']['mapping'] = static::make_user_mapping($configuration->getBackendUsersMapping(), $configuration->getBackendUsersFilter());
		static::$be['groups']['basedn'] = $configuration->getBackendGroupsBaseDn();
		static::$be['groups']['filter'] = $configuration->getBackendGroupsFilter();
		static::$be['groups']['mapping'] = static::make_group_mapping($configuration->getBackendGroupsMapping());

		static::$fe['LDAPAuthentication'] = $globalConfiguration['enableFELDAPAuthentication'];
		static::$fe['SSOAuthentication'] = (bool)$globalConfiguration['enableFESSO'];
		static::$fe['forceLowerCaseUsername'] = $globalConfiguration['forceLowerCaseUsername'] ? $globalConfiguration['forceLowerCaseUsername'] : 0;
		static::$fe['evaluateGroupsFromMembership'] = $configuration->getGroupMembership() === static::GROUP_MEMBERSHIP_FROM_MEMBER;
		static::$fe['IfUserExist'] = $globalConfiguration['TYPO3FEUserExist'];
		static::$fe['IfGroupExist'] = $globalConfiguration['TYPO3FEGroupExist'];
		static::$fe['BEfailsafe'] = 0;
		static::$fe['updateAdminAttribForGroups'] = 0;
		static::$fe['DeleteUserIfNoTYPO3Groups'] = $globalConfiguration['TYPO3FEDeleteUserIfNoTYPO3Groups'];
		static::$fe['DeleteUserIfNoLDAPGroups'] = $globalConfiguration['TYPO3FEDeleteUserIfNoLDAPGroups'];
		static::$fe['GroupsNotSynchronize'] = $globalConfiguration['TYPO3FEGroupsNotSynchronize'];
		static::$fe['assignGroups'] = $configuration->getFrontendGroupsAssigned() ? $configuration->getFrontendGroupsAssigned() : 0;
		static::$fe['keepTYPO3Groups'] = $globalConfiguration['keepFEGroups'];
		static::$fe['requiredLDAPGroups'] = $configuration->getFrontendGroupsRequired() ? $configuration->getFrontendGroupsRequired() : 0;
		static::$fe['users']['basedn'] = explode('||', $configuration->getFrontendUsersBaseDn());
		static::$fe['users']['filter'] = $configuration->getFrontendUsersFilter();
		static::$fe['users']['mapping'] = static::make_user_mapping($configuration->getFrontendUsersMapping(), $configuration->getFrontendUsersFilter());
		static::$fe['groups']['basedn'] = $configuration->getFrontendGroupsBaseDn();
		static::$fe['groups']['filter'] = $configuration->getFrontendGroupsFilter();
		static::$fe['groups']['mapping'] = static::make_group_mapping($configuration->getFrontendGroupsMapping());

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
		return static::$typo3_mode !== NULL;
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
	static protected function make_user_mapping($mapping = '', $filter = '') {
		// Default fields : username, tx_igldapssoauth_dn

		$user_mapping = static::make_mapping($mapping);
		$user_mapping['username'] = '<' . static::get_username_attribute($filter) . '>';
		$user_mapping['tx_igldapssoauth_dn'] = '<dn>';
		$user_mapping['tx_igldapssoauth_id'] = static::getUid();

		return $user_mapping;
	}

	/**
	 * Makes a group mapping.
	 *
	 * @param string $mapping
	 * @return array
	 */
	static protected function make_group_mapping($mapping = '') {
		// Default fields : title, tx_igldapssoauth_dn

		$group_mapping = static::make_mapping($mapping);
		if (!isset($group_mapping['title'])) {
			$group_mapping['title'] = '<dn>';
		}
		$group_mapping['tx_igldapssoauth_dn'] = '<dn>';

		return $group_mapping;
	}

	/**
	 * Makes a mapping.
	 *
	 * @param string $mapping
	 * @return array
	 */
	static public function make_mapping($mapping = '') {
		$config_mapping = array();
		$mapping_array = explode(LF, $mapping);

		foreach ($mapping_array as $field) {
			$field_mapping = explode('=', $field);
			if (isset($field_mapping[1]) && (bool)$field_mapping[1]) {
				$config_mapping[trim($field_mapping[0])] = trim($field_mapping[1]);
			}
		}

		return $config_mapping;
	}

	/**
	 * Gets the Pid to use.
	 *
	 * @param array $mapping
	 * @return int|NULL
	 */
	static public function get_pid($mapping = array()) {
		if (!$mapping) {
			return NULL;
		}

		if (isset($mapping['pid'])) {
			return is_numeric($mapping['pid']) ? intval($mapping['pid']) : 0;
		}

		return 0;
	}

	/**
	 * Returns the LDAP attribute holding the username.
	 *
	 * @param string $filter
	 * @return string
	 */
	static public function get_username_attribute($filter = NULL) {
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
	 */
	static public function getFeConfiguration() {
		return static::$fe;
	}

	/**
	 * Gets the Backend configuration.
	 *
	 * @return array
	 */
	static public function getBeConfiguration() {
		return static::$be;
	}

	/**
	 * Gets the TYPO3 mode.
	 *
	 * @return string
	 */
	static public function getTypo3Mode() {
		return static::$typo3_mode;
	}

	/**
	 * Sets the TYPO3 mode.
	 *
	 * @param string $typo3_mode
	 * @return void
	 */
	static public function setTypo3Mode($typo3_mode) {
		static::$typo3_mode = $typo3_mode;
	}

	/**
	 * Gets the uid.
	 *
	 * @return int
	 * @deprecated since 3.0, will be removed in 3.2
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
		return self::$configuration->getName();
	}

	static public function is_enable($feature = NULL) {
		$config = (static::$typo3_mode === 'be') ? static::getBeConfiguration() : static::getFeConfiguration();
		return (isset($config[$feature]) ? $config[$feature] : FALSE);
	}

	static public function get_ldap_attributes($mapping = array()) {
		$ldap_attributes = array();
		if (is_array($mapping)) {
			foreach ($mapping as $attribute) {
				if (preg_match_all('/<(.+?)>/', $attribute, $matches)) {
					foreach ($matches[1] as $matchedAttribute) {
						$ldap_attributes[] = strtolower($matchedAttribute);
					}
				}
			}
		}

		return array_values(array_unique($ldap_attributes));
	}

	static public function get_server_name($uid = NULL) {
		switch ($uid) {
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
	 */
	static public function replace_filter_markers($filter) {
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
