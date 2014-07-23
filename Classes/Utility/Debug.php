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
 * Debugging class.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Tx_IgLdapSsoAuth_Utility_Debug {

	// Severity constants used by \TYPO3\CMS\Core\Utility\GeneralUtility::devLog()
	// but adapted from RFC 3164 (http://www.ietf.org/rfc/rfc3164.txt)
	const SEVERITY_DEBUG = -1;		// aka 'OK', debug-level message
	const SEVERITY_INFO = 0;		// informational message
	const SEVERITY_NOTICE = 1;		// normal but significant condition
	const SEVERITY_WARNING = 2;		// warning condition
	const SEVERITY_ERROR = 3;		// error condition

	/**
	 * Wrapper for a log message with severity SEVERITY_DEBUG (debug-level message).
	 *
	 * @param string $message Message (in English)
	 * @param mixed $dataVar Additional data you want to pass to the logger
	 * @return void
	 * @api
	 */
	static public function debug($message, $dataVar = FALSE) {
		self::log($message, self::SEVERITY_DEBUG, $dataVar);
	}

	/**
	 * Wrapper for a log message with severity SEVERITY_INFO (informational message).
	 *
	 * @param string $message Message (in English)
	 * @param mixed $dataVar Additional data you want to pass to the logger
	 * @return void
	 * @api
	 */
	static public function info($message, $dataVar = FALSE) {
		self::log($message, self::SEVERITY_INFO, $dataVar);
	}

	/**
	 * Wrapper for a log message with severity SEVERITY_NOTICE (normal but significant condition).
	 *
	 * @param string $message Message (in English)
	 * @param mixed $dataVar Additional data you want to pass to the logger
	 * @return void
	 * @api
	 */
	static public function notice($message, $dataVar = FALSE) {
		self::log($message, self::SEVERITY_NOTICE, $dataVar);
	}

	/**
	 * Wrapper for a log message with severity SEVERITY_WARNING (warning condition).
	 *
	 * @param string $message Message (in English)
	 * @param mixed $dataVar Additional data you want to pass to the logger
	 * @return void
	 * @api
	 */
	static public function warning($message, $dataVar = FALSE) {
		self::log($message, self::SEVERITY_WARNING, $dataVar);
	}

	/**
	 * Wrapper for a log message with severity SEVERITY_ERROR (error condition).
	 *
	 * @param string $message Message (in English)
	 * @param mixed $dataVar Additional data you want to pass to the logger
	 * @return void
	 * @api
	 */
	static public function error($message, $dataVar = FALSE) {
		self::log($message, self::SEVERITY_ERROR, $dataVar);
	}

	/**
	 * Wrapper for dev log, in order to ease testing.
	 *
	 * @param string $message Message (in English)
	 * @param integer $severity Severity, one of the Tx_IgLdapSsoAuth_Utility_Debug::SEVERITY_* constants
	 * @param mixed $dataVar Additional data you want to pass to the logger
	 * @return void
	 * @api
	 */
	static public function log($message, $severity, $dataVar = FALSE) {
		t3lib_div::devLog($message, 'ig_ldap_sso_auth', $severity, $dataVar);
	}

}
