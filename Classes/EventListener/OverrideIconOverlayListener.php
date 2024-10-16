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

namespace Causal\IgLdapSsoAuth\EventListener;

use TYPO3\CMS\Core\Imaging\Event\ModifyRecordOverlayIconIdentifierEvent;

class OverrideIconOverlayListener
{
    /**
     * @param ModifyRecordOverlayIconIdentifierEvent $event
     */
    public function __invoke(ModifyRecordOverlayIconIdentifierEvent $event): void
    {
        if (in_array($event->getTable(), ['be_groups', 'be_users', 'fe_groups', 'fe_users'], true)) {
            $row = $event->getRow();
            $isDisabled = $row['disable'] ?? $row['hidden'] ?? false;
            if (!empty($row['tx_igldapssoauth_dn']) && !$isDisabled) {
                $event->setOverlayIconIdentifier('extensions-ig_ldap_sso_auth-overlay-ldap-record');
            }
        }
    }
}
