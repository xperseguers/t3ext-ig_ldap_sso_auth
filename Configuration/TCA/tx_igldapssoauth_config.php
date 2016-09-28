<?php
return array(
    'ctrl' => array(
        'title' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config',
        'label' => 'name',
        'sortby' => 'sorting',
        'adminOnly' => 1,
        'rootLevel' => 1,
        'dividers2tabs' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'delete' => 'deleted',
        'enablecolumns' => array(
            'disabled' => 'hidden',
        ),
        'requestUpdate' => 'ldap_server',
        'iconfile' => 'EXT:ig_ldap_sso_auth/Resources/Public/Icons/icon_tx_igldapssoauth_config.png',
    ),
    'interface' => array(
        'showRecordFieldList' => 'hidden, name, domains,
                        ldap_server, ldap_charset, ldap_host, ldap_port, ldap_tls, ldap_ssl, ldap_binddn,
                        ldap_password, group_membership,
                        be_users_basedn, be_users_filter, be_users_mapping,
                        be_groups_basedn, be_groups_filter, be_groups_mapping, be_groups_required, be_groups_assigned,
                        be_groups_admin,
                        fe_users_basedn, fe_users_filter, fe_users_mapping,
                        fe_groups_basedn, fe_groups_filter, fe_groups_mapping, fe_groups_required, fe_groups_assigned',
    ),
    'types' => array(
        '1' => array(
            'showitem' => '
                    --div--;GENERAL,
                        hidden, name, domains,
                    --div--;LDAP,
                        ldap_server;;1,
                    --palette--;LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:palette.connection;connection,
                        ldap_binddn, ldap_password, group_membership,
                    --div--;FE_USERS,
                        fe_users_basedn, fe_users_filter, fe_users_mapping, fe_groups_required, fe_groups_assigned,
                    --div--;FE_GROUPS,
                        fe_groups_basedn, fe_groups_filter, fe_groups_mapping,
                    --div--;BE_USERS,
                        be_users_basedn, be_users_filter, be_users_mapping, be_groups_required, be_groups_assigned,
                    --div--;BE_GROUPS,
                        be_groups_basedn, be_groups_filter, be_groups_mapping, be_groups_admin'
        ),
    ),
    'palettes' => array(
        '1' => array(
            'showitem' => 'ldap_charset'
        ),
        'connection' => array(
            'showitem' => 'ldap_host, ldap_port, ldap_tls, ldap_ssl',
            'canNotCollapse' => 1,
        ),
    ),
    'columns' => array(
        'hidden' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
            'config' => array(
                'type' => 'check',
                'default' => '0'
            )
        ),
        'name' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.name',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'required,trim',
            )
        ),
        'domains' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.domains',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'sys_domain',
                'size' => 6,
                'minitems' => 0,
                'maxitems' => 30,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    ),
                ),
            )
        ),
        'ldap_server' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_server',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array(
                        'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_server.I.0',
                        \Causal\IgLdapSsoAuth\Library\Configuration::SERVER_OPENLDAP
                    ),
                    array(
                        'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_server.I.1',
                        \Causal\IgLdapSsoAuth\Library\Configuration::SERVER_ACTIVE_DIRECTORY
                    ),
                ),
                'size' => 1,
                'maxitems' => 1,
            )
        ),
        'ldap_charset' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_charset',
            'config' => array(
                'type' => 'input',
                'size' => '10',
                'max' => '255',
                'eval' => 'trim',
                'default' => 'utf-8',
            )
        ),
        'ldap_host' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_host',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'trim',
            )
        ),
        'ldap_port' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_port',
            'config' => array(
                'type' => 'input',
                'size' => '5',
                'max' => '5',
                'eval' => 'int,trim',
                'default' => '389',
            )
        ),
        'ldap_tls' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_tls',
            'config' => array(
                'type' => 'check',
                'default' => '0',
            )
        ),
        'ldap_ssl' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_ssl',
            'config' => array(
                'type' => 'check',
                'default' => '0',
            )
        ),
        'ldap_binddn' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_binddn',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
            )
        ),
        'ldap_password' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.ldap_password',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'max' => '255',
                'eval' => 'password',
            )
        ),
        'group_membership' => array(
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.group_membership',
            'config' => array(
                'type' => 'select',
                'items' => array(
                    array(
                        'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.group_membership.I.1',
                        \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_GROUP,
                        'EXT:ig_ldap_sso_auth/Resources/Public/Icons/selicon_group_membership_1.png'
                    ),
                    array(
                        'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.group_membership.I.2',
                        \Causal\IgLdapSsoAuth\Library\Configuration::GROUP_MEMBERSHIP_FROM_MEMBER,
                        'EXT:ig_ldap_sso_auth/Resources/Public/Icons/selicon_group_membership_2.png'
                    ),
                ),
                'minitems' => 1,
                'maxitems' => 1,
                'default' => 1,
                'showIconTable' => true,
            ),
        ),
        'be_users_basedn' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_users_basedn',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'be_users_filter' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_users_filter',
            'config' => array(
                'type' => 'text',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'be_users_mapping' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_users_mapping',
            'config' => array(
                'type' => 'text',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'be_groups_basedn' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_basedn',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'be_groups_filter' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_filter',
            'config' => array(
                'type' => 'text',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'be_groups_mapping' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_mapping',
            'config' => array(
                'type' => 'text',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'be_groups_required' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_required',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND be_groups.tx_igldapssoauth_dn<>\'\' AND be_groups.tx_igldapssoauth_dn IS NOT NULL ORDER BY be_groups.title',
                'size' => 6,
                'minitems' => 0,
                'maxitems' => 30,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    ),
                ),
            )
        ),
        'be_groups_assigned' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_assigned',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'AND (be_groups.tx_igldapssoauth_dn=\'\' OR be_groups.tx_igldapssoauth_dn IS NULL) ORDER BY be_groups.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 30,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    ),
                ),
            )
        ),
        'be_groups_admin' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.be_groups_admin',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'be_groups',
                'foreign_table_where' => 'ORDER BY be_groups.title',
                'size' => 6,
                'minitems' => 0,
                'maxitems' => 30,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    ),
                ),
            )
        ),
        'fe_users_basedn' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_users_basedn',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'fe_users_filter' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_users_filter',
            'config' => array(
                'type' => 'text',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'fe_users_mapping' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_users_mapping',
            'config' => array(
                'type' => 'text',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'fe_groups_basedn' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_basedn',
            'config' => array(
                'type' => 'input',
                'size' => '30',
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'fe_groups_filter' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_filter',
            'config' => array(
                'type' => 'text',
                'rows' => 3,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'fe_groups_mapping' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_mapping',
            'config' => array(
                'type' => 'text',
                'rows' => 8,
                'cols' => 30,
                'eval' => 'trim',
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'userFunc',
                        'userFunc' => 'Causal\\IgLdapSsoAuth\\Tca\\Form\\SuggestWizard->render',
                    ),
                ),
            )
        ),
        'fe_groups_required' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_required',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'AND fe_groups.tx_igldapssoauth_dn<>\'\' AND fe_groups.tx_igldapssoauth_dn IS NOT NULL ORDER BY fe_groups.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    ),
                ),
            )
        ),
        'fe_groups_assigned' => array(
            'exclude' => 1,
            'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:tx_igldapssoauth_config.fe_groups_assigned',
            'config' => array(
                'type' => 'select',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'AND (fe_groups.tx_igldapssoauth_dn=\'\' OR fe_groups.tx_igldapssoauth_dn IS NULL) ORDER BY fe_groups.title',
                'size' => 10,
                'minitems' => 0,
                'maxitems' => 99,
                'wizards' => array(
                    'suggest' => array(
                        'type' => 'suggest'
                    ),
                ),
            )
        ),
    ),
);
