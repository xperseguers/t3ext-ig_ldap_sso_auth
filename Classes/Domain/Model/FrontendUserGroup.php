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
 * A Frontend User Group
 *
 */
class FrontendUserGroup extends AbstractEntity
{
    protected string $description = '';

    /**
     * @var ObjectStorage<FrontendUserGroup>
     */
    protected $subgroup;

    /**
     * Constructs a new Frontend User Group
     *
     * @param string $title
     */
    public function __construct(
        protected string $title = ''
    )
    {
        $this->subgroup = new ObjectStorage();
    }

    /**
     * Sets the title value
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): self
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
     * Sets the description value
     *
     * @param string $description
     * @return $this
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Returns the description value
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Sets the subgroups. Keep in mind that the property is called "subgroup"
     * although it can hold several subgroups.
     *
     * @param ObjectStorage<FrontendUserGroup> $subgroup An object storage containing the subgroups to add
     * @return $this
     */
    public function setSubgroup(ObjectStorage $subgroup): self
    {
        $this->subgroup = $subgroup;
        return $this;
    }

    /**
     * Adds a subgroup to the frontend user
     *
     * @param FrontendUserGroup $subgroup
     * @return $this
     */
    public function addSubgroup(FrontendUserGroup $subgroup): self
    {
        $this->subgroup->attach($subgroup);
        return $this;
    }

    /**
     * Removes a subgroup from the frontend user group
     *
     * @param FrontendUserGroup $subgroup
     * @return $this
     */
    public function removeSubgroup(FrontendUserGroup $subgroup): self
    {
        $this->subgroup->detach($subgroup);
        return $this;
    }

    /**
     * Returns the subgroups. Keep in mind that the property is called "subgroup"
     * although it can hold several subgroups.
     *
     * @return ObjectStorage<FrontendUserGroup> An object storage containing the subgroups
     */
    public function getSubgroup(): ObjectStorage
    {
        return $this->subgroup;
    }
}
