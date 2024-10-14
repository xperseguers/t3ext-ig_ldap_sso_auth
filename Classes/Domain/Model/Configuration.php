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
 * TODO: Add setters for all properties as it may be used by PSR-14 events
 *       and using _setProperty() is supposed to be internal-only.
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
    protected $uid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $sites;

    /**
     * @var int
     */
    protected $ldapServer;

    /**
     * @var string
     */
    protected $ldapCharset;

    /**
     * @var int
     * @deprecated
     */
    protected $ldapProtocol;

    /**
     * @var string
     */
    protected $ldapHost;

    /**
     * @var int
     */
    protected $ldapPort;

    /**
     * @var bool
     */
    protected $ldapTls;

    /**
     * @var bool
     */
    protected $ldapTlsReqcert;

    /**
     * @var bool
     */
    protected $ldapSsl;

    /**
     * @var string
     */
    protected $ldapBindDn;

    /**
     * @var string
     */
    protected $ldapPassword;

    /**
     * @var int
     */
    protected $groupMembership;

    /**
     * @var string
     */
    protected $backendUsersBaseDn;

    /**
     * @var string
     */
    protected $backendUsersFilter;

    /**
     * @var string
     */
    protected $backendUsersMapping;

    /**
     * @var string
     */
    protected $backendGroupsBaseDn;

    /**
     * @var string
     */
    protected $backendGroupsFilter;

    /**
     * @var string
     */
    protected $backendGroupsMapping;

    /**
     * @var array
     */
    protected $backendGroupsRequired;

    /**
     * @var array
     */
    protected $backendGroupsAssigned;

    /**
     * @var array
     */
    protected $backendGroupsAdministrator;

    /**
     * @var string
     */
    protected $frontendUsersBaseDn;

    /**
     * @var string
     */
    protected $frontendUsersFilter;

    /**
     * @var string
     */
    protected $frontendUsersMapping;

    /**
     * @var string
     */
    protected $frontendGroupsBaseDn;

    /**
     * @var string
     */
    protected $frontendGroupsFilter;

    /**
     * @var string
     */
    protected $frontendGroupsMapping;

    /**
     * @var array
     */
    protected $frontendGroupsRequired;

    /**
     * @var array
     */
    protected $frontendGroupsAssigned;

    /**
     * Getter for uid.
     *
     * @return int|null the uid or null if none set yet.
     */
    public function getUid(): ?int
    {
        if ($this->uid !== null) {
            return (int)$this->uid;
        } else {
            return null;
        }
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
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSites(): string
    {
        return $this->sites;
    }

    /**
     * @return int
     */
    public function getLdapServer(): int
    {
        return $this->ldapServer;
    }

    /**
     * @return string
     */
    public function getLdapCharset(): string
    {
        return $this->ldapCharset;
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
     * @return int
     */
    public function getLdapPort(): int
    {
        return $this->ldapPort;
    }

    /**
     * @return bool
     */
    public function isLdapTls(): bool
    {
        return $this->ldapTls;
    }

    /**
     * @return bool
     */
    public function isLdapSsl(): bool
    {
        return $this->ldapSsl;
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
     * @return string
     */
    public function getLdapBindDn(): string
    {
        return $this->ldapBindDn;
    }

    /**
     * @return string
     */
    public function getLdapPassword(): string
    {
        return $this->ldapPassword;
    }

    /**
     * @return int
     */
    public function getGroupMembership(): int
    {
        return $this->groupMembership;
    }

    /**
     * @return string
     */
    public function getBackendUsersBaseDn(): string
    {
        return $this->backendUsersBaseDn;
    }

    /**
     * @return string
     */
    public function getBackendUsersFilter(): string
    {
        return $this->backendUsersFilter ?? '';
    }

    /**
     * @return string
     */
    public function getBackendUsersMapping(): string
    {
        return $this->backendUsersMapping ?? '';
    }

    /**
     * @return string
     */
    public function getBackendGroupsBaseDn(): string
    {
        return $this->backendGroupsBaseDn;
    }

    /**
     * @return string
     */
    public function getBackendGroupsFilter(): string
    {
        return $this->backendGroupsFilter ?? '';
    }

    /**
     * @return string
     */
    public function getBackendGroupsMapping(): string
    {
        return $this->backendGroupsMapping ?? '';
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsRequired(): array
    {
        return $this->backendGroupsRequired;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsAssigned(): array
    {
        return $this->backendGroupsAssigned;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsAdministrator(): array
    {
        return $this->backendGroupsAdministrator;
    }

    /**
     * @return string
     */
    public function getFrontendUsersBaseDn(): string
    {
        return $this->frontendUsersBaseDn;
    }

    /**
     * @return string
     */
    public function getFrontendUsersFilter(): string
    {
        return $this->frontendUsersFilter ?? '';
    }

    /**
     * @return string
     */
    public function getFrontendUsersMapping(): string
    {
        return $this->frontendUsersMapping ?? '';
    }

    /**
     * @return string
     */
    public function getFrontendGroupsBaseDn(): string
    {
        return $this->frontendGroupsBaseDn;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsFilter(): string
    {
        return $this->frontendGroupsFilter ?? '';
    }

    /**
     * @return string
     */
    public function getFrontendGroupsMapping(): string
    {
        return $this->frontendGroupsMapping ?? '';
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup[]
     */
    public function getFrontendGroupsRequired(): array
    {
        return $this->frontendGroupsRequired;
    }

    /**
     * @return \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup[]
     */
    public function getFrontendGroupsAssigned(): array
    {
        return $this->frontendGroupsAssigned;
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
