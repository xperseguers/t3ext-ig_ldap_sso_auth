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
    /**
     * @var SymfonyStyle
     */
    protected $io;

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

        $options = $input->getOptions();
        return $this->doImport($configuration, $options);
    }

    /**
     * @param Configuration $configuration
     * @param array $options
     * @return int
     */
    protected function doImport(Configuration $configuration, array $options): int
    {
        $executionContexts = match(strtolower($options['context'])) {
            'fe' => ['fe'],
            'be' => ['be'],
            'all' => ['fe', 'be'],
            default => ['fe', 'be'],
        };

        // Start a database transaction with all our changes
        $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('be_users');    // arbitrary table
        $tableConnection->beginTransaction();

        $failures = 0;
        foreach ($executionContexts as $context) {
            /** @var UserImportUtility $importUtility */
            $importUtility = GeneralUtility::makeInstance(
                UserImportUtility::class,
                $configuration,
                $context
            );

            $config = $importUtility->getConfiguration();
            if (empty($config['users']['filter'])) {
                // Current context is not configured for this LDAP configuration record
                $this->io->warning(sprintf('Configuration record %s is not configured for context "%s"', $configuration->getUid(), $context));
                unset($importUtility);
                continue;
            }

            $this->io->info('Importing users for context: ' . strtoupper($context));
            // TODO
        }

        // If some failures were registered, rollback the whole transaction and report error
        if ($failures > 0) {
            $tableConnection->rollBack();
            $message = 'Some or all imports failed. Synchronisation was aborted. Check your settings or your network connection';
            $this->logger->error($message);
            $this->io->error($message);
            return Command::FAILURE;

        }

        // Everything went fine, commit the changes
        $tableConnection->commit();

        return Command::SUCCESS;
    }
}
