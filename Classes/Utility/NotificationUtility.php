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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Notification class.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class NotificationUtility
{
	protected static $instance;

	/**
	 * NotificationUtility constructor.
	 *
	 * @param \Psr\EventDispatcher\EventDispatcherInterface $
	 */
	public function __construct(protected \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher)
	{}

	/**
	 * Dispatches a signal by calling the registered slot methods.
	 *
	 * @param \Causal\IgLdapSsoAuth\Event\LdapEvent $event
	 */
    public static function dispatch(\Causal\IgLdapSsoAuth\Event\LdapEvent $event): void
    {
        self::getInstance()->eventDispatcher->dispatch($event);
    }

	/**
	 * Get instance.
	 *
	 * @return \Causal\IgLdapSsoAuth\Utility\NotificationUtility
	 */
	protected static function getInstance(): self
	{
		if (self::$instance === null) {
			self::$instance = GeneralUtility::makeInstance(self::class, GeneralUtility::makeInstance(\Psr\EventDispatcher\EventDispatcherInterface::class));
		}

		return self::$instance;
	}
}
