<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
 *  All rights reserved
 *
 *  Is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
		if (version_compare(TYPO3_version, '6.0.0', '<')) {
			// Observer design pattern with signal/slot is not available in TYPO3 4.x
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
