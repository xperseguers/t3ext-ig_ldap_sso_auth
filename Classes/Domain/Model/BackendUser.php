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

use TYPO3\CMS\Extbase\Annotation as Extbase;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * This model represents a back-end user.
 *
 */
class BackendUser extends AbstractEntity
{
    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected string $userName = '';

    protected string $description = '';

    protected bool $isAdministrator = false;

    protected bool $isDisabled = false;

    protected ?\DateTime $startDateAndTime;

    protected ?\DateTime $endDateAndTime;

    protected string $email = '';

    protected string $realName = '';

    protected ?\DateTime $lastLoginDateAndTime;

    /**
     * Gets the user name.
     *
     * @return string the user name, will not be empty
     */
    public function getUserName(): string
    {
        return $this->userName;
    }

    /**
     * Sets the user name.
     *
     * @param string $userName the user name to set, must not be empty
     * @return $this
     */
    public function setUserName(string $userName): self
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Checks whether this user is an administrator.
     *
     * @return bool whether this user is an administrator
     */
    public function getIsAdministrator(): bool
    {
        return $this->isAdministrator;
    }

    /**
     * Sets whether this user should be an administrator.
     *
     * @param bool $isAdministrator whether this user should be an administrator
     * @return $this
     */
    public function setIsAdministrator(bool $isAdministrator): self
    {
        $this->isAdministrator = $isAdministrator;
        return $this;
    }

    /**
     * Checks whether this user is disabled.
     *
     * @return bool whether this user is disabled
     */
    public function getIsDisabled(): bool
    {
        return $this->isDisabled;
    }

    /**
     * Checks whether this user is disabled.
     *
     * @return bool whether this user is disabled
     */
    public function isDisabled(): bool
    {
        return $this->isDisabled;
    }

    /**
     * Sets whether this user is disabled.
     *
     * @param bool $isDisabled whether this user is disabled
     * @return $this
     */
    public function setIsDisabled(bool $isDisabled): self
    {
        $this->isDisabled = $isDisabled;
        return $this;
    }

    /**
     * Returns the point in time from which this user is enabled.
     *
     * @return \DateTime|null the start date and time
     */
    public function getStartDateAndTime(): ?\DateTime
    {
        return $this->startDateAndTime;
    }

    /**
     * Sets the point in time from which this user is enabled.
     *
     * @param \DateTime|null $dateAndTime the start date and time
     * @return $this
     */
    public function setStartDateAndTime(?\DateTime $dateAndTime = null): self
    {
        $this->startDateAndTime = $dateAndTime;
        return $this;
    }

    /**
     * Returns the point in time before which this user is enabled.
     *
     * @return \DateTime|null the end date and time
     */
    public function getEndDateAndTime(): ?\DateTime
    {
        return $this->endDateAndTime;
    }

    /**
     * Sets the point in time before which this user is enabled.
     *
     * @param \DateTime|null $dateAndTime the end date and time
     * @return $this
     */
    public function setEndDateAndTime(?\DateTime $dateAndTime = null): self
    {
        $this->endDateAndTime = $dateAndTime;
        return $this;
    }

    /**
     * Gets the e-mail address of this user.
     *
     * @return string the e-mail address, might be empty
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Sets the e-mail address of this user.
     *
     * @param string $email the e-mail address, may be empty
     * @return $this
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Returns this user's real name.
     *
     * @return string the real name. might be empty
     */
    public function getRealName(): string
    {
        return $this->realName;
    }

    /**
     * Sets this user's real name.
     *
     * @param string $name the user's real name, may be empty
     * @return $this
     */
    public function setRealName(string $name): self
    {
        $this->realName = $name;
        return $this;
    }

    /**
     * Checks whether this user is currently activated.
     *
     * This function takes the "disabled" flag, the start date/time and the end date/time into account.
     *
     * @return bool whether this user is currently activated
     */
    public function isActivated(): bool
    {
        return !$this->getIsDisabled()
            && $this->isActivatedViaStartDateAndTime()
            && $this->isActivatedViaEndDateAndTime();
    }

    /**
     * Checks whether this user is activated as far as the start date and time is concerned.
     *
     * @return bool whether this user is activated as far as the start date and time is concerned
     */
    protected function isActivatedViaStartDateAndTime(): bool
    {
        if ($this->getStartDateAndTime() === null) {
            return true;
        }
        $now = new \DateTime('now');
        return $this->getStartDateAndTime() <= $now;
    }

    /**
     * Checks whether this user is activated as far as the end date and time is concerned.
     *
     * @return bool whether this user is activated as far as the end date and time is concerned
     */
    protected function isActivatedViaEndDateAndTime(): bool
    {
        if ($this->getEndDateAndTime() === null) {
            return true;
        }
        $now = new \DateTime('now');
        return $now <= $this->getEndDateAndTime();
    }

    /**
     * Gets this user's last login date and time.
     *
     * @return \DateTime|null this user's last login date and time, will be NULL if this user has never logged in before
     */
    public function getLastLoginDateAndTime(): ?\DateTime
    {
        return $this->lastLoginDateAndTime;
    }

    /**
     * Sets this user's last login date and time.
     *
     * @param \DateTime|null $dateAndTime this user's last login date and time
     * @return $this
     */
    public function setLastLoginDateAndTime(?\DateTime $dateAndTime = null): self
    {
        $this->lastLoginDateAndTime = $dateAndTime;
        return $this;
    }
}
