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

namespace Causal\IgLdapSsoAuth\Update;

use Causal\IgLdapSsoAuth\Task\ImportUsers;
use Doctrine\DBAL\Exception as DBALException;
use RuntimeException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class MigrateSchedulerTasks implements UpgradeWizardInterface
{
    protected string $tablename = 'tx_scheduler_task';

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return self::class;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return 'ig_ldap_sso_auth: Migrate scheduler tasks into Symfony commands';
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return 'Beware : this script will split scheduler with configuration "all" into a scheduler task per configuration';
    }

    /**
     * @return bool
     */
    public function updateNecessary(): bool
    {
        return !empty($this->getOldSchedulerTasks());
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * @return bool
     */
    public function executeUpdate(): bool
    {
        $oldSchedulerTasks = $this->getOldSchedulerTasks();
        foreach ($oldSchedulerTasks as $oldSchedulerTask) {
            /** @var \Causal\IgLdapSsoAuth\Task\ImportUsers $oldTask */
            $oldTask = unserialize($oldSchedulerTask['serialized_task_object']);
            $newTask = $this->getNewTask($oldTask);

            // "All configurations"
            if ($oldTask->getConfiguration() === 0) {
                $configurations = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('tx_igldapssoauth_config')
                    ->select(['uid'], 'tx_igldapssoauth_config')
                    ->fetchAllAssociative();
                foreach ($configurations as $configuration) {
                    $newTask->setArguments([
                        'configuration' => $configuration['uid'],
                    ]);

                    $this->insertNewSchedulerTask($newTask, $oldSchedulerTask);
                }
            } else {
                $this->insertNewSchedulerTask($newTask, $oldSchedulerTask);
            }

            // Mark old scheduler task as deleted
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tablename)
                ->update(
                    $this->tablename,
                    [
                        'deleted' => 1,
                    ],
                    [
                        'uid' => $oldSchedulerTask['uid'],
                    ]
                );
        }
        return true;
    }

    protected function getNewTask(ImportUsers $oldTask): ExecuteSchedulableCommandTask
    {
        /** @var ExecuteSchedulableCommandTask $newTask */
        $newTask = GeneralUtility::makeInstance(ExecuteSchedulableCommandTask::class);

        $commonProperties = [
            'description',
            'execution',
            'taskGroup',
        ];

        foreach ($commonProperties as $property) {
            $newTask->{'set' . ucfirst($property)}($oldTask->{'get' . ucfirst($property)}());
        }

        $newTask->setCommandIdentifier('ldap:importusers');
        $newTask->setOptions([
            'mode' => true,
            'context' => true,
            'missing-users' => true,
            'restored-users' => true,
        ]);
        $newTask->setOptionValues([
            'mode' => $oldTask->getMode(),
            'context' => $oldTask->getContext() === 'all' ? 'both': strtolower($oldTask->getContext()),
            'missing-users' => $oldTask->getMissingUsersHandling(),
            'restored-users' => $oldTask->getRestoredUsersHandling(),
        ]);
        $newTask->setArguments([
            'configuration' => $oldTask->getConfiguration(),
        ]);

        return $newTask;
    }

    protected function insertNewSchedulerTask(ExecuteSchedulableCommandTask $newTask, array $oldSchedulerTask): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tablename)
            ->insert(
                $this->tablename,
                [
                    'crdate' => $GLOBALS['EXEC_TIME'],
                    'description' => $oldSchedulerTask['description'],
                    'disable' => $oldSchedulerTask['disable'],
                    'task_group' => $oldSchedulerTask['task_group'],
                    'serialized_task_object' => serialize($newTask),
                ]
            );
    }

    /**
     * @return array
     */
    protected function getOldSchedulerTasks(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tablename);
        try {
            return $queryBuilder
                ->select('*')
                ->from($this->tablename)
                ->where(
                    $queryBuilder->expr()->like('serialized_task_object', $queryBuilder->createNamedParameter('%Causal\\\IgLdapSsoAuth\\\Task\\\ImportUsers%'))
                )
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (DBALException $e) {
            throw new RuntimeException(
                'Database query failed. Error was: ' . $e->getPrevious()->getMessage(),
                1511950673
            );
        }
    }
}
