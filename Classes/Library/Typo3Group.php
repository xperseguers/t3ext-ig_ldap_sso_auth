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

	static public function init($table = NULL) {
		$typo3_group = array();

		// Get users table structure.
		$typo3_group_default = self::getDatabaseConnection()->admin_get_fields($table);

		foreach ($typo3_group_default as $field => $configuration) {
			if ($configuration['Null'] === 'NO' && $configuration['Default'] === NULL) {
				$typo3_group[$field] = '';
			} else {
				$typo3_group[$field] = $configuration['Default'];
			}
		}

		return $typo3_group;
	}

	static public function select($table = NULL, $uid = 0, $pid = NULL, $title = NULL, $dn = NULL) {
		$databaseConnection = self::getDatabaseConnection();

			// Search with uid and pid.
		if ($uid) {
			$where = 'uid=' . intval($uid);

			// Search with DN, title and pid.
		} else {
			$where = 'tx_igldapssoauth_dn=' . $databaseConnection->fullQuoteStr($dn, $table) . ' AND pid IN (' . intval($pid) . ')';
		}

		// Return TYPO3 group.
		return $databaseConnection->exec_SELECTgetRows(
			'*',
			$table,
			$where
		);
	}

	static public function insert($table = NULL, $typo3_group = array()) {
		$databaseConnection = self::getDatabaseConnection();

		$databaseConnection->exec_INSERTquery(
			$table,
			$typo3_group,
			FALSE
		);
		$uid = $databaseConnection->sql_insert_id();

		return $databaseConnection->exec_SELECTgetRows(
			'*',
			$table,
			'uid=' . intval($uid)
		);
	}

	static public function update($table = NULL, $typo3_group = array()) {
		$databaseConnection = self::getDatabaseConnection();

		$databaseConnection->exec_UPDATEquery(
			$table,
			'uid=' . intval($typo3_group['uid']),
			$typo3_group,
			FALSE
		);
		$ret = $databaseConnection->sql_affected_rows();

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

	static public function get_title($ldap_user = array(), $mapping = array()) {
		if (!$mapping) {
			return NULL;
		}

		if (array_key_exists('title', $mapping) && preg_match('`<([^$]*)>`', $mapping['title'], $attribute)) {
			if ($attribute[1] === 'dn') {
				return $ldap_user[$attribute[1]];
			}

			return tx_igldapssoauth_auth::replaceLdapMarkers($mapping['title'], $ldap_user);
		}

		return NULL;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return t3lib_DB
	 */
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
