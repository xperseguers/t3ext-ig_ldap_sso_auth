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

namespace Causal\IgLdapSsoAuth\Utility;

use Causal\IgLdapSsoAuth\Event\LdapEventInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
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
    private static ?NotificationUtility $instance = null;

    /**
     * NotificationUtility constructor.
     *
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    )
    {
    }

    /**
     * Dispatches a PSR-14 event.
     *
     * @param LdapEventInterface $event
     * @return LdapEventInterface The event
     */
    public static function dispatch(LdapEventInterface $event): LdapEventInterface
    {
        self::getInstance()->eventDispatcher->dispatch($event);
        return $event;
    }

    /**
     * Get instance.
     *
     * @return \Causal\IgLdapSsoAuth\Utility\NotificationUtility
     */
    protected static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = GeneralUtility::makeInstance(self::class);
        }

        return self::$instance;
    }
}
