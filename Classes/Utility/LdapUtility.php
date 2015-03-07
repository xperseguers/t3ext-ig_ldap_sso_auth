<?php
namespace Causal\IgLdapSsoAuth\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LdapUtility.
 *
 * @package     TYPO3
 * @subpackage  ig_ldap_sso_auth
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @author      Michael Gagnon <mgagnon@infoglobe.ca>
 * @copyright	(c) 2011-2015 Xavier Perseguers <xavier@causal.ch>
 * @copyright	(c) 2007-2010 Michael Gagnon <mgagnon@infoglobe.ca>
 * @see http://www-sop.inria.fr/semir/personnel/Laurent.Mirtain/ldap-livre.html
 *
 * Status | Operation | LDAP description
 * -----------------------------------------------------------------------
 * done     Search      Search in the object directory using a DN and/or a filter
 *          Compare     Compare contents of two objects
 *          Add         Add an entry
 *          Modify      Modify contents of an entry
 *          Delete      Removes an object
 *          Rename      Modify DN of an entry
 * done     Connect     Connect to the server
 * done     Bind        Authenticate with the server
 * done     Disconnect  Disconnect from the server
 *          Abandon     Abandon an operation in progress
 *          Extended    Extended operations (v3)
 */
class LdapUtility {

	const MAX_ENTRIES = 1000;

	/**
	 * LDAP Server charset
	 * @var string
	 */
	protected $ldapCharacterSet;

	/**
	 * Local character set (TYPO3)
	 * @var string
	 */
	protected $typo3CharacterSet;

	/**
	 * LDAP Server Connection ID
	 * @var resource
	 */
	protected $connection;

	/**
	 * LDAP Server Search ID
	 * @var resource
	 */
	protected $searchResult;

	/**
	 * LDAP First Entry ID
	 * @var resource
	 */
	protected $firstResultEntry;

	/**
	 * LDAP server status
	 * @var array
	 */
	protected $status;

	/**
	 * 0 = OpenLDAP, 1 = Active Directory / Novell eDirectory
	 * @var int
	 */
	protected $serverType;

	/**
	 * @var resource|bool
	 */
	protected $previousEntry = FALSE;

	/**
	 * Connects to an LDAP server.
	 *
	 * @param string $host
	 * @param integer $port
	 * @param integer $protocol Either 2 or 3
	 * @param string $characterSet
	 * @param integer $serverType 0 = OpenLDAP, 1 = Active Directory / Novell eDirectory
	 * @param bool $tls
	 * @return bool TRUE if connection succeeded.
	 * @throws \Exception when LDAP extension for PHP is not available
	 */
	public function connect($host = NULL, $port = NULL, $protocol = NULL, $characterSet = NULL, $serverType = 0, $tls = FALSE) {
		// Valid if php load ldap module.
		if (!extension_loaded('ldap')) {
			throw new \Exception('Your PHP version seems to lack LDAP support. Please install/activate the extension.', 1409566275);
		}

		// Connect to ldap server.
		$this->status['connect']['host'] = $host;
		$this->status['connect']['port'] = $port;
		$this->serverType = (int)$serverType;

		if (!($this->connection = @ldap_connect($host, $port))) {
			// Could not connect to ldap server
			$this->connection = FALSE;
			$this->status['connect']['status'] = ldap_error($this->connection);
			return FALSE;
		}

		$this->status['connect']['status'] = ldap_error($this->connection);

		// Set configuration
		$this->initializeCharacterSet($characterSet);

		@ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $protocol);

		// Active Directory (User@Domain) configuration
		if ($serverType == 1) {
			@ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
		}

		if ($tls) {
			if (!@ldap_start_tls($this->connection)) {
				$this->status['option']['tls'] = 'Disable';
				$this->status['option']['status'] = ldap_error($this->connection);
				return FALSE;
			}

			$this->status['option']['tls'] = 'Enable';
			$this->status['option']['status'] = ldap_error($this->connection);
		}

		return TRUE;
	}

	/**
	 * Returns TRUE if connected to the LDAP server, @see connect().
	 *
	 * @return bool
	 */
	public function isConnected() {
		return (bool)$this->$connection;
	}

	/**
	 * Disconnects from the LDAP server.
	 *
	 * @return void
	 */
	public function disconnect() {
		if ($this->connection) {
			@ldap_close($this->connection);
		}
	}

	/**
	 * Binds to the LDAP server.
	 *
	 * @param string $dn
	 * @param string $password
	 * @return bool TRUE if bind succeeded
	 */
	public function bind($dn = NULL, $password = NULL) {
		// LDAP_OPT_DIAGNOSTIC_MESSAGE gets the extended error output
		// from the ldap_get_option() function
		if (!defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
			define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
		}

		$this->status['bind']['dn'] = $dn;
		$this->status['bind']['password'] = $password ? '********' : NULL;
		$this->status['bind']['diagnostic'] = '';

		if (!(@ldap_bind($this->connection, $dn, $password))) {
			// Could not bind to server
			$this->status['bind']['status'] = ldap_error($this->connection);

			if ($this->serverType === 1) {
				// We need to get the diagnostic message right after the call to ldap_bind(),
				// before any other LDAP operation
				ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
				if (!empty($extended_error)) {
					$this->status['bind']['diagnostic'] = $this->extractDiagnosticMessage($extended_error);
				}
			}

			return FALSE;
		}

		// Bind successful
		$this->status['bind']['status'] = ldap_error($this->connection);
		return TRUE;
	}

	/**
	 * Extracts the diagnostic message returned by an Active Directory server
	 * when ldap_bind() failed.
	 *
	 * The format of the diagnostic message is (actual examples from W2003 and W2008):
	 * "80090308: LdapErr: DSID-0C090334, comment: AcceptSecurityContext error, data 52e, vece"  (WS 2003)
	 * "80090308: LdapErr: DSID-0C090334, comment: AcceptSecurityContext error, data 773, vece"  (WS 2003)
	 * "80090308: LdapErr: DSID-0C0903AA, comment: AcceptSecurityContext error, data 52e, v1771" (WS 2008)
	 * "80090308: LdapErr: DSID-0C0903AA, comment: AcceptSecurityContext error, data 773, v1771" (WS 2008)
	 *
	 * @param string $message
	 * @return string Diagnostic message, in English
	 * @see http://www-01.ibm.com/support/docview.wss?uid=swg21290631
	 */
	protected function extractDiagnosticMessage($message) {
		$diagnostic = '';
		$codeMessages = array(
			'525' => 'The specified account does not exist.',
			'52e' => 'Logon failure: unknown user name or bad password.',
			'530' => 'Logon failure: account logon time restriction violation.',
			'531' => 'Logon failure: user not allowed to log on to this computer.',
			'532' => 'Logon failure: the specified account password has expired.',
			'533' => 'Logon failure: account currently disabled.',
			'534' => 'The user has not been granted the requested logon type at this machine.',
			'701' => 'The user\'s account has expired.',
			'773' => 'The user\'s password must be changed before logging on the first time.',
			'775' => 'The referenced account is currently locked out and may not be logged on to.',
		);

		$parts = explode(',', $message);
		if (preg_match('/data ([0-9a-f]+)/i', trim($parts[2]), $matches)) {
			$code = $matches[1];
			$diagnostic = isset($codeMessages[$code])
				? sprintf('%s (%s)', $codeMessages[$code], $code)
				: sprintf('Unknown reason. (%s)', $code);
		}

		return $diagnostic;
	}

	/**
	 * Performs a search on the LDAP server.
	 *
	 * @param string $baseDn
	 * @param string $filter
	 * @param array $attributes
	 * @param bool $attributesOnly
	 * @param int $sizeLimit
	 * @param int $timeLimit
	 * @param int $dereferenceAliases
	 * @return bool
	 * @see http://ca3.php.net/manual/fr/function.ldap-search.php
	 */
	public function search($baseDn = NULL, $filter = NULL, $attributes = array(), $attributesOnly = FALSE, $sizeLimit = 0, $timeLimit = 0, $dereferenceAliases = LDAP_DEREF_NEVER) {

		if (!$baseDn) {
			$this->status['search']['basedn'] = 'No valid base DN';
			return FALSE;
		}
		if (!$filter) {
			$this->status['search']['filter'] = 'No valid filter';
			return FALSE;
		}

		if ($this->connection) {
			$connections = $this->connection;
			if (is_array($baseDn)) {

				$connections = array();
				foreach ($baseDn as $dn) {
					$connections[] = $this->connection;
				}
			}

			if (!($this->searchResult = @ldap_search($connections, $baseDn, $filter, $attributes, $attributesOnly, $sizeLimit, $timeLimit, $dereferenceAliases))) {
				// Search failed.
				$this->status['search']['status'] = ldap_error($this->connection);
				return FALSE;
			}

			if (is_array($this->searchResult)) {
				// Search successful.
				$this->firstResultEntry = @ldap_first_entry($this->connection, $this->searchResult[0]);
			} else {
				$this->firstResultEntry = @ldap_first_entry($this->connection, $this->searchResult);
			}
			$this->status['search']['status'] = ldap_error($this->connection);
			return TRUE;
		}

		// No connection identifier (cid).
		$this->status['search']['status'] = ldap_error($this->connection);
		return FALSE;
	}

	/**
	 * Returns up to MAX_ENTRIES (1000) LDAP entries corresponding to a filter prepared by a call to
	 * @see search().
	 *
	 * @param resource $previousEntry Used to get the remaining entries after receiving a partial result set
	 * @return array
	 */
	public function getEntries($previousEntry = NULL) {
		$entries = array('count' => 0);
		$this->previousEntry = NULL;

		$searchResults = is_array($this->searchResult) ? $this->searchResult : array($this->searchResult);
		foreach ($searchResults as $searchResult) {
			$entry = ($previousEntry === NULL)
				? @ldap_first_entry($this->connection, $searchResult)
				: @ldap_next_entry($this->connection, $previousEntry);

			if (!$entry) {
				continue;
			}
			do {
				$attributes = ldap_get_attributes($this->connection, $entry);
				$attributes['dn'] = ldap_get_dn($this->connection, $entry);
				$tempEntry = array();
				foreach ($attributes as $key => $value) {
					$tempEntry[strtolower($key)] = $value;
				}
				$entries[] = $tempEntry;
				$entries['count']++;
				if ($entries['count'] == static::MAX_ENTRIES) {
					$this->previousEntry = $entry;
					break;
				}
			} while ($entry = @ldap_next_entry($this->connection, $entry));
		}

		$this->status['get_entries']['status'] = ldap_error($this->connection);

		return $entries['count'] > 0
			// Convert LDAP result character set  -> local character set
			? $this->convertCharacterSetForArray($entries, $this->ldapCharacterSet, $this->typo3CharacterSet)
			: array();
	}

	/**
	 * Returns next LDAP entries corresponding to a filter prepared by a call to
	 * @see search().
	 *
	 * @return array
	 */
	public function getNextEntries() {
		return $this->getEntries($this->previousEntry);
	}

	/**
	 * Returns TRUE if last call to @see getEntries()
	 * returned a partial result set.
	 *
	 * @return bool
	 */
	public function hasMoreEntries() {
		return $this->previousEntry !== NULL;
	}

	/**
	 * Returns the first entry.
	 *
	 * @return array
	 */
	public function getFirstEntry() {
		$this->status['get_first_entry']['status'] = ldap_error($this->connection);
		$attributes = @ldap_get_attributes($this->connection, $this->firstResultEntry);
		return ($this->convertCharacterSetForArray($attributes, $this->ldapCharacterSet, $this->typo3CharacterSet));
	}

	/**
	 * Returns the DN.
	 *
	 * @return string
	 */
	public function getDn() {
		return (@ldap_get_dn($this->connection, $this->firstResultEntry));
	}

	/**
	 * Returns the attributes.
	 *
	 * @return array
	 */
	public function getAttributes() {
		return (@ldap_get_attributes($this->connection, $this->firstResultEntry));
	}

	/**
	 * Returns the LDAP status.
	 *
	 * @return array
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * Initializes the character set.
	 *
	 * @param string $characterSet
	 * @return void
	 */
	protected function initializeCharacterSet($characterSet = NULL) {
		/** @var $csObj \TYPO3\CMS\Core\Charset\CharsetConverter */
		if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
			$csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$csObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		}

		// LDAP server charset
		$this->ldapCharacterSet = $csObj->parse_charset($characterSet ? $characterSet : 'utf-8');

		// TYPO3 charset
		$this->typo3CharacterSet = 'utf-8';
	}

	/**
	 * Converts entries from one character set to another.
	 *
	 * @param array|mixed $arr
	 * @param string $fromCharacterSet Source character set
	 * @param string $toCharacterSet Target character set
	 * @return array|mixed
	 */
	protected function convertCharacterSetForArray($arr, $fromCharacterSet, $toCharacterSet) {
		/** @var $csObj \TYPO3\CMS\Core\Charset\CharsetConverter */
		static $csObj = NULL;

		if (!is_array($arr)) {
			return $arr;
		}

		if ($csObj === NULL) {
			if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
				$csObj = $GLOBALS['TSFE']->csConvObj;
			} else {
				$csObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
			}
		}

		foreach ($arr as $k => $val) {
			if (is_array($val)) {
				$arr[$k] = $this->convertCharacterSetForArray($val, $fromCharacterSet, $toCharacterSet);
			} else {
				$arr[$k] = $csObj->conv($val, $fromCharacterSet, $toCharacterSet);
			}
		}

		return $arr;
	}

}
