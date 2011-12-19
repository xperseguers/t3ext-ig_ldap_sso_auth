<?php

/* * *************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2011 Michael Gagnon <mgagnon@infoglobe.ca>
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

require_once(t3lib_extMgm::extPath('sv') . 'class.tx_sv_auth.php');

/**
 * LDAP / SSO authentication service.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 *
 * $Id$
 */
class tx_igldapssoauth_sv1 extends tx_sv_auth {

	var $prefixId = 'tx_igldapssoauth_sv1'; // Same as class name
	var $scriptRelPath = 'sv1/class.tx_igldapssoauth_sv1.php'; // Path to this script relative to the extension dir.
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
		$this->config = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
	}

	/**
	 * Find a user (eg. look up the user record in database when a login is sent)
	 *
	 * @return	mixed		user array or FALSE
	 */
	function getUser() {
		$user = FALSE;

		$uidConf = $this->config['uidConfiguration'];
		$uidArray = t3lib_div::intExplode(',', $uidConf);
		foreach ($uidArray as $uid) {
			tx_igldapssoauth_config::init(TYPO3_MODE, $uid);

			// Enable feature
			$userTemp = FALSE;

			// CAS authentication
			if (tx_igldapssoauth_config::is_enable('CASAuthentication')) {

				$userTemp = tx_igldapssoauth_auth::cas_auth();

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
				if ($loginSecurityLevel == 'rsa') {
					$password = $this->login['uident'];
					/* @var $storage tx_rsaauth_abstract_storage */
					$storage = tx_rsaauth_storagefactory::getStorage();

					// Preprocess the password
					$key = $storage->get();

					$this->backend = tx_rsaauth_backendfactory::getBackend();
					$password = $this->backend->decrypt($key, substr($password, 4));

				}

				$userTemp = tx_igldapssoauth_auth::ldap_auth($this->login['uname'], $password);
			}
			if (is_array($userTemp)) {
				$user = $userTemp;
				break;
			}
		}

		if (!$user) {
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
	 * Authenticate a user (Check various conditions for the user that might invalidate its authentication, eg. password match, domain, IP, etc.)
	 *
	 * @param	array		Data of user.
	 * @return	boolean
	 */
	function authUser($user) {

		// 100 -> login failed
		// 200 -> login success
		$OK = 100;

		if (($this->login['uident'] && $this->login['uname']) || (tx_igldapssoauth_config::is_enable('CASAuthentication') && $user)) {
			$uidentComp = FALSE;
			global $TYPO3_CONF_VARS;
			// Checking password match for user:
			if (tx_igldapssoauth_config::is_enable('BEfailsafe')) {
				$oldSecurity = trim($TYPO3_CONF_VARS[TYPO3_MODE]['loginSecurityLevelOld']);
				$this->pObj->security_level = !empty($oldSecurity) ? $oldSecurity : 'superchallenged';
				$this->loginSec = $this->pObj->getLoginFormData();
				$this->pObj->challengeStoredInCookie = 0;
				$uidentComp = $this->compareUident($user, $this->loginSec, $this->pObj->security_level);
			}

			$OK = isset($user['tx_igldapssoauth_from']) ? 200 : $uidentComp;
			if (!$OK) {
				// Failed login attempt (wrong password) - write that to the log!

				if ($this->writeAttemptLog) {
					$this->writelog(255, 3, 3, 1,
						"Login-attempt from %s (%s), username '%s', password not accepted!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
				}

				if ($this->writeDevLog) {
					t3lib_div::devLog('Password not accepted: ' . $this->login['uident'], 'tx_igldapssoauth_sv1', 2);
				}
			}
			else {
				$OK = 200;
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

		// Make sure $OK returns the right value which must either 100 (login KO) or 200 (login OK)
		if ($OK === FALSE) {
			$OK = 100;
		}
		return $OK;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/sv1/class.tx_igldapssoauth_sv1.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/sv1/class.tx_igldapssoauth_sv1.php']);
}
?>