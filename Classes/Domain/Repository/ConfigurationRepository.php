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
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for fetching LDAP configurations.
 *
 * NOTE: this is a not a true Extbase repository.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @author     Francois Suter <typo3@cobweb.ch>
 */
class ConfigurationRepository
{

    /** @var string */
    protected $table = 'tx_igldapssoauth_config';

    /**
     * @var bool Set to true to also fetch disabled records (according to TCA enable fields)
     */
    protected $fetchDisabledRecords = false;

    /**
     * Returns all available LDAP configurations.
     *
     * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration[]
     */
    public function findAll()
    {
        $queryBuilder = static::getQueryBuilder();
        $where = $this->getWhereClauseForEnabledFields($queryBuilder);
        $query = $queryBuilder
            ->select('*')
            ->from($this->table);
        if (count($where)) {
            $query->where(...$where);
        }
        $rows = $query
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();

        $configurations = [];
        foreach ($rows as $row) {
            /** @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration */
            $configuration = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Domain\Model\Configuration::class);
            $this->thawProperties($configuration, $row);
            $configurations[] = $configuration;
        }

        return $configurations;
    }

    /**
     * Returns a single LDAP configuration.
     *
     * @param int $uid Primary key to look up
     * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration
     */
    public function findByUid($uid)
    {
        $queryBuilder = static::getQueryBuilder();
        $where = $this->getWhereClauseForEnabledFields($queryBuilder);
        $query = $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where(
                $queryBuilder->expr()->eq('uid', (int)$uid)
            );
        if (count($where)) {
            $query->andWhere(...$where);
        }
        $row = $query
            ->orderBy('sorting')
            ->execute()
            ->fetch();
        if ($row) {
            /** @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration */
            $configuration = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Domain\Model\Configuration::class);
            $this->thawProperties($configuration, $row);
        } else {
            $configuration = null;
        }

        return $configuration;
    }

    /**
     * Returns the WHERE clause for the enabled fields of this TCA table
     * depending on the context. In backend context the query builder gets
     * a deleted restriction if disabled records should be fetched.
     * @param \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder
     * @return array The additional where clause, as array
     */
    protected function getWhereClauseForEnabledFields(&$queryBuilder)
    {
        $whereClause = [];
        if (TYPO3_MODE === 'FE') {
            // Frontend context
            // $GLOBALS['TCA'] is not yet available/initialized:
            // Cannot use $GLOBALS['TSFE']->sys_page->deleteClause() / ->enableFields()
            $whereClause = [
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('hidden', 0),
                ];
        } else {
            // Backend context
            if ($this->fetchDisabledRecords) {
                $queryBuilder
                    ->getRestrictions()
                    ->removeAll()
                    ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            }
        }
        return $whereClause;
    }

    /**
     * Sets the flag for fetching disabled records or not.
     *
     * @param bool $flag Set to true to enable fetching of disabled record
     * @return void
     */
    public function setFetchDisabledRecords($flag)
    {
        $this->fetchDisabledRecords = (bool)$flag;
    }

    /**
     * Sets the given properties on the object.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $object The object to set properties on
     * @param array $row
     * @return void
     */
    protected function thawProperties(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $object, array $row)
    {
        $object->_setProperty('uid', (int)$row['uid']);

        // Mapping for properties to be set without any transformation
        $mapping = [
            'name' => 'name',
            'domains' => 'domains',
            'ldap_charset' => 'ldapCharset',
            'ldap_host' => 'ldapHost',
            'ldap_binddn' => 'ldapBindDn',
            'ldap_password' => 'ldapPassword',
            'be_users_basedn' => 'backendUsersBaseDn',
            'be_users_filter' => 'backendUsersFilter',
            'be_users_mapping' => 'backendUsersMapping',
            'be_groups_basedn' => 'backendGroupsBaseDn',
            'be_groups_filter' => 'backendGroupsFilter',
            'be_groups_mapping' => 'backendGroupsMapping',
            'fe_users_basedn' => 'frontendUsersBaseDn',
            'fe_users_filter' => 'frontendUsersFilter',
            'fe_users_mapping' => 'frontendUsersMapping',
            'fe_groups_basedn' => 'frontendGroupsBaseDn',
            'fe_groups_filter' => 'frontendGroupsFilter',
            'fe_groups_mapping' => 'frontendGroupsMapping',
        ];

        foreach ($mapping as $fieldName => $propertyName) {
            $object->_setProperty($propertyName, $row[$fieldName]);
        }

        // Mapping for backend / frontend user groups
        $groupsMapping = [
            'be_groups_required' => 'backendGroupsRequired',
            'be_groups_assigned' => 'backendGroupsAssigned',
            'be_groups_admin' => 'backendGroupsAdministrator',
            'fe_groups_required' => 'frontendGroupsRequired',
            'fe_groups_assigned' => 'frontendGroupsAssigned',
        ];

        foreach ($groupsMapping as $fieldName => $propertyName) {
            $groups = [];
            $groupUids = GeneralUtility::intExplode(',', $row[$fieldName], true);
            if (count($groupUids) > 0) {
                $repository = substr($fieldName, 0, 3) === 'be_'
                    ? static::getBackendUserGroupRepository()
                    : static::getFrontendUserGroupRepository();
                foreach ($groupUids as $groupUid) {
                    $group = $repository->findByUid($groupUid);
                    if ($group !== null) {
                        $groups[] = $group;
                    }
                }
            }
            $object->_setProperty($propertyName, $groups);
        }

        $object->_setProperty('ldapServer', (int)$row['ldap_server']);
        $object->_setProperty('ldapProtocol', 3);
        $object->_setProperty('ldapPort', (int)$row['ldap_port']);
        $object->_setProperty('ldapTls', (bool)$row['ldap_tls']);
        $object->_setProperty('ldapSsl', (bool)$row['ldap_ssl']);
        $object->_setProperty('groupMembership', (int)$row['group_membership']);
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
     * Returns a BackendUserGroupRepository.
     *
     * @return \Causal\IgLdapSsoAuth\Domain\Repository\BackendUserGroupRepository
     */
    protected static function getBackendUserGroupRepository()
    {
        static $backendUserGroupRepository = null;
        if ($backendUserGroupRepository == null) {
            $backendUserGroupRepository = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Domain\Repository\BackendUserGroupRepository::class);
        }
        return $backendUserGroupRepository;
    }

    /**
     * Returns a FrontendUserGroupRepository.
     *
     * @return \Causal\IgLdapSsoAuth\Domain\Repository\FrontendUserGroupRepository
     */
    protected static function getFrontendUserGroupRepository()
    {
        static $frontendUserGroupRepository = null;
        if ($frontendUserGroupRepository == null) {
            $frontendUserGroupRepository = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Domain\Repository\FrontendUserGroupRepository::class);
        }
        return $frontendUserGroupRepository;
    }
}
