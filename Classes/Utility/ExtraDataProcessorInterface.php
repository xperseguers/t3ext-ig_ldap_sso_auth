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

namespace Causal\IgLdapSsoAuth\Utility;

/**
 * Centralizes the code for importing users from LDAP/AD sources.
 *
 * @author     Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
interface ExtraDataProcessorInterface
{
    /**
     * Processes the extra data associated with the user record.
     *
     * @param string $table Name of the table into which the user was imported
     * @param array $user User record with merged TYPO3/LDAP data
     */
    public function processExtraData(string $table, array $user): void;
}
