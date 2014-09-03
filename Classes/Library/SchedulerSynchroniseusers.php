<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
 *  (c) 2005 - 2010 Michael Miousse (michael.miousse@infoglobe.ca)
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
 * This class provides Scheduler plugin implementation.
 *
 * @author Michael Miousse <michael.miousse@infoglobe.ca>
 */
class tx_igldapssoauth_scheduler_synchroniseusers extends tx_scheduler_Task {

	/**
	 * @var integer
	 */
	public $sleepTime;

	/**
	 * @var integer
	 */
	public $sleepAfterFinish;

	/**
	 * @var integer
	 */
	public $countInARun;

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return boolean
	 */
	public function execute() {
		$this->setCliArguments();
		$typo3_modes = array('fe', 'be');

		$configurationRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			'tx_igldapssoauth_config',
			'deleted=0 AND hidden=0',
			'',
			'sorting'
		);

		if (count($configurationRecords) > 0) {
			// Start a database transaction with all our changes
			// Syntax is compatible with MySQL, Oracle, MSSQL and PostgreSQL
			$this->getDatabaseConnection()->sql_query('START TRANSACTION');

			// Disable every local frontend and backend user bound to LDAP
			foreach ($typo3_modes as $typo3_mode) {
				$this->getDatabaseConnection()->exec_UPDATEquery(
					$typo3_mode . '_users',
					'uid<>' . $GLOBALS['BE_USER']->user['uid'] . ' AND disable=0' .
						' AND tx_igldapssoauth_dn IS NOT NULL AND tx_igldapssoauth_dn<>\'\'',
					array(
						'disable' => 1,
						'endtime' => $GLOBALS['EXEC_TIME'],
					)
				);
			}
		}

		foreach ($configurationRecords as $configurationRecord) {
			tx_igldapssoauth_config::init(NULL, $configurationRecord['uid']);

			// Valid user only if username and connect to LDAP server
			if (!tx_igldapssoauth_ldap::connect(tx_igldapssoauth_config::getLdapConfiguration())) {
				continue;
			}

			foreach ($typo3_modes as $typo3_mode) {
				tx_igldapssoauth_config::setTypo3Mode($typo3_mode);
				$configuration = tx_igldapssoauth_auth::initializeConfiguration();

				$isConfigured = !empty($configuration['users']['basedn']);
				$isConfigured &= $configuration['users']['mapping']['username'] !== '<>';
				if (!$isConfigured) {
					// No configuration for current TYPO3 mode
					continue;
				}

				$success = tx_igldapssoauth_utility_Ldap::search(
					$configuration['users']['basedn'],
					str_replace('{USERNAME}', '*', $configuration['users']['filter']),
					array('dn')
				);
				if (!$success) {
					// Rolls back pending changes in the database
					// Syntax is compatible with MySQL, Oracle, MSSQL and PostgreSQL
					$this->getDatabaseConnection()->sql_query('ROLLBACK');

					throw new RuntimeException(sprintf(
						'LDAP search failed for configuration record #%s (mode "%s").',
						$configurationRecord['uid'],
						$typo3_mode
					), 1408009158);
				}

				$tableUsers = $typo3_mode . '_users';
				$tableGroups = $typo3_mode . '_groups';

				$userList = tx_igldapssoauth_utility_Ldap::get_entries();
				unset($userList['count']);

				$authInfo['db_user']['table'] = $tableUsers;
				$authInfo['db_groups']['table'] = $tableGroups;

				/** @var tx_igldapssoauth_sv1 $sv1 */
				$sv1 = t3lib_div::makeInstance('tx_igldapssoauth_sv1');
				$sv1->authInfo = $authInfo;

				$activeUsers = array();
				foreach ($userList as $userInfo) {
					if (empty($userInfo['dn'])) {
						continue;
					}
					$user = tx_igldapssoauth_auth::synchroniseUser($userInfo['dn']);
					$activeUsers[] = (int)$user['uid'];
				}
				if (count($activeUsers) > 0) {
					$this->getDatabaseConnection()->exec_UPDATEquery(
						$tableUsers,
						'disable=1 AND uid IN (' . implode(',', $activeUsers) . ')',
						array(
							'disable' => 0,
							'endtime' => 0,
							'tstamp' => $GLOBALS['EXEC_TIME'],
						)
					);
				}
			}

			// Properly disconnect from LDAP
			tx_igldapssoauth_ldap::disconnect();
		}

		if (count($configurationRecords) > 0) {
			// Commit pending changes to the database
			// Syntax is compatible with MySQL, Oracle, MSSQL and PostgreSQL
			$this->getDatabaseConnection()->sql_query('COMMIT');
		}

		foreach ($typo3_modes as $typo3_mode) {
			$users = $this->getDatabaseConnection()->exec_SELECTgetRows(
				'uid',
				$typo3_mode . '_users',
				'tx_igldapssoauth_dn IS NOT NULL AND tx_igldapssoauth_dn<>\'\' AND disable=1 AND endtime=' . $GLOBALS['EXEC_TIME'],
				'',
				'',
				'',
				'uid'
			);
			if (count($users) > 0) {
				Tx_IgLdapSsoAuth_Utility_Notification::dispatch(
					__CLASS__,
					'usersDeactivated',
					array(
						'table' => $table,
						'userUids' => array_keys($users),
					)
				);
			}
		}

		// Task is supposed to always execute properly
		return TRUE;
	}

	/**
	 * Simulate cli call with setting the required options to the $_SERVER['argv']
	 *
	 * @return	void
	 * @access protected
	 */
	protected function setCliArguments() {
		$_SERVER['argv'] = array(
			$_SERVER['argv'][0],
			'tx_igldapssoauth_scheduler_synchroniseusers',
			'0',
			'-ss',
			'--sleepTime',
			$this->sleepTime,
			'--sleepAfterFinish',
			$this->sleepAfterFinish,
			'--countInARun',
			$this->countInARun
		);
	}

	/**
	 * Returns the database connection.
	 *
	 * @return t3lib_DB
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
