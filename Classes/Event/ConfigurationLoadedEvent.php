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

use Causal\IgLdapSsoAuth\Domain\Model\Configuration;

final class ConfigurationLoadedEvent
{
    public function __construct(
        private array $configurationRecords
    )
    {
    }

    /**
     * @return array<Configuration>
     */
    public function getConfigurationRecords(): array
    {
        return $this->configurationRecords;
    }
    
    /**
     * @param array<Configuration> $configurationRecords
     * @return $this
     */
    public function setConfigurationRecords(array $configurationRecords): self
    {
        $this->configurationRecords = $configurationRecords;
        return $this;
    }

    /**
     * @param Configuration $configurationRecord
     * @return $this
     */
    public function addConfigurationRecord(Configuration $configurationRecord): self
    {
        $this->configurationRecords[] = $configurationRecord;
        return $this;
    }
}
