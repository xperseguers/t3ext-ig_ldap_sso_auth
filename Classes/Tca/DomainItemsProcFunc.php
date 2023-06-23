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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class DomainItemsProcFunc
 * @package Causal\IgLdapSsoAuth\Tca
 */
class DomainItemsProcFunc
{
    /**
     * Fills the item list with available sys_domain records.
     *
     * @param array $config
     */
    public function getDomains(array &$config): void
    {
        $rows = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_domain')
            ->select(
                ['uid', 'domainName'],
                'sys_domain',
                [],
                [],
                [
                    'domainName' => 'ASC',
                ]
            )
            ->fetchAllAssociative();

        $config['items'] = array_map(
            static function (array $row) {
                return [
                    // displayed value
                    $row['domainName'],
                    // stored value
                    $row['uid']
                ];
            },
            $rows
        );
    }
}