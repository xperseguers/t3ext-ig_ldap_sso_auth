<?php
namespace Causal\IgLdapSsoAuth\Domain\Repository;

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
use Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException;
use Causal\IgLdapSsoAuth\Library\Authentication;
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

        $newGroup = array();
        $fieldsConfiguration = static::getDatabaseConnection()->admin_get_fields($table);

        foreach ($fieldsConfiguration as $field => $configuration) {
            if ($configuration['Null'] === 'NO' && $configuration['Default'] === null) {
                $newGroup[$field] = '';
            } else {
                $newGroup[$field] = $configuration['Default'];
            }
        }

        return $newGroup;
    }

    /**
     * Searches BE/FE groups either by uid or by DN in a given storage folder (pid).
     *
     * @param string $table Either 'be_groups' or 'fe_groups'
     * @param int $uid
     * @param int|null $pid
     * @param string $dn
     * @return array|null
     * @throws InvalidUserGroupTableException
     */
    public static function fetch($table, $uid = 0, $pid = null, $dn = null)
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891809);
        }

        $databaseConnection = static::getDatabaseConnection();

        if ($uid) {
            $where = 'uid=' . intval($uid);
        } else {
            $where = 'tx_igldapssoauth_dn=' . $databaseConnection->fullQuoteStr($dn, $table)
                . ($pid ? ' AND pid=' . intval($pid) : '');
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
    public static function add($table, array $data = array())
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891833);
        }

        $databaseConnection = static::getDatabaseConnection();

        $databaseConnection->exec_INSERTquery(
            $table,
            $data,
            false
        );
        $uid = $databaseConnection->sql_insert_id();

        $newRow = $databaseConnection->exec_SELECTgetSingleRow(
            '*',
            $table,
            'uid=' . intval($uid)
        );

        NotificationUtility::dispatch(
            __CLASS__,
            'groupAdded',
            array(
                'table' => $table,
                'group' => $newRow,
            )
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
    public static function update($table, array $data = array())
    {
        if (!GeneralUtility::inList('be_groups,fe_groups', $table)) {
            throw new InvalidUserGroupTableException('Invalid table "' . $table . '"', 1404891867);
        }

        $databaseConnection = static::getDatabaseConnection();

        $databaseConnection->exec_UPDATEquery(
            $table,
            'uid=' . intval($data['uid']),
            $data,
            false
        );
        $success = $databaseConnection->sql_errno() == 0;

        if ($success) {
            NotificationUtility::dispatch(
                __CLASS__,
                'groupUpdated',
                array(
                    'table' => $table,
                    'group' => $data,
                )
            );
        }

        return $success;
    }

    /**
     * Returns the title for a given user.
     *
     * @param array $ldap_user
     * @param array $mapping
     * @return null|string
     * @deprecated since 3.0, will be removed in 3.2
     */
    public static function get_title($ldap_user = array(), $mapping = array())
    {
        if (!$mapping) {
            return null;
        }

        if (isset($mapping['title']) && preg_match('`<([^$]*)>`', $mapping['title'], $attribute)) {
            if ($attribute[1] === 'dn') {
                return $ldap_user[$attribute[1]];
            }

            return Authentication::replaceLdapMarkers($mapping['title'], $ldap_user);
        }

        return null;
    }

    /**
     * Returns the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected static function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

}
