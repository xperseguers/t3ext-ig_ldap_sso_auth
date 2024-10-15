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

namespace Causal\IgLdapSsoAuth\Domain\Repository;

use Causal\IgLdapSsoAuth\Event\GroupAddedEvent;
use Causal\IgLdapSsoAuth\Event\GroupUpdatedEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException;
use Causal\IgLdapSsoAuth\Utility\NotificationUtility;

/**
 * Class Typo3GroupRepository for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Typo3GroupRepository
{
    /**
     * Creates a fresh BE/FE group record.
     *
     * @param string $table Either 'be_groups' or 'fe_groups'
     * @return array
     * @throws InvalidUserGroupTableException
     */
    public static function create(string $table): array
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404892331);
        }

        $newGroup = [];
        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12) {
            $fieldsConfiguration = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->createSchemaManager()
                ->listTableColumns($table);
        } else {
            $fieldsConfiguration = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($table)
                ->getSchemaManager()
                ->listTableColumns($table);
        }

        foreach ($fieldsConfiguration as $configuration) {
            $field = $configuration->getName();
            $newGroup[$field] = $configuration->getDefault();
        }

        // uid is a primary key, it should not be specified at all
        unset($newGroup['uid']);

        return $newGroup;
    }

    /**
     * Searches BE/FE groups either by uid or by DN in a given storage folder (pid).
     *
     * @param string $table Either 'be_groups' or 'fe_groups'
     * @param int $uid
     * @param int|null $pid
     * @param string $dn
     * @param string $groupName
     * @return null
     */
    public static function fetch(
        string $table,
        int $uid = 0,
        ?int $pid = null,
        ?string $dn = null,
        ?string $groupName = null
    ): array
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891809);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();

        if (!empty($uid)) {
            $where = $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT));
        } else {
            $where = $queryBuilder->expr()->eq('tx_igldapssoauth_dn', $queryBuilder->createNamedParameter($dn, \PDO::PARAM_STR));
            if (!empty($groupName)) {
                if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12) {
                    $where = $queryBuilder->expr()->or(
                        $where,
                        $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($groupName, \PDO::PARAM_STR))
                    );
                } else {
                    $where = $queryBuilder->expr()->orX(
                        $where,
                        $queryBuilder->expr()->eq('title', $queryBuilder->createNamedParameter($groupName, \PDO::PARAM_STR))
                    );
                }
            }
            if (!empty($pid)) {
                if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12) {
                    $where = $queryBuilder->expr()->and(
                        $where,
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                    );
                } else {
                    $where = $queryBuilder->expr()->andX(
                        $where,
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT))
                    );
                }
            }
        }

        $groups = $queryBuilder
            ->select('*')
            ->from($table)
            ->where($where)
            ->executeQuery()
            ->fetchAllAssociative();

        // Return TYPO3 groups
        return $groups;
    }

    /**
     * Adds a new BE/FE group to the database and returns the new record
     * with all columns.
     *
     * @param string $table Either 'be_groups' or 'fe_groups'
     * @param array $data
     * @return array The new record
     * @throws InvalidUserGroupTableException
     */
    public static function add(string $table, array $data = []): array
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891833);
        }

        $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);
        $tableConnection->insert(
            $table,
            $data
        );
        $uid = $tableConnection->lastInsertId($table);

        $newRow = $tableConnection
            ->select(
                ['*'],
            $table,
                [
                    'uid' => (int)$uid,
                ]
            )
            ->fetchAssociative();

        NotificationUtility::dispatch(new GroupAddedEvent($table, $newRow));

        return $newRow;
    }

    /**
     * Updates a BE/FE group in the database and returns a success flag.
     *
     * @param string $table Either 'be_groups' or 'fe_groups'
     * @param array $data
     * @return bool true on success, otherwise false
     * @throws InvalidUserGroupTableException
     */
    public static function update(string $table, array $data = []): bool
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891867);
        }

        $affectedRows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->update(
                $table,
                $data,
                [
                    'uid' => (int)$data['uid'],
                ]
            );
        $success = $affectedRows === 1;

        if ($success) {
            NotificationUtility::dispatch(new GroupUpdatedEvent($table, $data));
        }

        return $success;
    }
}
