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
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * Notification class.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class NotificationUtility
{
    /**
     * Dispatches a signal by calling the registered slot methods.
     *
     * @param string $signalClassName
     * @param string $signalName
     * @param array $signalArguments
     * @return mixed
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    public static function dispatch(string $signalClassName, string $signalName, array $signalArguments = [])
    {
        return static::getSignalSlotDispatcher()->dispatch($signalClassName, $signalName, $signalArguments);
    }

    /**
     * Returns the signal slot dispatcher.
     *
     * @return Dispatcher
     */
    protected static function getSignalSlotDispatcher(): Dispatcher
    {
        /** @var Dispatcher $signalSlotDispatcher */
        static $signalSlotDispatcher = null;

        if ($signalSlotDispatcher === null) {
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
            $signalSlotDispatcher = $objectManager->get(Dispatcher::class);
        }

        return $signalSlotDispatcher;
    }
}
