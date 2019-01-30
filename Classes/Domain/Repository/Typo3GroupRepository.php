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

namespace Causal\IgLdapSsoAuth\Domain\Repository;

use Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException;
use Causal\IgLdapSsoAuth\Utility\NotificationUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    public static function create($table)
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404892331);
        }

        $newGroup = [];
        $fieldsConfiguration = static::getDatabaseConnection()
            ->getSchemaManager()
            ->listTableColumns($table);

        foreach ($fieldsConfiguration as $field => $configuration) {
            if ($configuration->getNotnull() && $configuration->getDefault() === null) {
                $newGroup[$field] = '';
            } else {
                $newGroup[$field] = $configuration->getDefault();
            }
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
     * @return array|null
     * @throws InvalidUserGroupTableException
     */
    public static function fetch($table, $uid = 0, $pid = null, $dn = null, $groupName = null)
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891809);
        }

        $queryBuilder = static::getQueryBuilder();

        if ($uid) {
            $where[] = $queryBuilder->expr()->eq('uid', (int)$uid);
        } else {
            $dnCheck = $queryBuilder->expr()->eq(
                'tx_igldapssoauth_dn',
                $queryBuilder->createNamedParameter($dn)
            );
            if (!empty($groupName)) {
                $where[] = $queryBuilder->expr()->orX(
                    $dnCheck,
                    $queryBuilder->expr()->eq(
                        'title',
                        $queryBuilder->createNamedParameter($groupName)
                    )
                );
            } else {
                $where[] = $dnCheck;
            }
            if ($pid) {
                $where[] = $queryBuilder->expr()->eq('pid', (int)$pid);
            }
        }

        // Return TYPO3 group
        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(...$where)
            ->execute()
            ->fetchAll();
        return $rows;
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
    public static function add($table, array $data = [])
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891833);
        }

        $databaseConnection = static::getDatabaseConnection();
        $queryBuilder = static::getQueryBuilder();

        $databaseConnection
            ->insert($table, $data);

        $uid = (int)$databaseConnection->lastInsertId($table);
        $newRow = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    (int)$uid
                )
            )
            ->execute()
            ->fetch();

        NotificationUtility::dispatch(
            __CLASS__,
            'groupAdded',
            [
                'table' => $table,
                'group' => $newRow,
            ]
        );

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
    public static function update($table, array $data = [])
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891867);
        }

        $databaseConnection = static::getDatabaseConnection();

        $databaseConnection->update(
            $table,
            //'uid=' . (int)$data['uid'],
            $data,
            ['uid' => (int)$data['uid']]
        );
        $success = $databaseConnection->errorCode() == 0;

        if ($success) {
            NotificationUtility::dispatch(
                __CLASS__,
                'groupUpdated',
                [
                    'table' => $table,
                    'group' => $data,
                ]
            );
        }

        return $success;
    }

    /**
     * Returns the query builder for the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected static function getQueryBuilder()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_igldapssoauth_config');
        return $queryBuilder;
    }

    /**
     * Returns the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\Connection
     */
    protected static function getDatabaseConnection()
    {
        $databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_igldapssoauth_config');
        return $databaseConnection;
    }

}
