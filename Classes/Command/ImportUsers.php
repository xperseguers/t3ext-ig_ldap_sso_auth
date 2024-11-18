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

namespace Causal\IgLdapSsoAuth\Command;

use Causal\IgLdapSsoAuth\Domain\Model\Configuration;
use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;
use Causal\IgLdapSsoAuth\Library\Authentication;
use Causal\IgLdapSsoAuth\Library\Ldap;
use Causal\IgLdapSsoAuth\Utility\UserImportUtility;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ImportUsers extends Command
{
    protected SymfonyStyle $io;

    protected array $options;

    protected Configuration $configuration;

    /**
     * @param ConfigurationRepository $configurationRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly ConfigurationRepository $configurationRepository,
        private readonly LoggerInterface $logger
    )
    {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->addArgument(
                'configuration',
                InputArgument::REQUIRED,
                'UID of the LDAP configuration to use'
            )
            ->addOption(
                'mode',
                'm',
                InputArgument::OPTIONAL,
                'Mode to use: "import" to import new users and synchronize existing users, "sync" for synchronizing existing users only',
                'import',
                ['import', 'sync']
            )
            ->addOption(
                'context',
                'ctx',
                InputArgument::OPTIONAL,
                'Context: "fe" for Frontend, "be" for Backend, or "all" for all available contexts',
                'all',
                ['fe', 'be', 'all']
            )
            ->addOption(
                'missing-users',
                'mu',
                InputArgument::OPTIONAL,
                'Action to take for missing users: "ignore", "disable" or "delete"',
                'disable',
                ['ignore', 'disable', 'delete']
            )
            ->addOption(
                'restored-users',
                'ru',
                InputArgument::OPTIONAL,
                'Action to take for restored users: "ignore", "enable", "undelete", or "both"',
                'ignore',
                ['ignore', 'enable', 'undelete', 'both']
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $this->io->title($this->getDescription());

        $configuration = $this->configurationRepository->findByUid((int)$input->getArgument('configuration'));
        if ($configuration === null) {
            $this->io->error('Unknown configuration: ' . $input->getArgument('configuration'));
            return Command::FAILURE;
        }

        $this->options = $input->getOptions();
        $this->configuration = $configuration;

        $this->io->info('Importing users for configuration: ' . $configuration->getName());

        return $this->doImport();
    }

    /**
     * @return int
     */
    protected function doImport(): int
    {
        $executionContexts = match(strtolower($this->options['context'])) {
            'fe' => ['fe'],
            'be' => ['be'],
            'all' => ['fe', 'be'],
            default => ['fe', 'be'],
        };

        $failures = 0;
        foreach ($executionContexts as $context) {
            /** @var UserImportUtility $importUtility */
            $importUtility = GeneralUtility::makeInstance(
                UserImportUtility::class,
                $this->configuration,
                $context
            );

            $config = $importUtility->getConfiguration();
            if (empty($config['users']['filter'])) {
                // Current context is not configured for this LDAP configuration record
                $this->io->warning(sprintf(
                    'Configuration record %s is not configured for context "%s"',
                    $this->configuration->getUid(),
                    strtoupper($context))
                );
                unset($importUtility);
                continue;
            }

            $this->io->info('Importing users for context: ' . strtoupper($context));

            // Start by connecting to the designated LDAP/AD server
            $ldapInstance = Ldap::getInstance();
            $success = $ldapInstance->connect(\Causal\IgLdapSsoAuth\Library\Configuration::getLdapConfiguration());
            if (!$success) {
                $failures++;
                $this->io->error('Could not connect to LDAP server');
                unset($importUtility);
                continue;
            }

            $ldapUsers = $importUtility->fetchLdapUsers(false, $ldapInstance);

            // Consider that fetching no users from LDAP is an error
            if (empty($ldapUsers)) {
                $failures++;
                $this->io->error('No users found in LDAP server');
                unset($importUtility);
                continue;
            }

            // Start a database transaction with all our changes
            $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($context . '_users');
            $tableConnection->beginTransaction();

            try {
                $this->importUsers($ldapInstance, $importUtility, $ldapUsers);
                // Everything went fine, commit the changes
                $tableConnection->commit();
            } catch (ImportUsersException $e) {
                // Roll back the whole transaction and report error
                $tableConnection->rollBack();
                $failures++;
                $this->logger->error($e->getMessage());
                $this->io->error($e->getMessage());
            }

            // Clean up
            unset($importUtility);
            $ldapInstance->disconnectAll();
        }

        if ($failures > 0) {
            $message = 'Some or all imports failed. Synchronisation was incomplete. Check your settings or your network connection.';
            $this->logger->error($message);
            $this->io->error($message);
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    /**
     * @param Ldap $ldapInstance
     * @param UserImportUtility $importUtility
     * @param array $ldapUsers
     * @throws \Causal\IgLdapSsoAuth\Exception\ImportUsersException
     */
    protected function importUsers(
        Ldap $ldapInstance,
        UserImportUtility $importUtility,
        array $ldapUsers
    ): void
    {
        $config = $importUtility->getConfiguration();

        // We do not know the number of users to import as there is a potential
        // pagination in the LDAP query, so we cannot use the progress bar with
        // a known total number of steps
        $this->io->progressStart();
        $usersImported = 0;
        $start = microtime(true);

        // Disable or delete users, according to settings
        $disabledOrDeletedUserUids = [];
        if ($this->options['missing-users'] === 'disable') {
            $this->logger->debug(sprintf(
                'Disabling users (%s) for configuration record %s',
                strtoupper($importUtility->getContext()),
                $this->configuration->getUid()
            ));
            $disabledOrDeletedUserUids = $importUtility->disableUsers();
        } elseif ($this->options['missing-users'] === 'delete') {
            $this->logger->debug(sprintf(
                'Deleting users (%s) for configuration record %s',
                strtoupper($importUtility->getContext()),
                $this->configuration->getUid()
            ));
            $disabledOrDeletedUserUids = $importUtility->deleteUsers();
        }

        // Proceed with import (handle partial result sets until every LDAP record has been returned)
        do {
            $typo3Users = $importUtility->fetchTypo3Users($ldapUsers);

            // Loop on all users and import them
            foreach ($ldapUsers as $index => $ldapUser) {
                if ($this->options['mode'] === 'sync' && empty($typo3Users[$index]['uid'] ?? 0)) {
                    // New LDAP user => skip it since only existing TYPO3 users should get synchronized
                    continue;
                }

                // Merge LDAP and TYPO3 information
                $disableField = $GLOBALS['TCA'][$importUtility->getUserTable()]['ctrl']['enablecolumns']['disabled'] ?? '';
                $user = Authentication::merge(
                    $ldapUser,
                    $typo3Users[$index],
                    $config['users']['mapping'],
                    false,
                    $disableField
                );

                // Import the user using information from LDAP
                $restoreBehaviour = $this->options['restored-users'];
                if (in_array($user['uid'] ?? 0, $disabledOrDeletedUserUids, true)) {
                    // We disabled this user ourselves
                    if ($this->options['missing-users'] === 'disable') {
                        if ($restoreBehaviour === 'nothing') {
                            $restoreBehaviour = 'enable';
                        } elseif ($restoreBehaviour === 'undelete') {
                            $restoreBehaviour = 'both';
                        }
                    } elseif ($this->options['missing-users'] === 'delete') {
                        // We deleted this user ourselves
                        if ($restoreBehaviour === 'nothing') {
                            $restoreBehaviour = 'undelete';
                        } elseif ($restoreBehaviour === 'enable') {
                            $restoreBehaviour = 'both';
                        }
                    }
                }

                $importUtility->import($user, $ldapUser, $restoreBehaviour, $disableField);
                $this->io->progressAdvance();
                $usersImported++;
            }

            $this->logger->debug(sprintf(
                'Configuration record %s: processed %s LDAP users (%s)',
                $this->configuration->getUid(),
                count($ldapUsers),
                strtoupper($importUtility->getContext())
            ));

            // Free memory before going on
            $typo3Users = null;
            $ldapUsers = null;
            $ldapUsers = $importUtility->hasMoreLdapUsers($ldapInstance)
                ? $importUtility->fetchLdapUsers(true, $ldapInstance)
                : [];
        } while (!empty($ldapUsers));

        $end = microtime(true);
        $this->io->progressFinish();
        $this->io->success(sprintf(
            'Imported %d users for configuration record %d in %s seconds',
            $usersImported,
            $this->configuration->getUid(),
            number_format($end - $start, 2)
        ));
    }
}
