<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
 *  (c) 2007-2013 Michael Gagnon <mgagnon@infoglobe.ca>
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
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_typo3_group {

	/**
	 * Initializes a new BE/FE group record.
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @return array
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_typo3_group::create() instead.
	 */
	static public function init($table = NULL) {
		t3lib_div::logDeprecatedFunction();
		return self::create($table);
	}

	/**
	 * Creates a fresh BE/FE group record.
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @return array
	 * @throws RuntimeException
	 */
	static public function create($table) {
		if (!t3lib_div::inList('be_groups,fe_groups', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404892331);
		}

		$newGroup = array();
		$fieldsConfiguration = self::getDatabaseConnection()->admin_get_fields($table);

		foreach ($fieldsConfiguration as $field => $configuration) {
			if ($configuration['Null'] === 'NO' && $configuration['Default'] === NULL) {
				$newGroup[$field] = '';
			} else {
				$newGroup[$field] = $configuration['Default'];
			}
		}

		return $newGroup;
	}

	/**
	 * Searches BE/FE groups either by uid or by DN in a given storage folder (pid).
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @param int $uid
	 * @param int $pid
	 * @param string $title
	 * @param string $dn
	 * @return array|NULL
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_typo3_group::fetch() instead.
	 */
	static public function select($table = NULL, $uid = 0, $pid = NULL, $title = NULL, $dn = NULL) {
		t3lib_div::logDeprecatedFunction();
		return self::fetch($table, $uid, $pid, $dn);
	}

	/**
	 * Searches BE/FE groups either by uid or by DN in a given storage folder (pid).
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @param int $uid
	 * @param int $pid
	 * @param string $dn
	 * @return array|NULL
	 * @throws RuntimeException
	 */
	static public function fetch($table, $uid = 0, $pid = NULL, $dn = NULL) {
		if (!t3lib_div::inList('be_groups,fe_groups', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891809);
		}

		$databaseConnection = self::getDatabaseConnection();

			// Search with uid
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

	/**
	 * Adds a new BE/FE group to the database and returns the new record
	 * with all columns.
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @param array $data
	 * @return array The new record
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_typo3_group::add() instead.
	 */
	static public function insert($table = NULL, $data = array()) {
		t3lib_div::logDeprecatedFunction();
		return array(self::add($table, $data));
	}

	/**
	 * Adds a new BE/FE group to the database and returns the new record
	 * with all columns.
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @param array $data
	 * @return array The new record
	 * @throws RuntimeException
	 */
	static public function add($table, array $data = array()) {
		if (!t3lib_div::inList('be_groups,fe_groups', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891833);
		}

		$databaseConnection = self::getDatabaseConnection();

		$databaseConnection->exec_INSERTquery(
			$table,
			$data,
			FALSE
		);
		$uid = $databaseConnection->sql_insert_id();

		$newRow = $databaseConnection->exec_SELECTgetSingleRow(
			'*',
			$table,
			'uid=' . intval($uid)
		);

		Tx_IgLdapSsoAuth_Utility_Notification::dispatch(
			__CLASS__,
			'groupAdded',
			array(
				'table' => $table,
				'group' => $newRow,
			)
		);

		return $newRow;
	}

	/**
	 * Updates a BE/FE group in the database and returns a success flag.
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @param array $data
	 * @return bool TRUE on success, otherwise FALSE
	 * @throws Exception
	 */
	static public function update($table, array $data = array()) {
		if (!t3lib_div::inList('be_groups,fe_groups', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891867);
		}

		$databaseConnection = self::getDatabaseConnection();

		$databaseConnection->exec_UPDATEquery(
			$table,
			'uid=' . intval($data['uid']),
			$data,
			FALSE
		);
		$success = $databaseConnection->sql_errno() == 0;

		// Hook for post-processing the group
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateGroup'])) {
			if (version_compare(TYPO3_version, '6.0.0', '>=')) {
				t3lib_div::deprecationLog('Hook processUpdateGroup has been deprecated for users of TYPO3 6.x since version 1.3.0 and will be removed in version 1.5.0');
			}
			$params = array(
				'table' => $table,
				'typo3_group' => $data,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateGroup'] as $funcRef) {
				$null = NULL;
				t3lib_div::callUserFunction($funcRef, $params, $null);
			}
		}

		if ($success) {
			Tx_IgLdapSsoAuth_Utility_Notification::dispatch(
				__CLASS__,
				'groupUpdated',
				array(
					'table' => $table,
					'group' => $data,
				)
			);
		}

		return $success;
	}

	static public function get_title($ldap_user = array(), $mapping = array()) {
		if (!$mapping) {
			return NULL;
		}

		if (isset($mapping['title']) && preg_match('`<([^$]*)>`', $mapping['title'], $attribute)) {
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
