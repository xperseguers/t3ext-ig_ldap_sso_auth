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
 * Class tx_igldapssoauth_auth for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 */
class tx_igldapssoauth_auth {

	static protected $config;
	static protected $lastAuthenticationDiagnostic;

	/**
	 * Temporary storage for LDAP groups (should be removed after some refactoring).
	 *
	 * @var array|NULL
	 */
	static protected $ldapGroups = NULL;

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
	 * @throws Exception when LDAP extension for PHP is not available
	 */
	static public function ldap_auth($username = NULL, $password = NULL) {
		self::$lastAuthenticationDiagnostic = '';

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
				Tx_IgLdapSsoAuth_Utility_Debug::info(sprintf('Successfully authenticated user "%s" with LDAP', $username));

				if ($userdn === TRUE) {
					return TRUE;
				}
				return self::synchroniseUser($userdn, $username);
			} else {
				self::$lastAuthenticationDiagnostic = tx_igldapssoauth_ldap::getLastBindDiagnostic();
				if (!empty(self::$lastAuthenticationDiagnostic)) {
					Tx_IgLdapSsoAuth_Utility_Debug::notice(self::$lastAuthenticationDiagnostic);
				}
			}

			// LDAP authentication failed.
			tx_igldapssoauth_ldap::disconnect();

			// This is a notice because it is fine to fallback to standard TYPO3 authentication
			Tx_IgLdapSsoAuth_Utility_Debug::notice(sprintf('Could not authenticate user "%s" with LDAP', $username));

			return FALSE;
		}

		// LDAP authentication failed.
		Tx_IgLdapSsoAuth_Utility_Debug::warning('Cannot connect to LDAP or username is empty', array('username' => $username));
		tx_igldapssoauth_ldap::disconnect();
		return FALSE;
	}

	/**
	 * Returns the last self::ldap_auth() diagnostic (may be empty).
	 *
	 * @return string
	 */
	static public function getLastAuthenticationDiagnostic() {
		return self::$lastAuthenticationDiagnostic;
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

		if ($ldap_user === NULL) {
			return FALSE;
		}

		if (!$username) {
			$userAttribute = tx_igldapssoauth_config::get_username_attribute(self::$config['users']['filter']);
			$username = $ldap_user[$userAttribute][0];
		}
		// Get user pid from user mapping.
		$typo3_users_pid = tx_igldapssoauth_config::get_pid(self::$config['users']['mapping']);

		// Get TYPO3 user from username, DN and pid.
		$typo3_user = self::get_typo3_user($username, $userdn, $typo3_users_pid);
		if ($typo3_user === NULL) {
			// Non-existing local users are not allowed to authenticate
			return FALSE;
		}

		// Get LDAP and TYPO3 user groups for user
		// First reset the LDAP groups
		self::$ldapGroups = NULL;
		$typo3_groups = self::get_user_groups($ldap_user);
		if ($typo3_groups === NULL) {
			// Required LDAP groups are missing
			static::$lastAuthenticationDiagnostic = 'Missing required LDAP groups.';
			return FALSE;
		}

		if (tx_igldapssoauth_config::is_enable('IfUserExist') && !$typo3_user['uid']) {
			return FALSE;
			// User does not exist in TYPO3.
		} elseif (!$typo3_user['uid'] && (!empty($typo3_groups) || !tx_igldapssoauth_config::is_enable('DeleteUserIfNoTYPO3Groups'))) {

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
						$typo3_user[$column] = $defaultValue;
					}
				}
			}

			$typo3_user['username'] = tx_igldapssoauth_typo3_user::setUsername($typo3_user['username']);

			$typo3_user = tx_igldapssoauth_typo3_user::add($table, $typo3_user);
		}

		if (!empty($typo3_user['uid'])) {
			$typo3_user['deleted'] = 0;
			$typo3_user['endtime'] = 0;

			$typo3_user['password'] = tx_igldapssoauth_typo3_user::setRandomPassword();

			if ((empty($typo3_groups) && tx_igldapssoauth_config::is_enable('DeleteUserIfNoTYPO3Groups'))) {
				$typo3_user['deleted'] = 1;
				$typo3_user['endtime'] = $GLOBALS['EXEC_TIME'];
			}
			// Delete user if no LDAP groups found.
			if (tx_igldapssoauth_config::is_enable('DeleteUserIfNoLDAPGroups') && !self::$ldapGroups) {
				$typo3_user['deleted'] = 1;
				$typo3_user['endtime'] = $GLOBALS['EXEC_TIME'];
			}
			// Set groups to user.
			$typo3_user = tx_igldapssoauth_typo3_user::set_usergroup($typo3_groups, $typo3_user, self::$authenticationService);
			// Merge LDAP user with TYPO3 user from mapping.
			if ($typo3_user) {
				$typo3_user = tx_igldapssoauth_auth::merge($ldap_user, $typo3_user, self::$config['users']['mapping']);

				if (tx_igldapssoauth_config::is_enable('forceLowerCaseUsername')) {
					// Possible enhancement: use t3lib_cs::conv_case instead
					$typo3_user['username'] = strtolower($typo3_user['username']);
				}

				// Update TYPO3 user.
				tx_igldapssoauth_typo3_user::update(self::$authenticationService->authInfo['db_user']['table'], $typo3_user);

				$typo3_user['tx_igldapssoauth_from'] = 'LDAP';
			}
		} else {
			$typo3_user = FALSE;
		}
		return $typo3_user;
	}

	/**
	 * Returns a LDAP user.
	 *
	 * @param string $dn
	 * @return array
	 */
	static protected function get_ldap_user($dn = NULL) {
		// Restricting the list of returned attributes sometimes
		// makes the ldap_search() method issue a PHP warning:
		// Warning: ldap_search(): Array initialization wrong
		/*
		$attributes = tx_igldapssoauth_config::get_ldap_attributes(self::$config['users']['mapping']);
		if (strpos(self::$config['groups']['filter'], '{USERUID}') !== FALSE) {
			$attributes[] = 'uid';
			$attributes = array_unique($attributes);
		}
		*/
		// so we just ask for every attribute!
		$attributes = array();

		$users = tx_igldapssoauth_ldap::search(
			$dn,
			str_replace('{USERNAME}', '*', self::$config['users']['filter']),
			$attributes
		);

		$user = is_array($users[0]) ? $users[0] : NULL;

		Tx_IgLdapSsoAuth_Utility_Debug::debug(sprintf('Retrieving LDAP user from DN "%s"', $dn), $user);

		return $user;
	}

	/**
	 * Gets the LDAP and TYPO3 user groups for the given user.
	 *
	 * @param array $ldapUser LDAP user data
	 * @param array|null $configuration Current LDAP configuration
	 * @param string $groupTable Name of the group table (should normally be either "be_groups" or "fe_groups")
	 * @return array|NULL Array of groups or NULL if required LDAP groups are missing
	 */
	static public function get_user_groups($ldapUser, $configuration = NULL, $groupTable = '') {
		if (!isset($configuration)) {
			$configuration = self::$config;
		}
		if (empty($groupTable)) {
			if (isset(self::$authenticationService)) {
				$groupTable = self::$authenticationService->authInfo['db_groups']['table'];
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
		$typo3_groups = array();
		$ldapGroups = self::get_ldap_groups($ldapUser);
		unset($ldapGroups['count']);

		$requiredLDAPGroups = tx_igldapssoauth_config::is_enable('requiredLDAPGroups');
		if ($requiredLDAPGroups) {
			$requiredLDAPGroups = t3lib_div::trimExplode(',', $requiredLDAPGroups);
		} else {
			$requiredLDAPGroups = array();
		}

		if (count($ldapGroups) === 0) {
			if (count($requiredLDAPGroups) > 0) {
				return NULL;
			}
		} else {
			// Get pid from group mapping.
			$typo3_group_pid = tx_igldapssoauth_config::get_pid($configuration['groups']['mapping']);

			$typo3_groups_tmp = tx_igldapssoauth_auth::get_typo3_groups(
				$ldapGroups,
				$configuration['groups']['mapping'],
				$groupTable,
				$typo3_group_pid
			);

			if (count($requiredLDAPGroups) > 0) {
				$hasRequired = FALSE;
				$group_Listuid = array();
				foreach ($typo3_groups_tmp as $typo3_group) {
					$group_Listuid[] = $typo3_group['uid'];
				}
				foreach ($requiredLDAPGroups as $uid) {
					if (in_array($uid, $group_Listuid)) {
						$hasRequired = TRUE;
						break;
					}
				}
				if (!$hasRequired) {
					return NULL;
				}
			}

			if (tx_igldapssoauth_config::is_enable('IfGroupExist') && count($typo3_groups_tmp) === 0) {
				return array();
			}

			$i = 0;
			foreach ($typo3_groups_tmp as $typo3_group) {
				if (tx_igldapssoauth_config::is_enable('GroupsNotSynchronize') && !$typo3_group['uid']) {
					// Groups should not get synchronized and the current group is invalid
					continue;
				}
				if (tx_igldapssoauth_config::is_enable('GroupsNotSynchronize')) {
					$typo3_groups[] = $typo3_group;
				} elseif (!$typo3_group['uid']) {
					$newGroup = tx_igldapssoauth_typo3_group::add(
						$groupTable,
						$typo3_group
					);

					$typo3_group_merged = tx_igldapssoauth_auth::merge(
						$ldapGroups[$i],
						$newGroup,
						$configuration['groups']['mapping']
					);

					tx_igldapssoauth_typo3_group::update(
						$groupTable,
						$typo3_group_merged
					);

					$typo3_group = tx_igldapssoauth_typo3_group::fetch(
						$groupTable,
						$typo3_group_merged['uid']
					);
					$typo3_groups[] = $typo3_group[0];
				} else {
					// Restore group that may have been previously deleted
					$typo3_group['deleted'] = 0;
					$typo3_group_merged = tx_igldapssoauth_auth::merge(
						$ldapGroups[$i],
						$typo3_group,
						$configuration['groups']['mapping']
					);

					tx_igldapssoauth_typo3_group::update(
						$groupTable,
						$typo3_group_merged
					);

					$typo3_group = tx_igldapssoauth_typo3_group::fetch(
						$groupTable,
						$typo3_group_merged['uid']
					);
					$typo3_groups[] = $typo3_group[0];
				}

				$i++;
			}
		}
		return $typo3_groups;
	}

	/**
	 * Returns LDAP groups.
	 *
	 * @param array $ldap_user
	 * @return array
	 */
	static protected function get_ldap_groups(array $ldap_user = array()) {
		if (empty(self::$config)) {
			self::initializeConfiguration();
		}

		// Get groups attributes from group mapping configuration.
		$ldap_group_attributes = tx_igldapssoauth_config::get_ldap_attributes(self::$config['groups']['mapping']);
		$ldap_groups = array('count' => 0);

		if (tx_igldapssoauth_config::is_enable('evaluateGroupsFromMembership')) {
			// Get LDAP groups from membership attribute
			if ($membership = tx_igldapssoauth_ldap_group::get_membership($ldap_user, self::$config['users']['mapping'])) {
				$ldap_groups = tx_igldapssoauth_ldap_group::select_from_membership(
					$membership,
					self::$config['groups']['basedn'],
					self::$config['groups']['filter'],
					$ldap_group_attributes,
					// If groups should not get synchronized, there is no need to actively check them
					// against the LDAP server, simply accept every groups from $membership matching
					// the baseDN for groups, because LDAP groups not existing locally will simply be
					// skipped and not automatically created. This allows groups to be available on a
					// different LDAP server (see https://forge.typo3.org/issues/64141):
					!(bool)self::$config['GroupsNotSynchronize']
				);
			}
		} else {
			// Get LDAP groups from DN of user.
			$ldap_groups = tx_igldapssoauth_ldap_group::selectFromUser(
				self::$config['groups']['basedn'],
				self::$config['groups']['filter'],
				$ldap_user['dn'],
				!empty($ldap_user['uid'][0]) ? $ldap_user['uid'][0] : '',
				$ldap_group_attributes
			);
		}

		Tx_IgLdapSsoAuth_Utility_Debug::debug(sprintf('Retrieving LDAP groups for user "%s"', $ldap_user['dn']), $ldap_groups);

		// Store for later usage and return
		self::$ldapGroups = $ldap_groups;
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
		$user = NULL;

		$typo3_users = tx_igldapssoauth_typo3_user::fetch(self::$authenticationService->authInfo['db_user']['table'], 0, $pid, $username, $userdn);
		if ($typo3_users) {
			if (tx_igldapssoauth_config::is_enable('IfUserExist')) {
				// Ensure every returned record is active
				$numberOfUsers = count($typo3_users);
				for ($i = 0; $i < $numberOfUsers; $i++) {
					if (!empty($typo3_users[$i]['deleted'])) {
						// User is deleted => behave as if it did not exist at all!
						// Note: if user is inactive (disable=1), this will be catched by TYPO3 automatically
						unset($typo3_users[$i]);
					}
				}

				// Reset the array's indices
				$typo3_users = array_values($typo3_users);
			}

			// We want to return only first user in any case, if more than one are returned (e.g.,
			// same username/DN twice) actual authentication will fail anyway later on
			$user = is_array($typo3_users[0]) ? $typo3_users[0] : NULL;
		} elseif (!tx_igldapssoauth_config::is_enable('IfUserExist')) {
			$user = tx_igldapssoauth_typo3_user::create(self::$authenticationService->authInfo['db_user']['table']);

			$user['pid'] = $pid;
			$user['crdate'] = $GLOBALS['EXEC_TIME'];
			$user['tstamp'] = $GLOBALS['EXEC_TIME'];
			$user['username'] = $username;
			$user['tx_igldapssoauth_dn'] = $userdn;
		}

		return $user;
	}

	/**
	 * Returns TYPO3 groups associated to $ldap_groups or create fresh records
	 * if they don't exist yet.
	 *
	 * @param array $ldap_groups
	 * @param array $mapping
	 * @param string $table
	 * @param integer $pid
	 * @return array
	 */
	static public function get_typo3_groups(array $ldap_groups = array(), array $mapping = array(), $table = NULL, $pid = 0) {
		if (count($ldap_groups) === 0) {
			// Early return
			return array();
		}

		$typo3Groups = array();

		foreach ($ldap_groups as $ldap_group) {
			$existingTypo3Groups = tx_igldapssoauth_typo3_group::fetch($table, 0, $pid, $ldap_group['dn']);

			if (count($existingTypo3Groups) > 0) {
				$typo3Group = $existingTypo3Groups[0];
			} else {
				$typo3Group = tx_igldapssoauth_typo3_group::create($table);
				$typo3Group['pid'] = $pid;
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
	 * @param array $ldap_users
	 * @param array $mapping
	 * @param string $table
	 * @param integer $pid
	 * @return array
	 */
	static public function get_typo3_users(array $ldap_users = array(), array $mapping = array(), $table = NULL, $pid = 0) {
		if (count($ldap_users) === 0) {
			// Early return
			return array();
		}

		$typo3Users = array();

		foreach ($ldap_users as $ldap_user) {
			$existingTypo3Users = tx_igldapssoauth_typo3_user::fetch($table, 0, $pid, NULL, $ldap_user['dn']);

			if (count($existingTypo3Users) > 0) {
				$typo3User = $existingTypo3Users[0];
			} else {
				$typo3User = tx_igldapssoauth_typo3_user::create($table);
				$typo3User['pid'] = $pid;
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
	 * @param array $mapping
	 * @return array
	 */
	static public function merge(array $ldap = array(), array $typo3 = array(), array $mapping = array()) {
		foreach ($mapping as $field => $value) {

			// Process every field, except "usergroup"
			if ($field !== 'usergroup') {

				// Constant.
				if (preg_match("`{([^$]*)}`", $value, $match)) {
					switch ($value) {
						case '{DATE}' :
							$mappedValue = $GLOBALS['EXEC_TIME'];
							break;
						case '{RAND}' :
							$mappedValue = rand();
							break;
						default:
							$mappedValue = '';
							$params = explode(';', $match[1]);
							$passParams = array();

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
								$mappedValue = $_procObj->extraMerge($field, $typo3, $ldap, $ldapAttr, $passParams);
							}
							break;
					}

					// LDAP attribute.
				} elseif (preg_match("`<([^$]*)>`", $value, $attribute)) {
					if ($field === 'tx_igldapssoauth_dn' || ($field === 'title' && $value === '<dn>')) {
						$mappedValue = $ldap[strtolower($attribute[1])];
					} else {
						$mappedValue = self::replaceLdapMarkers($value, $ldap);
					}
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
						$typo3['__extraData'] = array();
					}
					$typo3['__extraData'][$field] = $mappedValue;
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
