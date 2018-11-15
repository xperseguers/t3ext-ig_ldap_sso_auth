<?php
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

/**
 * Converter which transforms simple types to \Causal\IgLdapSsoAuth\Domain\Model\Configuration.
 */
class ConfigurationConverter extends \TYPO3\CMS\Extbase\Property\TypeConverter\AbstractTypeConverter implements \TYPO3\CMS\Core\SingletonInterface
{

    /**
     * @var array<string>
     */
    protected $sourceTypes = ['integer', 'string'];

    /**
     * @var string
     */
    protected $targetType = 'Causal\\IgLdapSsoAuth\\Domain\\Model\\Configuration';

    /**
     * @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository
     * @inject
     */
    protected $configurationRepository;

    /**
     * @param \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository
     */
    public function injectConfigurationRepository(\Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository)
    {
        $this->configurationRepository = $configurationRepository;
    }


    /**
     * @param string|int $source
     * @param string $targetType
     * @param array $convertedChildProperties
     * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration
     * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration
     */
    public function convertFrom($source, $targetType, array $convertedChildProperties = [], \TYPO3\CMS\Extbase\Property\PropertyMappingConfigurationInterface $configuration = null)
    {
        return $this->configurationRepository->findByUid((int)$source);
    }

}
