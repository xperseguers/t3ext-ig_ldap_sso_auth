<?php
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

/**
 * Class tx_igldapssoauth_ldap_user for the 'ig_ldap_sso_auth' extension.
 *
 * @author	Michael Gagnon <mgagnon@infoglobe.ca>
 * @package	TYPO3
 * @subpackage	tx_igldapssoauth_ldap_user
 * @deprecated since version 1.3, this class will be removed in version 1.5, use methods from tx_igldapssoauth_ldap instead.
 */
class tx_igldapssoauth_ldap_user {

	static public function select($dn = NULL, $filter = NULL, $attributes = array()) {
		return tx_igldapssoauth_ldap::search($dn, str_replace('{USERNAME}', '*', $filter), $attributes);
	}

}
