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
 * Class tx_igldapssoauth_typo3_user for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_typo3_user {

	/**
	 * Initializes a new BE/FE user record.
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @return array
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_typo3_user::create() instead.
	 */
	static public function init($table = NULL) {
		t3lib_div::logDeprecatedFunction();
		return array(self::create($table));
	}

	/**
	 * Creates a fresh BE/FE user record.
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @return array
	 * @throws RuntimeException
	 */
	static public function create($table) {
		if (!t3lib_div::inList('be_users,fe_users', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891582);
		}

		$newUser = array();
		$fieldsConfiguration = self::getDatabaseConnection()->admin_get_fields($table);

		foreach ($fieldsConfiguration as $field => $configuration) {
			if ($configuration['Null'] === 'NO' && $configuration['Default'] === NULL) {
				$newUser[$field] = '';
			} else {
				$newUser[$field] = $configuration['Default'];
			}
		}

		return $newUser;
	}

	/**
	 * Searches BE/FE users either by uid or by DN (or username)
	 * in a given storage folder (pid).
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @param int $uid
	 * @param int $pid
	 * @param string $username
	 * @param string $dn
	 * @return array|NULL
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_typo3_user::fetch() instead.
	 */
	static public function select($table = NULL, $uid = 0, $pid = 0, $username = NULL, $dn = NULL) {
		t3lib_div::logDeprecatedFunction();
		$users = self::fetch($table, $uid, $pid, $username, $dn);
		return count($users) > 0 ? $users : NULL;
	}

	/**
	 * Searches BE/FE users either by uid or by DN (or username)
	 * in a given storage folder (pid).
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @param int $uid
	 * @param int $pid
	 * @param string $username
	 * @param string $dn
	 * @return array Array of user records
	 * @throws RuntimeException
	 */
	static public function fetch($table, $uid = 0, $pid = 0, $username = NULL, $dn = NULL) {
		if (!t3lib_div::inList('be_users,fe_users', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891636);
		}

		$users = array();
		$databaseConnection = self::getDatabaseConnection();

		if ($uid) {
			// Search with uid
			$users = $databaseConnection->exec_SELECTgetRows(
				'*',
				$table,
				'uid=' . intval($uid)
			);
		} elseif (!empty($dn)) {
			// Search with DN (or fall back to username) and pid
			$where = '(' . 'tx_igldapssoauth_dn=' . $databaseConnection->fullQuoteStr($dn, $table);
			if (!empty($username)) {
				// This additional condition will automatically add the mapping between
				// a local user unrelated to LDAP and a corresponding LDAP user
				$where .= ' OR username=' . $databaseConnection->fullQuoteStr($username, $table);
			}
			$where .= ')' . ($pid ? ' AND pid=' . intval($pid) : '');

			$users = $databaseConnection->exec_SELECTgetRows(
				'*',
				$table,
				$where,
				'',
				'tx_igldapssoauth_dn DESC'	// rows from LDAP first
			);
		} elseif (!empty($username)) {
			// Search with username and pid
			$users = $databaseConnection->exec_SELECTgetRows(
				'*',
				$table,
				'username=' . $databaseConnection->fullQuoteStr($username, $table)
					. ($pid ? ' AND pid=' . intval($pid) : '')
			);
		}

		// Return TYPO3 users.
		return $users;
	}

	/**
	 * Adds a new BE/FE user to the database and returns the new record
	 * with all columns.
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @param array $data
	 * @return array The new record
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_typo3_user::add() instead.
	 */
	static public function insert($table = NULL, $data = array()) {
		t3lib_div::logDeprecatedFunction();
		return array(self::add($table, $data));
	}

	/**
	 * Adds a new BE/FE user to the database and returns the new record
	 * with all columns.
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @param array $data
	 * @return array The new record
	 * @throws RuntimeException
	 */
	static public function add($table, array $data = array()) {
		if (!t3lib_div::inList('be_users,fe_users', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891712);
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
			'userAdded',
			array(
				'table' => $table,
				'user' => $newRow,
			)
		);

		return $newRow;
	}

	/**
	 * Updates a BE/FE user in the database and returns a success flag.
	 *
	 * @param string $table Either 'be_users' or 'fe_users'
	 * @param array $data
	 * @return bool TRUE on success, otherwise FALSE
	 * @throws Exception
	 */
	static public function update($table, array $data = array()) {
		if (!t3lib_div::inList('be_users,fe_users', $table)) {
			throw new RuntimeException('Invalid table "' . $table . '"', 1404891732);
		}

		$databaseConnection = self::getDatabaseConnection();

		$databaseConnection->exec_UPDATEquery(
			$table,
			'uid=' . intval($data['uid']),
			$data,
			FALSE
		);
		$success = $databaseConnection->sql_errno() == 0;

		// Hook for post-processing the user
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateUser'])) {
			if (version_compare(TYPO3_version, '6.0.0', '>=')) {
				t3lib_div::deprecationLog('Hook processUpdateUser has been deprecated for users of TYPO3 6.x since version 1.3.0 and will be removed in version 1.5.0');
			}
			$params = array(
				'table' => $table,
				'typo3_user' => $data,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['processUpdateUser'] as $funcRef) {
				Tx_IgLdapSsoAuth_Utility_Debug::debug('[HOOK] processUpdateUser: ' . $funcRef, $params);
				$null = NULL;
				t3lib_div::callUserFunction($funcRef, $params, $null);
			}
		}

		if ($success) {
			Tx_IgLdapSsoAuth_Utility_Notification::dispatch(
				__CLASS__,
				'userUpdated',
				array(
					'table' => $table,
					'user' => $data,
				)
			);
		}

		return $success;
	}

	static public function set_usergroup(array $typo3_groups = array(), array $typo3_user = array(), tx_igldapssoauth_sv1 $pObj) {
		$group_uid = array();

		foreach ($typo3_groups as $typo3_group) {
			if ($typo3_group['uid']) {
				$group_uid[] = $typo3_group['uid'];
			}
		}

		$assignGroups = t3lib_div::intExplode(',', tx_igldapssoauth_config::is_enable('assignGroups'), TRUE);
		foreach ($assignGroups as $uid) {
			if (tx_igldapssoauth_typo3_group::fetch($pObj->authInfo['db_groups']['table'], $uid) && !in_array($uid, $group_uid)) {
				$group_uid[] = $uid;
			}
		}

		if (tx_igldapssoauth_config::is_enable('keepTYPO3Groups') && $typo3_user['usergroup']) {
			$usergroup = t3lib_div::intExplode(',', $typo3_user['usergroup'], TRUE);

			foreach ($usergroup as $uid) {
				if (!in_array($uid, $group_uid)) {
					$group_uid[] = $uid;
				}
			}
		}

		$updateAdminAttribForGroups = tx_igldapssoauth_config::is_enable('updateAdminAttribForGroups');
		if ($updateAdminAttribForGroups) {
			$updateAdminAttribForGroups = t3lib_div::trimExplode(',', $updateAdminAttribForGroups);
			$typo3_user['admin'] = 0;
			foreach ($updateAdminAttribForGroups as $uid) {
				if (in_array($uid, $group_uid)) {
					$typo3_user['admin'] = 1;
					break;
				}
			}
		}

		$typo3_user['usergroup'] = implode(',', $group_uid);

		return $typo3_user;
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
