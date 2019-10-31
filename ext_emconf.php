<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ig_ldap_sso_auth".
 *
 * Auto generated 26-05-2015 09:00
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = [
    'title' => 'LDAP / SSO Authentication',
    'description' => 'This extension provides LDAP support for TYPO3 by delegating the authentication of frontend and/or backend users to the centrally-managed directory of your organization. It fully supports OpenLDAP and Active Directory and is capable of connecting securely to the authentication server using either TLS or SSL (ldaps://).
In case of use in an intranet environment, this extension is a perfect match since it natively brings Single Sign-On (SSO) capability to TYPO3 without any complex configuration.',
    'category' => 'services',
    'shy' => 0,
    'version' => '3.4.0',
    'dependencies' => '',
    'conflicts' => '',
    'priority' => '',
    'loadOrder' => '',
    'module' => '',
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'modify_tables' => 'be_groups,be_users,fe_groups,fe_users',
    'clearcacheonload' => 0,
    'lockType' => '',
    'author' => 'Xavier Perseguers',
    'author_email' => 'xavier@causal.ch',
    'author_company' => '',
    'CGLcompliance' => '',
    'CGLcompliance_note' => '',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-7.3.99',
            'typo3' => '8.7.0-10.2.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
    '_md5_values_when_last_written' => '',
    'suggests' => [],
    'autoload' => [
        'psr-4' => ['Causal\\IgLdapSsoAuth\\' => 'Classes']
    ],
];
