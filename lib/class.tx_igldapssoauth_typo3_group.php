<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
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
 *
 * $Id$
 */
class tx_igldapssoauth_typo3_group {

	function init($table = null) {
		// Get users table structure.
		$typo3_group_default = tx_igldapssoauth_utility_Db::get_columns_from($table);

		foreach ($typo3_group_default as $field => $value) {
			$typo3_group[$field] = null;
		}

		return $typo3_group;
	}

	function select($table = null, $uid = 0, $pid = null, $title = null, $dn = null) {

		// Search with uid and pid.
		if ($uid) {
			$QUERY = array(
				'SELECT' => '*',
				'FROM' => $table,
				'WHERE' => 'uid=' . intval($uid),
				'GROUP_BY' => '',
				'ORDER_BY' => '',
				'LIMIT' => '',
				'UID_INDEX_FIELD' => '',
			);

			// Search with DN, title and pid.
		} else {
			$QUERY = array(
				'SELECT' => '*',
				'FROM' => $table,
				'WHERE' => 'tx_igldapssoauth_dn=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($dn, $table) . ' AND pid IN (' . $pid . ')',
				'GROUP_BY' => '',
				'ORDER_BY' => '',
				'LIMIT' => '',
				'UID_INDEX_FIELD' => '',
			);
		}

		// Return TYPO3 group.
		return tx_igldapssoauth_utility_Db::select($QUERY);
	}

	function insert($table = null, $typo3_group = array()) {
		$QUERY = array(
			'TABLE' => $table,
			'FIELDS_VALUES' => $typo3_group,
			'NO_QUOTE_FIELDS' => FALSE,
		);

		$uid = tx_igldapssoauth_utility_Db::insert($QUERY);

		$QUERY = array(
			'SELECT' => '*',
			'FROM' => $table,
			'WHERE' => 'uid=' . intval($uid),
			'GROUP_BY' => '',
			'ORDER_BY' => '',
			'LIMIT' => '',
			'UID_INDEX_FIELD' => '',
		);

		return tx_igldapssoauth_utility_Db::select($QUERY);
	}

	function update($table = null, $typo3_group = array()) {
		$QUERY = array(
			'TABLE' => $table,
			'WHERE' => 'uid=' . intval($typo3_group['uid']),
			'FIELDS_VALUES' => $typo3_group,
			'NO_QUOTE_FIELDS' => FALSE,
		);

		$ret = tx_igldapssoauth_utility_Db::update($QUERY);

		// Hook for post-processing the group
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateGroup'])) {
			$params = array(
				'table' => $table,
				'typo3_group' => $typo3_group,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateGroup'] as $funcRef) {
				t3lib_div::callUserFunction($funcRef, $params, $this);
			}
		}

		return $ret;
	}

	function get_title($ldap_user = array(), $mapping = array()) {
		if (!$mapping) {
			return null;
		}

		if (array_key_exists('title', $mapping) && preg_match('`<([^$]*)>`', $mapping['title'], $attribute)) {
			if ($attribute[1] === 'dn') {
				return $ldap_user[$attribute[1]];
			}

			return $ldap_user[$attribute[1]][0];
		}

		return null;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_group.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_group.php']);
}
?>