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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class to be used to migrate configuration to be compatible with a newer major
 * version of this extension.
 *
 * @category    Extension Manager
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

    /** @var \TYPO3\CMS\Core\Database\Connection */
    protected $databaseConnection;

    /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder */
    protected $queryBuilder;

    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->configuration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extKey];
        $this->databaseConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tx_igldapssoauth_config');
        $this->queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_igldapssoauth_config');
    }

    /**
     * Checks whether the "UPDATE!" menu item should be
     * shown.
     *
     * @return bool
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
        if ($this->checkEuLdap()) {
            $this->operations[] = 'migrateEuLdap';
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

        $queryBuilder = $this->queryBuilder;
        $where = [];
        foreach ($mapping as $configKey => $field) {
            if (!empty($this->configuration[$configKey])) {
                // Global setting present => should be migrated if not already done
                $updateNeeded = true;
            }
            $where[] = $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter(''));
        }
        if ($updateNeeded) {
            $oldConfigurationRecords = $queryBuilder
                ->count('*')
                ->from($this->table)
                ->where(...$where)
                ->execute()
                ->fetchColumn(0);
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
        $queryBuilder = $this->queryBuilder;
        $oldConfigurationRecords = $queryBuilder
            ->count('*')
            ->from($this->table)
            ->where($queryBuilder->expr()->eq('group_membership', 0))
            ->execute()
            ->fetchColumn(0);

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
        $queryBuilder = $this->queryBuilder;
        $oldTaskRecords = $queryBuilder
            ->count('*')
            ->from('tx_scheduler_task')
            ->where(
                $queryBuilder->expr()->like(
                    'serialized_task_object',
                    $queryBuilder->createNamedParameter('O:33:"Tx_IgLdapSsoAuth_Task_ImportUsers":%')
                )
            )
            ->execute()
            ->fetchColumn(0);

        return $oldTaskRecords > 0;
    }

    /**
     * Returns true if upgrade wizard for legacy EXT:eu_ldap records should be run.
     *
     * @return bool
     */
    protected function checkEuLdap()
    {
        $table = 'tx_euldap_server';
        $migrationField = 'tx_igldapssoauth_migrated';

        // We check the database table itself and not whether EXT:eu_ldap is loaded
        // because it may have been deactivated since it is not incompatible
        if (!$this->databaseConnection->getSchemaManager()->tablesExist([$table])) {
            return false;
        }

        // Ensure the column used to flag processed records is present
        $fields = $this->databaseConnection
            ->getSchemaManager()
            ->listTableColumns($table);
        if (!isset($fields[$migrationField])) {
            $alterTableQuery = 'ALTER TABLE ' . $table . ' ADD ' . $migrationField . ' tinyint(4) NOT NULL default \'0\'';
            // Method admin_query() will parse the query and make it compatible with DBAL, if needed
            $this->databaseConnection->query($alterTableQuery);
        }

        $queryBuilder = $this->queryBuilder;
        $euLdapConfigurationRecords = $queryBuilder
            ->count('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $migrationField,
                    '0'
                )
            )
            ->execute()
            ->fetchColumn(0);

        return $euLdapConfigurationRecords > 0;
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
        $queryBuilder = $this->queryBuilder;
        $where = [];
        foreach ($mapping as $configKey => $field) {
            if (!empty($this->configuration[$configKey])) {
                // Global setting present => should be migrated
                $fieldValues[$field] = $this->configuration[$configKey];
            }
            $where[] = $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter(''));
        }
        $oldConfigurationRecords = $queryBuilder
            ->select('uid')
            ->from($this->table)
            ->where(...$where)
            ->execute()
            ->fetchAll();

        $i = 0;
        foreach ($oldConfigurationRecords as $oldConfigurationRecord) {
            $this->databaseConnection->update(
                $this->table,
                $fieldValues,
                ['uid' => $oldConfigurationRecord['uid']]
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
        $this->databaseConnection->update(
            $this->table,
            [
                'group_membership' => (bool)$this->configuration['evaluateGroupsFromMembership'] ? 2 : 1,
            ],
            ['1' => '1']
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

        $queryBuilder = $this->queryBuilder;

        $oldTaskRecords = $queryBuilder
            ->select('uid', 'serialized_task_object')
            ->from($table)
            ->where(
                $queryBuilder->expr()->like(
                    'serialized_task_object',
                    $queryBuilder->createNamedParameter($oldPattern . '%')
                )
            )
            ->execute()
            ->fetchAll();

        $i = 0;
        foreach ($oldTaskRecords as $oldTaskRecord) {
            $data = [
                'serialized_task_object' => preg_replace('/^' . $oldPattern . '/', $newPattern, $oldTaskRecord['serialized_task_object']),
            ];
            $this->databaseConnection->update(
                $table,
                $data,
                ['uid' => (int)$oldTaskRecord['uid']]
            );
            $i++;
        }

        return $this->formatOk('Successfully updated ' . $i . ' user import scheduler task' . ($i > 1 ? 's' : ''));
    }

    /**
     * Migrates configuration records from EXT:eu_ldap.
     *
     * @return string
     */
    protected function migrateEuLdap()
    {
        $out = [];

        // STEP 1: check global options
        $this->migrateEuLdapGlobalOptions($out);

        // STEP 2: migrate configuration records
        $this->migrateEuLdapConfiguration($out);

        // STEP 3: migrate users
        $this->migrateEuLdapUsers($out);

        return implode(LF, $out);
    }

    /**
     * Migrates global options from eu_ldap.
     *
     * @param array &$out
     * @return void
     */
    protected function migrateEuLdapGlobalOptions(array &$out)
    {
        $queryBuilder = $this->queryBuilder;

        $automaticImportRows = $queryBuilder
            ->select('DISTINCT authenticate_be', 'automatic_import', 'doitfe')
            ->from('tx_euldap_server')
            ->execute()
            ->fetchAll();

        $hasBackendAuthentication = false;
        $hasFrontendAuthentication = false;
        $shouldAutomaticallyImportBackendUsers = false;
        $shouldAutomaticallyImportBackendGroups = false;
        $shouldAutomaticallyImportFrontendUsers = false;
        $shouldAutomaticallyImportFrontendGroups = false;

        foreach ($automaticImportRows as $row) {
            if ($row['authenticate_be'] == 1 || $row['authenticate_be'] == 2) {
                $hasBackendAuthentication = true;
                if ($row['automatic_import'] == 1) {
                    $shouldAutomaticallyImportBackendUsers = true;
                }
                if ($row['doitfe'] == 1) {
                    $shouldAutomaticallyImportBackendGroups = true;
                }
            }
            if ($row['authenticate_be'] == 0 || $row['authenticate_be'] == 2) {
                $hasFrontendAuthentication = true;
                if ($row['automatic_import'] == 1) {
                    $shouldAutomaticallyImportFrontendUsers = true;
                }
                if ($row['doitfe'] == 1) {
                    $shouldAutomaticallyImportFrontendGroups = true;
                }
            }
        }

        if ($hasBackendAuthentication && $this->configuration['enableBELDAPAuthentication'] == 0) {
            $out[] = $this->formatWarning('eu_ldap was configured for backend authentication but this extension does not. You should set enableBELDAPAuthentication = 1.');
        } elseif (!$hasBackendAuthentication && $this->configuration['enableBELDAPAuthentication'] == 1) {
            $out[] = $this->formatWarning('eu_ldap was NOT configured for backend authentication but this extension does. You should probably set enableBELDAPAuthentication = 0.');
        }
        if ($hasFrontendAuthentication && $this->configuration['enableFELDAPAuthentication'] == 0) {
            $out[] = $this->formatWarning('eu_ldap was configured for frontend authentication but this extension does not. You should set enableFELDAPAuthentication = 1.');
        } elseif (!$hasFrontendAuthentication && $this->configuration['enableFELDAPAuthentication'] == 1) {
            $out[] = $this->formatWarning('eu_ldap was NOT configured for frontend authentication but this extension does. You should probably set enableFELDAPAuthentication = 0.');
        }

        if ($shouldAutomaticallyImportBackendUsers && $this->configuration['TYPO3BEUserExist'] == '1') {
            $out[] = $this->formatWarning('eu_ldap was configured to automatically import backend users but this extension does not. You should set TYPO3BEUserExist = 0.');
        } elseif (!$shouldAutomaticallyImportBackendUsers && $this->configuration['TYPO3BEUserExist'] == '0') {
            $out[] = $this->formatWarning('eu_ldap was configured to NEVER automatically import backend users but this extension does. You should set TYPO3BEUserExist = 1.');
        }
        if ($shouldAutomaticallyImportFrontendUsers && $this->configuration['TYPO3FEUserExist'] == '1') {
            $out[] = $this->formatWarning('eu_ldap was configured to automatically import frontend users but this extension does not. You should set TYPO3FEUserExist = 0.');
        } elseif (!$shouldAutomaticallyImportFrontendUsers && $this->configuration['TYPO3FEUserExist'] == '0') {
            $out[] = $this->formatWarning('eu_ldap was configured to NEVER automatically import frontend users but this extension does. You should set TYPO3FEUserExist = 1.');
        }

        if ($shouldAutomaticallyImportBackendGroups && $this->configuration['TYPO3BEGroupsNotSynchronize'] == '1') {
            $out[] = $this->formatWarning('eu_ldap was configured to automatically import backend groups but this extension does not. You should set TYPO3BEGroupsNotSynchronize = 0.');
        } elseif (!$shouldAutomaticallyImportBackendGroups && $this->configuration['TYPO3BEGroupsNotSynchronize'] == '0') {
            $out[] = $this->formatWarning('eu_ldap was configured to NEVER automatically import backend group but this extension does. You should set TYPO3BEGroupsNotSynchronize = 1.');
        }
        if ($shouldAutomaticallyImportFrontendGroups && $this->configuration['TYPO3FEGroupsNotSynchronize'] == '1') {
            $out[] = $this->formatWarning('eu_ldap was configured to automatically import frontend groups but this extension does not. You should set TYPO3FEGroupsNotSynchronize = 0.');
        } elseif (!$shouldAutomaticallyImportFrontendGroups && $this->configuration['TYPO3FEGroupsNotSynchronize'] == '0') {
            $out[] = $this->formatWarning('eu_ldap was configured to NEVER automatically import frontend group but this extension does. You should set TYPO3FEGroupsNotSynchronize = 1.');
        }
    }

    /**
     * Migrates eu_ldap configuration records.
     *
     * @param array &$out
     * @return void
     */
    protected function migrateEuLdapConfiguration(array &$out)
    {
        $queryBuilder = $this->queryBuilder;

        $euLdapConfigurationRecords = $queryBuilder
            ->select('*')
            ->from('tx_euldap_server')
            ->where(
                $queryBuilder->expr()->eq(
                    'tx_igldapssoauth_migrated',
                    0
                )
            )
            ->execute()
            ->fetchAll();

        foreach ($euLdapConfigurationRecords as $legacy) {
            $hasBackendAuthentication = $legacy['authenticate_be'] == 1 || $legacy['authenticate_be'] == 2;
            $hasFrontendAuthentication = $legacy['authenticate_be'] == 0 || $legacy['authenticate_be'] == 2;

            $data = [
                'pid' => 0,
                'tstamp' => $GLOBALS['EXEC_TIME'],
                'crdate' => $GLOBALS['EXEC_TIME'],
                'cruser_id' => $GLOBALS['BE_USER']->user['uid'],
                'name' => '[eu_ldap] ' . $legacy['server'],
                'ldap_server' => $legacy['servertype'] == 3 ? 0 : 1,
                'ldap_charset' => $legacy['characterset'],
                'ldap_host' => $legacy['server'],
                'ldap_port' => $legacy['port'],
                'ldap_tls' => 0,
                'ldap_binddn' => $legacy['servertype'] == 2 || $legacy['servertype'] == 3
                    ? $legacy['user']
                    : (
                    $legacy['servertype'] == 0
                        ? $legacy['domain'] . '\\' . $legacy['user']
                        : $legacy['user'] . '@' . $legacy['domain']
                    ),
                'ldap_password' => $legacy['password'],
                'be_users_basedn' => $hasBackendAuthentication ? $legacy['base_dn'] : '',
                'be_users_filter' => $hasBackendAuthentication ? str_replace('<search>', '{USERNAME}', $legacy['filter']) : '',
                'be_users_mapping' => '', // computed below
                'be_groups_basedn' => $hasBackendAuthentication ? $legacy['base_dn'] : '',
                'be_groups_filter' => '',    // computed below
                'be_groups_mapping' => $hasBackendAuthentication
                    ? implode(LF, [
                        'title = <cn>',
                        'tstamp = {DATE}',
                    ]) : '',
                'fe_users_basedn' => $hasFrontendAuthentication ? $legacy['base_dn'] : '',
                'fe_users_filter' => $hasFrontendAuthentication ? str_replace('<search>', '{USERNAME}', $legacy['filter']) : '',
                'fe_users_mapping' => '', // computed below
                'fe_groups_basedn' => $hasFrontendAuthentication ? $legacy['base_dn'] : '',
                'fe_groups_filter' => '', // computed below
                'fe_groups_mapping' => $hasFrontendAuthentication
                    ? implode(LF, [
                        'pid = ' . (int)$legacy['feuser_pid'],
                        'title = <cn>',
                        'tstamp = {DATE}',
                    ]) : '',
                'be_groups_required' => $hasBackendAuthentication ? $legacy['matchgrps'] : '',
                'be_groups_assigned' => $legacy['be_group'],
                'fe_groups_required' => $hasFrontendAuthentication ? $legacy['matchgrps'] : '',
                'fe_groups_assigned' => $legacy['fe_group'],
                'group_membership' => $legacy['memberof'] == 1
                    ? (
                    $legacy['servertype'] == 3
                        ? \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_GROUP
                        : \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_MEMBER
                    )
                    : 0,    // No standard mapping, will have to be manually configured
                'sorting' => $legacy['sorting'],
            ];

            if ($hasBackendAuthentication) {
                $mapping = [];
                $mapping[] = 'tstamp = ' . (!empty($legacy['timestamp']) ? '<' . $legacy['timestamp'] . '>' : '{DATE}');

                switch ($legacy['servertype']) {
                    case 0:
                    case 1:
                        $mapping[] = 'usergroup = <memberof>';
                        $data['be_groups_filter'] = '(objectClass=posixGroup)';
                        break;
                    case 2:
                        $mapping[] = 'usergroup = <groupmembership>';
                        $data['be_groups_filter'] = '(objectClass=posixGroup)';
                        break;
                    case 3:
                        $data['be_groups_filter'] = '(&(memberUid={USERUID})(objectClass=posixGroup))';
                        break;
                }

                $mapping[] = 'realName = <' . $legacy['name'] . '>';
                if (!empty($legacy['mail'])) {
                    $mapping[] = 'email = <' . $legacy['mail'] . '>';
                }

                $data['be_users_mapping'] = implode(LF, $mapping);
            }
            if ($hasFrontendAuthentication) {
                $mapping = [];
                $mapping[] = 'pid = ' . (int)$legacy['feuser_pid'];
                $mapping[] = 'tstamp = ' . (!empty($legacy['timestamp']) ? '<' . $legacy['timestamp'] . '>' : '{DATE}');

                switch ($legacy['servertype']) {
                    case 0:
                    case 1:
                        $mapping[] = 'usergroup = <memberof>';
                        $data['fe_groups_filter'] = '(objectClass=posixGroup)';
                        break;
                    case 2:
                        $mapping[] = 'usergroup = <groupmembership>';
                        $data['fe_groups_filter'] = '(objectClass=posixGroup)';
                        break;
                    case 3:
                        $data['fe_groups_filter'] = '(&(memberUid={USERUID})(objectClass=posixGroup))';
                        break;
                }

                if (!empty($legacy['mail'])) {
                    $mapping[] = 'email = <' . $legacy['mail'] . '>';
                }
                $mapping[] = 'name = <' . $legacy['name'] . '>';
                if (!empty($legacy['address'])) {
                    $mapping[] = 'address = <' . $legacy['address'] . '>';
                }
                if (!empty($legacy['zip'])) {
                    $mapping[] = 'zip = <' . $legacy['zip'] . '>';
                }
                if (!empty($legacy['city'])) {
                    $mapping[] = 'city = <' . $legacy['city'] . '>';
                }
                if (!empty($legacy['country'])) {
                    $mapping[] = 'country = <' . $legacy['country'] . '>';
                }
                if (!empty($legacy['phone'])) {
                    $mapping[] = 'telephone = <' . $legacy['phone'] . '>';
                }
                if (!empty($legacy['fax'])) {
                    $mapping[] = 'fax = <' . $legacy['fax'] . '>';
                }
                if (!empty($legacy['www'])) {
                    $mapping[] = 'www = <' . $legacy['www'] . '>';
                }

                $additionalInstructions = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $legacy['map_additional_fields'], true);
                foreach ($additionalInstructions as $additionalInstruction) {
                    list($dbField, $ldapField) = explode('=', $additionalInstruction, 2);
                    $mapping[] = $dbField . ' = <' . $ldapField . '>';
                }

                $data['fe_users_mapping'] = implode(LF, $mapping);
            }

            if ($data['be_groups_required'] === '*') {
                // Replace '*' by every local BE group
                $groups = $queryBuilder
                    ->select('uid')
                    ->from('be_groups')
                    ->where(
                        $queryBuilder->expr()->eq('hidden', 0),
                        $queryBuilder->expr()->eq('deleted', 0),
                        $queryBuilder->expr()->eq('tx_igldapssoauth_dn', ''),
                        $queryBuilder->expr()->eq('eu_ldap', 0)
                    )
                    ->execute()
                    ->fetchAll();

                $data['be_groups_required'] = implode(',', array_keys($groups));
            }
            if ($data['fe_groups_required'] === '*') {
                // Replace '*' by every local FE group
                $groups = $queryBuilder
                    ->select('uid')
                    ->from('fe_groups')
                    ->where(
                        $queryBuilder->expr()->eq('hidden', 0),
                        $queryBuilder->expr()->eq('deleted', 0),
                        $queryBuilder->expr()->eq('tx_igldapssoauth_dn', ''),
                        $queryBuilder->expr()->eq('eu_ldap', 0)
                    )
                    ->execute()
                    ->fetchAll();

                $data['fe_groups_required'] = implode(',', array_keys($groups));
            }
            if ($legacy['only_emailusers'] == 1) {
                $emailAttribute = !empty($legacy['mail']) ? $legacy['mail'] : 'mail';
                if ($hasBackendAuthentication) {
                    $data['be_users_filter'] = sprintf('(&%s(%s=*))', $data['be_users_filter'], $emailAttribute);
                }
                if ($hasFrontendAuthentication) {
                    $data['fe_users_filter'] = sprintf('(&%s(%s=*))', $data['fe_users_filter'], $emailAttribute);
                }
            }

            // Insert the migrated record to ig_ldap_sso_auth
            $this->databaseConnection->insert($this->table, $data);
            if ($this->databaseConnection->prepare('SELECT ROW_COUNT()')->fetchColumn() == 1) {
                $this->databaseConnection->update(
                    'tx_euldap_server',
                    [
                        'tx_igldapssoauth_migrated' => 1,
                    ],
                    ['uid' => $legacy['uid']]
                );
            }
        }

        $out[] = $this->formatOk('Successfully migrated eu_ldap configuration records.');
    }

    /**
     * Migrates backend and/or frontend users that were previously imported
     * with eu_ldap.
     *
     * @param array &$out
     * @return void
     */
    protected function migrateEuLdapUsers(array &$out)
    {
        foreach (['fe_users', 'be_users'] as $table) {
            $query = <<<SQL
UPDATE $table
SET tx_igldapssoauth_dn=tx_euldap_dn
WHERE tx_igldapssoauth_dn='' AND tx_euldap_dn<>''
SQL;
            $this->databaseConnection->query($query);
        }

        $out[] = $this->formatOk('Successfully migrated eu_ldap users.');
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
