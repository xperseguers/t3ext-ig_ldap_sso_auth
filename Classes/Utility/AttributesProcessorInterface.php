<?php
namespace Causal\IgLdapSsoAuth\Utility;

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
 * Centralizes the code for post-processing LDAP entry attributes.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
interface AttributesProcessorInterface {

	/**
	 * Post-processes the attributes of an LDAP entry.
	 *
	 * @param resource $link LDAP link from ldap_connect()
	 * @param resource $entry LDAP entry from ldap_first_entry() or ldap_next_entry()
	 * @param array $attributes LDAP attributes
	 * @return void
	 */
	public function processAttributes($link, $entry, array &$attributes);

}
