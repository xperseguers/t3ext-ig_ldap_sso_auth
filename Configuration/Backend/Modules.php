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

use Causal\IgLdapSsoAuth\Controller\ModuleController;

return [
    'txigldapssoauthM1' => [
        'parent' => 'system',
        'position' => ['top'],
        'access' => 'admin',
        'workspaces' => 'live',
        'path' => '/module/ig-ldap-sso-auth/management',
        'labels' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf',
        'extensionName' => 'IgLdapSsoAuth',
        'iconIdentifier' => 'extensions-ig_ldap_sso_auth-module',
        'controllerActions' => [
            ModuleController::class => implode(',', [
                'index',
                'status',
                'search',
                'importFrontendUsers', 'importBackendUsers',
                'importFrontendUserGroups', 'importBackendUserGroups',
            ])
        ],
    ],
];
