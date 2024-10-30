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

use Causal\IgLdapSsoAuth\Domain\Model\Configuration;
use Causal\IgLdapSsoAuth\Event\ConfigurationLoadedEvent;
use Causal\IgLdapSsoAuth\Event\CustomConfigurationEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Utility class for fetching LDAP configurations.
 *
 * NOTE: this is a not a true Extbase repository.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @author     Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ConfigurationRepository
{
    /** @var string */
    protected $table = 'tx_igldapssoauth_config';

    /**
     * @var bool Set to true to also fetch disabled records (according to TCA enable fields)
     */
    protected $fetchDisabledRecords = false;

    /*
     * @var array Extension configuration for supporting reading LDAP configuration from there
     */
    protected $config = [];

    /**
     * ConfigurationRepository constructor.
     */
    public function __construct(
        protected readonly EventDispatcherInterface $eventDispatcher
    )
    {
        $this->config = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('ig_ldap_sso_auth') ?? [];
    }

    /**
     * Returns all available LDAP configurations.
     *
     * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration[]
     */
    public function findAll(): array
    {
        $rows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table)
            ->select(
                ['*'],
                $this->table,
                [],
                [],
                [
                    'sorting' => 'ASC',
                ]
            )
            ->fetchAllAssociative();

        // TODO: Drop "support" in version 4.2 or so
        if ((bool)($this->config['useExtConfConfiguration'] ?? false) && !empty($this->config)) {
            trigger_error(
                'Using useExtConfConfiguration is not supported anymore since version 4.0. Please switch to PSR-14 ConfigurationLoadedEvent.',
                E_USER_DEPRECATED
            );
        }

        $configurations = [];
        foreach ($rows as $row) {
            /** @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration */
            $configuration = GeneralUtility::makeInstance(Configuration::class);
            $this->thawProperties($configuration, $row);
            $configurations[] = $configuration;
        }

        /** @var ConfigurationLoadedEvent $event */
        $event = GeneralUtility::makeInstance(ConfigurationLoadedEvent::class, $configurations);
        $this->eventDispatcher->dispatch($event);

        return $event->getConfigurationRecords();
    }

    /**
     * Returns a single LDAP configuration.
     *
     * @param int $uid Primary key to look up
     * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration
     */
    public function findByUid(int $uid): ?Configuration
    {
        $row = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->table)
            ->select(
                ['*'],
                $this->table,
                [
                    'uid' => $uid,
                ]
            )
            ->fetchAssociative();

        if (!empty($row)) {
            /** @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration */
            $configuration = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Domain\Model\Configuration::class);
            $this->thawProperties($configuration, $row);
        } else {
            $configuration = null;
        }

        // TODO: Drop "support" in version 4.2 or so
        if ((bool)($this->config['useExtConfConfiguration'] ?? false) && !empty($this->config)) {
            trigger_error(
                'Using useExtConfConfiguration is not supported anymore since version 4.0. Please switch to PSR-14 CustomConfigurationEvent.',
                E_USER_DEPRECATED
            );
        }

        /** @var CustomConfigurationEvent $event */
        $event = GeneralUtility::makeInstance(CustomConfigurationEvent::class, $uid, $configuration);
        $this->eventDispatcher->dispatch($event);

        return $event->getConfigurationRecord();
    }

    /**
     * Sets the flag for fetching disabled records or not.
     *
     * @param bool $flag Set to true to enable fetching of disabled record
     * @return $this
     */
    public function setFetchDisabledRecords(bool $flag): self
    {
        $this->fetchDisabledRecords = $flag;
        return $this;
    }

    /**
     * Sets the given properties on the object.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $object The object to set properties on
     * @param array $row
     * @return $this
     */
    protected function thawProperties(Configuration $object, array $row): self
    {
        $object->_setProperty('uid', (int)$row['uid']);

        // Mapping for properties to be set without any transformation
        $mapping = [
            'name' => 'name',
            'sites' => 'sites',
            'ldap_charset' => 'ldapCharset',
            'ldap_host' => 'ldapHost',
            'ldap_timeout' => 'ldapTimeout',
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
            $groupUids = GeneralUtility::intExplode(',', $row[$fieldName] ?? '', true);
            if (!empty($groupUids)) {
                $repository = str_starts_with($fieldName, 'be_')
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
        $object->_setProperty('ldapTlsReqcert', (bool)$row['ldap_tls_reqcert']);
        $object->_setProperty('ldapSsl', (bool)$row['ldap_ssl']);
        $object->_setProperty('groupMembership', (int)$row['group_membership']);

        return $this;
    }

    /**
     * Returns a BackendUserGroupRepository.
     *
     * @return BackendUserGroupRepository
     */
    protected static function getBackendUserGroupRepository(): BackendUserGroupRepository
    {
        static $backendUserGroupRepository = null;
        if ($backendUserGroupRepository === null) {
            $backendUserGroupRepository = GeneralUtility::makeInstance(BackendUserGroupRepository::class);
        }
        return $backendUserGroupRepository;
    }

    /**
     * Returns a FrontendUserGroupRepository.
     *
     * @return FrontendUserGroupRepository
     */
    protected static function getFrontendUserGroupRepository(): FrontendUserGroupRepository
    {
        static $frontendUserGroupRepository = null;
        if ($frontendUserGroupRepository === null) {
            $frontendUserGroupRepository = GeneralUtility::makeInstance(FrontendUserGroupRepository::class);
        }
        return $frontendUserGroupRepository;
    }
}
