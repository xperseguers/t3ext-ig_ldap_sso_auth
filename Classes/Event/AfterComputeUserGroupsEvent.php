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

namespace Causal\IgLdapSsoAuth\Event;

final class AfterComputeUserGroupsEvent implements LdapEventInterface
{
    public function __construct(
        protected readonly array $ldapUser,
        protected readonly array $configuration,
        protected readonly string $groupTable,
        protected array $typo3Groups
    )
    {
    }

    /**
     * @return array
     */
    public function getLdapUser(): array
    {
        return $this->ldapUser;
    }

    /**
     * @return array
     */
    public function getConfiguration(): array
    {
        return $this->configuration;
    }

    /**
     * @return string
     */
    public function getGroupTable(): string
    {
        return $this->groupTable;
    }

    /**
     * @return array
     */
    public function getTypo3Groups(): array
    {
        return $this->typo3Groups;
    }

    /**
     * @param array $typo3Groups
     * @return $this
     */
    public function setTypo3Groups(array $typo3Groups): self
    {
        $this->typo3Groups = $typo3Groups;
        return $this;
    }
}
