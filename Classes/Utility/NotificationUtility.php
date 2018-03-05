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
    public static function dispatch($signalClassName, $signalName, array $signalArguments = [])
    {
        return static::getSignalSlotDispatcher()->dispatch($signalClassName, $signalName, $signalArguments);
    }

    /**
     * Returns the signal slot dispatcher.
     *
     * @return \TYPO3\CMS\Extbase\SignalSlot\Dispatcher
     */
    protected static function getSignalSlotDispatcher()
    {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        static $signalSlotDispatcher = null;

        if ($signalSlotDispatcher === null) {
            /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
            $objectManager = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
            $signalSlotDispatcher = $objectManager->get(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        }

        return $signalSlotDispatcher;
    }
}
