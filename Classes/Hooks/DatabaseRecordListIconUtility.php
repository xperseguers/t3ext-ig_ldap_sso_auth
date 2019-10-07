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
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class DatabaseRecordListIconUtility implements \TYPO3\CMS\Backend\RecordList\RecordListGetTableHookInterface
{

    /**
     * Modifies the DB list query.
     *
     * @param string $table The current database table
     * @param integer $pageId The record's page ID
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

}
