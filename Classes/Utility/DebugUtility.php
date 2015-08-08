<?php
namespace Causal\IgLdapSsoAuth\Utility;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Debugging class.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 * @deprecated since 3.0 will be removed in 3.2
 */
class DebugUtility
{

    // Severity constants used by \TYPO3\CMS\Core\Utility\GeneralUtility::devLog()
    // but adapted from RFC 3164 (http://www.ietf.org/rfc/rfc3164.txt)
    const SEVERITY_DEBUG = -1;        // aka 'OK', debug-level message
    const SEVERITY_INFO = 0;        // informational message
    const SEVERITY_NOTICE = 1;        // normal but significant condition
    const SEVERITY_WARNING = 2;        // warning condition
    const SEVERITY_ERROR = 3;        // error condition

    /**
     * Wrapper for a log message with severity SEVERITY_DEBUG (debug-level message).
     *
     * @param string $message Message (in English)
     * @param mixed $dataVar Additional data you want to pass to the logger
     * @return void
     * @api
     */
    static public function debug($message, $dataVar = false)
    {
        static::log($message, static::SEVERITY_DEBUG, $dataVar);
    }

    /**
     * Wrapper for a log message with severity SEVERITY_INFO (informational message).
     *
     * @param string $message Message (in English)
     * @param mixed $dataVar Additional data you want to pass to the logger
     * @return void
     * @api
     */
    static public function info($message, $dataVar = false)
    {
        static::log($message, static::SEVERITY_INFO, $dataVar);
    }

    /**
     * Wrapper for a log message with severity SEVERITY_NOTICE (normal but significant condition).
     *
     * @param string $message Message (in English)
     * @param mixed $dataVar Additional data you want to pass to the logger
     * @return void
     * @api
     */
    static public function notice($message, $dataVar = false)
    {
        static::log($message, static::SEVERITY_NOTICE, $dataVar);
    }

    /**
     * Wrapper for a log message with severity SEVERITY_WARNING (warning condition).
     *
     * @param string $message Message (in English)
     * @param mixed $dataVar Additional data you want to pass to the logger
     * @return void
     * @api
     */
    static public function warning($message, $dataVar = false)
    {
        static::log($message, static::SEVERITY_WARNING, $dataVar);
    }

    /**
     * Wrapper for a log message with severity SEVERITY_ERROR (error condition).
     *
     * @param string $message Message (in English)
     * @param mixed $dataVar Additional data you want to pass to the logger
     * @return void
     * @api
     */
    static public function error($message, $dataVar = false)
    {
        static::log($message, static::SEVERITY_ERROR, $dataVar);
    }

    /**
     * Wrapper for dev log, in order to ease testing.
     *
     * @param string $message Message (in English)
     * @param integer $severity Severity, one of the \Causal\IgLdapSsoAuth\Utility\DebugUtility::SEVERITY_* constants
     * @param mixed $dataVar Additional data you want to pass to the logger
     * @return void
     * @api
     */
    static public function log($message, $severity, $dataVar = false)
    {
        GeneralUtility::devLog($message, 'ig_ldap_sso_auth', $severity, $dataVar);
    }

}
