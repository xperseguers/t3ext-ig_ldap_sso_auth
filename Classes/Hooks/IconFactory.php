<?php
declare(strict_types=1);

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

namespace Causal\IgLdapSsoAuth\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Hook into \TYPO3\CMS\Core\Imaging\IconFactory to visually change
 * the icon associated to a FE/BE user/group record based on whether
 * the record is linked to LDAP.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class IconFactory
{
    /**
     * Overrides the icon overlay with a LDAP symbol, if needed.
     *
     * @param string $table The current database table
     * @param array $row The current record
     * @param array $status The array of associated statuses
     * @param string|null $iconName The computed overlay icon name
     * @return string|null The overlay icon name
     * @see \TYPO3\CMS\Core\Imaging\IconFactory::mapRecordTypeToOverlayIdentifier()
     */
    public function postOverlayPriorityLookup(
        string $table,
        array $row,
        array $status,
        ?string $iconName
    ): ?string
    {
        if (!empty($row)
            && in_array($table, ['be_groups', 'be_users', 'fe_groups', 'fe_users'], true)) {
            if ($row['uid'] ?? false) {
                // This is the case, e.g., in Backend users module
                $row = BackendUtility::getRecord($table, $row['uid']);
            }
            $isDisabled = $row['disable'] ?? $row['hidden'] ?? false;
            if (!empty($row['tx_igldapssoauth_dn']) && !$isDisabled) {
                $iconName = 'extensions-ig_ldap_sso_auth-overlay-ldap-record';
            }
        }

        return $iconName;
    }
}
