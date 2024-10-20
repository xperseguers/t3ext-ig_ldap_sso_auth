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

use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'extensions-ig_ldap_sso_auth-overlay-ldap-record' => [
        'provider' => BitmapIconProvider::class,
        'source' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/overlay-ldap-record.png',
    ],
    'extensions-ig_ldap_sso_auth-module' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/module-ldap.svg',
    ]
];
