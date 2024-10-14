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

final class CustomConfigurationEvent
{
    private int $configurationUid;
    private ?Configuration $configurationRecord;
    
    public function __construct(int $configurationUid, ?Configuration $configurationRecord)
    {
        $this->configurationUid = $configurationUid;
        $this->configurationRecord = $configurationRecord;
    }

    /**
     * @return int
     */
    public function getConfigurationUid(): int
    {
        return $this->configurationUid;
    }

    /**
     * @return Configuration|null
     */
    public function getConfigurationRecord(): ?Configuration
    {
        return $this->configurationRecord;
    }

    /**
     * @param Configuration|null $configurationRecord
     * @return $this
     */
    public function setConfigurationRecord(?Configuration $configurationRecord): self
    {
        $this->configurationRecord = $configurationRecord;
        return $this;
    }
}
