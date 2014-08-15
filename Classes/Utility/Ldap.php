<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
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
 * Class tx_igldapssoauth_utility_Ldap.
 *
 * @access public
 * @package	TYPO3
 * @subpackage	iglib
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @copyright	(c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
 * @version	$Id: class.tx_igldapssoauth_utility_ldap.php
 * @see http://www-sop.inria.fr/semir/personnel/Laurent.Mirtain/ldap-livre.html
 *
 * Opération  |	LDAP description
 * -----------------------------------------------------------------------
 * Search		 Recherche dans l'annuaire d'objets à partir d'un DN et/ou d'un filtre [ok]
 * Compare		 Comparaison du contenu de deux objets
 * Add			 Ajout d'une entrée
 * Modify		 Modification du contenu d'une entrée
 * Delete		 Suppression d'un objet
 * Rename		Modification du DN d'une entrée (Modify DN)
 * Connect		 Connexion au serveur [ok]
 * Bind		 Authentification au serveur [ok]
 * Disconnect	 Deconnexion (unbind) [ok]
 * Abandon		 Abandon d'une opération en cours
 * Extended	 Opérations étendues (v3)
 *
 */
class tx_igldapssoauth_utility_Ldap {

	static protected $ldap_charset; // LDAP Server charset.
	static protected $local_charset; // Local character set (TYPO3).
	static protected $cid; // LDAP Server Connection ID
	static protected $bid; // LDAP Server Bind ID
	static protected $sid; // LDAP Server Search ID
	static protected $feid; // LDAP First Entry ID
	static protected $status; // LDAP server status.
	static protected $serverType; // 0 = OpenLDAP, 1 = Active Directory / Novell eDirectory

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
	 */
	static public function connect($host = NULL, $port = NULL, $protocol = NULL, $charset = NULL, $serverType = 0, $tls = FALSE) {
		// Valid if php load ldap module.
		if (!extension_loaded('ldap')) {
			echo 'Your PHP version seems to lack LDAP support. Please install.';
			return FALSE;
		}

		// Connect to ldap server.
		self::$status['connect']['host'] = $host;
		self::$status['connect']['port'] = $port;
		self::$serverType = $serverType;

		if (!(self::$cid = @ldap_connect($host, $port))) {
			// Could not connect to ldap server.
			self::$cid = FALSE;
			self::$status['connect']['status'] = ldap_error(self::$cid);
			return FALSE;
		}

		self::$status['connect']['status'] = ldap_error(self::$cid);

		// Set configuration.
		self::init_charset($charset);

		@ldap_set_option(self::$cid, LDAP_OPT_PROTOCOL_VERSION, $protocol);

		// Active Directory (User@Domain) configuration.
		if ($serverType == 1) {
			@ldap_set_option(self::$cid, LDAP_OPT_REFERRALS, 0);
		}

		if ($tls) {
			if (!@ldap_start_tls(self::$cid)) {
				self::$status['option']['tls'] = 'Disable';
				self::$status['option']['status'] = ldap_error(self::$cid);
				return FALSE;
			}

			self::$status['option']['tls'] = 'Enable';
			self::$status['option']['status'] = ldap_error(self::$cid);
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

		self::$status['bind']['dn'] = $dn;
		self::$status['bind']['password'] = $password ? '********' : NULL;
		self::$status['bind']['diagnostic'] = '';

		if (!(self::$bid = @ldap_bind(self::$cid, $dn, $password))) {
			// Could not bind to server
			self::$bid = FALSE;
			self::$status['bind']['status'] = ldap_error(self::$cid);

			if (self::$serverType == 1) {
				// We need to get the diagnostic message right after the call to ldap_bind(),
				// before any other LDAP operation
				ldap_get_option(self::$cid, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error);
				if (!empty($extended_error)) {
					self::$status['bind']['diagnostic'] = self::extractDiagnosticMessage($extended_error);
				}
			}

			return FALSE;
		}

		// Bind successful
		self::$status['bind']['status'] = ldap_error(self::$cid);
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
	 * @param integer $attributes_only
	 * @param integer $size_limit
	 * @param integer $time_limit
	 * @param string $deref
	 * @return bool
	 * @see http://ca3.php.net/manual/fr/function.ldap-search.php
	 */
	static public function search($basedn = NULL, $filter = NULL, $attributes = array(), $attributes_only = 0, $size_limit = 0, $time_limit = 0, $deref = LDAP_DEREF_NEVER) {

		if (!$basedn) {
			self::$status['search']['basedn'] = 'No valid base DN';
			return FALSE;
		}
		if (!$filter) {
			self::$status['search']['filter'] = 'No valid filter';
			return FALSE;
		}

		if (self::$cid) {
			$cid = self::$cid;
			if (is_array($basedn)) {

				$cid = array();
				foreach ($basedn as $dn) {
					$cid[] = self::$cid;
				}
			}

			if (!(self::$sid = @ldap_search($cid, $basedn, $filter, $attributes, $attributes_only, $size_limit, $time_limit, $deref))) {
				// Search failed.
				self::$status['search']['status'] = ldap_error(self::$cid);
				return FALSE;
			}

			if (is_array(self::$sid)) {
				// Search successful.
				self::$feid = @ldap_first_entry(self::$cid, self::$sid[0]);
			} else {
				self::$feid = @ldap_first_entry(self::$cid, self::$sid);
			}
			self::$status['search']['status'] = ldap_error(self::$cid);
			return TRUE;
		}

		// No connection identifer (cid).
		self::$status['search']['status'] = ldap_error(self::$cid);
		return FALSE;
	}

	static public function get_entries() {
		$result = array();
		if (is_array(self::$sid)) {
			foreach (self::$sid as $sid) {
				$resulttemp = @ldap_get_entries(self::$cid, $sid);
				if (is_array($resulttemp)) {
					$result['count'] += $resulttemp['count'];
					unset($resulttemp['count']);
					foreach ($resulttemp as $resultRow) {
						$result[] = $resultRow;
					}
				}

			}
		} else {
			$result = @ldap_get_entries(self::$cid, self::$sid);
		}

		// Search successfull
		if ($result) {
			self::$status['get_entries']['status'] = ldap_error(self::$cid);
			// Convert LDAP result character set  -> local character set
			return (tx_igldapssoauth_utility_Ldap::convert_charset_array($result, self::$ldap_charset, self::$local_charset));
		}

		self::$status['get_entries']['status'] = ldap_error(self::$cid);
		return array();
	}

	static public function get_first_entry() {
		self::$status['get_first_entry']['status'] = ldap_error(self::$cid);
		return (tx_igldapssoauth_utility_Ldap::convert_charset_array(@ldap_get_attributes(self::$cid, self::$feid), self::$ldap_charset, self::$local_charset));
	}

	static public function get_dn() {
		return (@ldap_get_dn(self::$cid, self::$feid));
	}

	static public function get_attributes() {
		return (@ldap_get_attributes(self::$cid, self::$feid));
	}

	static public function get_status() {
		return self::$status;
	}

	/**
	 * Disconnect.
	 *
	 * @return void
	 */
	static public function disconnect() {
		if (self::$cid) {
			@ldap_close(self::$cid);
		}
	}

	function is_connect() {
		return (bool)self::$cid;
	}

	static protected function init_charset($charset = NULL) {
		/** @var $csObj t3lib_cs */
		if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
			$csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			if (!class_exists('t3lib_cs') && defined('PATH_t3lib')) {
				require_once(PATH_t3lib . 'class.t3lib_cs.php');
			}

			$csObj = t3lib_div::makeInstance('t3lib_cs');
		}

		// LDAP server charset
		self::$ldap_charset = $csObj->parse_charset($charset ? $charset : 'utf-8');

		// TYPO3 charset
		if (version_compare(TYPO3_version, '4.7.0', '<')) {
			self::$local_charset = $csObj->parse_charset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'iso-8859-1');
		} else {
			self::$local_charset = 'utf-8';
		}
	}

	static public function convert_charset_array($arr, $char1, $char2) {
		if (!is_array($arr)) {
			return $arr;
		}

		/** @var $csObj t3lib_cs */
		if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
			$csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			if (!class_exists('t3lib_cs') && defined('PATH_t3lib')) {
				require_once(PATH_t3lib . 'class.t3lib_cs.php');
			}

			$csObj = t3lib_div::makeInstance('t3lib_cs');
		}

		foreach ($arr as $k => $val) {
			if (is_array($val)) {
				$arr[$k] = tx_igldapssoauth_utility_Ldap::convert_charset_array($val, $char1, $char2);
			} else {
				$arr[$k] = $csObj->conv($val, $char1, $char2);
			}
		}

		return $arr;
	}

}
