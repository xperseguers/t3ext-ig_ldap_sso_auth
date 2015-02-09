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
 * LDAP / SSO authentication service.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_sv1 extends \TYPO3\CMS\Sv\AuthenticationService {

	var $prefixId = 'tx_igldapssoauth_sv1'; // Same as class name
	var $scriptRelPath = 'Classes/Service/Sv1.php'; // Path to this script relative to the extension dir.
	var $extKey = 'ig_ldap_sso_auth'; // The extension key.
	var $igldapssoauth;

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
	 * @throws \RuntimeException
	 */
	public function getUser() {
		$user = FALSE;
		$userRecordOrIsValid = FALSE;
		$enableFrontendSso = TYPO3_MODE === 'FE' && (bool)$this->config['enableFESSO'] && !empty($_SERVER['REMOTE_USER']);

		// This simple check is the key to prevent your log being filled up with warnings
		// due to the AJAX calls to maintain the session active if your configuration forces
		// the authentication stack to always fetch the user:
		// $TYPO3_CONF_VARS['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;
		// This is the case, e.g., when using EXT:crawler.
		if ($this->login['status'] !== 'login' && !$enableFrontendSso) {
			return $user;
		}

		$configurationRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			'tx_igldapssoauth_config',
			'deleted=0 AND hidden=0',
			'',
			'sorting'
		);

		if (count($configurationRecords) === 0) {
			// Early return since LDAP is not configured
			Tx_IgLdapSsoAuth_Utility_Debug::warning('Skipping LDAP authentication as extension is not yet configured');
			return FALSE;
		}

		foreach ($configurationRecords as $configurationRecord) {
			tx_igldapssoauth_config::init(TYPO3_MODE, $configurationRecord['uid']);
			if (!tx_igldapssoauth_config::isEnabledForCurrentHost()) {
				$msg = sprintf(
					'Configuration record #%s is not enabled for domain %s',
					$configurationRecord['uid'],
					\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY')
				);
				Tx_IgLdapSsoAuth_Utility_Debug::info($msg);
				continue;
			}

			// Enable feature
			$userRecordOrIsValid = FALSE;

			// Single Sign-On authentication
			if ($enableFrontendSso) {
				$remoteUser = $_SERVER['REMOTE_USER'];

				// Strip the domain name
				if ($pos = strpos($remoteUser, '@')) {
					$remoteUser = substr($remoteUser, 0, $pos);
				} elseif ($pos = strrpos($remoteUser, '\\')) {
					$remoteUser = substr($remoteUser, $pos + 1);
				}

				$userRecordOrIsValid = tx_igldapssoauth_auth::ldap_auth($remoteUser);

			// Authenticate user from LDAP
			} elseif ($this->login['status'] === 'login' && $this->login['uident']) {

				// Configuration of authentication service.
				$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['loginSecurityLevel'];
				// normal case
				// Check if $loginSecurityLevel is set to "challenged" or "superchallenged" and throw an error if the configuration allows it
				// By default, it will not throw an Exception
				if (isset($this->config['throwExceptionAtLogin']) && $this->config['throwExceptionAtLogin'] == 1) {
					if ($loginSecurityLevel === 'challenged' || $loginSecurityLevel === 'superchallenged') {
						$message = "ig_ldap_sso_auth error: current login security level '" . $loginSecurityLevel . "' is not supported.";
						$message .= " Try to use 'normal' or 'rsa' (recommended but would need more settings): ";
						$message .= "\$TYPO3_CONF_VARS['BE']['loginSecurityLevel'] = 'normal';";
						throw new \RuntimeException($message, 1324313489);
					}
				}

				// normal case
				$password = $this->login['uident_text'];

				try {
					if ($password !== NULL) {
						$userRecordOrIsValid = tx_igldapssoauth_auth::ldap_auth($this->login['uname'], $password);
					} else {
						// Could not decrypt password
						$userRecordOrIsValid = FALSE;
					}
				} catch (\Exception $e) {
					// Possible known exception: 1409566275, LDAP extension is not available for PHP
					$userRecordOrIsValid = FALSE;
				}
			}
			if (is_array($userRecordOrIsValid)) {
				$user = $userRecordOrIsValid;
				break;
			} elseif ($userRecordOrIsValid) {
				// Authentication is valid
				break;
			} else {
				$diagnostic = tx_igldapssoauth_auth::getLastAuthenticationDiagnostic();
				if (!empty($diagnostic)) {
					$this->writelog(255, 3, 3, 1,
						"Login-attempt from %s (%s), username '%s': " . $diagnostic,
						array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
				}
				Tx_IgLdapSsoAuth_Utility_Notification::dispatch(
					__CLASS__,
					'authenticationFailed',
					array(
						'username' => $this->login['uname'],
						'diagnostic' => $diagnostic,
						'configUid' => $configurationRecord['uid'],
					)
				);
			}

			// Continue and try with next configuration record...
		}

		if (!$user && $userRecordOrIsValid) {
			$user = $this->fetchUserRecord($this->login['uname']);
		}

		if (is_array($user)) {
			Tx_IgLdapSsoAuth_Utility_Debug::info('User found', $this->db_user);
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
	public function authUser(array $user) {
		if (!tx_igldapssoauth_config::isInitialized()) {
			// Early return since LDAP is not configured
			return 100;
		}

		if (TYPO3_MODE === 'BE') {
			$OK = tx_igldapssoauth_config::is_enable('BEfailsafe') ? 100 : FALSE;
		} else {
			$OK = 100;
		}

		$enableFrontendSso = TYPO3_MODE === 'FE' && (bool)$this->config['enableFESSO'] && !empty($_SERVER['REMOTE_USER']);

		if ((($this->login['uident'] && $this->login['uname']) || $enableFrontendSso) && !empty($user['tx_igldapssoauth_dn'])) {
			if (isset($user['tx_igldapssoauth_from'])) {
				$OK = 200;
			} elseif (TYPO3_MODE === 'BE' && tx_igldapssoauth_config::is_enable('BEfailsafe')) {
				return 100;
			} else {
				// Failed login attempt (wrong password) - write that to the log!
				$this->writelog(255, 3, 3, 1,
					"Login-attempt from %s (%s), username '%s', password not accepted!",
					array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));

				Tx_IgLdapSsoAuth_Utility_Debug::warning('Password not accepted: ' . $this->login['uident']);
				$OK = FALSE;
			}

			// Checking the domain (lockToDomain)
			if ($OK && $user['lockToDomain'] && $user['lockToDomain'] != $this->authInfo['HTTP_HOST']) {

				// Lock domain didn't match, so error:
				$this->writelog(255, 3, 3, 1,
					"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
					array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
				$OK = FALSE;
			}
		}

		return $OK;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
