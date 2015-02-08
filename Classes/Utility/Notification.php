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

/**
 * Notification class.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Tx_IgLdapSsoAuth_Utility_Notification {

	/**
	 * Dispatches a signal by calling the registered slot methods.
	 *
	 * @param string $signalClassName
	 * @param string $signalName
	 * @param array $signalArguments
	 * @return mixed
	 * @throws TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
	 * @throws TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
	 */
	static public function dispatch($signalClassName, $signalName, array $signalArguments = array()) {
		if (version_compare(TYPO3_branch, '4.6', '<')) {
			// Observer design pattern with signal/slot is not available prior to TYPO3 4.6
			return;
		}

		// Log the call to ease debugging
		Tx_IgLdapSsoAuth_Utility_Debug::debug('Signal from ' . $signalClassName . ' with name ' . $signalName, $signalArguments);

		return self::getSignalSlotDispatcher()->dispatch($signalClassName, $signalName, $signalArguments);
	}

	/**
	 * Returns the signal slot dispatcher.
	 *
	 * @return Tx_Extbase_SignalSlot_Dispatcher
	 */
	static protected function getSignalSlotDispatcher() {
		/** @var Tx_Extbase_SignalSlot_Dispatcher $signalSlotDispatcher */
		static $signalSlotDispatcher = NULL;

		if ($signalSlotDispatcher === NULL) {
			/** @var Tx_Extbase_Object_ObjectManager $objectManager */
			$objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
			$signalSlotDispatcher = $objectManager->get('Tx_Extbase_SignalSlot_Dispatcher');
		}

		return $signalSlotDispatcher;
	}

}
