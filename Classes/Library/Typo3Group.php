<?php
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
 * Class tx_igldapssoauth_typo3_group for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_typo3_group {

	/**
	 * Creates a fresh BE/FE group record.
	 *
	 * @param string $table Either 'be_groups' or 'fe_groups'
	 * @return array
	 * @throws \RuntimeException
	 */
	static public function create($table) {
		if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
			throw new \RuntimeException('Invalid table "' . $table . '"', 1404892331);
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
	 * @param string $dn
	 * @return array|NULL
	 * @throws \RuntimeException
	 */
	static public function fetch($table, $uid = 0, $pid = NULL, $dn = NULL) {
		if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
			throw new \RuntimeException('Invalid table "' . $table . '"', 1404891809);
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
	 * @throws \RuntimeException
	 */
	static public function add($table, array $data = array()) {
		if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
			throw new \RuntimeException('Invalid table "' . $table . '"', 1404891833);
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
	 * @throws \Exception
	 */
	static public function update($table, array $data = array()) {
		if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
			throw new \RuntimeException('Invalid table "' . $table . '"', 1404891867);
		}

		$databaseConnection = self::getDatabaseConnection();

		$databaseConnection->exec_UPDATEquery(
			$table,
			'uid=' . intval($data['uid']),
			$data,
			FALSE
		);
		$success = $databaseConnection->sql_errno() == 0;

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
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
