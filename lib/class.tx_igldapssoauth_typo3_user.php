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
 * Class tx_igldapssoauth_typo3_user for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	tx_igldapssoauth_typo3_user
 *
 * $Id$
 */
class tx_igldapssoauth_typo3_user {

	function init($table = null) {
		$typo3_user = array();

		// Get users table structure.
		$typo3_user_default = $GLOBALS['TYPO3_DB']->admin_get_fields($table);

		foreach ($typo3_user_default as $field => $value) {
			$typo3_user[0][$field] = null;
		}

		return $typo3_user;
	}

	function select($table = null, $uid = 0, $pid = 0, $username = null, $dn = null) {

		// Search with uid and pid.
		if ($uid) {
			$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				$table,
				'uid=' . intval($uid)
			);

			// Search with DN, username and pid.
		} else {
			$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				$table,
				'tx_igldapssoauth_dn=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($dn, $table)
					. ($pid ? ' AND pid IN (' . $pid . ')' : '')
			);
			if (!$user) {
				$user = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					$table,
					'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, $table)
						. ($pid ? ' AND pid IN (' . $pid . ')' : '')
				);
			}
		}

		// Return TYPO3 user.
		return $user;
	}

	function insert($table = null, $typo3_user = array()) {
		$GLOBALS['TYPO3_DB']->exec_INSERTquery(
			$table,
			$typo3_user,
			FALSE
		);
		$uid = $GLOBALS['TYPO3_DB']->sql_insert_id();

		return $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			$table,
			'uid=' . intval($uid)
		);
	}

	public static function update($table = null, $typo3_user = array()) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
			$table,
			'uid=' . intval($typo3_user['uid']),
			$typo3_user,
			FALSE
		);
		$ret = $GLOBALS['TYPO3_DB']->sql_affected_rows();

		// Hook for post-processing the user
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateUser'])) {
			$params = array(
				'table' => $table,
				'typo3_user' => $typo3_user,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateUser'] as $funcRef) {
				$null = NULL;
				t3lib_div::callUserFunction($funcRef, $params, $null);
			}
		}

		return $ret;
	}

	function set_usergroup($typo3_groups = array(), $typo3_user = array()) {
		$required = TRUE;
		$group_uid = array();

		if ($typo3_groups) {
			foreach ($typo3_groups as $typo3_group) {
				if ($typo3_group['uid']) {
					$group_uid[] = $typo3_group['uid'];
				}
			}
		}

		if ($assignGroups = t3lib_div::trimExplode(',', tx_igldapssoauth_config::is_enable('assignGroups'))) {
			foreach ($assignGroups as $uid) {
				if (tx_igldapssoauth_typo3_group::select($this->authInfo['db_groups']['table'], $uid) && !in_array($uid, $group_uid)) {
					$group_uid[] = $uid;
				}
			}
		}

		if (tx_igldapssoauth_config::is_enable('keepTYPO3Groups') && $typo3_user[0]['usergroup']) {
			$usergroup = t3lib_div::trimExplode(',', $typo3_user[0]['usergroup']);

			foreach ($usergroup as $uid) {
				if (!in_array($uid, $group_uid)) {
					$group_uid[] = $uid;
				}
			}
		}

		if ($updateAdminAttribForGroups = tx_igldapssoauth_config::is_enable('updateAdminAttribForGroups')) {
			$updateAdminAttribForGroups = t3lib_div::trimExplode(',', $updateAdminAttribForGroups);
			$typo3_user[0]['admin'] = 0;
			foreach ($updateAdminAttribForGroups as $uid) {
				if (in_array($uid, $group_uid)) {
					$typo3_user[0]['admin'] = 1;
				}
			}
		}

		$typo3_user[0]['usergroup'] = (string)implode(',', $group_uid);

		if ($required) {
			return $typo3_user;
		} else {
			return FALSE;
		}
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_user.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_user.php']);
}

?>