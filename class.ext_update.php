<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@causal.ch>
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


$BACK_PATH = $GLOBALS['BACK_PATH'] . TYPO3_mainDir;

/**
 * Class to be used to migrate global configuration from v1.1.x and below to
 * configuration records in v1.2.
 *
 * @category    Extension Manager
 * @package     TYPO3
 * @subpackage  tx_igldapssoauth
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ext_update extends t3lib_SCbase {

	/** @var string */
	protected $extKey = 'ig_ldap_sso_auth';

	/** @var array */
	protected $configuration;

	/**
	 * Default constructor.
	 */
	public function __construct() {
		$this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
	}

	/**
	 * Checks whether the "UPDATE!" menu item should be
	 * shown.
	 *
	 * @return boolean
	 */
	public function access() {
		$updateNeeded = FALSE;

		$table = 'tx_igldapssoauth_config';
		$mapping = $this->getMapping();

		$where = array();
		foreach ($mapping as $configKey => $field) {
			if (!empty($this->configuration[$configKey])) {
				// Global setting present => should be migrated if not already done
				$updateNeeded = TRUE;
			}
			$where[] = $field . '=' . $this->getDatabaseConnection()->fullQuoteStr('', $table);
		}
		if ($updateNeeded) {
			$oldConfigurationRecords = $this->getDatabaseConnection()->exec_SELECTcountRows(
				'*',
				$table,
				implode(' AND ', $where)
			);
			$updateNeeded = ($oldConfigurationRecords > 0);
		}
		return $updateNeeded;
	}

	/**
	 * Main method that is called whenever UPDATE! menu
	 * was clicked.
	 *
	 * @return string HTML to display
	 */
	public function main() {
		$table = 'tx_igldapssoauth_config';
		$mapping = $this->getMapping();

		$fieldValues = array(
			'tstamp' => $GLOBALS['EXEC_TIME'],
		);
		$where = array();
		foreach ($mapping as $configKey => $field) {
			if (!empty($this->configuration[$configKey])) {
				// Global setting present => should be migrated
				$fieldValues[$field] = $this->configuration[$configKey];
			}
			$where[] = $field . '=' . $this->getDatabaseConnection()->fullQuoteStr('', $table);
		}
		$oldConfigurationRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			$table,
			implode(' AND ', $where)
		);

		$i = 0;
		foreach ($oldConfigurationRecords as $oldConfigurationRecord) {
			$this->getDatabaseConnection()->exec_UPDATEquery(
				$table,
				'uid=' . $oldConfigurationRecord['uid'],
				$fieldValues
			);
			$i++;
		}

		return $this->formatOk('Successfully updated ' . $i . ' configuration record' . ($i > 1 ? 's' : ''));
	}

	/**
	 * Returns the mapping between global configuration options and
	 * configuration record fields.
	 *
	 * @return array
	 */
	protected function getMapping() {
		return array(
			'requiredLDAPBEGroups' => 'be_groups_required',
			'assignBEGroups' => 'be_groups_assigned',
			'updateAdminAttribForGroups' => 'be_groups_admin',
			'requiredLDAPFEGroups' => 'fe_groups_required',
			'assignFEGroups' => 'fe_groups_assigned',
		);
	}

	/**
	 * Creates an OK message for backend output.
	 *
	 * @param string $message
	 * @param boolean $hsc
	 * @return string
	 */
	protected function formatOk($message, $hsc = TRUE) {
		$output = '<div class="typo3-message message-ok">';
		//$output .= '<div class="message-header">Message head</div>';
		if ($hsc) {
			$message = nl2br(htmlspecialchars($message));
		}
		$output .= '<div class="message-body">' . $message . '</div>';
		$output .= '</div>';

		return $output;
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
