<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Francois Suter <typo3@cobweb.ch>
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
 * Utility class for fetching LDAP configurations.
 *
 * NOTE: this is a not a true Extbase repository.
 *
 * @author     Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Tx_IgLdapSsoAuth_Domain_Repository_ConfigurationRepository {

	/**
	 * @var bool Set to TRUE to also fetch disabled records (according to TCA enable fields)
	 */
	protected $fetchDisabledRecords = FALSE;

	/**
	 * Returns all available LDAP configurations.
	 *
	 * @return array
	 */
	public function fetchAll() {
		$where = '1 = 1' . t3lib_BEfunc::deleteClause('tx_igldapssoauth_config');
		if (!$this->fetchDisabledRecords) {
			$where .= t3lib_BEfunc::BEenableFields('tx_igldapssoauth_config');
		}
		$configurations = self::getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			'tx_igldapssoauth_config',
			$where,
			'',
			'sorting'
		);
		if ($configurations == NULL) {
			$configurations = array();
		}
		return $configurations;
	}

	/**
	 * Returns a single LDAP configuration.
	 *
	 * @param integer $uid Primary key to look up
	 * @return array
	 */
	public function fetchByUid($uid) {
		$where = 'uid = ' . intval($uid) . t3lib_BEfunc::deleteClause('tx_igldapssoauth_config');
		if (!$this->fetchDisabledRecords) {
			$where .= t3lib_BEfunc::BEenableFields('tx_igldapssoauth_config');
		}
		$configuration = self::getDatabaseConnection()->exec_SELECTgetSingleRow(
			'*',
			'tx_igldapssoauth_config',
			$where
		);
		return $configuration;
	}

	/**
	 * Sets the flag for fetching disabled records or not.
	 *
	 * @param boolean $flag Set to TRUE to enable fetching of disabled record
	 * @return void
	 */
	public function setFetchDisabledRecords($flag) {
		$this->fetchDisabledRecords = (bool)$flag;
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
