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

namespace Causal\IgLdapSsoAuth\Property\TypeConverter;

use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;

/**
 * Converter which transforms simple types to \Causal\IgLdapSsoAuth\Domain\Model\Configuration.
 */
class ConfigurationConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter
{
	/**
	 * ConfigurationConverter constructor.
	 *
	 * @param \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository
	 */
	public function __construct(protected ConfigurationRepository $configurationRepository)
	{}

    /**
     * @inheritDoc
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null): ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration
    {
        return $this->configurationRepository->findByUid((int)$source);
    }
}
