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

/**
 * Class tx_igldapssoauth_config for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_config {

	const GROUP_MEMBERSHIP_FROM_GROUP = 1;
	const GROUP_MEMBERSHIP_FROM_MEMBER = 2;

	static protected $uid;
	static protected $name;
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
	 */
	static public function init($typo3_mode = NULL, $uid) {
		$globalConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
		if (!is_array($globalConfig)) $globalConfig = array();

		// Legacy configuration options
		unset($globalConfig['evaluateGroupsFromMembership']);

		self::$uid = $uid;

		// Default TYPO3_MODE is BE
		self::$typo3_mode = $typo3_mode ? strtolower($typo3_mode) : strtolower(TYPO3_MODE);

		// Select configuration from database, merge with extension configuration template and initialise class attributes.
		$config = self::select(self::$uid);
		$config = array_merge($globalConfig, $config);

		self::$name = $config['name'];
		self::$domains = array();
		$domainUids = t3lib_div::intExplode(',', $config['domains'], TRUE);
		foreach ($domainUids as $domainUid) {
			$row = self::getDatabaseConnection()->exec_SELECTgetSingleRow('domainName', 'sys_domain', 'uid=' . intval($domainUid));
			self::$domains[] = $row['domainName'];
		}

		self::$be['LDAPAuthentication'] = $config['enableBELDAPAuthentication'];
		self::$be['SSOAuthentication'] = FALSE;
		self::$be['DeleteCookieLogout'] = 0;
		self::$be['forceLowerCaseUsername'] = $config['forceLowerCaseUsername'] ? $config['forceLowerCaseUsername'] : 0;
		self::$be['evaluateGroupsFromMembership'] = $config['group_membership'] == self::GROUP_MEMBERSHIP_FROM_MEMBER;
		self::$be['IfUserExist'] = $config['TYPO3BEUserExist'];
		self::$be['IfGroupExist'] = $config['TYPO3BEGroupExist'];
		self::$be['BEfailsafe'] = $config['BEfailsafe'];
		self::$be['DeleteUserIfNoLDAPGroups'] = 0;
		self::$be['DeleteUserIfNoTYPO3Groups'] = 0;
		self::$be['GroupsNotSynchronize'] = $config['TYPO3BEGroupsNotSynchronize'];
		self::$be['requiredLDAPGroups'] = $config['be_groups_required'] ? $config['be_groups_required'] : 0;
		self::$be['updateAdminAttribForGroups'] = $config['be_groups_admin'] ? $config['be_groups_admin'] : 0;
		self::$be['assignGroups'] = $config['be_groups_assigned'] ? $config['be_groups_assigned'] : 0;
		self::$be['keepTYPO3Groups'] = $config['keepBEGroups'];
		self::$be['users']['basedn'] = explode('||', $config['be_users_basedn']);
		self::$be['users']['filter'] = $config['be_users_filter'];
		self::$be['users']['mapping'] = self::make_user_mapping($config['be_users_mapping'], $config['be_users_filter']);
		self::$be['groups']['basedn'] = $config['be_groups_basedn'];
		self::$be['groups']['filter'] = $config['be_groups_filter'];
		self::$be['groups']['mapping'] = self::make_group_mapping($config['be_groups_mapping']);

		self::$fe['LDAPAuthentication'] = $config['enableFELDAPAuthentication'];
		self::$fe['SSOAuthentication'] = (bool)$config['enableFESSO'];
		self::$fe['DeleteCookieLogout'] = $config['DeleteCookieLogout'];
		self::$fe['forceLowerCaseUsername'] = $config['forceLowerCaseUsername'] ? $config['forceLowerCaseUsername'] : 0;
		self::$fe['evaluateGroupsFromMembership'] = $config['group_membership'] == self::GROUP_MEMBERSHIP_FROM_MEMBER;
		self::$fe['IfUserExist'] = $config['TYPO3FEUserExist'];
		self::$fe['IfGroupExist'] = $config['TYPO3FEGroupExist'];
		self::$fe['BEfailsafe'] = 0;
		self::$fe['updateAdminAttribForGroups'] = 0;
		self::$fe['DeleteUserIfNoTYPO3Groups'] = $config['TYPO3FEDeleteUserIfNoTYPO3Groups'];
		self::$fe['DeleteUserIfNoLDAPGroups'] = $config['TYPO3FEDeleteUserIfNoLDAPGroups'];
		self::$fe['GroupsNotSynchronize'] = $config['TYPO3FEGroupsNotSynchronize'];
		self::$fe['assignGroups'] = $config['fe_groups_assigned'] ? $config['fe_groups_assigned'] : 0;
		self::$fe['keepTYPO3Groups'] = $config['keepFEGroups'];
		self::$fe['requiredLDAPGroups'] = $config['fe_groups_required'] ? $config['fe_groups_required'] : 0;
		self::$fe['users']['basedn'] = explode('||', $config['fe_users_basedn']);
		self::$fe['users']['filter'] = $config['fe_users_filter'];
		self::$fe['users']['mapping'] = self::make_user_mapping($config['fe_users_mapping'], $config['fe_users_filter']);
		self::$fe['groups']['basedn'] = $config['fe_groups_basedn'];
		self::$fe['groups']['filter'] = $config['fe_groups_filter'];
		self::$fe['groups']['mapping'] = self::make_group_mapping($config['fe_groups_mapping']);

		foreach ($config as $key => $value) {
			switch (TRUE) {
				case (substr($key, 0, 5) === 'ldap_'):
					self::$ldap[substr($key, 5)] = $value;
					break;
			}
		}
		self::$ldap['charset'] = $config['ldap_charset'] ? $config['ldap_charset'] : 'utf-8';
	}

	/**
	 * Returns TRUE if configuration has been initialized, otherwise FALSE.
	 *
	 * @return bool
	 */
	static public function isInitialized() {
		return self::$typo3_mode !== NULL;
	}

	/**
	 * Returns TRUE if this configuration is enabled for current host.
	 *
	 * @return bool
	 */
	static public function isEnabledForCurrentHost() {
		static $host = NULL;
		if ($host === NULL && count(self::$domains) > 0) {
			$host = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		}
		return count(self::$domains) === 0 || in_array($host, self::$domains);
	}

	/**
	 * Returns the list of domains.
	 *
	 * @return array
	 */
	static public function getDomains() {
		return self::$domains;
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

		$user_mapping = self::make_mapping($mapping);
		$user_mapping['username'] = '<' . self::get_username_attribute($filter) . '>';
		$user_mapping['tx_igldapssoauth_dn'] = '<dn>';
		$user_mapping['tx_igldapssoauth_id'] = self::getUid();

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

		$group_mapping = self::make_mapping($mapping);
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
		return self::$ldap;
	}

	/**
	 * Gets the Frontend configuration.
	 *
	 * @return array
	 */
	static public function getFeConfiguration() {
		return self::$fe;
	}

	/**
	 * Gets the Backend configuration.
	 *
	 * @return array
	 */
	static public function getBeConfiguration() {
		return self::$be;
	}

	/**
	 * Gets the TYPO3 mode.
	 *
	 * @return string
	 */
	static public function getTypo3Mode() {
		return self::$typo3_mode;
	}

	/**
	 * Sets the TYPO3 mode.
	 *
	 * @param string $typo3_mode
	 * @return void
	 */
	static public function setTypo3Mode($typo3_mode) {
		self::$typo3_mode = $typo3_mode;
	}

	/**
	 * Gets the uid.
	 *
	 * @return mixed
	 */
	static public function getUid() {
		return self::$uid;
	}

	/**
	 * Gets the name.
	 *
	 * @return mixed
	 */
	static public function getName() {
		return self::$name;
	}

	static public function is_enable($feature = NULL) {
		$config = (self::$typo3_mode === 'be') ? self::getBeConfiguration() : self::getFeConfiguration();
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
		$config = self::getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			'tx_igldapssoauth_config',
			'deleted=0 AND hidden=0 AND uid=' . intval($uid)
		);

		return count($config) == 1 ? $config[0] : array();
	}

	/**
	 * Returns the database connection.
	 *
	 * @return t3lib_DB
	 */
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
