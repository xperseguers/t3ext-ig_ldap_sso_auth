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

if (version_compare(TYPO3_branch, '6.0', '<')) {
	require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_auth.php');
}

/**
 * LDAP / SSO authentication service.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_sv1 extends tx_sv_auth {

	var $prefixId = 'tx_igldapssoauth_sv1'; // Same as class name
	var $scriptRelPath = 'Classes/Service/Sv1.php'; // Path to this script relative to the extension dir.
	var $extKey = 'ig_ldap_sso_auth'; // The extension key.
	var $igldapssoauth;

	/**
	 * @var tx_rsaauth_abstract_backend
	 */
	protected $backend;

	/**
	 * @var array
	 */
	protected $config;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
		$this->config = $config ? unserialize($config) : array();
		tx_igldapssoauth_auth::setAuthenticationService($this);
	}

	/**
	 * Find a user (eg. look up the user record in database when a login is sent)
	 *
	 * @return mixed user array or FALSE
	 */
	public function getUser() {
		$user = FALSE;

		$configurationRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			'tx_igldapssoauth_config',
			'deleted=0 AND hidden=0',
			'',
			'sorting'
		);

		if (count($configurationRecords) === 0) {
			// Early return since LDAP is not configured
			return FALSE;
		}

		foreach ($configurationRecords as $configurationRecord) {
			tx_igldapssoauth_config::init(TYPO3_MODE, $configurationRecord['uid']);
			if (!tx_igldapssoauth_config::isEnabledForCurrentHost()) {
				continue;
			}

			// Enable feature
			$userRecordOrIsValid = FALSE;

			// CAS authentication
			if (tx_igldapssoauth_config::is_enable('CASAuthentication')) {

				$userRecordOrIsValid = tx_igldapssoauth_auth::cas_auth();

				// Authenticate user from LDAP
			} elseif ($this->login['status'] === 'login' && $this->login['uident']) {

				// Configuration of authentication service.
				$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['loginSecurityLevel'];
				// normal case
				// Check if $loginSecurityLevel is set to "challenged" or "superchallenged" and throw an error if the configuration allows it
				// By default, it will not throw an Exception
				$throwExceptionAtLogin = 0;
				if (isset($this->config['throwExceptionAtLogin']) && $this->config['throwExceptionAtLogin'] == 1) {
					if ($loginSecurityLevel === 'challenged' || $loginSecurityLevel === 'superchallenged') {
						$message = "ig_ldap_sso_auth error: current login security level '" . $loginSecurityLevel . "' is not supported.";
						$message .= " Try to use 'normal' or 'rsa' (recommanded but would need more settings): ";
						$message .= "\$TYPO3_CONF_VARS['BE']['loginSecurityLevel'] = 'normal';";
						throw new Exception($message, 1324313489);
					}
				}

				// normal case
				$password = $this->login['uident_text'];

				//if ($loginSecurityLevel === 'rsa') {
				//	$password = $this->login['uident'];
				//	/* @var $storage tx_rsaauth_abstract_storage */
				//	$storage = tx_rsaauth_storagefactory::getStorage();
				//
				//	// Preprocess the password
				//	$key = $storage->get();

				//	$this->backend = tx_rsaauth_backendfactory::getBackend();
				//	$password = $this->backend->decrypt($key, substr($password, 4));
				//}

				$userRecordOrIsValid = tx_igldapssoauth_auth::ldap_auth($this->login['uname'], $password);
			}
			if (is_array($userRecordOrIsValid)) {
				$user = $userRecordOrIsValid;
				break;
			} elseif ($userRecordOrIsValid) {
				// Authentication is valid
				break;
			}

			// Continue and try with next configuration record...
		}

		if (!$user && $userRecordOrIsValid) {
			$user = $this->fetchUserRecord($this->login['uname']);
		}

		// Failed login attempt (no username found)
		if (!is_array($user)) {
			$this->writelog(255, 3, 3, 2,
				"Login-attempt from %s (%s), username '%s' not found!!",
				array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname'])); // Logout written to log
			// User found
		} else {
			if ($this->writeDevLog) {
				t3lib_div::devLog('User found: ' . t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'], $this->db_user['username_column'])), 'tx_igldapssoauth_sv1');
			}
		}

		return $user;
	}

	/**
	 * Authenticates a user (Check various conditions for the user that might invalidate its
	 * authentication, eg. password match, domain, IP, etc.).
	 *
	 * The return value is defined like that:
	 *
	 * FALSE -> login failed and authentication should stop
	 * 100 -> login failed but authentication should try next service
	 * 200 -> login succeeded
	 *
	 * @param array $user Data of user.
	 * @return int|FALSE
	 */
	public function authUser($user) {
		if (!tx_igldapssoauth_config::isInitialized()) {
			// Early return since LDAP is not configured
			return 100;
		}

		if (TYPO3_MODE === 'BE') {
			$OK = tx_igldapssoauth_config::is_enable('BEfailsafe') ? 100 : FALSE;
		} else {
			$OK = 100;
		}

		if ($this->login['uident'] && $this->login['uname'] && (!empty($user['tx_igldapssoauth_dn']) || tx_igldapssoauth_config::is_enable('CASAuthentication'))) {
			$uidentComp = FALSE;

			if (isset($user['tx_igldapssoauth_from'])) {
				$OK = 200;
			} elseif (TYPO3_MODE === 'BE' && tx_igldapssoauth_config::is_enable('BEfailsafe')) {
				return 100;
			} else {
				// Failed login attempt (wrong password) - write that to the log!
				if ($this->writeAttemptLog) {
					$this->writelog(255, 3, 3, 1,
						"Login-attempt from %s (%s), username '%s', password not accepted!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
				}

				if ($this->writeDevLog) {
					t3lib_div::devLog('Password not accepted: ' . $this->login['uident'], 'tx_igldapssoauth_sv1', 2);
				}

				$OK = FALSE;
			}

			// Checking the domain (lockToDomain)
			if ($OK && $user['lockToDomain'] && $user['lockToDomain'] != $this->authInfo['HTTP_HOST']) {

				// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255, 3, 3, 1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
				}
				$OK = FALSE;
			}
		}

		return $OK;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return t3lib_DB
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
