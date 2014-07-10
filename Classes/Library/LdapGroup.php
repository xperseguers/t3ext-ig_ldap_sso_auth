<?php
/***************************************************************
 *  Copyright notice
 *
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
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_ldap_group {

	static public function select_from_membership($membership = array(), $filter = NULL, $attributes = array()) {
		$ldap_groups['count'] = 0;

		if (!$membership) {
			return $ldap_groups;
		}
		if (!$filter) {
			return $ldap_groups;
		}

		unset($membership['count']);

		foreach ($membership as $groupdn) {
			$ldap_group = tx_igldapssoauth_ldap::search($groupdn, $filter, $attributes);
			if ($ldap_group['count'] == 0) {
				continue;
			}
			$ldap_groups['count']++;
			$ldap_groups[] = $ldap_group[0];
		}

		return $ldap_groups;
	}

	/**
	 * @deprecated since version 1.3, this method will be removed in version 1.5, use tx_igldapssoauth_ldap_group::selectFromUser() instead.
	 */
	static public function select_from_userdn($userdn = NULL, $basedn = NULL, $filter = NULL, $attributes = array()) {
		t3lib_div::logDeprecatedFunction();
		return tx_igldapssoauth_ldap::search($basedn, str_replace('{USERDN}', tx_igldapssoauth_ldap::escapeDnForFilter($userdn), $filter), $attributes);
	}

	/**
	 * Returns groups associated to a given user (identified either by his DN or his uid attribute).
	 *
	 * @param string $baseDn
	 * @param string $filter
	 * @param string $userDn
	 * @param string $userUid
	 * @param array $attributes
	 */
	static public function selectFromUser($baseDn, $filter = '', $userDn = '', $userUid = '', array $attributes = array()) {
		$filter = str_replace('{USERDN}', tx_igldapssoauth_ldap::escapeDnForFilter($userDn), $filter);
		$filter = str_replace('{USERUID}', tx_igldapssoauth_ldap::escapeDnForFilter($userUid), $filter);

		$groups = tx_igldapssoauth_ldap::search($baseDn, $filter, $attributes);
		return $groups;
	}

	static public function get_membership($ldap_user = array(), $mapping = array()) {
		if (isset($mapping['usergroup']) && preg_match("`<([^$]*)>`", $mapping['usergroup'], $attribute)) {
			return $ldap_user[strtolower($attribute[1])];
		}

		return FALSE;
	}

}
