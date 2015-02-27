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

use Causal\IgLdapSsoAuth\Utility\LdapUtility;
use Causal\IgLdapSsoAuth\Utility\DebugUtility;

/**
 * Class Ldap for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 */
class Ldap {

	static protected $lastBindDiagnostic = '';

	/**
	 * Initializes a connection to the LDAP server.
	 *
	 * @param array $config
	 * @return bool
	 */
	static public function connect(array $config = array()) {
		$debugConfiguration = array(
			'host' => $config['host'],
			'port' => $config['port'],
			'protocol' => $config['protocol'],
			'charset' => $config['charset'],
			'server' => $config['server'],
			'tls' => $config['tls'],
		);

		// Connect to ldap server.
		if (!LdapUtility::connect($config['host'], $config['port'], $config['protocol'], $config['charset'], $config['server'], $config['tls'])) {
			DebugUtility::error('Cannot connect', $debugConfiguration);
			return FALSE;
		}

		$debugConfiguration['binddn'] = $config['binddn'];
		$debugConfiguration['password'] = $config['password'] !== '' ? '********' : '';

		// Bind to ldap server.
		if (!LdapUtility::bind($config['binddn'], $config['password'])) {
			$status = LdapUtility::get_status();
			static::$lastBindDiagnostic = $status['bind']['diagnostic'];

			$message = 'Cannot bind to LDAP';
			if (!empty(static::$lastBindDiagnostic)) {
				$message .= ': ' . static::$lastBindDiagnostic;
			}
			DebugUtility::error($message, $debugConfiguration);

			static::disconnect();
			return FALSE;
		}

		DebugUtility::info('Successfully connected', $debugConfiguration);
		return TRUE;
	}

	/**
	 * Returns the corresponding DN if a given user is provided, otherwise FALSE.
	 *
	 * @param string $username
	 * @param string $password User's password. If NULL password will not be checked
	 * @param string $basedn
	 * @param string $filter
	 * @return bool|string
	 */
	static public function valid_user($username = NULL, $password = NULL, $basedn = NULL, $filter = NULL) {

		// If user found on ldap server.
		if (LdapUtility::search($basedn, str_replace('{USERNAME}', $username, $filter), array('dn'))) {

			// Validate with password.
			if ($password !== NULL) {

				// Bind DN of user with password.
				if (empty($password)) {
					static::$lastBindDiagnostic = 'Empty password provided!';
					return FALSE;
				} elseif (LdapUtility::bind(LdapUtility::get_dn(), $password)) {
					$dn = LdapUtility::get_dn();

					// Restore last LDAP binding
					$config = Configuration::getLdapConfiguration();
					LdapUtility::bind($config['binddn'], $config['password']);
					static::$lastBindDiagnostic = '';

					return $dn;
				} else {
					$status = LdapUtility::get_status();
					static::$lastBindDiagnostic = $status['bind']['diagnostic'];
					return FALSE;	// Password does not match
				}

			// If enable, SSO authentication without password
			} elseif ($password === NULL && Configuration::is_enable('SSOAuthentication')) {

				return LdapUtility::get_dn();

			} else {

				// User invalid. Authentication failed.
				return FALSE;
			}

		}

		return FALSE;
	}

	/**
	 * Searches LDAP entries satisfying some filter.
	 *
	 * @param string $basedn
	 * @param string $filter
	 * @param array $attributes
	 * @param bool $first_entry
	 * @param int $limit
	 * @return array
	 */
	static public function search($basedn = NULL, $filter = NULL, $attributes = array(), $first_entry = FALSE, $limit = 0) {
		$result = array();

		if (LdapUtility::search($basedn, $filter, $attributes, 0, $first_entry ? 1 : $limit)) {
			if ($first_entry) {
				$result = LdapUtility::get_first_entry();
				$result['dn'] = LdapUtility::get_dn();
				unset($result['count']);
			} else {
				$result = LdapUtility::get_entries();
			}
		}

		return $result;
	}

	/**
	 * Returns TRUE if last call to @see search() returned a partial result set.
	 * You should then call @see searchNext().
	 *
	 * @return bool
	 */
	static public function isPartialSearchResult() {
		return LdapUtility::has_more_entries();
	}

	/**
	 * Returns the next block of entries satisfying a previous call to @see search().
	 *
	 * @return array
	 */
	static public function searchNext() {
		$result = LdapUtility::get_next_entries();
		return $result;
	}

	static public function get_status() {
		return LdapUtility::get_status();
	}

	static public function disconnect() {
		LdapUtility::disconnect();
	}

	/**
	 * Returns the last ldap_bind() diagnostic (may be empty).
	 *
	 * @return string
	 */
	static public function getLastBindDiagnostic() {
		return static::$lastBindDiagnostic;
	}

	/**
	 * Escapes a string for use in a LDAP filter statement.
	 *
	 * To find the groups of a user by filtering the groups where the
	 * authenticated user is in the members list some characters
	 * in the users distinguished name can make the filter expression
	 * invalid.
	 *
	 * At the moment this problem was experienced with brackets which
	 * are also used in the filter, e.g.:
	 * (&(objectClass=group)(member={USERDN}))
	 *
	 * Additionally a single backslash (that is used for escaping special
	 * characters like commas) needs to be escaped. E.g.:
	 * CN=Lastname\, Firstname,DC=company,DC=tld needs to be escaped like:
	 * CN=Lastname\\, Firstname,DC=company,DC=tld
	 *
	 * @param string $dn
	 * @return string Escaped $dn
	 */
	static public function escapeDnForFilter($dn) {
		$escapeCharacters = array('(', ')', '\\');
		foreach ($escapeCharacters as $escapeCharacter) {
			$dn = str_replace($escapeCharacter, '\\' . $escapeCharacter, $dn);
		}
		return $dn;
	}

}
