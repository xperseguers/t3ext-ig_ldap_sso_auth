<?php

declare(strict_types=1);

namespace Causal\IgLdapSsoAuth\EventListener;

/**
 * Listener for the event ModifyDatabaseQueryForRecordListingEvent and extends the query for be_groups, be_users, fe_groups and fe_users
 */
class ExtendUserGroupQueryListener
{
	/**
	 * Handle the event.
	 *
	 * @param \TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent $event
	 */
	public function __invoke(\TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent $event): void
	{
		if ($event->getTable() === 'be_groups' || $event->getTable() === 'be_users' || $event->getTable() === 'fe_groups' || $event->getTable() === 'fe_users'	) {
			$event->getQueryBuilder()->addSelect('tx_igldapssoauth_dn');
		}
	}
}
