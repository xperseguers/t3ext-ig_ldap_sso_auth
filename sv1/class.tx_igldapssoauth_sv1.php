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

require_once(t3lib_extMgm::extPath('sv').'class.tx_sv_auth.php');

/**
 * LDAP / SSO authentication service.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	ig_ldap_sso_auth
 *
 * $Id$
 */

class tx_igldapssoauth_sv1 extends tx_sv_auth {

	var $prefixId = 'tx_igldapssoauth_sv1';		// Same as class name
	var $scriptRelPath = 'sv1/class.tx_igldapssoauth_sv1.php';	// Path to this script relative to the extension dir.
	var $extKey = 'ig_ldap_sso_auth';	// The extension key.
	var $igldapssoauth;

	/**
	 * [Put your description here]
	 * performs the service processing
	 *
	 * @param	string		Content which should be processed.
	 * @param	string		Content type
	 * @param	array		Configuration array
	 * @return	boolean
	 */
	function getUser()	{

		//$this->logoff;
		//$this->login['uname']
		//$this->login['uident_text']
		//iglib_debug::print_this($GLOBALS['TSFE']->cObj);
		//iglib_debug::print_this($this);
		//iglib_debug::print_this(TYPO3_MODE);

		//iglib_debug::var_dump_this($this);
		//iglib_debug::print_this(tx_igldapssoauth_auth::is_enable());

		tx_igldapssoauth_config::init(TYPO3_MODE, 0);

		// Enable feature

//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('LDAPAuthentication'), 'Enable LDAP Authentication');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('CASAuthentication'), 'CAS authentication');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('evaluateGroupsFromMembership'), 'Evaluate groups from membership');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('IfUserExist'), 'If user exist');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('IfGroupExist'), 'If group exist');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('DeleteUserIfNoLDAPGroups'), 'Delete user if no LDAP groups found');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('GroupsNotSynchronize'), 'Groups not synchronize');
//		iglib_debug::print_this(tx_igldapssoauth_config::is_enable('assignGroups'), 'Assign these groups');

		$user = false;
		// CAS authentication
		if (tx_igldapssoauth_config::is_enable('CASAuthentication')) {

			$user = tx_igldapssoauth_auth::cas_auth();

		// Authenticate user from LDAP
		} elseif ($this->login['status']=='login' && $this->login['uident']) {

			$user = tx_igldapssoauth_auth::ldap_auth($this->login['uname'], $this->login['uident_text']);

			if (!$user) {

				$user = $this->fetchUserRecord($this->login['uname']);

			}

		}

		// Failed login attempt (no username found)
		if (!is_array($user)) {

			$this->writelog(255,3,3,2,
				"Login-attempt from %s (%s), username '%s' not found!!",
				Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));	// Logout written to log

		// User found
		} else {

			if ($this->writeDevLog)	t3lib_div::devLog('User found: '.t3lib_div::arrayToLogString($user, array($this->db_user['userid_column'],$this->db_user['username_column'])), 'tx_igldapssoauth_sv1');

		}

		return $user;
	}

	function authUser($user) {

		$OK = 100;

		if (($this->login['uident'] && $this->login['uname']) || (tx_igldapssoauth_config::is_enable('CASAuthentication') && $user))	{

			// Checking password match for user:
			$OK = isset($user['tx_igldapssoauth_from']) ? 200 : $this->compareUident($user, $this->login);

			if (!$OK) {
					// Failed login attempt (wrong password) - write that to the log!

				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', password not accepted!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $this->login['uname']));
				}

				if ($this->writeDevLog) t3lib_div::devLog('Password not accepted: '.$this->login['uident'], 'tx_igldapssoauth_sv1', 2);
			}

				// Checking the domain (lockToDomain)
			if ($OK && $user['lockToDomain'] && $user['lockToDomain']!=$this->authInfo['HTTP_HOST'])	{

					// Lock domain didn't match, so error:
				if ($this->writeAttemptLog) {
					$this->writelog(255,3,3,1,
						"Login-attempt from %s (%s), username '%s', locked domain '%s' did not match '%s'!",
						Array($this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST'], $user[$this->db_user['username_column']], $user['lockToDomain'], $this->authInfo['HTTP_HOST']));
				}
				$OK = false;
			}
		}

		return $OK;

	}

}



if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/sv1/class.tx_igldapssoauth_sv1.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/sv1/class.tx_igldapssoauth_sv1.php']);
}

?>