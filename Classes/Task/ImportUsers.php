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

namespace Causal\IgLdapSsoAuth\Task;

use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Exception\ImportUsersException;
use Causal\IgLdapSsoAuth\Library\Authentication;
use Causal\IgLdapSsoAuth\Library\Configuration;
use Causal\IgLdapSsoAuth\Library\Ldap;

/**
 * Synchronizes users for selected context and configuration.
 *
 * Context may be FE, BE or both. A single configuration may be chosen or all of them.
 *
 * @author     Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ImportUsers extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * Synchronization context (may be FE, BE or both).
     *
     * @var string
     */
    protected $context = 'both';

    /**
     * Selected LDAP configuration (may be 0 (for all) or a configuration uid).
     *
     * @var int
     */
    protected $configuration = 0;

    /**
     * Defines how missing users (i.e. TYPO3 users which are no longer found on the LDAP server)
     * should be handled. Can be "disable", "delete" or "nothing".
     *
     * @var string
     */
    protected $missingUsersHandling = 'nothing';

    /**
     * Defines how restored users (i.e. TYPO3 users which were deleted or disabled on the TYPO3 side,
     * but still exist on the LDAP server) should be handled. Can be "enable", "undelete", "both" or "nothing".
     *
     * @var string
     */
    protected $restoredUsersHandling = 'nothing';

    /**
     * Defines whether missing users in TYPO3 should be imported ("imported") or if only already existing
     * users in TYPO3 should be synchronized ("sync") with LDAP.
     *
     * @var string
     */
    protected $mode = 'import';

    /**
     * Performs the synchronization of LDAP users according to selected parameters.
     *
     * @return bool Returns true on successful execution, false on error
     * @throws ImportUsersException
     */
    public function execute()
    {
        // Assemble a list of configuration and contexts for import
        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        if (empty($this->configuration)) {
            $ldapConfigurations = $configurationRepository->findAll();
        } else {
            $configuration = $configurationRepository->findByUid($this->configuration);
            $ldapConfigurations = [];
            if ($configuration !== null) {
                $ldapConfigurations[] = $configuration;
            }
        }
        if ($this->context === 'both') {
            $executionContexts = ['fe', 'be'];
        } else {
            $executionContexts = [$this->context];
        }

        $mode = $this->getMode();

        // Start a database transaction with all our changes
        $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users');    // arbitrary table
        $tableConnection->beginTransaction();

        // Loop on each configuration and context and import the related users
        $failures = 0;
        foreach ($ldapConfigurations as $configuration) {
            foreach ($executionContexts as $aContext) {
                /** @var \Causal\IgLdapSsoAuth\Utility\UserImportUtility $importUtility */
                $importUtility = GeneralUtility::makeInstance(
                    \Causal\IgLdapSsoAuth\Utility\UserImportUtility::class,
                    $configuration,
                    $aContext
                );

                $config = $importUtility->getConfiguration();
                if (empty($config['users']['filter'])) {
                    // Current context is not configured for this LDAP configuration record
                    $this->getLogger()->debug(sprintf('Configuration record %s is not configured for context "%s"', $configuration->getUid(), $aContext));
                    unset($importUtility);
                    continue;
                }

                // Start by connecting to the designated LDAP/AD server
                $ldapInstance = Ldap::getInstance();
                $success = $ldapInstance->connect(Configuration::getLdapConfiguration());
                // Proceed with import if successful
                if (!$success) {
                    $failures++;
                    unset($importUtility);
                    continue;
                }

                $ldapUsers = $importUtility->fetchLdapUsers(false, $ldapInstance);

                // Consider that fetching no users from LDAP is an error
                if (empty($ldapUsers)) {
                    $this->getLogger()->error(sprintf(
                        'No users (%s) found for configuration record %s', $aContext, $configuration->getUid()
                    ));
                    $failures++;
                } else {
                    // Disable or delete users, according to settings
                    $disabledOrDeletedUserUids = [];
                    if ($this->missingUsersHandling === 'disable') {
                        $this->getLogger()->debug(sprintf(
                            'Disabling users (%s) for configuration record %s', $aContext, $configuration->getUid()
                        ));
                        $disabledOrDeletedUserUids = $importUtility->disableUsers();
                    } elseif ($this->missingUsersHandling === 'delete') {
                        $this->getLogger()->debug(
                            sprintf('Deleting users (%s) for configuration record %s', $aContext, $configuration->getUid()
                        ));
                        $disabledOrDeletedUserUids = $importUtility->deleteUsers();
                    }

                    // Proceed with import (handle partial result sets until every LDAP record has been returned)
                    do {
                        $typo3Users = $importUtility->fetchTypo3Users($ldapUsers);

                        // Loop on all users and import them
                        foreach ($ldapUsers as $index => $aUser) {
                            if ($mode === 'sync' && empty($typo3Users[$index]['uid'] ?? 0)) {
                                // New LDAP user => skip it since only existing TYPO3 users should get synchronized
                                continue;
                            }

                            // Merge LDAP and TYPO3 information
                            $user = Authentication::merge($aUser, $typo3Users[$index], $config['users']['mapping']);

                            // Import the user using information from LDAP
                            $restoreBehaviour = $this->restoredUsersHandling;
                            if (in_array($user['uid'] ?? 0, $disabledOrDeletedUserUids, true)) {
                                // We disabled this user ourselves
                                if ($this->missingUsersHandling === 'disable') {
                                    if ($restoreBehaviour === 'nothing') {
                                        $restoreBehaviour = 'enable';
                                    } elseif ($restoreBehaviour === 'undelete') {
                                        $restoreBehaviour = 'both';
                                    }
                                } elseif ($this->missingUsersHandling === 'delete') {
                                    // We deleted this user ourselves
                                    if ($restoreBehaviour === 'nothing') {
                                        $restoreBehaviour = 'undelete';
                                    } elseif ($restoreBehaviour === 'enable') {
                                        $restoreBehaviour = 'both';
                                    }
                                }
                            }

                            $importUtility->import($user, $aUser, $restoreBehaviour);
                        }

                        $this->getLogger()->info(sprintf(
                            'Configuration record %s: processed %s LDAP users (%s)', $configuration->getUid(), count($ldapUsers), $aContext
                        ));

                        // Free memory before going on
                        $typo3Users = null;
                        $ldapUsers = null;
                        $ldapUsers = $importUtility->hasMoreLdapUsers($ldapInstance)
                            ? $importUtility->fetchLdapUsers(true, $ldapInstance)
                            : [];
                    } while (!empty($ldapUsers));
                }

                // Clean up
                unset($importUtility);
                $ldapInstance->disconnect();
            }
        }

        // If some failures were registered, rollback the whole transaction and report error
        if ($failures > 0) {
            $tableConnection->rollBack();
            $message = 'Some or all imports failed. Synchronisation was aborted. Check your settings or your network connection';
            $this->getLogger()->error($message);
            throw new ImportUsersException($message, 1410774015);

        } else {
            // Everything went fine, commit the changes
            $tableConnection->commit();
        }
        return true;
    }

    /**
     * This method returns the context and configuration as additional information.
     *
     * @return string Information to display
     */
    public function getAdditionalInformation(): string
    {
        $languageService = $this->getLanguageServiceForLdap();

        if (empty($this->configuration)) {
            $configurationName = $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.configuration.all');
        } else {
            $configurationName = $this->getConfigurationName();
        }
        $info = sprintf(
            $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.additionalinformation'),
            $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.mode.' . $this->getMode() . '.short'),
            $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.context.' . strtolower($this->getContext())),
            $configurationName
        );
        return $info;
    }

    /**
     * Sets the mode.
     *
     * @param string $mode
     * @return $this
     */
    public function setMode(string $mode): self
    {
        $this->mode = $mode;
        return $this;
    }

    /**
     * Returns the mode.
     *
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * Sets the context parameter.
     *
     * @param string $context
     * @return $this
     */
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Returns the context parameter.
     *
     * @return string
     */
    public function getContext(): string
    {
        return $this->context;
    }

    /**
     * Sets the configuration.
     *
     * @param int $configuration
     * @return $this
     */
    public function setConfiguration(int $configuration): self
    {
        $this->configuration = $configuration;

        return $this;
    }

    /**
     * Returns the current configuration.
     *
     * @return int
     */
    public function getConfiguration(): int
    {
        return $this->configuration;
    }

    /**
     * Sets the missing users handling flag.
     *
     * NOTE: behavior defaults to "nothing".
     *
     * @param string $missingUsersHandling Can be "disable", "delete" or "nothing"
     * @return $this
     */
    public function setMissingUsersHandling(string $missingUsersHandling): self
    {
        $this->missingUsersHandling = $missingUsersHandling;
        return $this;
    }

    /**
     * Returns the missing users handling flag.
     *
     * @return string
     */
    public function getMissingUsersHandling(): string
    {
        return $this->missingUsersHandling;
    }

    /**
     * Sets the restored users handling flag.
     *
     * NOTE: behavior defaults to "nothing".
     *
     * @param string $restoredUsersHandling Can be "enable", "undelete", "both" or "nothing"
     * @return $this
     */
    public function setRestoredUsersHandling(string $restoredUsersHandling): self
    {
        $this->restoredUsersHandling = $restoredUsersHandling;
        return $this;
    }

    /**
     * Returns the restored users handling flag.
     *
     * @return string
     */
    public function getRestoredUsersHandling(): string
    {
        return $this->restoredUsersHandling;
    }

    /**
     * Returns the name of the current configuration.
     *
     * @return string
     */
    public function getConfigurationName(): string
    {
        /** @var ConfigurationRepository $configurationRepository */
        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $ldapConfiguration = $configurationRepository->findByUid($this->configuration);
        if ($ldapConfiguration === null) {
            return '';
        } else {
            return $ldapConfiguration->getName();
        }
    }

    /**
     * Returns the LanguageService.
     *
     * @return LanguageService
     */
    protected function getLanguageServiceForLdap(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns a logger.
     *
     * @return LoggerInterface
     */
    protected function getLogger(): LoggerInterface
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }
        return $logger;
    }
}
