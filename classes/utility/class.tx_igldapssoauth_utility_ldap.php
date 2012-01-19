<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Michael Gagnon <mgagnon
 * @infoglobe.ca>
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

	protected static $ldap_charset; // LDAP Server charset.
	protected static $local_charset; // Local character set (TYPO3).
	protected static $cid; // LDAP Server Connection ID
	protected static $bid; // LDAP Server Bind ID
	protected static $sid; // LDAP Server Search ID
	protected static $feid; // LDAP First Entry ID
	protected static $status; // LDAP server status.

	/**
	 * Connects to LDAP Server and set the cid.
	 *
	 * @param	void
	 * @return	bool TRUE if connection succeeded.
	 */
	public static function connect($host = null, $port = null, $protocol = null, $charset = null, $type = 0) {

		// Valid if php load ldap module.
		if (!extension_loaded('ldap')) {

			//Your PHP version seems to lack LDAP support. Please install.
			echo 'Your PHP version seems to lack LDAP support. Please install.';
			return FALSE;
		}

		// Connect to ldap server.

		self::$status['connect']['host'] = $host;
		self::$status['connect']['port'] = $port;

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
		if ($type == 1) {
			@ldap_set_option(self::$cid, LDAP_OPT_REFERRALS, 0);
		}

		if (substr(strtolower($host), 0, 8) == 'ldaps://') {

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
	 * @param	void
	 * @return	bool TRUE if bind succeeded.
	 */
	public static function bind($dn = NULL, $password = NULL) {

		self::$status['bind']['dn'] = $dn;
		self::$status['bind']['password'] = $password ? '********' : null;

		if (!(self::$bid = @ldap_bind(self::$cid, $dn, $password))) {

			// Could not bind to server.
			self::$bid = FALSE;
			self::$status['bind']['status'] = ldap_error(self::$cid);
			return FALSE;

		}

		// Bind successful.
		self::$status['bind']['status'] = ldap_error(self::$cid);
		return TRUE;

	}

	/**
	 * Search.
	 *
	 * @param	string
	 * @param	string
	 * @param	array
	 * @param	integer
	 * @param	integer
	 * @param	integer
	 * @param	string
	 * @return	boolean
	 * @see http://ca3.php.net/manual/fr/function.ldap-search.php
	 */
	public static function search($basedn = null, $filter = null, $attributes = array(), $attributes_only = 0, $size_limit = 0, $time_limit = 0, $deref = LDAP_DEREF_NEVER) {

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

		// No connexion identifer (cid).
		self::$status['search']['status'] = ldap_error(self::$cid);
		return FALSE;

	}

	public static function get_entries() {
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
		}
		else {
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

	public static function get_first_entry() {
		self::$status['get_first_entry']['status'] = ldap_error(self::$cid);
		return (tx_igldapssoauth_utility_Ldap::convert_charset_array(@ldap_get_attributes(self::$cid, self::$feid), self::$ldap_charset, self::$local_charset));
	}

	public static function get_dn() {
		return (@ldap_get_dn(self::$cid, self::$feid));
	}

	public static function get_attributes() {
		return (@ldap_get_attributes(self::$cid, self::$feid));
	}

	public static function get_status() {
		return self::$status;
	}

	/**
	 * Disconnect.
	 *
	 * @param	void
	 * @return	void
	 */
	public static function disconnect() {
		if (self::$cid) {
			@ldap_close(self::$cid);
		}
	}

	function is_connect() {
		return (bool)self::$cid;
	}

	private static function init_charset($charset = null) {
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
		self::$local_charset = $csObj->parse_charset($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] ? $GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset'] : 'iso-8859-1');

	}

	public static function convert_charset_array($arr, $char1, $char2) {
		/** @var $csObj t3lib_cs */

		if ((isset($GLOBALS['TSFE'])) && (isset($GLOBALS['TSFE']->csConvObj))) {
			$csObj = $GLOBALS['TSFE']->csConvObj;
		} else {
			if (!class_exists('t3lib_cs') && defined('PATH_t3lib')) {
				require_once(PATH_t3lib . 'class.t3lib_cs.php');
			}

			$csObj = t3lib_div::makeInstance('t3lib_cs');
		}

		while (list($k, $val) = each($arr)) {
			if (is_array($val)) {
				$arr[$k] = tx_igldapssoauth_utility_Ldap::convert_charset_array($val, $char1, $char2);
			} else {
				$arr[$k] = $csObj->conv($val, $char1, $char2);
			}
		}

		return $arr;
	}

}

?>