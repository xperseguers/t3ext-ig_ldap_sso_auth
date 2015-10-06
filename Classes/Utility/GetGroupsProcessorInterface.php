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
 * An interface to post-process determining groups for a user.
 *
 * @author     Peter Niederlag <peter.niederlag@datenbetrieb.de>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
interface GetGroupsProcessorInterface
{

    /**
     * Post-processes the groups of a user
     *
     * @param string $groupTable Table name of the group table
     * @param array $ldapUser Full ldap data of the currently processed user
     * @param array $userGroups User groups as they have been determined before hitting this function
     * @return void
     */
    public function getUserGroups($groupTable, array $ldapUser, array &$userGroups);

}
