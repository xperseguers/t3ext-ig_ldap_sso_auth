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

use Causal\IgLdapSsoAuth\Domain\Model\Configuration;
use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface;
use TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter;

/**
 * Converter which transforms simple types to \Causal\IgLdapSsoAuth\Domain\Model\Configuration.
 */
class ConfigurationConverter extends AbstractTypeConverter implements SingletonInterface
{
    protected array $sourceTypes = ['integer', 'string'];

    protected string $targetType = Configuration::class;

    protected int $priority = 10;

    protected ConfigurationRepository $configurationRepository;

    public function __construct(ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }

    /**
     * @param string|int $source TODO: should actually be type-hinted as int
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param PropertyMappingConfigurationInterface|null $configuration
     * @return Configuration|null
     */
    public function convertFrom(
        $source,
        string $targetType,
        array $convertedChildProperties = [],
        ?PropertyMappingConfigurationInterface $configuration = null
    ): ?Configuration
    {
        return $this->configurationRepository->findByUid((int)$source);
    }
}
