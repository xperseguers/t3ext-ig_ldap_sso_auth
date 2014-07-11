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

	/** @var array */
	protected $operations = array();

	/** @var string */
	protected $table = 'tx_igldapssoauth_config';

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
		if ($this->checkV1xToV12()) {
			$this->operations[] = 'upgradeV1xToV12';
		}
		if ($this->checkV12ToV13()) {
			$this->operations[] = 'upgradeV12ToV13';
		}

		return count($this->operations) > 0;
	}

	/**
	 * Returns TRUE if upgrade wizard from v1.x to v1.2 should be run.
	 *
	 * @return bool
	 */
	protected function checkV1xToV12() {
		$updateNeeded = FALSE;
		$mapping = $this->getMapping();

		$where = array();
		foreach ($mapping as $configKey => $field) {
			if (!empty($this->configuration[$configKey])) {
				// Global setting present => should be migrated if not already done
				$updateNeeded = TRUE;
			}
			$where[] = $field . '=' . $this->getDatabaseConnection()->fullQuoteStr('', $this->table);
		}
		if ($updateNeeded) {
			$oldConfigurationRecords = $this->getDatabaseConnection()->exec_SELECTcountRows(
				'*',
				$this->table,
				implode(' AND ', $where)
			);
			$updateNeeded = ($oldConfigurationRecords > 0);
		}

		return $updateNeeded;
	}

	/**
	 * Returns TRUE if upgrade wizard from v1.2 to v1.3 should be run.
	 *
	 * @return bool
	 */
	protected function checkV12ToV13() {
		$oldConfigurationRecords = $this->getDatabaseConnection()->exec_SELECTcountRows(
			'*',
			$this->table,
			'group_membership=0'
		);
		return $oldConfigurationRecords > 0;
	}

	/**
	 * Main method that is called whenever UPDATE! menu
	 * was clicked.
	 *
	 * @return string HTML to display
	 */
	public function main() {
		$out = array();

		foreach ($this->operations as $operation) {
			$out[] = call_user_func(array($this, $operation));
		}

		return implode(LF, $out);
	}

	/**
	 * Upgrades configuration from v1.x to v1.2.
	 *
	 * @return string
	 */
	protected function upgradeV1xToV12() {
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
			$where[] = $field . '=' . $this->getDatabaseConnection()->fullQuoteStr('', $this->table);
		}
		$oldConfigurationRecords = $this->getDatabaseConnection()->exec_SELECTgetRows(
			'uid',
			$this->table,
			implode(' AND ', $where)
		);

		$i = 0;
		foreach ($oldConfigurationRecords as $oldConfigurationRecord) {
			$this->getDatabaseConnection()->exec_UPDATEquery(
				$this->table,
				'uid=' . $oldConfigurationRecord['uid'],
				$fieldValues
			);
			$i++;
		}

		return $this->formatOk('Successfully updated ' . $i . ' configuration record' . ($i > 1 ? 's' : ''));
	}

	/**
	 * Upgrades configuration from v1.2 to v1.3.
	 *
	 * @return string
	 */
	protected function upgradeV12ToV13() {
		$this->getDatabaseConnection()->exec_UPDATEquery(
			$this->table,
			'1=1',
			array(
				'group_membership' => (bool) $this->configuration['evaluateGroupsFromMembership'] ? 2 : 1,
			)
		);

		return $this->formatOk('Successfully transferred how the group membership should be extracted from LDAP from global configuration to the configuration records.');
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
