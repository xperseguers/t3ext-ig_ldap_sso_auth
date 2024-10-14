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

final class UserDisabledEvent implements LdapEventInterface
{
    /**
     * @param string $table
     * @param array $data
     */
    public function __construct(
        protected readonly string $table,
        protected readonly int $configurationUid
    )
    {
    }

    /**
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @return int
     */
    public function getConfigurationUid(): int
    {
        return $this->configurationUid;
    }
}
