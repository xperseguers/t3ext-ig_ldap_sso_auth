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

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * A Frontend User
 *
 */
class FrontendUser extends AbstractEntity
{
    /**
     * @var ObjectStorage<FrontendUserGroup>
     */
    protected $usergroup;

    protected string $name = '';

    protected string $firstName = '';

    protected string $middleName = '';

    protected string $lastName = '';

    protected string $address = '';

    protected string $telephone = '';

    protected string $fax = '';

    protected string $email = '';

    protected string $title = '';


    protected string $zip = '';

    protected string $city = '';

    protected string $country = '';

    protected string $www = '';

    protected string $company = '';

    /**
     * @var ObjectStorage<FileReference>
     */
    protected $image;

    protected ?\DateTime $lastlogin = null;

    /**
     * Constructs a new Front-End User
     *
     * @param string $username
     * @param string $password
     */
    public function __construct(
        protected string $username = '',
        #[\SensitiveParameter] protected string $password = ''
    )
    {
        $this->usergroup = new ObjectStorage();
        $this->image = new ObjectStorage();
    }

    /**
     * Called again with initialize object, as fetching an entity from the DB does not use the constructor
     */
    public function initializeObject()
    {
        $this->usergroup = $this->usergroup ?? new ObjectStorage();
        $this->image = $this->image ?? new ObjectStorage();
    }

    /**
     * Sets the username value
     *
     * @param string $username
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Returns the username value
     *
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * Sets the password value
     *
     * @param string $password
     * @return $this
     */
    public function setPassword(#[\SensitiveParameter] string $password): self
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Returns the password value
     *
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * Sets the usergroups. Keep in mind that the property is called "usergroup"
     * although it can hold several usergroups.
     *
     * @param ObjectStorage<FrontendUserGroup> $usergroup
     * @return $this
     */
    public function setUsergroup(ObjectStorage $usergroup): self
    {
        $this->usergroup = $usergroup;
        return $this;
    }

    /**
     * Adds a usergroup to the frontend user
     *
     * @param FrontendUserGroup $usergroup
     * @return $this
     */
    public function addUsergroup(FrontendUserGroup $usergroup): self
    {
        $this->usergroup->attach($usergroup);
        return $this;
    }

    /**
     * Removes a usergroup from the frontend user
     *
     * @param FrontendUserGroup $usergroup
     * @return $this
     */
    public function removeUsergroup(FrontendUserGroup $usergroup): self
    {
        $this->usergroup->detach($usergroup);
        return $this;
    }

    /**
     * Returns the usergroups. Keep in mind that the property is called "usergroup"
     * although it can hold several usergroups.
     *
     * @return ObjectStorage<FrontendUserGroup> An object storage containing the usergroup
     */
    public function getUsergroup(): ObjectStorage
    {
        return $this->usergroup;
    }

    /**
     * Sets the name value
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the name value
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the firstName value
     *
     * @param string $firstName
     * @return $this
     */
    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * Returns the firstName value
     *
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->firstName;
    }

    /**
     * Sets the middleName value
     *
     * @param string $middleName
     * @return $this
     */
    public function setMiddleName(string $middleName): self
    {
        $this->middleName = $middleName;
        return $this;
    }

    /**
     * Returns the middleName value
     *
     * @return string
     */
    public function getMiddleName(): string
    {
        return $this->middleName;
    }

    /**
     * Sets the lastName value
     *
     * @param string $lastName
     * @return $this
     */
    public function setLastName(string $lastName): string
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * Returns the lastName value
     *
     * @return string
     */
    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * Sets the address value
     *
     * @param string $address
     * @return $this
     */
    public function setAddress(string $address): self
    {
        $this->address = $address;
        return $this;
    }

    /**
     * Returns the address value
     *
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * Sets the telephone value
     *
     * @param string $telephone
     * @return $this
     */
    public function setTelephone(string $telephone): self
    {
        $this->telephone = $telephone;
        return $this;
    }

    /**
     * Returns the telephone value
     *
     * @return string
     */
    public function getTelephone(): string
    {
        return $this->telephone;
    }

    /**
     * Sets the fax value
     *
     * @param string $fax
     * @return $this
     */
    public function setFax(string $fax): self
    {
        $this->fax = $fax;
        return $this;
    }

    /**
     * Returns the fax value
     *
     * @return string
     */
    public function getFax(): string
    {
        return $this->fax;
    }

    /**
     * Sets the email value
     *
     * @param string $email
     * @return $this
     */
    public function setEmail(string $email): string
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Returns the email value
     *
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets the title value
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): string
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns the title value
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Sets the zip value
     *
     * @param string $zip
     * @return $this
     */
    public function setZip(string $zip): string
    {
        $this->zip = $zip;
        return $this;
    }

    /**
     * Returns the zip value
     *
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * Sets the city value
     *
     * @param string $city
     * @return $this
     */
    public function setCity(string $city): string
    {
        $this->city = $city;
        return $this;
    }

    /**
     * Returns the city value
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->city;
    }

    /**
     * Sets the country value
     *
     * @param string $country
     * @return $this
     */
    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    /**
     * Returns the country value
     *
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
    }

    /**
     * Sets the www value
     *
     * @param string $www
     * @return $this
     */
    public function setWww(string $www): self
    {
        $this->www = $www;
        return $this;
    }

    /**
     * Returns the www value
     *
     * @return string
     */
    public function getWww(): string
    {
        return $this->www;
    }

    /**
     * Sets the company value
     *
     * @param string $company
     */
    public function setCompany(string $company): self
    {
        $this->company = $company;
        return $this;
    }

    /**
     * Returns the company value
     *
     * @return string
     */
    public function getCompany(): string
    {
        return $this->company;
    }

    /**
     * Sets the image value
     *
     * @param ObjectStorage<FileReference> $image
     */
    public function setImage(ObjectStorage $image): self
    {
        $this->image = $image;
        return $this;
    }

    /**
     * Gets the image value
     *
     * @return ObjectStorage<FileReference>
     */
    public function getImage(): ObjectStorage
    {
        return $this->image;
    }

    /**
     * Sets the lastlogin value
     *
     * @param \DateTime $lastlogin
     * @return $this
     */
    public function setLastlogin(\DateTime $lastlogin): self
    {
        $this->lastlogin = $lastlogin;
        return $this;
    }

    /**
     * Returns the lastlogin value
     *
     * @return \DateTime
     */
    public function getLastlogin(): ?\DateTime
    {
        return $this->lastlogin;
    }
}
