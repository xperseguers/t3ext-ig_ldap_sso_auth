<?php

declare(strict_types=1);

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
