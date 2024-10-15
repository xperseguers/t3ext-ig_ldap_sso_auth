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
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * This model represents a backend usergroup.
 *
 */
class BackendUserGroup extends AbstractEntity
{
    public const FILE_OPPERATIONS = 1;
    public const DIRECTORY_OPPERATIONS = 4;
    public const DIRECTORY_COPY = 8;
    public const DIRECTORY_REMOVE_RECURSIVELY = 16;

    /**
     * @Extbase\Validate("NotEmpty")
     */
    protected string $title = '';

    protected string $description = '';

    /**
     * @var \TYPO3\CMS\Extbase\Persistence\ObjectStorage<\Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup>
     */
    protected $subGroups;

    protected string $modules = '';

    protected string $tablesListening = '';

    protected string $tablesModify = '';

    protected string $pageTypes = '';

    protected string $allowedExcludeFields = '';

    protected string $explicitlyAllowAndDeny = '';

    protected string $allowedLanguages = '';

    protected bool $workspacePermission = false;

    protected string $databaseMounts = '';

    protected int $fileOperationPermissions = 0;

    protected string $tsConfig = '';

    /**
     * Constructs this backend usergroup
     */
    public function __construct()
    {
        $this->subGroups = new ObjectStorage();
    }

    /**
     * Setter for title
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
     * Getter for title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * Setter for description
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
     * Getter for description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Setter for the sub groups
     *
     * @param \TYPO3\CMS\Extbase\Persistence\ObjectStorage $subGroups
     * @return $this
     */
    public function setSubGroups(ObjectStorage $subGroups): self
    {
        $this->subGroups = $subGroups;
        return $this;
    }

    /**
     * Adds a sub group to this backend user group
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup $beGroup
     * @return $this
     */
    public function addSubGroup(\Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup $beGroup): self
    {
        $this->subGroups->attach($beGroup);
        return $this;
    }

    /**
     * Removes sub group from this backend user group
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup $groupToDelete
     * @return $this
     */
    public function removeSubGroup(\Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup $groupToDelete): self
    {
        $this->subGroups->detach($groupToDelete);
        return $this;
    }

    /**
     * Remove all sub groups from this backend user group
     *
     * @return $this
     */
    public function removeAllSubGroups(): self
    {
        $subGroups = clone $this->subGroups;
        $this->subGroups->removeAll($subGroups);
        return $this;
    }

    /**
     * Getter of sub groups
     *
     * @return \TYPO3\CMS\Extbase\Persistence\ObjectStorage
     */
    public function getSubGroups(): ObjectStorage
    {
        return $this->subGroups;
    }

    /**
     * Setter for modules
     *
     * @param string $modules
     * @return $this
     */
    public function setModules(string $modules): self
    {
        $this->modules = $modules;
        return $this;
    }

    /**
     * Getter for modules
     *
     * @return string
     */
    public function getModules(): string
    {
        return $this->modules;
    }

    /**
     * Setter for tables listening
     *
     * @param string $tablesListening
     * @return $this
     */
    public function setTablesListening(string $tablesListening): self
    {
        $this->tablesListening = $tablesListening;
        return $this;
    }

    /**
     * Getter for tables listening
     *
     * @return string
     */
    public function getTablesListening(): string
    {
        return $this->tablesListening;
    }

    /**
     * Setter for tables modify
     *
     * @param string $tablesModify
     * @return $this
     */
    public function setTablesModify(string $tablesModify): self
    {
        $this->tablesModify = $tablesModify;
        return $this;
    }

    /**
     * Getter for tables modify
     *
     * @return string
     */
    public function getTablesModify(): string
    {
        return $this->tablesModify;
    }

    /**
     * Setter for page types
     *
     * @param string $pageTypes
     * @return $this
     */
    public function setPageTypes(string $pageTypes): self
    {
        $this->pageTypes = $pageTypes;
        return $this;
    }

    /**
     * Getter for page types
     *
     * @return string
     */
    public function getPageTypes(): string
    {
        return $this->pageTypes;
    }

    /**
     * Setter for allowed exclude fields
     *
     * @param string $allowedExcludeFields
     * @return $this
     */
    public function setAllowedExcludeFields(string $allowedExcludeFields): self
    {
        $this->allowedExcludeFields = $allowedExcludeFields;
        return $this;
    }

    /**
     * Getter for allowed exclude fields
     *
     * @return string
     */
    public function getAllowedExcludeFields(): string
    {
        return $this->allowedExcludeFields;
    }

    /**
     * Setter for explicitly allow and deny
     *
     * @param string $explicitlyAllowAndDeny
     * @return $this
     */
    public function setExplicitlyAllowAndDeny(string $explicitlyAllowAndDeny): self
    {
        $this->explicitlyAllowAndDeny = $explicitlyAllowAndDeny;
        return $this;
    }

    /**
     * Getter for explicitly allow and deny
     *
     * @return string
     */
    public function getExplicitlyAllowAndDeny(): string
    {
        return $this->explicitlyAllowAndDeny;
    }

    /**
     * Setter for allowed languages
     *
     * @param string $allowedLanguages
     * @return $this
     */
    public function setAllowedLanguages(string $allowedLanguages): self
    {
        $this->allowedLanguages = $allowedLanguages;
        return $this;
    }

    /**
     * Getter for allowed languages
     *
     * @return string
     */
    public function getAllowedLanguages(): string
    {
        return $this->allowedLanguages;
    }

    /**
     * Setter for workspace permission
     *
     * @param bool $workspacePermission
     * @return $this
     */
    public function setWorkspacePermissions(bool $workspacePermission): self
    {
        $this->workspacePermission = $workspacePermission;
        return $this;
    }

    /**
     * Getter for workspace permission
     *
     * @return bool
     */
    public function getWorkspacePermission(): bool
    {
        return $this->workspacePermission;
    }

    /**
     * Setter for database mounts
     *
     * @param string $databaseMounts
     * @return $this
     */
    public function setDatabaseMounts(string $databaseMounts): self
    {
        $this->databaseMounts = $databaseMounts;
        return $this;
    }

    /**
     * Getter for database mounts
     *
     * @return string
     */
    public function getDatabaseMounts(): string
    {
        return $this->databaseMounts;
    }

    /**
     * Getter for file operation permissions
     *
     * @param int $fileOperationPermissions
     * @return $this
     */
    public function setFileOperationPermissions(int $fileOperationPermissions): self
    {
        $this->fileOperationPermissions = $fileOperationPermissions;
        return $this;
    }

    /**
     * Getter for file operation permissions
     *
     * @return int
     */
    public function getFileOperationPermissions(): int
    {
        return $this->fileOperationPermissions;
    }

    /**
     * Check if file operations like upload, copy, move, delete, rename, new and
     * edit files is allowed.
     *
     * @return bool
     */
    public function isFileOperationAllowed(): bool
    {
        return $this->isPermissionSet(self::FILE_OPPERATIONS);
    }

    /**
     * Set the the bit for file operations are allowed.
     *
     * @param bool $value
     * @return $this
     */
    public function setFileOperationAllowed(bool $value): self
    {
        $this->setPermission(self::FILE_OPPERATIONS, $value);
        return $this;
    }

    /**
     * Check if folder operations like move, delete, rename, and new are allowed.
     *
     * @return bool
     */
    public function isDirectoryOperationAllowed(): bool
    {
        return $this->isPermissionSet(self::DIRECTORY_OPPERATIONS);
    }

    /**
     * Set the the bit for directory operations are allowed.
     *
     * @param bool $value
     * @return $this
     */
    public function setDirectoryOperationAllowed(bool $value): self
    {
        $this->setPermission(self::DIRECTORY_OPPERATIONS, $value);
        return $this;
    }

    /**
     * Check if it is allowed to copy folders.
     *
     * @return bool
     */
    public function isDirectoryCopyAllowed(): bool
    {
        return $this->isPermissionSet(self::DIRECTORY_COPY);
    }

    /**
     * Set the the bit for copy directories.
     *
     * @param bool $value
     * @return $this
     */
    public function setDirectoryCopyAllowed(bool $value): self
    {
        $this->setPermission(self::DIRECTORY_COPY, $value);
        return $this;
    }

    /**
     * Check if it is allowed to remove folders recursively.
     *
     * @return bool
     */
    public function isDirectoryRemoveRecursivelyAllowed(): bool
    {
        return $this->isPermissionSet(self::DIRECTORY_REMOVE_RECURSIVELY);
    }

    /**
     * Set the the bit for remove directories recursively.
     *
     * @param bool $value
     * @return $this
     */
    public function setDirectoryRemoveRecursivelyAllowed(bool $value): self
    {
        $this->setPermission(self::DIRECTORY_REMOVE_RECURSIVELY, $value);
        return $this;
    }

    /**
     * Setter for ts config
     *
     * @param string $tsConfig
     * @return $this
     */
    public function setTsConfig(string $tsConfig): self
    {
        $this->tsConfig = $tsConfig;
        return $this;
    }

    /**
     * Getter for ts config
     *
     * @return string
     */
    public function getTsConfig(): string
    {
        return $this->tsConfig;
    }

    /**
     * Helper method for checking the permissions bitwise.
     *
     * @param int $permission
     * @return bool
     */
    protected function isPermissionSet(int $permission): bool
    {
        return ($this->fileOperationPermissions & $permission) === $permission;
    }

    /**
     * Helper method for setting permissions bitwise.
     *
     * @param int $permission
     * @param bool $value
     */
    protected function setPermission(int $permission, bool $value): void
    {
        if ($value) {
            $this->fileOperationPermissions |= $permission;
        } else {
            $this->fileOperationPermissions &= ~$permission;
        }
    }
}
