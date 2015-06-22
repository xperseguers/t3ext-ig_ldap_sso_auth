<?php
namespace Causal\IgLdapSsoAuth\Service;

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
class AuthenticationService extends \TYPO3\CMS\Sv\AuthenticationService {

	/**
	 * TRUE - this service was able to authenticate the user
	 */
	const STATUS_AUTHENTICATION_SUCCESS_CONTINUE = TRUE;

	/**
	 * 200 - authenticated and no more checking needed
	 */
	const STATUS_AUTHENTICATION_SUCCESS_BREAK = 200;

	/**
	 * FALSE - this service was the right one to authenticate the user but it failed
	 */
	const STATUS_AUTHENTICATION_FAILURE_BREAK = FALSE;

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
	 * @var bool
	 */
	protected $cleanUpExtbaseCache = FALSE;

	/**
	 * Default constructor
	 */
	public function __construct() {
		$config = $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey];
		$this->config = $config ? unserialize($config) : array();
		Authentication::setAuthenticationService($this);
		$this->initializeExtbaseFramework();
	}

	/**
	 * Find a user (eg. look up the user record in database when a login is sent)
	 *
	 * @return mixed user array or FALSE
	 * @throws UnsupportedLoginSecurityLevelException
	 */
	public function getUser() {
		$user = FALSE;
		$userRecordOrIsValid = FALSE;
		$enableFrontendSso = TYPO3_MODE === 'FE' && (bool)$this->config['enableFESSO'] && !empty($_SERVER['REMOTE_USER']);

		// This simple check is the key to prevent your log being filled up with warnings
		// due to the AJAX calls to maintain the session active if your configuration forces
		// the authentication stack to always fetch the user:
		// $TYPO3_CONF_VARS['SVCONF']['auth']['setup']['BE_alwaysFetchUser'] = true;
		// This is the case, e.g., when using EXT:crawler.
		if ($this->login['status'] !== 'login' && !$enableFrontendSso) {
			return $user;
		}

		/** @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository */
		$configurationRepository = GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Domain\\Repository\\ConfigurationRepository');
		$configurationRecords = $configurationRepository->findAll();

		if (count($configurationRecords) === 0) {
			// Early return since LDAP is not configured
			static::getLogger()->warning('Skipping LDAP authentication as extension is not yet configured');
			$this->cleanUpExtbaseDataMapper();
			return FALSE;
		}

		foreach ($configurationRecords as $configurationRecord) {
			Configuration::initialize(TYPO3_MODE, $configurationRecord);
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
			$userRecordOrIsValid = FALSE;

			// Single Sign-On authentication
			if ($enableFrontendSso) {
				$remoteUser = $_SERVER['REMOTE_USER'];

				// Strip the domain name
				if ($pos = strpos($remoteUser, '@')) {
					$remoteUser = substr($remoteUser, 0, $pos);
				} elseif ($pos = strrpos($remoteUser, '\\')) {
					$remoteUser = substr($remoteUser, $pos + 1);
				}

				$userRecordOrIsValid = Authentication::ldapAuthenticate($remoteUser);
				if (is_array($userRecordOrIsValid)) {
					// Useful for debugging purpose
					$this->login['uname'] = $remoteUser;
				}

			// Authenticate user from LDAP
			} elseif ($this->login['status'] === 'login' && $this->login['uident']) {

				// Configuration of authentication service.
				$loginSecurityLevel = $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['loginSecurityLevel'];
				// normal case
				// Check if $loginSecurityLevel is set to "challenged" or "superchallenged" and throw an error if the configuration allows it
				// By default, it will not throw an exception
				if (isset($this->config['throwExceptionAtLogin']) && $this->config['throwExceptionAtLogin'] == 1) {
					if ($loginSecurityLevel === 'challenged' || $loginSecurityLevel === 'superchallenged') {
						$message = "ig_ldap_sso_auth error: current login security level '" . $loginSecurityLevel . "' is not supported.";
						$message .= " Try to use 'normal' or 'rsa' (highly recommended): ";
						$message .= "\$GLOBALS['TYPO3_CONF_VARS']['" . TYPO3_MODE . "']['loginSecurityLevel'] = 'rsa';";
						$this->cleanUpExtbaseDataMapper();
						throw new UnsupportedLoginSecurityLevelException($message, 1324313489);
					}
				}

				// normal case
				$password = $this->login['uident_text'];

				try {
					if ($password !== NULL) {
						$userRecordOrIsValid = Authentication::ldapAuthenticate($this->login['uname'], $password);
					} else {
						// Could not decrypt password
						$userRecordOrIsValid = FALSE;
					}
				} catch (UnresolvedPhpDependencyException $e) {
					// Possible known exception: 1409566275, LDAP extension is not available for PHP
					$userRecordOrIsValid = FALSE;
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
				$info = array(
					'username' => $this->login['uname'],
					'remote' => sprintf('%s (%s)', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']),
					'diagnostic' => $diagnostic,
					'configUid' => $configurationRecord->getUid(),
				);
				static::getLogger()->error('Authentication failed', $info);
				NotificationUtility::dispatch(__CLASS__, 'authenticationFailed', $info);
			}

			// Continue and try with next configuration record...
		}

		if (!$user && $userRecordOrIsValid) {
			$user = $this->fetchUserRecord($this->login['uname']);
		}

		if (is_array($user)) {
			static::getLogger()->debug(sprintf('User found: "%s"', $this->login['uname']));
		}

		$this->cleanUpExtbaseDataMapper();
		return $user;
	}

	/**
	 * Authenticates a user (Check various conditions for the user that might invalidate its
	 * authentication, e.g., password match, domain, IP, etc.).
	 *
	 * @param array $user Data of user.
	 * @return int|FALSE
	 */
	public function authUser(array $user) {
		if (!Configuration::isInitialized()) {
			// Early return since LDAP is not configured
			return static::STATUS_AUTHENTICATION_FAILURE_CONTINUE;
		}

		if (TYPO3_MODE === 'BE') {
			$status = Configuration::getValue('BEfailsafe')
				? static::STATUS_AUTHENTICATION_FAILURE_CONTINUE
				: static::STATUS_AUTHENTICATION_FAILURE_BREAK;
		} else {
			$status = static::STATUS_AUTHENTICATION_FAILURE_CONTINUE;
		}

		$enableFrontendSso = TYPO3_MODE === 'FE' && (bool)$this->config['enableFESSO'] && !empty($_SERVER['REMOTE_USER']);

		if ((($this->login['uident'] && $this->login['uname']) || $enableFrontendSso) && !empty($user['tx_igldapssoauth_dn'])) {
			if (isset($user['tx_igldapssoauth_from'])) {
				$status = static::STATUS_AUTHENTICATION_SUCCESS_BREAK;
			} elseif (TYPO3_MODE === 'BE' && Configuration::getValue('BEfailsafe')) {
				return static::STATUS_AUTHENTICATION_FAILURE_CONTINUE;
			} else {
				// Failed login attempt (wrong password) - write that to the log!
				static::getLogger()->warning('Password not accepted: ' . array(
					'username' => $this->login['uname'],
					'remote' => sprintf('%s (%s)', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']),
				));
				$status = static::STATUS_AUTHENTICATION_FAILURE_BREAK;
			}

			// Checking the domain (lockToDomain)
			if ($status && $user['lockToDomain'] && $user['lockToDomain'] != $this->authInfo['HTTP_HOST']) {

				// Lock domain didn't match, so error:
				static::getLogger()->error(sprintf('Locked domain "%s" did not match "%s"', $user['lockToDomain'], $this->authInfo['HTTP_HOST']), array(
					'username' => $user[$this->db_user['username_column']],
					'remote' => sprintf('%s (%s)', $this->authInfo['REMOTE_ADDR'], $this->authInfo['REMOTE_HOST']),
				));

				$status = static::STATUS_AUTHENTICATION_FAILURE_BREAK;
			}
		}

		return $status;
	}

	/**
	 * Properly initializes Extbase since the authentication service is called
	 * very early during the general TYPO3 bootstrap process, namely during
	 * $TSFE->initFEUser().
	 *
	 * @return void
	 */
	protected function initializeExtbaseFramework() {
		if (TYPO3_MODE !== 'FE') {
			return;
		}

		// Fix for "Fatal error: Call to a member function versionOL() on string"
		if (!is_object($GLOBALS['TSFE']->sys_page)) {
			$GLOBALS['TSFE']->sys_page = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\Page\\PageRepository');
		}

		// Fix for uncached mapping for frontend and backend user groups: warm up Extbase's data mapper
		$databaseConnection = $this->getDatabaseConnection();
		$table = 'cf_extbase_datamapfactory_datamap';
		$identifiers = $this->getExtbaseCacheIdentifiers();
		$quotedIdentifiers = array();
		foreach ($identifiers as $identifier) {
			$quotedIdentifiers[] = $databaseConnection->fullQuoteStr($identifier, $table);
		}
		$cacheEntries = $databaseConnection->exec_SELECTcountRows(
			'*',
			$table,
			'identifier IN (' . implode(',', $quotedIdentifiers) . ')' .
				' AND expires>' . $GLOBALS['EXEC_TIME']
		);
		if ((int)$cacheEntries !== 2) {
			$this->warmUpExtbaseDataMapper();
		}
	}

	/**
	 * Warms up the Extbase's data mapper (this is known not to be as complete as when
	 * the whole Extbase boostrap is run but this is sufficient here and we do not want
	 * to mix-up completely the normal TYPO3 bootstrap sequence).
	 *
	 * @return void
	 */
	protected function warmUpExtbaseDataMapper() {
		$backupTemplateService = $GLOBALS['TSFE']->tmpl;

		$setup = \Causal\IgLdapSsoAuth\Utility\TypoScriptUtility::loadTypoScriptFromFile('EXT:extbase/ext_typoscript_setup.txt');
		$GLOBALS['TSFE']->tmpl = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
		$GLOBALS['TSFE']->tmpl->setup = $setup;

		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');

		/** @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager */
		$configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManagerInterface');
		$configuration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

		// Do not forget to clean up our partial cache when exiting
		$this->cleanUpExtbaseCache = TRUE;

		// Restore previous template service
		$GLOBALS['TSFE']->tmpl = $backupTemplateService;
	}

	/**
	 * Cleans up Extbase's data mapper by removing partial cache entries.
	 *
	 * @return void
	 */
	protected function cleanUpExtbaseDataMapper() {
		if (!$this->cleanUpExtbaseCache) {
			return;
		}

		$databaseConnection = $this->getDatabaseConnection();
		$table = 'cf_extbase_datamapfactory_datamap';
		$identifiers = $this->getExtbaseCacheIdentifiers();
		$quotedIdentifiers = array();
		foreach ($identifiers as $identifier) {
			$quotedIdentifiers[] = $databaseConnection->fullQuoteStr($identifier, $table);
		}
		$databaseConnection->exec_DELETEquery($table, 'identifier IN (' . implode(',', $quotedIdentifiers) . ')');
	}

	/**
	 * Returns Extbase cache identifiers involved in the
	 * authentication process.
	 *
	 * @return array
	 */
	protected function getExtbaseCacheIdentifiers() {
		$classNames = array(
			'TYPO3\\CMS\\Extbase\\Domain\\Model\\FrontendUserGroup',
			'TYPO3\\CMS\\Extbase\\Domain\\Model\\BackendUserGroup',
		);
		$identifiers = array();
		foreach ($classNames as $className) {
			/** @see \TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapFactory::buildDataMap() */
			$identifiers[] = str_replace('\\', '%', $className);
		}
		return $identifiers;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Returns a logger.
	 *
	 * @return \TYPO3\CMS\Core\Log\Logger
	 */
	static protected function getLogger() {
		/** @var \TYPO3\CMS\Core\Log\Logger $logger */
		static $logger = NULL;
		if ($logger === NULL) {
			$logger = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Log\\LogManager')->getLogger(__CLASS__);
		}
		return $logger;
	}

}
