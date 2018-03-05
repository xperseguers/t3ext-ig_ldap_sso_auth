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

namespace Causal\IgLdapSsoAuth\Hooks;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook into \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList and
 * \TYPO3\CMS\Backend\Utility\IconUtility to visually change
 * the icon associated to a FE/BE user/group record based on whether
 * the record is linked to LDAP.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 */
class DatabaseRecordListIconUtility implements \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface
{

    /**
     * Modifies the DB list query.
     *
     * @param string $table The current database table
     * @param int $pageId The record's page ID
     * @param string $additionalWhereClause An additional WHERE clause
     * @param string $selectedFieldsList Comma separated list of selected fields
     * @param \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList $parentObject
     * @return void
     * @see \TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList::getTable()
     */
    public function getDBlistQuery($table, $pageId, &$additionalWhereClause, &$selectedFieldsList, &$parentObject)
    {
        if (GeneralUtility::inList('be_groups,be_users,fe_groups,fe_users', $table)) {
            $selectedFieldsList .= ',tx_igldapssoauth_dn';
        }
    }

    /**
     * Overrides the icon overlay with a LDAP symbol, if needed.
     *
     * @param string $table The current database table
     * @param array $row The current record
     * @param array &$status The array of associated statuses
     * @return void
     * @see \TYPO3\CMS\Backend\Utility\IconUtility::mapRecordOverlayToSpriteIconName()
     * @target TYPO3 6.2 LTS, see \Causal\IgLdapSsoAuth\Hooks\IconFactory for TYPO3 >= 7 LTS
     */
    public function overrideIconOverlay($table, array $row, array &$status)
    {
        if (GeneralUtility::inList('be_groups,be_users,fe_groups,fe_users', $table)) {
            if (!array_key_exists('tx_igldapssoauth_dn', $row)) {
                // This is the case, e.g., in Backend users module
                $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $row['uid']);
            }
            $status['is_ldap_record'] = !empty($row['tx_igldapssoauth_dn']);
        }
    }
}
