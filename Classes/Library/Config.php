<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
 *  (c) 2007-2013 Michael Gagnon <mgagnon@infoglobe.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class tx_igldapssoauth_config for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_config {

	static protected $uid;
	static protected $name;
	static protected $typo3_mode;
	static protected $be = array();
	static protected $fe = array();
	static protected $ldap = array();
	static protected $cas = array();
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
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('domainName', 'sys_domain', 'uid=' . intval($domainUid));
			self::$domains[] = $row['domainName'];
		}

		self::$be['LDAPAuthentication'] = $config['enableBELDAPAuthentication'];
		self::$be['CASAuthentication'] = 0;
		self::$be['DeleteCookieLogout'] = 0;
		self::$be['forceLowerCaseUsername'] = $config['forceLowerCaseUsername'] ? $config['forceLowerCaseUsername'] : 0;
		self::$be['evaluateGroupsFromMembership'] = $config['evaluateGroupsFromMembership'];
		self::$be['IfUserExist'] = $config['TYPO3BEUserExist'];
		self::$be['IfGroupExist'] = 0;
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
		self::$fe['DeleteCookieLogout'] = $config['DeleteCookieLogout'];
		self::$fe['CASAuthentication'] = $config['enableFECASAuthentication'];
		self::$fe['forceLowerCaseUsername'] = $config['forceLowerCaseUsername'] ? $config['forceLowerCaseUsername'] : 0;
		self::$fe['evaluateGroupsFromMembership'] = $config['evaluateGroupsFromMembership'];
		self::$fe['IfUserExist'] = 0;
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
				case (substr($key, 0, 4) === 'cas_'):
					self::$cas[substr($key, 4)] = $value;
					break;

				case (substr($key, 0, 5) === 'ldap_'):
					self::$ldap[substr($key, 5)] = $value;
					break;
			}
		}
		self::$ldap['charset'] = $config['ldap_charset'] ? $config['ldap_charset'] : 'utf-8';
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
		if (!array_key_exists('title', $group_mapping)) {
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
	static protected function make_mapping($mapping = '') {
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
	 * @param string $filter
	 * @return string
	 */
	static public function get_username_attribute($filter = NULL) {
		if ($filter && preg_match("'([^$]*)\\(([^$]*)={USERNAME}\\)'", $filter, $username)) {
			return $username[2];
		}

		return '';
	}

	/**
	 * Gets the CAS configuration.
	 *
	 * @return array
	 */
	static public function getCasConfiguration() {
		return self::$cas;
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

		return $ldap_attributes;
	}

	static public function get_server_name($uid = NULL) {
		switch ($uid) {
			case 0:
				$server = 'OpenLDAP';
				break;
			case 1:
				$server = 'Novell eDirectory';
				break;
			default:
				$server = NULL;
				break;
		}

		return $server;
	}

	static public function replace_filter_markers($filter = NULL) {
		$filter = str_replace('{USERNAME}', '*', $filter);
		preg_match("'([^$]*)\\(([^$]*)={USERDN}\\)'", $filter, $member_attribute);
		//return str_replace('('.$member_attribute[2].'={USERDN})', '', $filter);
		return str_replace('{USERDN}', '*', $filter);
	}

	/**
	 * Gets the extension configuration array from table tx_igldapssoauth_config.
	 *
	 * @param int $uid
	 * @return array
	 */
	static protected function select($uid = 0) {
		$config = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_igldapssoauth_config',
			'deleted=0 AND hidden=0 AND uid=' . intval($uid)
		);

		return count($config) == 1 ? $config[0] : array();
	}

	/*
	function update($config = array()) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			'tx_igldapssoauth_config',
			'tx_igldapssoauth_config.uid=' . intval($config['uid']),
			$config,
			FALSE
		);

		self::init(tx_igldapssoauth_config::select($config['uid']));
	}
	*/
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_config.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_config.php']);
}
