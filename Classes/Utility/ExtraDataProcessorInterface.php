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
 * Centralizes the code for importing users from LDAP/AD sources.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
interface Tx_IgLdapSsoAuth_Utility_ExtraDataProcessorInterface {

	/**
	 * Processes the extra data associated with the user record.
	 *
	 * @param string $table Name of the table into which the user was imported
	 * @param array $user User record with merged TYPO3/LDAP data
	 * @return void
	 */
	public function processExtraData($table, array $user);

}
