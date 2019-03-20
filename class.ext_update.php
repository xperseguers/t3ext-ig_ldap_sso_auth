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

$BACK_PATH = $GLOBALS['BACK_PATH'] . TYPO3_mainDir;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class to be used to migrate configuration to be compatible with a newer major
 * version of this extension.
 *
 * @category    Extension Manager
 * @package     TYPO3
 * @subpackage  tx_igldapssoauth
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @license     http://www.gnu.org/copyleft/gpl.html
 */
class ext_update extends \TYPO3\CMS\Backend\Module\BaseScriptClass
{

    /** @var string */
    protected $extKey = 'ig_ldap_sso_auth';

    /** @var array */
    protected $configuration;

    /** @var array */
    protected $operations = [];

    /** @var string */
    protected $table = 'tx_igldapssoauth_config';

    /** @var \TYPO3\CMS\Core\Database\DatabaseConnection */
    protected $databaseConnection;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extKey]);
        $this->databaseConnection = $GLOBALS['TYPO3_DB'];
    }

    /**
     * Checks whether the "UPDATE!" menu item should be
     * shown.
     *
     * @return boolean
     */
    public function access()
    {
        if ($this->checkV1xToV12()) {
            $this->operations[] = 'upgradeV1xToV12';
        }
        if ($this->checkV12ToV13()) {
            $this->operations[] = 'upgradeV12ToV13';
        }
        if ($this->checkV2xToV30()) {
            $this->operations[] = 'upgradeV2xToV30';
        }

        return count($this->operations) > 0;
    }

    /**
     * Returns true if upgrade wizard from v1.x to v1.2 should be run.
     *
     * @return bool
     */
    protected function checkV1xToV12()
    {
        $updateNeeded = false;
        $mapping = $this->getMapping();

        $where = [];
        foreach ($mapping as $configKey => $field) {
            if (!empty($this->configuration[$configKey])) {
                // Global setting present => should be migrated if not already done
                $updateNeeded = true;
            }
            $where[] = $field . '=' . $this->databaseConnection->fullQuoteStr('', $this->table);
        }
        if ($updateNeeded) {
            $oldConfigurationRecords = $this->databaseConnection->exec_SELECTcountRows(
                '*',
                $this->table,
                implode(' AND ', $where)
            );
            $updateNeeded = ($oldConfigurationRecords > 0);
        }

        return $updateNeeded;
    }

    /**
     * Returns true if upgrade wizard from v1.2 to v1.3 should be run.
     *
     * @return bool
     */
    protected function checkV12ToV13()
    {
        $oldConfigurationRecords = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table)
            ->count(
            '*',
            $this->table,
                [
                    'group_membership' => 0,
                ]
        );
        return $oldConfigurationRecords > 0;
    }

    /**
     * Returns true if upgrade wizard from v2.x to v3.0 should be run.
     *
     * @return bool
     */
    protected function checkV2xToV30()
    {
        if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('scheduler')) {
            return false;
        }
        $oldTaskRecords = $this->databaseConnection->exec_SELECTcountRows(
            '*',
            'tx_scheduler_task',
            'serialized_task_object LIKE ' . $this->databaseConnection->fullQuoteStr('O:33:"Tx_IgLdapSsoAuth_Task_ImportUsers":%', 'tx_scheduler_task')
        );
        return $oldTaskRecords > 0;
    }

    /**
     * Main method that is called whenever UPDATE! menu
     * was clicked.
     *
     * @return string HTML to display
     */
    public function main()
    {
        $out = [];

        foreach ($this->operations as $operation) {
            $out[] = call_user_func([$this, $operation]);
        }

        return implode(LF, $out);
    }

    /**
     * Upgrades configuration from v1.x to v1.2.
     *
     * @return string
     */
    protected function upgradeV1xToV12()
    {
        $mapping = $this->getMapping();

        $fieldValues = [
            'tstamp' => $GLOBALS['EXEC_TIME'],
        ];
        $where = [];
        foreach ($mapping as $configKey => $field) {
            if (!empty($this->configuration[$configKey])) {
                // Global setting present => should be migrated
                $fieldValues[$field] = $this->configuration[$configKey];
            }
            $where[] = $field . '=' . $this->databaseConnection->fullQuoteStr('', $this->table);
        }
        $oldConfigurationRecords = $this->databaseConnection->exec_SELECTgetRows(
            'uid',
            $this->table,
            implode(' AND ', $where)
        );

        $i = 0;
        foreach ($oldConfigurationRecords as $oldConfigurationRecord) {
            $this->databaseConnection->exec_UPDATEquery(
                $this->table,
                'uid=' . $oldConfigurationRecord['uid'],
                $fieldValues
            );
            $i++;
        }

        return $this->formatOk('Successfully updated ' . $i . ' configuration record' . ($i > 1 ? 's' : ''));
    }

    /**
     * Upgrades configuration from v1.2 to v1.3.
     *
     * @return string
     */
    protected function upgradeV12ToV13()
    {
        $this->databaseConnection->exec_UPDATEquery(
            $this->table,
            '1=1',
            [
                'group_membership' => (bool)$this->configuration['evaluateGroupsFromMembership'] ? 2 : 1,
            ]
        );

        return $this->formatOk('Successfully transferred how the group membership should be extracted from LDAP from global configuration to the configuration records.');
    }

    /**
     * Upgrades configuration from v2.x to v3.0.
     *
     * @return string
     */
    protected function upgradeV2xToV30()
    {
        $table = 'tx_scheduler_task';
        $oldClassName = 'Tx_IgLdapSsoAuth_Task_ImportUsers';
        $newClassName = 'Causal\\IgLdapSsoAuth\\Task\\ImportUsers';
        $oldPattern = 'O:' . strlen($oldClassName) . ':"' . $oldClassName . '":';
        $newPattern = 'O:' . strlen($newClassName) . ':"' . $newClassName . '":';

        $oldTaskRecords = $this->databaseConnection->exec_SELECTgetRows(
            'uid, serialized_task_object',
            $table,
            'serialized_task_object LIKE ' . $this->databaseConnection->fullQuoteStr($oldPattern . '%', $table)
        );

        $i = 0;
        foreach ($oldTaskRecords as $oldTaskRecord) {
            $data = [
                'serialized_task_object' => preg_replace('/^' . $oldPattern . '/', $newPattern, $oldTaskRecord['serialized_task_object']),
            ];
            $this->databaseConnection->exec_UPDATEquery(
                $table,
                'uid=' . (int)$oldTaskRecord['uid'],
                $data
            );
            $i++;
        }

        return $this->formatOk('Successfully updated ' . $i . ' user import scheduler task' . ($i > 1 ? 's' : ''));
    }

    /**
     * Returns the mapping between global configuration options and
     * configuration record fields.
     *
     * @return array
     */
    protected function getMapping()
    {
        return [
            'requiredLDAPBEGroups' => 'be_groups_required',
            'assignBEGroups' => 'be_groups_assigned',
            'updateAdminAttribForGroups' => 'be_groups_admin',
            'requiredLDAPFEGroups' => 'fe_groups_required',
            'assignFEGroups' => 'fe_groups_assigned',
        ];
    }

    /**
     * Creates an OK message for backend output.
     *
     * @param string $message
     * @param bool $hsc
     * @return string
     */
    protected function formatOk($message, $hsc = true)
    {
        $output = '<div class="typo3-message message-ok">';
        //$output .= '<div class="message-header">Message head</div>';
        if ($hsc) {
            $message = nl2br(htmlspecialchars($message));
        }
        $output .= '<div class="message-body">' . $message . '</div>';
        $output .= '</div>';

        return $output;
    }

    /**
     * Creates a WARNING message for backend output.
     *
     * @param string $message
     * @param bool $hsc
     * @return string
     */
    protected function formatWarning($message, $hsc = true)
    {
        $output = '<div class="typo3-message message-warning">';
        //$output .= '<div class="message-header">Message head</div>';
        if ($hsc) {
            $message = nl2br(htmlspecialchars($message));
        }
        $output .= '<div class="message-body">' . $message . '</div>';
        $output .= '</div>';

        return $output;
    }

}
