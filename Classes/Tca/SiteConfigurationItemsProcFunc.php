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

namespace Causal\IgLdapSsoAuth\Tca;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SiteConfigurationItemsProcFunc
 * @package Causal\IgLdapSsoAuth\Tca
 */
class SiteConfigurationItemsProcFunc
{
    /**
     * @var SiteFinder
     */
    protected $siteFinder;

    /**
     * SiteConfigurationItemsProcFunc constructor.
     */
    public function __construct()
    {
        $this->siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
    }

    /**
     * Fills the item list with pairs of site identifier and host for each available site config.
     *
     * @param array $config
     */
    public function getSites(array &$config): void
    {
        $allSites = $this->siteFinder->getAllSites();

        $config['items'] = array_map(
            static function (Site $site) {
                $host = $site->getBase()->getHost();
                if (empty($host)) {
                    $host = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
                }
                return [
                    // displayed value
                    $host,
                    // stored value
                    $site->getIdentifier()
                ];
            },
            $allSites
        );
    }
}