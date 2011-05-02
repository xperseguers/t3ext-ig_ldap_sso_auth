<?php
/***************************************************************
 *  Copyright notice
 *
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
	 * @var integer
	 */
	public $totalBrokenLink = 0;

	/**
	 * @var integer
	 */
	public $oldTotalBrokenLink = 0;

	/**
	 * Function executed from the Scheduler.
	 *
	 * @return	void
	 */
	public function execute() {
		$this->setCliArguments();
		$auth = t3lib_div::makeInstance('tx_igldapssoauth_auth');
		$this->table = 'fe_users';
		$confArr = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
		
		$uidConf = $confArr['uidConfiguration'];
		$uidArray = t3lib_div::trimExplode(',', $uidConf);
		if(is_array($uidArray)) {
			foreach($uidArray as $uid) {
				tx_igldapssoauth_config::init('fe', $uid);
				//$this->fe['requiredLDAPGroups'] = ;
				// Valid user only if username and connect to LDAP server.
				if (tx_igldapssoauth_ldap::connect(tx_igldapssoauth_config::get_values('ldap'))) {
					$this->config = tx_igldapssoauth_config::get_values('fe');
		
					
					$search = tx_igldapssoauth_utility_Ldap::search($this->config['users']['basedn'], str_replace('{USERNAME}', '*', $this->config['users']['filter']), array('dn'));
					global $TYPO3_CONF_VARS;
					$userList = tx_igldapssoauth_utility_Ldap::get_entries();
		
					$this->authInfo['db_user']['table'] = $this->table;
					$this->authInfo['db_groups']['table'] = 'fe_groups';
					$nbres = $userList['count'];
					unset($userList['count']);
					
					if(is_array($userList)) {
						foreach($userList as $userInfo) {		
							if(!empty($userInfo['dn'])){				
							$user = tx_igldapssoauth_auth::synchroniseUser($userInfo['dn']);
							}
							$typoActivUsersList[] = $user['uid'];
						}
					}
					if(is_array($typoActivUsersList)) {
						$GLOBALS['TYPO3_DB']->exec_UPDATEquery($this->table,'disable=0 and uid not in (\'' . implode("','",$typoActivUsersList) . '\') and tx_igldapssoauth_dn  IS NOT NULL  and tx_igldapssoauth_dn not like \'\'',array('disable'=>1));
					}
				}
			}
		}
		return TRUE;
	} // end function execute()

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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_scheduler_synchroniseusers.php'])  {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/ig_ldap_sso_auth/lib/class.tx_igldapssoauth_scheduler_synchroniseusers.php']);
}
?>