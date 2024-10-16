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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportUsers extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

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

        return Command::SUCCESS;
    }
}
