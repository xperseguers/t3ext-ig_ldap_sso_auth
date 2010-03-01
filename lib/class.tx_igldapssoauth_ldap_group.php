<?php

/***************************************************************
*  Copyright notice
*
*  (c) 2007 Michael Gagnon <mgagnon@infoglobe.ca>
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
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	tx_igldapssoauth_ldap_group
 *
 * $Id$
 */
class tx_igldapssoauth_ldap_group {


	function select_from_membership ($membership = array(), $filter = null, $attributes = array()) {

		//iglib_debug::print_this($membership, 'Membership');
		//iglib_debug::print_this($filter, 'Filter');
		//iglib_debug::print_this($attributes, 'Attribute');

		$ldap_groups['count'] = 0;

		if (!$membership) { return $ldap_groups; }
		if (!$filter) { return $ldap_groups; }

		unset($membership['count']);

		foreach ($membership as $groupdn) {

			$ldap_group = tx_igldapssoauth_ldap::search($groupdn, $filter, $attributes);

			$ldap_groups['count'] = $ldap_groups['count'] + 1;
			$ldap_groups[] = $ldap_group[0];

		}

		//iglib_debug::print_this($ldap_groups, 'LDAP groups from membership');

		return $ldap_groups;

	}

	function select_from_userdn ($userdn = null, $basedn = null, $filter = null, $attributes = array()) {

		return tx_igldapssoauth_ldap::search($basedn, str_replace('{USERDN}', $userdn, $filter), $attributes);

	}

	function get_membership ($ldap_user = array(), $mapping = array()) {

		//iglib_debug::print_this($ldap_user);
		//iglib_debug::print_this($mapping);

		if (array_key_exists('usergroup', $mapping) && preg_match("`<([^$]*)>`", $mapping['usergroup'], $attribute)) {

			return $ldap_user[strtolower($attribute[1])];

		}

		return false;

	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_ldap_group.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_ldap_group.php']);
}

?>