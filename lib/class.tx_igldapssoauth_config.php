<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2014 Michael Gagnon <mgagnon@infoglobe.ca>
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
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 */
class tx_igldapssoauth_config {

	protected static $uid;
	protected static $name;
	protected static $typo3_mode;
	protected static $be = array();
	protected static $fe = array();
	protected static $ldap = array();
	protected static $cas = array();

	/**
	 * Initializes the configuration class.
	 *
	 * @param string $typo3_mode
	 * @param int $uid
	 * @return void
	 */
	public static function init($typo3_mode = null, $uid = 0) {
		$globalConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
		self::$uid = $uid ? $uid : $globalConfig['uidConfiguration'];

		// Default TYPO3_MODE is BE
		self::$typo3_mode = $typo3_mode ? strtolower($typo3_mode) : strtolower(TYPO3_MODE);

		// Select configuration from database, merge with extension configuration template and initialise class attributes.
		$config = self::select(self::$uid);
		$config = array_merge($globalConfig, $config);

		self::$name = $config['name'];

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
		self::$be['requiredLDAPGroups'] = $config['requiredLDAPBEGroups'] ? $config['requiredLDAPBEGroups'] : 0;
		self::$be['updateAdminAttribForGroups'] = $config['updateAdminAttribForGroups'] ? $config['updateAdminAttribForGroups'] : 0;
		self::$be['assignGroups'] = $config['assignBEGroups'] ? $config['assignBEGroups'] : 0;
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
		self::$fe['assignGroups'] = $config['assignFEGroups'] ? $config['assignFEGroups'] : 0;
		self::$fe['keepTYPO3Groups'] = $config['keepFEGroups'];
		self::$fe['requiredLDAPGroups'] = $config['requiredLDAPFEGroups'] ? $config['requiredLDAPFEGroups'] : 0;
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
	 * Makes the user mapping.
	 *
	 * @param string $mapping
	 * @param string $filter
	 * @return array
	 */
	private static function make_user_mapping($mapping = '', $filter = '') {
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
	private static function make_group_mapping($mapping = '') {
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
	private static function make_mapping($mapping = '') {
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
	 * @return int|null
	 */
	public static function get_pid($mapping = array()) {
		if (!$mapping) {
			return null;
		}

		if (isset($mapping['pid'])) {
			return is_numeric($mapping['pid']) ? intval($mapping['pid']) : 0;
		}

		return 0;
	}

	/**
	 * @param null $filter
	 * @return string
	 */
	public static function get_username_attribute($filter = NULL) {
		if ($filter && preg_match("'([^$]*)\(([^$]*)={USERNAME}\)'", $filter, $username)) {
			return $username[2];
		}

		return '';
	}

	/**
	 * Gets the CAS configuration.
	 *
	 * @return array
	 */
	public static function getCasConfiguration() {
		return self::$cas;
	}

	/**
	 * Gets the LDAP configuration.
	 *
	 * @return array
	 */
	public static function getLdapConfiguration() {
		return self::$ldap;
	}

	/**
	 * Gets the Frontend configuration.
	 *
	 * @return array
	 */
	public static function getFeConfiguration() {
		return self::$fe;
	}

	/**
	 * Gets the Backend configuration.
	 *
	 * @return array
	 */
	public static function getBeConfiguration() {
		return self::$be;
	}

	/**
	 * Gets the TYPO3 mode.
	 *
	 * @return string
	 */
	public static function getTypo3Mode() {
		return self::$typo3_mode;
	}

	/**
	 * Gets the uid.
	 *
	 * @return mixed
	 */
	public static function getUid() {
		return self::$uid;
	}

	/**
	 * Gets the name.
	 *
	 * @return mixed
	 */
	public static function getName() {
		return self::$name;
	}

	public static function is_enable($feature = null) {
		$config = (self::$typo3_mode === 'be') ? self::getBeConfiguration() : self::getFeConfiguration();
		return (isset($config[$feature]) ? $config[$feature] : FALSE);
	}

	public static function get_ldap_attributes($mapping = array()) {
		$ldap_attributes = array();
		if (is_array($mapping)) {
			foreach ($mapping as $attribute) {
				if (preg_match("`<([^$]*)>`", $attribute, $match)) {
					$ldap_attributes[] = strtolower($match[1]);
				}
			}
		}

		return $ldap_attributes;
	}

	public static function get_server_name($uid = NULL) {
		switch ($uid) {
			case 0 :
				$server = 'OpenLDAP';
				break;
			case 1 :
				$server = 'Novell eDirectory';
				break;
			default:
				$server = NULL;
		}

		return $server;
	}

	public static function replace_filter_markers($filter = null) {
		$filter = str_replace('{USERNAME}', '*', $filter);
		preg_match("'([^$]*)\(([^$]*)={USERDN}\)'", $filter, $member_attribute);
		//return str_replace('('.$member_attribute[2].'={USERDN})', '', $filter);
		return str_replace('{USERDN}', '*', $filter);
	}

	/**
	 * Gets the extension configuration array from table tx_igldapssoauth_config.
	 *
	 * @param int $uid
	 * @return array
	 */
	private static function select($uid = 0) {
		$config = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_igldapssoauth_config',
			'tx_igldapssoauth_config.hidden=0 AND tx_igldapssoauth_config.deleted=0 AND tx_igldapssoauth_config.uid=' . intval($uid)
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

?>