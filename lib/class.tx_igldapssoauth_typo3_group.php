<?php

/***************************************************************
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
 ***************************************************************/

/**
 * Class tx_igldapssoauth_typo3_group for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	tx_igldapssoauth_typo3_group
 */
class tx_igldapssoauth_typo3_group {

	public static function init($table = null) {
		$typo3_group = array();

		// Get users table structure.
		$typo3_group_default = $GLOBALS['TYPO3_DB']->admin_get_fields($table);

		foreach ($typo3_group_default as $field => $value) {
			$typo3_group[$field] = NULL;
		}

		return $typo3_group;
	}

	public static function select($table = null, $uid = 0, $pid = null, $title = null, $dn = null) {
			// Search with uid and pid.
		if ($uid) {
			$where = 'uid=' . intval($uid);

			// Search with DN, title and pid.
		} else {
			$where = 'tx_igldapssoauth_dn=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($dn, $table) . ' AND pid IN (' . $pid . ')';
		}

		// Return TYPO3 group.
		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$table,
			$where
		);
	}

	public static function insert($table = null, $typo3_group = array()) {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$table,
			$typo3_group,
			FALSE
		);
		$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();

		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$table,
			'uid=' . intval($uid)
		);
	}

	public static function update($table = null, $typo3_group = array()) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$table,
			'uid=' . intval($typo3_group['uid']),
			$typo3_group,
			FALSE
		);
		$ret = $GLOBALS['TYPO3_DB']->sql_affected_rows();

		// Hook for post-processing the group
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateGroup'])) {
			$params = array(
				'table' => $table,
				'typo3_group' => $typo3_group,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateGroup'] as $funcRef) {
				$null = NULL;
				t3lib_div::callUserFunction($funcRef, $params, $null);
			}
		}

		return $ret;
	}

	public static function get_title($ldap_user = array(), $mapping = array()) {
		if (!$mapping) {
			return NULL;
		}

		if (array_key_exists('title', $mapping) && preg_match('`<([^$]*)>`', $mapping['title'], $attribute)) {
			if ($attribute[1] === 'dn') {
				return $ldap_user[$attribute[1]];
			}

			return $ldap_user[$attribute[1]][0];
		}

		return NULL;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_group.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_group.php']);
}

?>