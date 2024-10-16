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

return [
    \Causal\IgLdapSsoAuth\Domain\Model\BackendUser::class => [
        'tableName' => 'be_users',
        'properties' => [
            'userName' => [
                'fieldName' => 'username',
            ],
            'isAdministrator' => [
                'fieldName' => 'admin',
            ],
            'isDisabled' => [
                'fieldName' => 'disable',
            ],
            'realName' => [
                'fieldName' => 'realName',
            ],
            'startDateAndTime' => [
                'fieldName' => 'starttime',
            ],
            'endDateAndTime' => [
                'fieldName' => 'endtime',
            ],
            'lastLoginDateAndTime' => [
                'fieldName' => 'lastlogin',
            ],
        ],
    ],
    \Causal\IgLdapSsoAuth\Domain\Model\BackendUserGroup::class => [
        'tableName' => 'be_groups',
        'properties' => [
            'subGroups' => [
                'fieldName' => 'subgroup',
            ],
            'modules' => [
                'fieldName' => 'groupMods',
            ],
            'tablesListening' => [
                'fieldName' => 'tables_select',
            ],
            'tablesModify' => [
                'fieldName' => 'tables_modify',
            ],
            'pageTypes' => [
                'fieldName' => 'pagetypes_select',
            ],
            'allowedExcludeFields' => [
                'fieldName' => 'non_exclude_fields',
            ],
            'explicitlyAllowAndDeny' => [
                'fieldName' => 'explicit_allowdeny',
            ],
            'allowedLanguages' => [
                'fieldName' => 'allowed_languages',
            ],
            'workspacePermission' => [
                'fieldName' => 'workspace_perms',
            ],
            'databaseMounts' => [
                'fieldName' => 'db_mountpoints',
            ],
            'fileOperationPermissions' => [
                'fieldName' => 'file_permissions',
            ],
            'tsConfig' => [
                'fieldName' => 'TSconfig',
            ],
        ],
    ],
    \Causal\IgLdapSsoAuth\Domain\Model\FrontendUser::class => [
        'tableName' => 'fe_users',
    ],
    \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup::class => [
        'tableName' => 'fe_groups',
    ],
];
