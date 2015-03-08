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
 * @author	Xavier Perseguers <xavier@causal.ch>
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 */
class Ldap implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var string
	 */
	protected $lastBindDiagnostic = '';

	/**
	 * @var \Causal\IgLdapSsoAuth\Utility\LdapUtility
	 * @inject
	 */
	protected $ldapUtility;

	/**
	 * Returns an instance of this class.
	 *
	 * @return \Causal\IgLdapSsoAuth\Library\Ldap
	 */
	static public function getInstance() {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		static $objectManager = NULL;
		if ($objectManager === NULL) {
			$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		}
		return $objectManager->get(__CLASS__);
	}

	/**
	 * Initializes a connection to the LDAP server.
	 *
	 * @param array $config
	 * @return bool
	 * @throws \Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException when LDAP extension for PHP is not available
	 */
	public function connect(array $config = array()) {
		$debugConfiguration = array(
			'host' => $config['host'],
			'port' => $config['port'],
			'protocol' => $config['protocol'],
			'charset' => $config['charset'],
			'server' => $config['server'],
			'tls' => $config['tls'],
		);

		// Connect to ldap server.
		if (!$this->ldapUtility->connect($config['host'], $config['port'], $config['protocol'], $config['charset'], $config['server'], $config['tls'])) {
			DebugUtility::error('Cannot connect', $debugConfiguration);
			return FALSE;
		}

		$debugConfiguration['binddn'] = $config['binddn'];
		$debugConfiguration['password'] = $config['password'] !== '' ? '********' : '';

		// Bind to ldap server.
		if (!$this->ldapUtility->bind($config['binddn'], $config['password'])) {
			$status = $this->ldapUtility->getStatus();
			$this->lastBindDiagnostic = $status['bind']['diagnostic'];

			$message = 'Cannot bind to LDAP';
			if (!empty($this->lastBindDiagnostic)) {
				$message .= ': ' . $this->lastBindDiagnostic;
			}
			DebugUtility::error($message, $debugConfiguration);

			$this->disconnect();
			return FALSE;
		}

		DebugUtility::info('Successfully connected', $debugConfiguration);
		return TRUE;
	}

	/**
	 * Disconnects the LDAP server.
	 *
	 * @return void
	 */
	public function disconnect() {
		$this->ldapUtility->disconnect();
	}

	/**
	 * Returns the corresponding DN if a given user is provided, otherwise FALSE.
	 *
	 * @param string $username
	 * @param string $password User's password. If NULL password will not be checked
	 * @param string $baseDn
	 * @param string $filter
	 * @return bool|string
	 */
	public function validateUser($username = NULL, $password = NULL, $baseDn = NULL, $filter = NULL) {

		// If user found on ldap server.
		if ($this->ldapUtility->search($baseDn, str_replace('{USERNAME}', $username, $filter), array('dn'))) {

			// Validate with password.
			if ($password !== NULL) {

				// Bind DN of user with password.
				if (empty($password)) {
					$this->lastBindDiagnostic = 'Empty password provided!';
					return FALSE;
				} elseif ($this->ldapUtility->bind($this->ldapUtility->getDn(), $password)) {
					$dn = $this->ldapUtility->getDn();

					// Restore last LDAP binding
					$config = Configuration::getLdapConfiguration();
					$this->ldapUtility->bind($config['binddn'], $config['password']);
					$this->lastBindDiagnostic = '';

					return $dn;
				} else {
					$status = $this->ldapUtility->getStatus();
					$this->lastBindDiagnostic = $status['bind']['diagnostic'];
					return FALSE;	// Password does not match
				}

			// If enable, SSO authentication without password
			} elseif ($password === NULL && Configuration::is_enable('SSOAuthentication')) {

				return $this->ldapUtility->getDn();

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
	 * @param string $baseDn
	 * @param string $filter
	 * @param array $attributes
	 * @param bool $firstEntry
	 * @param int $limit
	 * @return array
	 */
	public function search($baseDn = NULL, $filter = NULL, $attributes = array(), $firstEntry = FALSE, $limit = 0) {
		$result = array();

		if ($this->ldapUtility->search($baseDn, $filter, $attributes, FALSE, $firstEntry ? 1 : $limit)) {
			if ($firstEntry) {
				$result = $this->ldapUtility->getFirstEntry();
				$result['dn'] = $this->ldapUtility->getDn();
				unset($result['count']);
			} else {
				$result = $this->ldapUtility->getEntries();
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
	public function isPartialSearchResult() {
		return $this->ldapUtility->hasMoreEntries();
	}

	/**
	 * Returns the next block of entries satisfying a previous call to @see search().
	 *
	 * @return array
	 */
	public function searchNext() {
		$result = $this->ldapUtility->getNextEntries();
		return $result;
	}

	/**
	 * Returns the LDAP status.
	 *
	 * @return array
	 */
	public function getStatus() {
		return $this->ldapUtility->getStatus();
	}

	/**
	 * Returns the last ldap_bind() diagnostic (may be empty).
	 *
	 * @return string
	 */
	public function getLastBindDiagnostic() {
		return $this->lastBindDiagnostic;
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
	public function escapeDnForFilter($dn) {
		$escapeCharacters = array('(', ')', '\\');
		foreach ($escapeCharacters as $escapeCharacter) {
			$dn = str_replace($escapeCharacter, '\\' . $escapeCharacter, $dn);
		}
		return $dn;
	}

}
