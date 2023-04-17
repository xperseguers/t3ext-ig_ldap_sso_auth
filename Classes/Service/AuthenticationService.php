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

namespace Causal\IgLdapSsoAuth\Service;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Exception\UnsupportedLoginSecurityLevelException;
use Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException;
use Causal\IgLdapSsoAuth\Library\Authentication;
use Causal\IgLdapSsoAuth\Library\Configuration;
use Causal\IgLdapSsoAuth\Utility\NotificationUtility;

/**
 * LDAP / SSO authentication service.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class AuthenticationService extends \TYPO3\CMS\Core\Authentication\AuthenticationService
{

    /**
     * 200 - authenticated and no more checking needed
     */
    const STATUS_AUTHENTICATION_SUCCESS_BREAK = 200;

    /**
     * 0 - this service was the right one to authenticate the user but it failed
     */
    const STATUS_AUTHENTICATION_FAILURE_BREAK = 0;

    /**
     * 100 - just go on. User is not authenticated but there's still no reason to stop
     */
    const STATUS_AUTHENTICATION_FAILURE_CONTINUE = 100;

    var $prefixId = 'tx_igldapssoauth_sv1'; // Keep class name
    var $scriptRelPath = 'Classes/Service/AuthenticationService.php'; // Path to this script relative to the extension dir.
    var $extKey = 'ig_ldap_sso_auth'; // The extension key.
    var $igldapssoauth;

    /**
     * @var array
     */
    protected $config;

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->config = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey] ?? [];
        Authentication::setAuthenticationService($this);
    }

    /**
     * Find a user (eg. look up the user record in database when a login is sent)
     *
     * @return mixed user array or false
     * @throws UnsupportedLoginSecurityLevelException
     */
    public function getUser()
    {
        $user = false;
        $userRecordOrIsValid = false;
        $remoteUser = $this->getRemoteUser();
        $enableFrontendSso = \Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode() === 'FE' && $this->config['enableFESSO'] && $remoteUser;
        $enableBackendSso = \Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode() === 'BE' && $this->config['enableBESSO'] && $remoteUser;

        // This simple check is the key to prevent your log being filled up with warnings
        // due to the AJAX calls to maintain the session active if your configuration forces
        // the authentication stack to always fetch the user:
        // $TYPO3_CONF_VARS['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;
        // This is the case, e.g., when using EXT:crawler.
        if ($this->login['status'] !== 'login' && !($enableFrontendSso || $enableBackendSso)) {
            return $user;
        }

        /** @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository */
        $configurationRepository = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository::class);
        $configurationRecords = $configurationRepository->findAll();

        if (count($configurationRecords) === 0) {
            // Early return since LDAP is not configured
            static::getLogger()->warning('Skipping LDAP authentication as extension is not yet configured');
            return false;
        }

        foreach ($configurationRecords as $configurationRecord) {
            Configuration::initialize(\Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode(), $configurationRecord);
            if (!Configuration::isEnabledForCurrentHost()) {
                $msg = sprintf(
                    'Configuration record #%s is not enabled for domain %s',
                    $configurationRecord->getUid(),
                    GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY')
                );
                static::getLogger()->info($msg);
                continue;
            }

            // Enable feature
            $userRecordOrIsValid = false;

            // Single Sign-On authentication
            if ($enableFrontendSso || $enableBackendSso) {
                // Strip the domain name
                $domain = null;
                if (!Configuration::getValue('SSOKeepDomainName')) {
                    if ($pos = strpos($remoteUser, '@')) {
                        $domain = substr($remoteUser, $pos + 1);
                        $remoteUser = substr($remoteUser, 0, $pos);
                    } elseif ($pos = strrpos($remoteUser, '\\')) {
                        $domain = substr($remoteUser, 0, $pos);
                        $remoteUser = substr($remoteUser, $pos + 1);
                    }
                }

                $userRecordOrIsValid = Authentication::ldapAuthenticate($remoteUser, null, $domain);
                if (is_array($userRecordOrIsValid)) {
                    // Useful for debugging purpose
                    $this->login['uname'] = $remoteUser;
                    if (!empty($domain)) {
                        $this->login['domain'] = $domain;
                    }
                }
            }

            // Authenticate user from LDAP
            if (!$userRecordOrIsValid && $this->login['status'] === 'login' && $this->login['uident']) {
                // normal case
                $password = $this->login['uident_text'] ?? $this->login['uident'];

                try {
                    if ($password !== null) {
                        $userRecordOrIsValid = Authentication::ldapAuthenticate($this->login['uname'], $password);
                    } else {
                        // Could not decrypt password
                        $userRecordOrIsValid = false;
                    }
                } catch (UnresolvedPhpDependencyException $e) {
                    // Possible known exception: 1409566275, LDAP extension is not available for PHP
                    $userRecordOrIsValid = false;
                }
            }
            if (is_array($userRecordOrIsValid)) {
                $user = $userRecordOrIsValid;
                break;
            } elseif ($userRecordOrIsValid) {
                // Authentication is valid
                break;
            } else {
                $diagnostic = Authentication::getLastAuthenticationDiagnostic();
                $info = [
                    'username' => $this->login['uname'],
                    'remote' => sprintf('%s (%s)', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']),
                    'diagnostic' => $diagnostic,
                    'configUid' => $configurationRecord->getUid(),
                ];
				// @extensionScannerIgnoreLine
                static::getLogger()->error('Authentication failed', $info);
                NotificationUtility::dispatch(
					new \Causal\IgLdapSsoAuth\Event\AuthenticationFailedEvent($info)
				);
            }

            // Continue and try with next configuration record...
        }

        if (!$user && $userRecordOrIsValid) {
            $user = $this->fetchUserRecord($this->login['uname']);
        }

        if (is_array($user)) {
            static::getLogger()->debug(sprintf('User found: "%s"', $this->login['uname']));
        }

        return $user;
    }

    /**
     * Authenticates a user (Check various conditions for the user that might invalidate its
     * authentication, e.g., password match, domain, IP, etc.).
     *
     * @param array $user Data of user.
     * @return int
     */
    public function authUser(array $user): int
    {
        if (!Configuration::isInitialized()) {
            // Early return since LDAP is not configured
            return static::STATUS_AUTHENTICATION_FAILURE_CONTINUE;
        }

        if (\Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode() === 'BE') {
            $status = Configuration::getValue('BEfailsafe')
                ? static::STATUS_AUTHENTICATION_FAILURE_CONTINUE
                : static::STATUS_AUTHENTICATION_FAILURE_BREAK;
        } else {
            $status = static::STATUS_AUTHENTICATION_FAILURE_CONTINUE;
        }

        $remoteUser = $this->getRemoteUser();
        $enableFrontendSso = \Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode() === 'FE' && $this->config['enableFESSO'] && $remoteUser;
        $enableBackendSso = \Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode() === 'BE' && $this->config['enableBESSO'] && $remoteUser;

        if ((($this->login['uident'] && $this->login['uname']) || $enableFrontendSso || $enableBackendSso) && !empty($user['tx_igldapssoauth_dn'])) {
            if (isset($user['tx_igldapssoauth_from'])) {
                $status = static::STATUS_AUTHENTICATION_SUCCESS_BREAK;
            } elseif (\Causal\IgLdapSsoAuth\Utility\Typo3Utility::getTypo3Mode() === 'BE' && Configuration::getValue('BEfailsafe')) {
                return static::STATUS_AUTHENTICATION_FAILURE_CONTINUE;
            } else {
                // Failed login attempt (wrong password) - write that to the log!
                static::getLogger()->warning('Password not accepted: ', [
                        'username' => $this->login['uname'],
                        'remote' => sprintf('%s (%s)', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']),
                    ]);
                $status = static::STATUS_AUTHENTICATION_FAILURE_BREAK;
            }

            // Checking the domain (lockToDomain)
            if ($status && !empty($user['lockToDomain']) && $user['lockToDomain'] !== $this->authInfo['HTTP_HOST']) {

                // Lock domain didn't match, so error:
				// @extensionScannerIgnoreLine
                static::getLogger()->error(sprintf('Locked domain "%s" did not match "%s"', $user['lockToDomain'], $this->authInfo['HTTP_HOST']), [
                    'username' => $user[$this->db_user['username_column']],
                    'remote' => sprintf('%s (%s)', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']),
                ]);

                $status = static::STATUS_AUTHENTICATION_FAILURE_BREAK;
            }
        }

        return $status;
    }

    /**
     * Returns the remote user ($_SERVER['REMOTE_USER']).
     *
     * @return string
     */
    protected function getRemoteUser()
    {
        $remoteUser = !empty($_SERVER['REMOTE_USER'])
            ? $_SERVER['REMOTE_USER']
            : (!empty($_SERVER['REDIRECT_REMOTE_USER'])
                ? $_SERVER['REDIRECT_REMOTE_USER']
                : null
            );
        if (!empty($remoteUser) && function_exists('mb_detect_encoding') && mb_detect_encoding($remoteUser, mb_detect_order(), true) !== 'UTF-8') {
            $remoteUser = utf8_encode($remoteUser);
        }
        return $remoteUser;
    }

    /**
     * Returns a logger.
     *
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected static function getLogger()
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
        }
        return $logger;
    }

}
