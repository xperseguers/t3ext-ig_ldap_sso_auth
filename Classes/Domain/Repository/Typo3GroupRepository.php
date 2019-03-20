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
    public static function create($table)
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404892331);
        }

        $newGroup = [];
        $fieldsConfiguration = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->getSchemaManager()
            ->listTableColumns($table);

        foreach ($fieldsConfiguration as $field => $configuration) {
            if ($configuration->getNotnull() === true && empty($configuration->getDefault())) {
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

        $databaseConnection = static::getDatabaseConnection();

        if ($uid) {
            $where = 'uid=' . (int)$uid;
        } else {
            $where = '(' . 'tx_igldapssoauth_dn=' . $databaseConnection->fullQuoteStr($dn, $table);
            if (!empty($groupName)) {
                $where .= ' OR title=' . $databaseConnection->fullQuoteStr($groupName, $table);
            }
            $where .= ')' . ($pid ? ' AND pid=' . (int)$pid : '');
        }

        // Return TYPO3 group
        return $databaseConnection->exec_SELECTgetRows(
            '*',
            $table,
            $where
        );
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

        $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);
        $tableConnection->insert(
            $table,
            $data
        );
        $uid = $tableConnection->lastInsertId();

        $newRow = $tableConnection
            ->select(
                ['*'],
            $table,
                [
                    'uid' => (int)$uid,
                ]
            )
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

        $tableConnection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table);
        $tableConnection->update(
            $table,
            $data,
            [
                'uid' => (int)$data['uid'],
            ]
        );
        $success = $tableConnection->errorCode() === 0;

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

}
