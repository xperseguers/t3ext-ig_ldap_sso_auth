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

namespace Causal\IgLdapSsoAuth\Domain\Model;

/**
 * Domain model for configuration records.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Configuration
{
    /**
     * @var int The uid of the record. The uid is only unique in the context of the database table.
     */
    protected ?int $uid = null;

    protected string $name = '';

    protected string $sites = '';

    protected int $ldapServer = \Causal\IgLdapSsoAuth\Library\Configuration::SERVER_OPENLDAP;

    protected string $ldapCharset = '';

    /**
     * @deprecated
     */
    protected int $ldapProtocol = 3;

    protected string $ldapHost = '';

    protected int $ldapPort = 389;

    protected bool $ldapTls = false;

    protected bool $ldapTlsReqcert = false;

    protected bool $ldapSsl = false;

    protected string $ldapBindDn = '';

    protected string $ldapPassword = '';

    protected int $groupMembership = \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_GROUP;

    protected string $backendUsersBaseDn = '';

    protected ?string $backendUsersFilter = null;

    protected ?string $backendUsersMapping = null;

    protected string $backendGroupsBaseDn = '';

    protected ?string $backendGroupsFilter = null;

    protected ?string $backendGroupsMapping = null;

    protected array $backendGroupsRequired = [];

    protected array $backendGroupsAssigned = [];

    protected array $backendGroupsAdministrator = [];

    protected string $frontendUsersBaseDn = '';

    protected ?string $frontendUsersFilter = null;

    protected ?string $frontendUsersMapping = null;

    protected string $frontendGroupsBaseDn = '';

    protected ?string $frontendGroupsFilter = null;

    protected ?string $frontendGroupsMapping = null;

    protected array $frontendGroupsRequired = [];

    protected array $frontendGroupsAssigned = [];

    /**
     * @return int|null the uid or null if none set yet.
     */
    public function getUid(): ?int
    {
        return $this->uid;
    }

    /**
     * @param int $uid
     * @return $this
     */
    public function setUid(int $uid): self
    {
        $this->uid = $uid;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getSites(): string
    {
        return $this->sites;
    }

    /**
     * @param string $sites
     * @return $this
     */
    public function setSites(string $sites): self
    {
        $this->sites = $sites;
        return $this;
    }

    /**
     * @return int
     */
    public function getLdapServer(): int
    {
        return $this->ldapServer;
    }

    /**
     * @param int $ldapServer
     * @return $this
     */
    public function setLdapServer(int $ldapServer): self
    {
        $this->ldapServer = $ldapServer;
        return $this;
    }

    /**
     * @return string
     */
    public function getLdapCharset(): string
    {
        return $this->ldapCharset;
    }

    /**
     * @param string $ldapCharset
     * @return $this
     */
    public function setLdapCharset(string $ldapCharset): self
    {
        $this->ldapCharset = $ldapCharset;
        return $this;
    }

    /**
     * @return int
     * @deprecated
     */
    public function getLdapProtocol(): int
    {
        return $this->ldapProtocol;
    }

    /**
     * @return string
     */
    public function getLdapHost(): string
    {
        return $this->ldapHost;
    }

    /**
     * @param string $ldapHost
     * @return $this
     */
    public function setLdapHost(string $ldapHost): self
    {
        $this->ldapHost = $ldapHost;
        return $this;
    }

    /**
     * @return int
     */
    public function getLdapPort(): int
    {
        return $this->ldapPort;
    }

    /**
     * @param int $ldapPort
     * @return $this
     */
    public function setLdapPort(int $ldapPort): self
    {
        $this->ldapPort = $ldapPort;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLdapTls(): bool
    {
        return $this->ldapTls;
    }

    /**
     * @param bool $ldapTls
     * @return $this
     */
    public function setLdapTls(bool $ldapTls): self
    {
        $this->ldapTls = $ldapTls;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLdapTlsReqcert(): bool
    {
        return $this->ldapTlsReqcert;
    }

    /**
     * @param bool $ldapTlsReqcert
     * @return $this
     */
    public function setLdapTlsReqcert(bool $ldapTlsReqcert): self
    {
        $this->ldapTlsReqcert = $ldapTlsReqcert;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLdapSsl(): bool
    {
        return $this->ldapSsl;
    }

    /**
     * @param bool $ldapSsl
     * @return $this
     */
    public function setLdapSsl(bool $ldapSsl): self
    {
        $this->ldapSsl = $ldapSsl;
        return $this;
    }

    /**
     * @return string
     */
    public function getLdapBindDn(): string
    {
        return $this->ldapBindDn;
    }

    /**
     * @param string $ldapBindDn
     * @return $this
     */
    public function setLdapBindDn(string $ldapBindDn): self
    {
        $this->ldapBindDn = $ldapBindDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getLdapPassword(): string
    {
        return $this->ldapPassword;
    }

    /**
     * @param string $ldapPassword
     * @return $this
     */
    public function setLdapPassword(string $ldapPassword): self
    {
        $this->ldapPassword = $ldapPassword;
        return $this;
    }

    /**
     * @return int
     */
    public function getGroupMembership(): int
    {
        return $this->groupMembership;
    }

    /**
     * @param int $groupMembership
     * @return $this
     */
    public function setGroupMembership(int $groupMembership): self
    {
        $this->groupMembership = $groupMembership;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackendUsersBaseDn(): string
    {
        return $this->backendUsersBaseDn;
    }

    /**
     * @param string $backendUsersBaseDn
     * @return $this
     */
    public function setBackendUsersBaseDn(string $backendUsersBaseDn): self
    {
        $this->backendUsersBaseDn = $backendUsersBaseDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackendUsersFilter(): string
    {
        return $this->backendUsersFilter ?? '';
    }

    /**
     * @param string|null $backendUsersFilter
     * @return $this
     */
    public function setBackendUsersFilter(?string $backendUsersFilter): self
    {
        $this->backendUsersFilter = $backendUsersFilter;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackendUsersMapping(): string
    {
        return $this->backendUsersMapping ?? '';
    }

    /**
     * @param string|null $backendUsersMapping
     * @return $this
     */
    public function setBackendUsersMapping(?string $backendUsersMapping): self
    {
        $this->backendUsersMapping = $backendUsersMapping;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackendGroupsBaseDn(): string
    {
        return $this->backendGroupsBaseDn;
    }

    /**
     * @param string $backendGroupsBaseDn
     * @return $this
     */
    public function setBackendGroupsBaseDn(string $backendGroupsBaseDn): self
    {
        $this->backendGroupsBaseDn = $backendGroupsBaseDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackendGroupsFilter(): string
    {
        return $this->backendGroupsFilter ?? '';
    }

    /**
     * @param string|null $backendGroupsFilter
     * @return $this
     */
    public function setBackendGroupsFilter(?string $backendGroupsFilter): self
    {
        $this->backendGroupsFilter = $backendGroupsFilter;
        return $this;
    }

    /**
     * @return string
     */
    public function getBackendGroupsMapping(): string
    {
        return $this->backendGroupsMapping ?? '';
    }

    /**
     * @param string|null $backendGroupsMapping
     * @return $this
     */
    public function setBackendGroupsMapping(?string $backendGroupsMapping): self
    {
        $this->backendGroupsMapping = $backendGroupsMapping;
        return $this;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsRequired(): array
    {
        return $this->backendGroupsRequired;
    }

    /**
     * @param \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[] $backendGroupsRequired
     * @return $this
     */
    public function setBackendGroupsRequired(array $backendGroupsRequired): self
    {
        $this->backendGroupsRequired = $backendGroupsRequired;
        return $this;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsAssigned(): array
    {
        return $this->backendGroupsAssigned;
    }

    /**
     * @param \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[] $backendGroupsAssigned
     * @return $this
     */
    public function setBackendGroupsAssigned(array $backendGroupsAssigned): self
    {
        $this->backendGroupsAssigned = $backendGroupsAssigned;
        return $this;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsAdministrator(): array
    {
        return $this->backendGroupsAdministrator;
    }

    /**
     * @param \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[] $backendGroupsAdministrator
     * @return $this
     */
    public function setBackendGroupsAdministrator(array $backendGroupsAdministrator): self
    {
        $this->backendGroupsAdministrator = $backendGroupsAdministrator;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrontendUsersBaseDn(): string
    {
        return $this->frontendUsersBaseDn;
    }

    /**
     * @param string $frontendUsersBaseDn
     * @return $this
     */
    public function setFrontendUsersBaseDn(string $frontendUsersBaseDn): self
    {
        $this->frontendUsersBaseDn = $frontendUsersBaseDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrontendUsersFilter(): string
    {
        return $this->frontendUsersFilter ?? '';
    }

    /**
     * @param string|null $frontendUsersFilter
     * @return $this
     */
    public function setFrontendUsersFilter(?string $frontendUsersFilter): self
    {
        $this->frontendUsersFilter = $frontendUsersFilter;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrontendUsersMapping(): string
    {
        return $this->frontendUsersMapping ?? '';
    }

    /**
     * @param string|null $frontendUsersMapping
     * @return $this
     */
    public function setFrontendUsersMapping(?string $frontendUsersMapping): self
    {
        $this->frontendUsersMapping = $frontendUsersMapping;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsBaseDn(): string
    {
        return $this->frontendGroupsBaseDn;
    }

    /**
     * @param string $frontendGroupsBaseDn
     * @return $this
     */
    public function setFrontendGroupsBaseDn(string $frontendGroupsBaseDn): self
    {
        $this->frontendGroupsBaseDn = $frontendGroupsBaseDn;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsFilter(): string
    {
        return $this->frontendGroupsFilter ?? '';
    }

    /**
     * @param string|null $frontendGroupsFilter
     * @return $this
     */
    public function setFrontendGroupsFilter(?string $frontendGroupsFilter): self
    {
        $this->frontendGroupsFilter = $frontendGroupsFilter;
        return $this;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsMapping(): string
    {
        return $this->frontendGroupsMapping ?? '';
    }

    /**
     * @param string|null $frontendGroupsMapping
     * @return $this
     */
    public function setFrontendGroupsMapping(?string $frontendGroupsMapping): self
    {
        $this->frontendGroupsMapping = $frontendGroupsMapping;
        return $this;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup[]
     */
    public function getFrontendGroupsRequired(): array
    {
        return $this->frontendGroupsRequired;
    }

    /**
     * @param \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup[] $frontendGroupsRequired
     * @return $this
     */
    public function setFrontendGroupsRequired(array $frontendGroupsRequired): self
    {
        $this->frontendGroupsRequired = $frontendGroupsRequired;
        return $this;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup[]
     */
    public function getFrontendGroupsAssigned(): array
    {
        return $this->frontendGroupsAssigned;
    }

    /**
     * @param \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup[] $frontendGroupsAssigned
     * @return $this
     */
    public function setFrontendGroupsAssigned(array $frontendGroupsAssigned): self
    {
        $this->frontendGroupsAssigned = $frontendGroupsAssigned;
        return $this;
    }

    /**
     * Reconstitutes a property. Only for internal use.
     *
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return bool
     * @internal
     */
    public function _setProperty(string $propertyName, $propertyValue): bool
    {
        if ($this->_hasProperty($propertyName)) {
            $this->{$propertyName} = $propertyValue;
            return true;
        }
        return false;
    }

    /**
     * Returns the property value of the given property name. Only for internal use.
     *
     * @param string $propertyName
     * @return bool true bool true if the property exists, false  if it doesn't exist or null in case of an error.
     * @internal
     */
    public function _hasProperty(string $propertyName): bool
    {
        return property_exists($this, $propertyName);
    }

    /**
     * Returns the class name and the uid of the object as string
     *
     * @return string
     */
    public function __toString()
    {
        return get_class($this) . ':' . (string)$this->uid;
    }
}
