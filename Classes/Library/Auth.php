<?php
/***************************************************************
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
 ***************************************************************/

/**
 * Class tx_igldapssoauth_auth for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 */
class tx_igldapssoauth_auth {

	static protected $config;

	/**
	 * @var tx_igldapssoauth_sv1
	 */
	static protected $authenticationService;

	/**
	 * Sets the base authentication class.
	 *
	 * @param tx_igldapssoauth_sv1 $authenticationService
	 * @return void
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_auth::setAuthenticationService() instead.
	 */
	static public function init(tx_igldapssoauth_sv1 $authenticationService) {
		t3lib_div::logDeprecatedFunction();
		self::$authenticationService = $authenticationService;
	}

	/**
	 * Sets the authentication service.
	 *
	 * @param tx_igldapssoauth_sv1 $authenticationService
	 * @return void
	 */
	static public function setAuthenticationService(tx_igldapssoauth_sv1 $authenticationService) {
		self::$authenticationService = $authenticationService;
	}

	/**
	 * Initializes the configuration based on current TYPO3 mode (BE/FE) and
	 * returns it afterwards.
	 *
	 * @return array The corresponding configuration (BE/FE)
	 */
	static public function initializeConfiguration() {
		if (tx_igldapssoauth_config::getTypo3Mode() === 'be') {
			self::$config = tx_igldapssoauth_config::getBeConfiguration();
		} else {
			self::$config = tx_igldapssoauth_config::getFeConfiguration();
		}
		return self::$config;
	}

	/**
	 * Authenticates using LDAP and returns a user record or FALSE
	 * if operation fails.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool|array TRUE or array of user info on success, otherwise FALSE
	 */
	static public function ldap_auth($username = NULL, $password = NULL) {
		if ($username && tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
			// Possible enhancement: use t3lib_cs::conv_case instead
			$username = strtolower($username);
		}

		// Valid user only if username and connect to LDAP server.
		if ($username && tx_igldapssoauth_ldap::connect(tx_igldapssoauth_config::getLdapConfiguration())) {

			// Set extension configuration from TYPO3 mode (BE/FE).
			self::initializeConfiguration();

			// Valid user from LDAP server.
			if ($userdn = tx_igldapssoauth_ldap::valid_user($username, $password, self::$config['users']['basedn'], self::$config['users']['filter'])) {
				if ($userdn === TRUE) {
					return TRUE;
				}
				return self::synchroniseUser($userdn, $username);
			}

			// LDAP authentication failed.
			tx_igldapssoauth_ldap::disconnect();
			return FALSE;
		}

		// LDAP authentication failed.
		tx_igldapssoauth_ldap::disconnect();
		return FALSE;
	}

	/**
	 * Synchronizes a user.
	 *
	 * @param string $userdn
	 * @param $username
	 * @return array|FALSE
	 */
	static public function synchroniseUser($userdn, $username = NULL) {
		// User is valid. Get it from DN.
		$ldap_user = self::get_ldap_user($userdn);

		if (!is_array($ldap_user[0])) {
			return FALSE;
		}

		if (!$username) {
			$userAttribute = tx_igldapssoauth_config::get_username_attribute(self::$config['users']['filter']);
			$username = $ldap_user[0][$userAttribute][0];
		}
		// Get user pid from user mapping.
		$typo3_users_pid = tx_igldapssoauth_config::get_pid(self::$config['users']['mapping']);
		//$typo3_users_pid = tx_igldapssoauth_config::get_pid($this->config['users']['mapping']) ? tx_igldapssoauth_config::get_pid($this->config['users']['mapping']) : $this->authInfo['db_user']['checkPidList'];

		// Get TYPO3 user from username, DN and pid.
		$typo3_user = self::get_typo3_user($username, $userdn, $typo3_users_pid);
		if (!$typo3_user) {
			// Non-existing local users are not allowed to authenticate
			return FALSE;
		}

		// User is valid only if exist in TYPO3.
		// Get LDAP groups from LDAP user.
		$ldap_groups = self::get_ldap_groups($ldap_user);
		if ($ldap_groups) {

			// Get pid from group mapping.
			$typo3_group_pid = tx_igldapssoauth_config::get_pid(self::$config['groups']['mapping']);
			//$typo3_group_pid = tx_igldapssoauth_config::get_pid($this->config['groups']['mapping']) ? tx_igldapssoauth_config::get_pid($this->config['groups']['mapping']) : $this->authInfo['db_user']['checkPidList'];

			$typo3_groups_tmp = tx_igldapssoauth_auth::get_typo3_groups($ldap_groups, self::$config['groups']['mapping'], self::$authenticationService->authInfo['db_groups']['table'], $typo3_group_pid);

			if (tx_igldapssoauth_config::is_enable('IfGroupExist') && $typo3_groups_tmp['count'] == 0) {

				return FALSE;
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
					return FALSE;
				}
			}
			$i = 0;
			foreach ($typo3_groups_tmp as $typo3_group) {

				if (tx_igldapssoauth_config::is_enable('GroupsNotSynchronize') && !$typo3_group['uid']) {
					$typo3_groups[] = NULL;
				} elseif (tx_igldapssoauth_config::is_enable('GroupsNotSynchronize')) {
					$typo3_groups[] = $typo3_group;
				} elseif (!$typo3_group['uid']) {
					$typo3_group = tx_igldapssoauth_typo3_group::insert(self::$authenticationService->authInfo['db_groups']['table'], $typo3_group);

					$typo3_group_merged = tx_igldapssoauth_auth::merge($ldap_groups[$i], $typo3_group[0], self::$config['groups']['mapping']);

					$typo3_group_updated = tx_igldapssoauth_typo3_group::update(self::$authenticationService->authInfo['db_groups']['table'], $typo3_group_merged);

					$typo3_group = tx_igldapssoauth_typo3_group::select(self::$authenticationService->authInfo['db_groups']['table'], $typo3_group_merged['uid']);

					$typo3_groups[] = $typo3_group[0];
				} else {

					$typo3_group_merged = tx_igldapssoauth_auth::merge($ldap_groups[$i], $typo3_group, self::$config['groups']['mapping']);

					$typo3_group_updated = tx_igldapssoauth_typo3_group::update(self::$authenticationService->authInfo['db_groups']['table'], $typo3_group_merged);

					$typo3_group = tx_igldapssoauth_typo3_group::select(self::$authenticationService->authInfo['db_groups']['table'], $typo3_group_merged['uid']);

					$typo3_groups[] = $typo3_group[0];
				}

				$i++;
			}
		} else {
			if ($requiredLDAPGroups = tx_igldapssoauth_config::is_enable('requiredLDAPGroups')) {
				return FALSE;
			}
		}

		if (tx_igldapssoauth_config::is_enable('IfUserExist') && !$typo3_user[0]['uid']) {
			return FALSE;
			// User does not exist in TYPO3.
		} elseif (!$typo3_user[0]['uid'] && (!empty($typo3_groups) || !tx_igldapssoauth_config::is_enable('DeleteUserIfNoTYPO3Groups'))) {

			if (empty($GLOBALS['TCA'])) {
				/** @var $tslibFe tslib_fe */
				$tslibFe = t3lib_div::makeInstance('tslib_fe', $GLOBALS['TYPO3_CONF_VARS'], t3lib_div::_GP('id'), '');
				$tslibFe->includeTCA();
			}

			// Insert new user: use TCA configuration to override default values
			$table = self::$authenticationService->authInfo['db_user']['table'];
			if (is_array($GLOBALS['TCA'][$table]['columns'])) {
				foreach ($GLOBALS['TCA'][$table]['columns'] as $column => $columnConfig) {
					if (isset($columnConfig['config']['default'])) {
						$defaultValue = $columnConfig['config']['default'];
						$typo3_user[0][$column] = $defaultValue;
					}
				}
			}

			if (tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
				// Possible enhancement: use t3lib_cs::conv_case instead
				$typo3_user[0]['username'] = strtolower($typo3_user[0]['username']);
			}

			$typo3_user = tx_igldapssoauth_typo3_user::insert($table, $typo3_user[0]);
		}

		if (!empty($typo3_user[0]['uid'])) {
			$typo3_user[0]['deleted'] = 0;

			// Set random password
			/** @var tx_saltedpasswords_salts $instance */
			$instance = NULL;
			if (t3lib_extMgm::isLoaded('saltedpasswords')) {
				$instance = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL, TYPO3_MODE);
			}
			$password = t3lib_div::generateRandomBytes(16);
			$typo3_user[0]['password'] = $instance ? $instance->getHashedPassword($password) : md5($password);

			if ((empty($typo3_groups) && tx_igldapssoauth_config::is_enable('DeleteUserIfNoTYPO3Groups'))) {
				$typo3_user[0]['deleted'] = 1;
			}
			// Delete user if no LDAP groups found.
			if (tx_igldapssoauth_config::is_enable('DeleteUserIfNoLDAPGroups') && !$ldap_groups) {

				$typo3_user[0]['deleted'] = 1;

				// If LDAP groups found.
			}
			// Set groups to user.
			$typo3_user = tx_igldapssoauth_typo3_user::set_usergroup($typo3_groups, $typo3_user, self::$authenticationService);
			// Merge LDAP user with TYPO3 user from mapping.
			if ($typo3_user) {
				$typo3_user = tx_igldapssoauth_auth::merge($ldap_user[0], $typo3_user[0], self::$config['users']['mapping']);

				if (tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
					// Possible enhancement: use t3lib_cs::conv_case instead
					$typo3_user['username'] = strtolower($typo3_user['username']);
				}

				// Update TYPO3 user.
				$typo3_user_updated = tx_igldapssoauth_typo3_user::update(self::$authenticationService->authInfo['db_user']['table'], $typo3_user);

				$typo3_user['tx_igldapssoauth_from'] = 'LDAP';
			}
		} else {
			$typo3_user = FALSE;
		}
		return $typo3_user;
	}

	/**
	 * Authenticates with CAS.
	 *
	 * @return boolean
	 */
	static public function cas_auth() {

		$cas = tx_igldapssoauth_config::getCasConfiguration();
		phpCAS::client(CAS_VERSION_2_0, (string)$cas['host'], (integer)$cas['port'], (string)$cas['uri']);
		if (!empty($cas['service_url'])) {
			phpCAS::setFixedServiceURL((string)$cas['service_url']);
		}

		switch (self::$authenticationService->login['status']) {
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
							$name = trim($parts[0]);
							setcookie($name, '', time() - 1000);
							setcookie($name, '', time() - 1000, '/');
						}
					}
				}
				phpCAS::logout($cas['logout_url']);
				return FALSE;
				break;
		}

		if (phpCAS::isAuthenticated()) {
			if (tx_igldapssoauth_config::is_enable('LDAPAuthentication')) {
				$typo3_user = self::ldap_auth(phpCAS::getUser());
			} else {
				$typo3_user = tx_igldapssoauth_typo3_user::select(self::$authenticationService->authInfo['db_user']['table'], 0, 0, phpCAS::getUser());
			}
			if ($typo3_user) {
				return $typo3_user;
			} else {
				phpCAS::logout($cas['logout_url']);
				return FALSE;
			}
		}

		return FALSE;
	}

	/**
	 * Returns a LDAP user.
	 *
	 * @param string $userdn
	 * @return array
	 */
	static protected function get_ldap_user($userdn = NULL) {
		// Get user from LDAP server with DN.
		return tx_igldapssoauth_ldap_user::select($userdn, self::$config['users']['filter'], tx_igldapssoauth_config::get_ldap_attributes(self::$config['users']['mapping']));
	}

	/**
	 * Returns LDAP groups.
	 *
	 * @param array $ldap_user
	 * @return array
	 */
	static protected function get_ldap_groups(array $ldap_user = array()) {

		// Get groups attributes from group mapping configuration.
		$ldap_group_attributes = tx_igldapssoauth_config::get_ldap_attributes(self::$config['groups']['mapping']);
		$ldap_groups = array('count' => 0);

		// Get LDAP groups from membership attribute.
		if (tx_igldapssoauth_config::is_enable('evaluateGroupsFromMembership')) {

			if ($membership = tx_igldapssoauth_ldap_group::get_membership($ldap_user[0], self::$config['users']['mapping'])) {

				$ldap_groups = tx_igldapssoauth_ldap_group::select_from_membership($membership, self::$config['groups']['filter'], $ldap_group_attributes);
			}

			// Get LDAP groups from DN of user.
		} else {

			$ldap_groups = tx_igldapssoauth_ldap_group::select_from_userdn($ldap_user[0]['dn'], self::$config['groups']['basedn'], self::$config['groups']['filter'], $ldap_group_attributes);
		}

		return $ldap_groups;
	}

	/**
	 * Returns a TYPO3 user.
	 *
	 * @param string $username
	 * @param string $userdn
	 * @param integer $pid
	 * @return array
	 */
	static protected function get_typo3_user($username = NULL, $userdn = NULL, $pid = 0) {
		if ($typo3_user = tx_igldapssoauth_typo3_user::select(self::$authenticationService->authInfo['db_user']['table'], 0, $pid, $username, $userdn)) {
			if (tx_igldapssoauth_config::is_enable('IfUserExist')) {
				// Ensure every returned record is active
				$numberOfUsers = count($typo3_user);
				for ($i = 0; $i < $numberOfUsers; $i++) {
					if (!empty($typo3_user[$i]['deleted'])) {
						// User is deleted => behave as if it did not exist at all!
						// Note: if user is inactive (disable=1), this will be catched by TYPO3 automatically
						unset($typo3_user[$i]);
					}
				}

				// Reset the array's indices
				$typo3_user = array_values($typo3_user);
			}
			return $typo3_user;
		} elseif (!tx_igldapssoauth_config::is_enable('IfUserExist')) {
			$typo3_user = tx_igldapssoauth_typo3_user::init(self::$authenticationService->authInfo['db_user']['table']);

			$typo3_user[0]['pid'] = $pid;
			$typo3_user[0]['crdate'] = $GLOBALS['EXEC_TIME'];
			$typo3_user[0]['tstamp'] = $GLOBALS['EXEC_TIME'];
			$typo3_user[0]['username'] = $username;
			$typo3_user[0]['tx_igldapssoauth_dn'] = $userdn;

			return $typo3_user;
		}
	}

	/**
	 * Returns TYPO3 groups.
	 *
	 * @param array $ldap_groups
	 * @param array $mapping
	 * @param string $table
	 * @param integer $pid
	 * @return array
	 */
	static public function get_typo3_groups(array $ldap_groups = array(), array $mapping = array(), $table = NULL, $pid = 0) {
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
	 * Merges a user from LDAP and from TYPO3.
	 *
	 * @param array $ldap
	 * @param array $typo3
	 * @param array $mapping
	 * @return array
	 */
	static public function merge(array $ldap = array(), array $typo3 = array(), array $mapping = array()) {
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
						default:
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
					if ($field === 'tx_igldapssoauth_dn' || ($field === 'title' && $value === '<dn>')) {
						$typo3[$field] = $ldap[strtolower($attribute[1])];
					} else {
						$typo3[$field] = self::replaceLdapMarkers($value, $ldap);
					}
				} else {
					$typo3[$field] = $value;
				}
			}
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
	static public function replaceLdapMarkers($markerString, $ldapData) {
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

}
