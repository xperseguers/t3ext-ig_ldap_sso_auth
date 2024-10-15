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

use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;

/**
 * Listener for the event ModifyDatabaseQueryForRecordListingEvent and
 * extends the query for be_groups, be_users, fe_groups and fe_users.
 */
class ExtendUserGroupQueryListener
{
    /**
     * @param ModifyDatabaseQueryForRecordListingEvent $event
     */
    public function __invoke(ModifyDatabaseQueryForRecordListingEvent $event): void
    {
        if (in_array($event->getTable(), ['be_groups', 'be_users', 'fe_groups', 'fe_users'], true)) {
            $event->getQueryBuilder()->addSelect('tx_igldapssoauth_dn');
        }
    }
}