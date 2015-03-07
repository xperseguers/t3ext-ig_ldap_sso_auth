<?php
namespace Causal\IgLdapSsoAuth\Library;

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

use Causal\IgLdapSsoAuth\Library\Ldap;

/**
 * Class tx_igldapssoauth_typo3_group for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class LdapGroup {

	/**
	 * Returns LDAP group records based on a list of DNs provided as $membership,
	 * taking group's baseDN and filter into consideration.
	 *
	 * @param array $membership
	 * @param string $baseDn
	 * @param string $filter
	 * @param array $attributes
	 * @param bool $extendedCheck TRUE if groups should be actively checked against LDAP server, FALSE to check against baseDN solely
	 * @return array
	 */
	static public function select_from_membership($membership = array(), $baseDn = NULL, $filter = NULL, $attributes = array(), $extendedCheck = TRUE) {
		$ldap_groups['count'] = 0;

		if (!$membership) {
			return $ldap_groups;
		}
		if (!$filter) {
			return $ldap_groups;
		}

		unset($membership['count']);

		foreach ($membership as $groupdn) {
			if (substr($groupdn, -strlen($baseDn)) !== $baseDn) {
				// Group $groupdn does not match the required baseDn for LDAP groups
				continue;
			}
			if ($extendedCheck) {
				$ldap_group = Ldap::getInstance()->search($groupdn, $filter, $attributes);
			} else {
				$parts = explode(',', $groupdn);
				list($firstAttribute, $value) = explode('=', $parts[0]);
				$firstAttribute = strtolower($firstAttribute);
				$ldap_group = array(
					0 => array(
						0 => $firstAttribute,
						$firstAttribute => array(
							0 => $value,
							'count' => 1,
						),
						'dn' => $groupdn,
						'count' => 1,
					),
					'count' => 1,
				);
			}
			if (!isset($ldap_group['count']) || $ldap_group['count'] == 0) {
				continue;
			}
			$ldap_groups['count']++;
			$ldap_groups[] = $ldap_group[0];
		}

		return $ldap_groups;
	}

	/**
	 * Returns groups associated to a given user (identified either by his DN or his uid attribute).
	 *
	 * @param string $baseDn
	 * @param string $filter
	 * @param string $userDn
	 * @param string $userUid
	 * @param array $attributes
	 * @return array
	 */
	static public function selectFromUser($baseDn, $filter = '', $userDn = '', $userUid = '', array $attributes = array()) {
		$filter = str_replace('{USERDN}', Ldap::getInstance()->escapeDnForFilter($userDn), $filter);
		$filter = str_replace('{USERUID}', Ldap::getInstance()->escapeDnForFilter($userUid), $filter);

		$groups = Ldap::getInstance()->search($baseDn, $filter, $attributes);
		return $groups;
	}

	/**
	 * @param array $ldap_user
	 * @param array $mapping
	 * @return array|bool
	 */
	static public function get_membership($ldap_user = array(), $mapping = array()) {
		if (isset($mapping['usergroup']) && preg_match("`<([^$]*)>`", $mapping['usergroup'], $attribute)) {
			return $ldap_user[strtolower($attribute[1])];
		}

		return FALSE;
	}

}
