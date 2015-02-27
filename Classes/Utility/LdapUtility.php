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
 * @subpackage  iglib
 * @author      Michael Gagnon <mgagnon@infoglobe.ca>
 * @copyright	(c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
 * @see http://www-sop.inria.fr/semir/personnel/Laurent.Mirtain/ldap-livre.html
 *
 * Opération  |	LDAP description
 * -----------------------------------------------------------------------
 * Search       Recherche dans l'annuaire d'objets à partir d'un DN et/ou d'un filtre [ok]
 * Compare      Comparaison du contenu de deux objets
 * Add          Ajout d'une entrée
 * Modify       Modification du contenu d'une entrée
 * Delete       Suppression d'un objet
 * Rename       Modification du DN d'une entrée (Modify DN)
 * Connect      Connexion au serveur [ok]
 * Bind         Authentification au serveur [ok]
 * Disconnect   Deconnexion (unbind) [ok]
 * Abandon      Abandon d'une opération en cours
 * Extended     Opérations étendues (v3)
 */
class LdapUtility {

	/**
	 * LDAP Server charset
	 * @var string
	 */
	static protected $ldap_charset;

	/**
	 * Local character set (TYPO3)
	 * @var string
	 */
	static protected $local_charset;

	/**
	 * LDAP Server Connection ID
	 * @var resource
	 */
	static protected $cid;

	/**
	 * LDAP Server Bind ID
	 * @var resource
	 */
	static protected $bid;

	/**
	 * LDAP Server Search ID
	 * @var resource
	 */
	static protected $sid;

	/**
	 * LDAP First Entry ID
	 * @var resource
	 */
	static protected $feid;

	/**
	 * LDAP server status
	 * @var array
	 */
	static protected $status;

	/**
	 * 0 = OpenLDAP, 1 = Active Directory / Novell eDirectory
	 * @var string
	 */
	static protected $serverType;

	/**
	 * @var resource|bool
	 */
	static protected $previousEntry = FALSE;

	/**
	 * Connects to LDAP Server and sets the cid.
	 *
	 * @param string $host
	 * @param integer $port
	 * @param integer $protocol Either 2 or 3
	 * @param string $charset
	 * @param integer $serverType 0 = OpenLDAP, 1 = Active Directory / Novell eDirectory
	 * @param bool $tls
	 * @return bool TRUE if connection succeeded.
	 * @throws \Exception when LDAP extension for PHP is not available
	 */
	static public function connect($host = NULL, $port = NULL, $protocol = NULL, $charset = NULL, $serverType = 0, $tls = FALSE) {
		// Valid if php load ldap module.
		if (!extension_loaded('ldap')) {
			throw new \Exception('Your PHP version seems to lack LDAP support. Please install/activate the extension.', 1409566275);
		}

		// Connect to ldap server.
		static::$status['connect']['host'] = $host;
		static::$status['connect']['port'] = $port;
		static::$serverType = $serverType;

		if (!(static::$cid = @ldap_connect($host, $port))) {
			// Could not connect to ldap server.
			static::$cid = FALSE;
			static::$status['connect']['status'] = ldap_error(static::$cid);
			return FALSE;
		}

		static::$status['connect']['status'] = ldap_error(static::$cid);

		// Set configuration.
		static::init_charset($charset);

		@ldap_set_option(static::$cid, LDAP_OPT_PROTOCOL_VERSION, $protocol);

		// Active Directory (User@Domain) configuration.
		if ($serverType == 1) {
			@ldap_set_option(static::$cid, LDAP_OPT_REFERRALS, 0);
		}

		if ($tls) {
			if (!@ldap_start_tls(static::$cid)) {
				static::$status['option']['tls'] = 'Disable';
				static::$status['option']['status'] = ldap_error(static::$cid);
				return FALSE;
			}

			static::$status['option']['tls'] = 'Enable';
			static::$status['option']['status'] = ldap_error(static::$cid);
		}

		return TRUE;
	}

	/**
	 * Bind.
	 *
	 * @param string $dn
	 * @param string $password
	 * @return bool TRUE if bind succeeded.
	 */
	static public function bind($dn = NULL, $password = NULL) {
		// LDAP_OPT_DIAGNOSTIC_MESSAGE gets the extended error output
		// from the ldap_get_option() function
		if (!defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
			define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
		}

		static::$status['bind']['dn'] = $dn;
		static::$status['bind']['password'] = $password ? '********' : NULL;
		static::$status['bind']['diagnostic'] = '';

		if (!(static::$bid = @ldap_bind(static::$cid, $dn, $password))) {
			// Could not bind to server
			static::$bid = FALSE;
			static::$status['bind']['status'] = ldap_error(static::$cid);

			if (static::$serverType == 1) {
				// We need to get the diagnostic message right after the call to ldap_bind(),
				// before any other LDAP operation
				ldap_get_option(static::$cid, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
				if (!empty($extended_error)) {
					static::$status['bind']['diagnostic'] = static::extractDiagnosticMessage($extended_error);
				}
			}

			return FALSE;
		}

		// Bind successful
		static::$status['bind']['status'] = ldap_error(static::$cid);
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
	static protected function extractDiagnosticMessage($message) {
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
	 * Search.
	 *
	 * @param string $basedn
	 * @param string $filter
	 * @param array $attributes
	 * @param int $attributes_only
	 * @param int $size_limit
	 * @param int $time_limit
	 * @param int $deref
	 * @return bool
	 * @see http://ca3.php.net/manual/fr/function.ldap-search.php
	 */
	static public function search($basedn = NULL, $filter = NULL, $attributes = array(), $attributes_only = 0, $size_limit = 0, $time_limit = 0, $deref = LDAP_DEREF_NEVER) {

		if (!$basedn) {
			static::$status['search']['basedn'] = 'No valid base DN';
			return FALSE;
		}
		if (!$filter) {
			static::$status['search']['filter'] = 'No valid filter';
			return FALSE;
		}

		if (static::$cid) {
			$cid = static::$cid;
			if (is_array($basedn)) {

				$cid = array();
				foreach ($basedn as $dn) {
					$cid[] = static::$cid;
				}
			}

			if (!(static::$sid = @ldap_search($cid, $basedn, $filter, $attributes, $attributes_only, $size_limit, $time_limit, $deref))) {
				// Search failed.
				static::$status['search']['status'] = ldap_error(static::$cid);
				return FALSE;
			}

			if (is_array(static::$sid)) {
				// Search successful.
				static::$feid = @ldap_first_entry(static::$cid, static::$sid[0]);
			} else {
				static::$feid = @ldap_first_entry(static::$cid, static::$sid);
			}
			static::$status['search']['status'] = ldap_error(static::$cid);
			return TRUE;
		}

		// No connection identifier (cid).
		static::$status['search']['status'] = ldap_error(static::$cid);
		return FALSE;
	}

	/**
	 * Returns up to 1000 LDAP entries corresponding to a filter prepared by a call to
	 * @see LdapUtility::search().
	 *
	 * @param resource $previousEntry Used to get the remaining entries after receiving a partial result set
	 * @return array
	 */
	static public function get_entries($previousEntry = NULL) {
		$entries = array('count' => 0);
		static::$previousEntry = NULL;

		$sids = is_array(static::$sid) ? static::$sid : array(static::$sid);
		foreach ($sids as $sid) {
			$entry = $previousEntry === NULL ? @ldap_first_entry(static::$cid, $sid) : @ldap_next_entry(static::$cid, $previousEntry);
			if (!$entry) continue;
			do {
				$attributes = ldap_get_attributes(static::$cid, $entry);
				$attributes['dn'] = ldap_get_dn(static::$cid, $entry);
				$tempEntry = array();
				foreach ($attributes as $key => $value) {
					$tempEntry[strtolower($key)] = $value;
				}
				$entries[] = $tempEntry;
				$entries['count']++;
				if ($entries['count'] == 1000) {
					static::$previousEntry = $entry;
					break;
				}
			} while ($entry = @ldap_next_entry(static::$cid, $entry));
		}

		static::$status['get_entries']['status'] = ldap_error(static::$cid);

		return $entries['count'] > 0
			// Convert LDAP result character set  -> local character set
			? static::convert_charset_array($entries, static::$ldap_charset, static::$local_charset)
			: array();
	}

	/**
	 * Returns next LDAP entries corresponding to a filter prepared by a call to
	 * @see LdapUtility::search().
	 *
	 * @return array
	 */
	static public function get_next_entries() {
		return static::get_entries(static::$previousEntry);
	}

	/**
	 * Returns TRUE if last call to @see LdapUtility::get_entries()
	 * returned a partial result set.
	 *
	 * @return bool
	 */
	static public function has_more_entries() {
		return static::$previousEntry !== NULL;
	}

	static public function get_first_entry() {
		static::$status['get_first_entry']['status'] = ldap_error(static::$cid);
		return (static::convert_charset_array(@ldap_get_attributes(static::$cid, static::$feid), static::$ldap_charset, static::$local_charset));
	}

	static public function get_dn() {
		return (@ldap_get_dn(static::$cid, static::$feid));
	}

	static public function get_attributes() {
		return (@ldap_get_attributes(static::$cid, static::$feid));
	}

	static public function get_status() {
		return static::$status;
	}

	/**
	 * Disconnect.
	 *
	 * @return void
	 */
	static public function disconnect() {
		if (static::$cid) {
			@ldap_close(static::$cid);
		}
	}

	function is_connect() {
		return (bool)static::$cid;
	}

	static protected function init_charset($charset = NULL) {
		/** @var $csObj \TYPO3\CMS\Core\Charset\CharsetConverter */
		if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
			$csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$csObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		}

		// LDAP server charset
		static::$ldap_charset = $csObj->parse_charset($charset ? $charset : 'utf-8');

		// TYPO3 charset
		static::$local_charset = 'utf-8';
	}

	static public function convert_charset_array($arr, $char1, $char2) {
		if (!is_array($arr)) {
			return $arr;
		}

		/** @var $csObj \TYPO3\CMS\Core\Charset\CharsetConverter */
		if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
			$csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			$csObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		}

		foreach ($arr as $k => $val) {
			if (is_array($val)) {
				$arr[$k] = static::convert_charset_array($val, $char1, $char2);
			} else {
				$arr[$k] = $csObj->conv($val, $char1, $char2);
			}
		}

		return $arr;
	}

}
