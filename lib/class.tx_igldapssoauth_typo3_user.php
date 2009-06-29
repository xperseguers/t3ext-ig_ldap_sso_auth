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
 * Class tx_igldapssoauth_typo3_user for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	tx_igldapssoauth_typo3_user
 */
class tx_igldapssoauth_typo3_user {

	function init ($table = null) {

		// Get users table structure.
		$typo3_user_default = iglib_db::get_columns_from($table);

		foreach ($typo3_user_default as $field => $value) {

			$typo3_user[0][$field] = null;

		}

		return $typo3_user;

	}

	function select ($table = null, $uid = 0, $pid = 0, $username = null, $dn = null) {

		// Search with uid and pid.
		if ($uid) {

			$QUERY = array (
				"SELECT" => "*",
				"FROM" => $table,
				"WHERE" => "uid=".$uid,
				"GROUP_BY" => "",
				"ORDER_BY" => "",
				"LIMIT" => "",
				"UID_INDEX_FIELD" => "" ,
			);

		// Search with DN, username and pid.
		} else {

			$QUERY = array (
				"SELECT" => "*",
				"FROM" => $table,
				"WHERE" => "tx_igldapssoauth_dn='".$dn."' AND username='".$username."'".' AND pid IN ('.$pid.')',
				"GROUP_BY" => "",
				"ORDER_BY" => "",
				"LIMIT" => "",
				"UID_INDEX_FIELD" => "" ,
			);

			// If no user found with DN and username, search with username and pid only.
			if (!iglib_db::select($QUERY) && $pid) {

				$QUERY = array (
					"SELECT" => "*",
					"FROM" => $table,
					"WHERE" => "username='".$username."'".' AND pid IN ('.$pid.')',
					"GROUP_BY" => "",
					"ORDER_BY" => "",
					"LIMIT" => "",
					"UID_INDEX_FIELD" => "" ,
				);

			}elseif(!iglib_db::select($QUERY)){
				$QUERY = array (
					"SELECT" => "*",
					"FROM" => $table,
					"WHERE" => "username='".$username."'",
					"GROUP_BY" => "",
					"ORDER_BY" => "",
					"LIMIT" => "",
					"UID_INDEX_FIELD" => "" ,
				);
			}

		}

		// Return TYPO3 user.
		return iglib_db::select($QUERY);

	}


	function insert ($table = null, $typo3_user = array()) {

		$QUERY = array (
			"TABLE" => $table,
			"FIELDS_VALUES" => $typo3_user,
			"NO_QUOTE_FIELDS" => FALSE,
		);

		$uid = iglib_db::insert($QUERY);

		$QUERY = array (
			"SELECT" => "*",
			"FROM" => $table,
			"WHERE" => "uid=".$uid,
			"GROUP_BY" => "",
			"ORDER_BY" => "",
			"LIMIT" => "",
			"UID_INDEX_FIELD" => "" ,
		);

		return iglib_db::select($QUERY);

	}

	function update ($table = null, $typo3_user = array()) {

		$QUERY = array (
			"TABLE" => $table,
			"WHERE" => "uid=".$typo3_user['uid'],
			"FIELDS_VALUES" => $typo3_user,
			"NO_QUOTE_FIELDS" => FALSE,
		);

		return iglib_db::update($QUERY);

	}


	function set_usergroup ($typo3_groups = array(), $typo3_user = array()) {
		$required=true;
		$group_uid = array();

		if ($typo3_groups) {

			foreach ($typo3_groups as $typo3_group) {

				if ($typo3_group['uid']) {

					$group_uid[] = $typo3_group['uid'];

				}

			}

		}

		if ($assignGroups = explode(',', tx_igldapssoauth_config::is_enable('assignGroups'))) {

			foreach ($assignGroups as $uid) {

				if (tx_igldapssoauth_typo3_group::select($this->authInfo['db_groups']['table'], $uid) && !in_array($uid, $group_uid)) {

					$group_uid[] = $uid;

				}

			}

		}

		if (tx_igldapssoauth_config::is_enable('keepTYPO3Groups') && $typo3_user[0]['usergroup']) {

			$usergroup = explode(',', $typo3_user[0]['usergroup']);

			foreach ($usergroup as $uid) {

				if (!in_array($uid, $group_uid)) {

					$group_uid[] = $uid;

				}

			}

		}
		
	 	if ($requiredLDAPGroups = tx_igldapssoauth_config::is_enable('requiredLDAPGroups')) {
               	$requiredLDAPGroups = explode(',', $requiredLDAPGroups);
				$required = false;
            	foreach ($requiredLDAPGroups as $uid) {
                	if (in_array($uid, $group_uid)) {
						$required = true;
					}
            	}
        }
        
        if ($updateAdminAttribForGroups = tx_igldapssoauth_config::is_enable('updateAdminAttribForGroups')) {
				$updateAdminAttribForGroups = explode(',', $updateAdminAttribForGroups);
            	$typo3_user[0]['admin'] = 0;
            	foreach ($updateAdminAttribForGroups as $uid) {
                	if (in_array($uid, $group_uid)) {
                    	$typo3_user[0]['admin'] = 1;
                	}
            	}
        }

		$typo3_user[0]['usergroup'] = (string)implode(',', $group_uid);

		if ($required)
            	return $typo3_user;
        else
            	return false;

	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_user.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_typo3_user.php']);
}

?>