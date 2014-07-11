<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Hook into \TYPO3\CMS\Core\DataHandling\DataHandler.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Tx_IgLdapSsoAuth_Hooks_DataHandler {

	/**
	 * Hooks into \TYPO3\CMS\Core\DataHandling\DataHandler after records have been saved to the database.
	 *
	 * @param string $operation
	 * @param string $table
	 * @param mixed $id
	 * @param array $fieldArray
	 * @param t3lib_TCEmain $pObj
	 * @return void
	 */
	public function processDatamap_afterDatabaseOperations($operation, $table, $id, array $fieldArray, t3lib_TCEmain $pObj) {
		if ($table !== 'tx_igldapssoauth_config') {
			// Early return
			return;
		}
		if ($operation === 'new' && !is_numeric($id)) {
			$id = $pObj->substNEWwithIDs[$id];
		}

		$row = t3lib_BEfunc::getRecord($table, $id);
		if ($row['group_membership'] == tx_igldapssoauth_config::GROUP_MEMBERSHIP_FROM_MEMBER) {
			$warningMessageKeys = array();

			if (!empty($row['be_users_basedn']) && !empty($row['be_groups_basedn'])) {
				// Check backend mapping
				$mapping = tx_igldapssoauth_config::make_mapping($row['be_users_mapping']);
				if (!isset($mapping['usergroup'])) {
					$warningMessageKeys[] = 'tx_igldapssoauth_config.group_membership.fe.missingUsergroupMapping';
				}
			}
			if (!empty($row['fe_users_basedn']) && !empty($row['fe_groups_basedn'])) {
				// Check frontend mapping
				$mapping = tx_igldapssoauth_config::make_mapping($row['fe_users_mapping']);
				if (!isset($mapping['usergroup'])) {
					$warningMessageKeys[] = 'tx_igldapssoauth_config.group_membership.be.missingUsergroupMapping';
				}
			}

			foreach ($warningMessageKeys as $key) {
				$flashMessage = t3lib_div::makeInstance(
					't3lib_FlashMessage',
					$GLOBALS['LANG']->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:' . $key, TRUE),
					'',
					t3lib_FlashMessage::WARNING,
					TRUE
				);
				t3lib_FlashMessageQueue::addMessage($flashMessage);
			}
		}
	}

}
