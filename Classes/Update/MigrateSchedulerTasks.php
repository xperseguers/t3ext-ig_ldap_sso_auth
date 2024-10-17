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

use Doctrine\DBAL\Exception as DBALException;
use RuntimeException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

class MigrateSchedulerTasks implements UpgradeWizardInterface
{
    protected string $tableName = 'tx_scheduler_task';

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
        return 'Beware: this script will split scheduler with configuration "all" into a scheduler task per configuration';
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
            $oldTask = $this->castToClass(unserialize($oldSchedulerTask['serialized_task_object']));
            $newTask = $this->getNewTask($oldTask);

            // "All configurations"
            if ($oldTask->configuration === 0) {
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
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName)
                ->update(
                    $this->tableName,
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

    /**
     * @see: https://stackoverflow.com/a/965704/2377961
     *
     * @param object $object The object that should be casted
     * @param String $class The name of the class
     * @return mixed   The new created object
     */
    function castToClass(object $object, string $class = 'stdClass')
    {
        $ser_data = serialize($object);
        # preg_match_all('/O:\d+:"([^"]++)"/', $ser_data, $matches); // find all classes

        /*
         * make private and protected properties public
         *   privates  is stored as "s:14:\0class_name\0property_name")
         *   protected is stored as "s:14:\0*\0property_name")
         */
        $ser_data = preg_replace_callback('/s:\d+:"\0([^\0]+)\0([^"]+)"/',
            function ($prop_match) {
                list($old, $classname, $propname) = $prop_match;
                return 's:' . strlen($propname) . ':"' . $propname . '"';
            }, $ser_data);

        // replace object-names
        $ser_data = preg_replace('/O:\d+:"[^"]++"/', 'O:' . strlen($class) . ':"' . $class . '"', $ser_data);
        return unserialize($ser_data);
    }

    protected function getNewTask(\stdClass $oldTask): ExecuteSchedulableCommandTask
    {
        /** @var ExecuteSchedulableCommandTask $newTask */
        $newTask = GeneralUtility::makeInstance(ExecuteSchedulableCommandTask::class);

        $commonProperties = [
            'description',
            'taskGroup',
        ];

        foreach ($commonProperties as $property) {
            $newTask->{'set' . ucfirst($property)}($oldTask->{$property});
        }

        /** @var Execution $newExecution */
        $newExecution = GeneralUtility::makeInstance(Execution::class);

        $executionProperties = [
            'start',
            'end',
            'interval',
            'multiple',
            'cronCmd',
            'isNewSingleExecution',
        ];

        foreach ($executionProperties as $property) {
            $newExecution->{'set' . ucfirst($property)}($oldTask->execution->{$property});
        }

        $newTask->setExecution($newExecution);

        $newTask->setCommandIdentifier('ldap:importusers');
        $newTask->setOptions([
            'mode' => true,
            'context' => true,
            'missing-users' => true,
            'restored-users' => true,
        ]);
        $newTask->setOptionValues([
            'mode' => $oldTask->mode,
            'context' => $oldTask->context === 'both' ? 'all' : strtolower($oldTask->context),
            'missing-users' => $oldTask->missingUsersHandling === 'nothing' ? 'ignore' : strtolower($oldTask->missingUsersHandling),
            'restored-users' => $oldTask->restoredUsersHandling === 'nothing' ? 'ignore' : strtolower($oldTask->restoredUsersHandling),
        ]);
        $newTask->setArguments([
            'configuration' => $oldTask->configuration,
        ]);

        return $newTask;
    }

    protected function insertNewSchedulerTask(ExecuteSchedulableCommandTask $newTask, array $oldSchedulerTask): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName)
            ->insert(
                $this->tableName,
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
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->tableName);
        try {
            return $queryBuilder
                ->select('*')
                ->from($this->tableName)
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
