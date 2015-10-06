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
    protected $uid;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $domains;

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
     * @return int the uid or null if none set yet.
     */
    public function getUid()
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
     */
    public function _setProperty($propertyName, $propertyValue)
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
     */
    public function _hasProperty($propertyName)
    {
        return property_exists($this, $propertyName);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDomains()
    {
        return $this->domains;
    }

    /**
     * @return int
     */
    public function getLdapServer()
    {
        return $this->ldapServer;
    }

    /**
     * @return string
     */
    public function getLdapCharset()
    {
        return $this->ldapCharset;
    }

    /**
     * @return int
     */
    public function getLdapProtocol()
    {
        return $this->ldapProtocol;
    }

    /**
     * @return string
     */
    public function getLdapHost()
    {
        return $this->ldapHost;
    }

    /**
     * @return int
     */
    public function getLdapPort()
    {
        return $this->ldapPort;
    }

    /**
     * @return boolean
     */
    public function isLdapTls()
    {
        return $this->ldapTls;
    }

    /**
     * @return string
     */
    public function getLdapBindDn()
    {
        return $this->ldapBindDn;
    }

    /**
     * @return string
     */
    public function getLdapPassword()
    {
        return $this->ldapPassword;
    }

    /**
     * @return int
     */
    public function getGroupMembership()
    {
        return $this->groupMembership;
    }

    /**
     * @return string
     */
    public function getBackendUsersBaseDn()
    {
        return $this->backendUsersBaseDn;
    }

    /**
     * @return string
     */
    public function getBackendUsersFilter()
    {
        return $this->backendUsersFilter;
    }

    /**
     * @return string
     */
    public function getBackendUsersMapping()
    {
        return $this->backendUsersMapping;
    }

    /**
     * @return string
     */
    public function getBackendGroupsBaseDn()
    {
        return $this->backendGroupsBaseDn;
    }

    /**
     * @return string
     */
    public function getBackendGroupsFilter()
    {
        return $this->backendGroupsFilter;
    }

    /**
     * @return string
     */
    public function getBackendGroupsMapping()
    {
        return $this->backendGroupsMapping;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsRequired()
    {
        return $this->backendGroupsRequired;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsAssigned()
    {
        return $this->backendGroupsAssigned;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup[]
     */
    public function getBackendGroupsAdministrator()
    {
        return $this->backendGroupsAdministrator;
    }

    /**
     * @return string
     */
    public function getFrontendUsersBaseDn()
    {
        return $this->frontendUsersBaseDn;
    }

    /**
     * @return string
     */
    public function getFrontendUsersFilter()
    {
        return $this->frontendUsersFilter;
    }

    /**
     * @return string
     */
    public function getFrontendUsersMapping()
    {
        return $this->frontendUsersMapping;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsBaseDn()
    {
        return $this->frontendGroupsBaseDn;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsFilter()
    {
        return $this->frontendGroupsFilter;
    }

    /**
     * @return string
     */
    public function getFrontendGroupsMapping()
    {
        return $this->frontendGroupsMapping;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup[]
     */
    public function getFrontendGroupsRequired()
    {
        return $this->frontendGroupsRequired;
    }

    /**
     * @return \TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup[]
     */
    public function getFrontendGroupsAssigned()
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
