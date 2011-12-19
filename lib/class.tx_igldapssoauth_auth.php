<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
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
 * ************************************************************* */

/**
 * Class tx_igldapssoauth_auth for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 *
 * $Id$
 */
class tx_igldapssoauth_auth {

	var $config;

	/**
	 * ...
	 *
	 * @return ...
	 */
	function ldap_auth($username = null, $password = null) {
		if ($username && tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
			// Possible enhancement: use t3lib_cs::conv_case instead
			$username = strtolower($username);
		}

		// Valid user only if username and connect to LDAP server.
		if ($username && tx_igldapssoauth_ldap::connect(tx_igldapssoauth_config::getLdapConfiguration())) {

			// Get extension configuration from TYPO3 mode (BE / FE).
			$this->config = (tx_igldapssoauth_config::getTypo3Mode() === 'be') ? tx_igldapssoauth_config::getBeConfiguration() : tx_igldapssoauth_config::getFeConfiguration();

			// Valid user from LDAP server.
			if ($userdn = tx_igldapssoauth_ldap::valid_user($username, $password, $this->config['users']['basedn'], $this->config['users']['filter'])) {
				if ($userdn === TRUE) {
					return TRUE;
				}
				return tx_igldapssoauth_auth::synchroniseUser($userdn, $username);
			}

			// LDAP authentication failed.
			tx_igldapssoauth_ldap::disconnect();
			return false;
		}

		// LDAP authentication failed.
		tx_igldapssoauth_ldap::disconnect();
		return false;
	}

	/**
	 * ...
	 *
	 * @return ...
	 */
	function synchroniseUser($userdn, $username = null) {
		// User is valid. Get it from DN.
		$ldap_user = tx_igldapssoauth_auth::get_ldap_user($userdn);

		if (!$username) {
			$userAttribute = tx_igldapssoauth_config::get_username_attribute($this->config['users']['filter']);
			$username = $ldap_user[0][$userAttribute][0];
		}
		// Get user pid from user mapping.
		$typo3_users_pid = tx_igldapssoauth_config::get_pid($this->config['users']['mapping']);
		//$typo3_users_pid = tx_igldapssoauth_config::get_pid($this->config['users']['mapping']) ? tx_igldapssoauth_config::get_pid($this->config['users']['mapping']) : $this->authInfo['db_user']['checkPidList'];
		// Get TYPO3 user from username, DN and pid.
		$typo3_user = tx_igldapssoauth_auth::get_typo3_user($username, $userdn, $typo3_users_pid);

		// User is valid only if exist in TYPO3.
		// Get LDAP groups from LDAP user.
		$ldap_groups = tx_igldapssoauth_auth::get_ldap_groups($ldap_user);
		if ($ldap_groups) {

			// Get pid from group mapping.
			$typo3_group_pid = tx_igldapssoauth_config::get_pid($this->config['groups']['mapping']);
			//$typo3_group_pid = tx_igldapssoauth_config::get_pid($this->config['groups']['mapping']) ? tx_igldapssoauth_config::get_pid($this->config['groups']['mapping']) : $this->authInfo['db_user']['checkPidList'];

			$typo3_groups_tmp = tx_igldapssoauth_auth::get_typo3_groups($ldap_groups, $this->config['groups']['mapping'], $this->authInfo['db_groups']['table'], $typo3_group_pid);

			if (tx_igldapssoauth_config::is_enable('IfGroupExist') && $typo3_groups_tmp['count'] == 0) {

				return false;
			}
			unset($typo3_groups_tmp['count']);

			if ($requiredLDAPGroups = tx_igldapssoauth_config::is_enable('requiredLDAPGroups')) {
				$requiredLDAPGroups = t3lib_div::trimExplode(',', $requiredLDAPGroups);
				$required = FALSE;
				$group_Listuid = array();
				foreach ($typo3_groups_tmp as $typo3_group) {
					$group_Listuid[] = $typo3_group['uid'];
				}
				foreach ($requiredLDAPGroups as $uid) {
					if (in_array($uid, $group_Listuid)) {
						$required = TRUE;
					}
				}
				if (!$required) {
					return false;
				}
			}
			$i = 0;
			foreach ($typo3_groups_tmp as $typo3_group) {

				if (tx_igldapssoauth_config::is_enable('GroupsNotSynchronize') && !$typo3_group['uid']) {

					$typo3_groups[] = null;
				} elseif (tx_igldapssoauth_config::is_enable('GroupsNotSynchronize')) {

					$typo3_groups[] = $typo3_group;
				} elseif (!$typo3_group['uid']) {

					$typo3_group = tx_igldapssoauth_typo3_group::insert($this->authInfo['db_groups']['table'], $typo3_group);

					$typo3_group_merged = tx_igldapssoauth_auth::merge($ldap_groups[$i], $typo3_group[0], $this->config['groups']['mapping']);

					$typo3_group_updated = tx_igldapssoauth_typo3_group::update($this->authInfo['db_groups']['table'], $typo3_group_merged);

					$typo3_group = tx_igldapssoauth_typo3_group::select($this->authInfo['db_groups']['table'], $typo3_group_merged['uid']);

					$typo3_groups[] = $typo3_group[0];
				} else {

					$typo3_group_merged = tx_igldapssoauth_auth::merge($ldap_groups[$i], $typo3_group, $this->config['groups']['mapping']);

					$typo3_group_updated = tx_igldapssoauth_typo3_group::update($this->authInfo['db_groups']['table'], $typo3_group_merged);

					$typo3_group = tx_igldapssoauth_typo3_group::select($this->authInfo['db_groups']['table'], $typo3_group_merged['uid']);

					$typo3_groups[] = $typo3_group[0];
				}

				$i++;
			}
		} else {
			if ($requiredLDAPGroups = tx_igldapssoauth_config::is_enable('requiredLDAPGroups')) {
				return false;
			}
		}

		if (tx_igldapssoauth_config::is_enable('IfUserExist') && !$typo3_user[0]['uid']) {

			return false;

			// User not exist in TYPO3.
		} elseif (!$typo3_user[0]['uid'] && (!empty($typo3_groups) || !tx_igldapssoauth_config::is_enable('DeleteUserIfNoTYPO3Groups'))) {

			include_once(PATH_tslib . 'class.tslib_fe.php');
			if (empty($GLOBALS['TCA'])) {
				tslib_fe::includeTCA();
			}

			// Insert new user: use TCA configuration to override default values
			$table = $this->authInfo['db_user']['table'];
			if (is_array($GLOBALS['TCA'][$table]['columns'])) {
				foreach ($GLOBALS['TCA'][$table]['columns'] as $column => $columnConfig) {
					if (isset($columnConfig['config']['default'])) {
						$defaultValue = $columnConfig['config']['default'];
						$typo3_user[0][$column] = $defaultValue;
					}
				}
			}
			// Set random password
			$charSet = 'abdeghjmnpqrstuvxyzABDEGHJLMNPQRSTVWXYZ23456789@#$%';
			$password = '';
			for ($i = 0; $i < 12; $i++) {
				$password .= $charSet[(rand() % strlen($charSet))];
			}
			$typo3_user[0]['password'] = $password;

			if (tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
				// Possible enhancement: use t3lib_cs::conv_case instead
				$typo3_user[0]['username'] = strtolower($typo3_user[0]['username']);
			}

			$typo3_user = tx_igldapssoauth_typo3_user::insert($table, $typo3_user[0]);
		}

		if (!empty($typo3_user[0]['uid'])) {
			$typo3_user[0]['deleted'] = 0;
			if ((empty($typo3_groups) && tx_igldapssoauth_config::is_enable('DeleteUserIfNoTYPO3Groups'))) {
				$typo3_user[0]['deleted'] = 1;
			}
			// Delete user if no LDAP groups found.
			if (tx_igldapssoauth_config::is_enable('DeleteUserIfNoLDAPGroups') && !$ldap_groups) {

				$typo3_user[0]['deleted'] = 1;

				// If LDAP groups found.
			}
			// Set groups to user.
			$typo3_user = tx_igldapssoauth_typo3_user::set_usergroup($typo3_groups, $typo3_user);
			// Merge LDAP user with TYPO3 user from mapping.
			if ($typo3_user) {
				$typo3_user = tx_igldapssoauth_auth::merge($ldap_user[0], $typo3_user[0], $this->config['users']['mapping']);

				if (tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
					// Possible enhancement: use t3lib_cs::conv_case instead
					$typo3_user['username'] = strtolower($typo3_user['username']);
				}

				// Update TYPO3 user.
				$typo3_user_updated = tx_igldapssoauth_typo3_user::update($this->authInfo['db_user']['table'], $typo3_user);

				$typo3_user['tx_igldapssoauth_from'] = 'LDAP';
			}
		} else {
			$typo3_user = false;
		}
		return $typo3_user;
	}

	/**
	 * Authenticate with CAS
	 *
	 * @return boolean
	 */
	function cas_auth() {

		$cas = tx_igldapssoauth_config::getCasConfiguration();
		phpCAS::client(CAS_VERSION_2_0, (string)$cas['host'], (integer)$cas['port'], (string)$cas['uri']);
		if (!empty($cas['service_url'])) {
			phpCAS::setFixedServiceURL((string)$cas['service_url']);
		}

		switch ($this->login['status']) {
			case 'login' :
				if (phpCAS::isAuthenticated()) {
					phpCAS::logout($cas['logout_url']);
				}
				phpCAS::forceAuthentication();
				break;

			case 'logout' :
				if (tx_igldapssoauth_config::is_enable('DeleteCookieLogout')) {
					if (isset($_SERVER['HTTP_COOKIE'])) {
						$cookies = explode(';', $_SERVER['HTTP_COOKIE']);
						foreach ($cookies as $cookie) {
							$parts = explode('=', $cookie);
							$name = trim($parts0);
							setcookie($name, '', time() - 1000);
							setcookie($name, '', time() - 1000, '/');
						}
					}
				}
				phpCAS::logout($cas['logout_url']);
				return false;
				break;
		}

		if (phpCAS::isAuthenticated()) {
			if (tx_igldapssoauth_config::is_enable('LDAPAuthentication')) {
				$typo3_user = tx_igldapssoauth_auth::ldap_auth(phpCAS::getUser());
			} else {
				$typo3_user = tx_igldapssoauth_typo3_user::select($this->authInfo['db_user']['table'], 0, 0, phpCAS::getUser());
			}
			if ($typo3_user) {
				return $typo3_user;
			} else {
				phpCAS::logout($cas['logout_url']);
				return false;
			}
		}

		return false;
	}

	/**
	 * ...
	 *
	 * @return ...
	 */
	function get_ldap_user($userdn = null) {

		// Get user from LDAP server with DN.
		return tx_igldapssoauth_ldap_user::select($userdn, $this->config['users']['filter'], tx_igldapssoauth_config::get_ldap_attributes($this->config['users']['mapping']));
	}

	/**
	 * ...
	 *
	 * @return ...
	 */
	function get_ldap_groups($ldap_user = array()) {

		// Get groups attributes from group mapping configuration.
		$ldap_group_attributes = tx_igldapssoauth_config::get_ldap_attributes($this->config['groups']['mapping']);
		$ldap_groups = array('count' => 0);

		// Get LDAP groups from membership attribute.
		if (tx_igldapssoauth_config::is_enable('evaluateGroupsFromMembership')) {

			if ($membership = tx_igldapssoauth_ldap_group::get_membership($ldap_user[0], $this->config['users']['mapping'])) {

				$ldap_groups = tx_igldapssoauth_ldap_group::select_from_membership($membership, $this->config['groups']['filter'], $ldap_group_attributes);
			}

			// Get LDAP groups from DN of user.
		} else {

			$ldap_groups = tx_igldapssoauth_ldap_group::select_from_userdn($ldap_user[0]['dn'], $this->config['groups']['basedn'], $this->config['groups']['filter'], $ldap_group_attributes);
		}

		return $ldap_groups;
	}

	/**
	 * ...
	 *
	 * @return ...
	 */
	function get_typo3_user($username = null, $userdn = null, $pid = 0) {

		if ($typo3_user = tx_igldapssoauth_typo3_user::select($this->authInfo['db_user']['table'], 0, $pid, $username, $userdn)) {

			return $typo3_user;
		} else {

			$typo3_user = tx_igldapssoauth_typo3_user::init($this->authInfo['db_user']['table']);
			$typo3_user[0]['pid'] = $pid;
			$typo3_user[0]['tstamp'] = time();

			return $typo3_user;
		}
	}

	/**
	 * ...
	 *
	 * @return ...
	 */
	function get_typo3_groups($ldap_groups = array(), $mapping = array(), $table = null, $pid = 0) {

		$typo3_groups = array();

		if (!$ldap_groups) {
			return $typo3_groups;
		}

		unset($ldap_groups['count']);

		$i = 0;
		foreach ($ldap_groups as $ldap_group) {

			$typo3_group_title = tx_igldapssoauth_typo3_group::get_title($ldap_group, $mapping);
			if ($typo3_group = tx_igldapssoauth_typo3_group::select($table, 0, $pid, $typo3_group_title, $ldap_group['dn'])) {

				$typo3_groups[] = $typo3_group[0];
				$i++;
			} else {

				$typo3_group = tx_igldapssoauth_typo3_group::init($table);
				$typo3_group['pid'] = $pid;
				$typo3_group['tstamp'] = time();
				$typo3_groups[] = $typo3_group;
			}
		}

		$typo3_groups['count'] = $i;

		return $typo3_groups;
	}

	/**
	 * ...
	 *
	 * @return ...
	 */
	function merge($ldap = array(), $typo3 = array(), $mapping = array()) {

		foreach ($mapping as $field => $value) {

			// If field exist in TYPO3.
			if (array_key_exists($field, $typo3) && $field != 'usergroup') {

				// Constant.
				if (preg_match("`{([^$]*)}`", $value, $match)) {

					switch ($value) {

						case '{DATE}' :

							$typo3[$field] = time();
							break;

						case '{RAND}' :

							$typo3[$field] = rand();
							break;
						DEFAULT :
							$params = explode(';', $match[1]);

							foreach ($params as $param) {
								$paramTemps = explode('|', $param);
								$passParams[$paramTemps[0]] = $paramTemps[1];
							}
							$newVal = $passParams['hookName'];
							$ldapAttr = tx_igldapssoauth_config::get_ldap_attributes(array($value));
							// hook for processing user information once inserted or updated in the database
							if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField']) &&
								!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'][$newVal])
							) {

								$_procObj = & t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField'][$newVal]);
								$typo3[$field] = $_procObj->extraMerge($field, $typo3, $ldap, $ldapAttr, $passParams);
							}
							break;
					}

					// LDAP attribute.
				} elseif (preg_match("`<([^$]*)>`", $value, $attribute)) {

					if ($field == 'tx_igldapssoauth_dn' || ($field == 'title' && $value == '<dn>')) {

						$typo3[$field] = $ldap[strtolower($attribute[1])];
					} else {

						$typo3[$field] = $ldap[strtolower($attribute[1])][0];
					}
				} else {

					$typo3[$field] = $value;
				}
			}
		}

		return $typo3;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_auth.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_auth.php']);
}
?>