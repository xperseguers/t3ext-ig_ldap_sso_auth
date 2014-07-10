<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$TCA['tx_igldapssoauth_config'] = array(
	'ctrl' => $TCA['tx_igldapssoauth_config']['ctrl'],
	'interface' => array(
		'showRecordFieldList' => 'hidden, name, domains,
						ldap_server, ldap_protocol, ldap_charset, ldap_host, ldap_port, ldap_tls, ldap_binddn,
						ldap_password,
						be_users_basedn, be_users_filter, be_users_mapping,
						be_groups_basedn, be_groups_filter, be_groups_mapping, be_groups_required, be_groups_assigned,
						be_groups_admin,
						fe_users_basedn, fe_users_filter, fe_users_mapping,
						fe_groups_basedn, fe_groups_filter, fe_groups_mapping, fe_groups_required, fe_groups_assigned,
						cas_host, cas_port, cas_logout_url',
	),
	'types' => array(
		'1' => array(
			'showitem' => '
					--div--;GENERAL,
						name, domains,
					--div--;LDAP,
						ldap_server, ldap_protocol, ldap_charset,
					--palette--;LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:palette.connection;connection,
						ldap_binddn, ldap_password,
					--div--;BE_USERS,
						be_users_basedn, be_users_filter, be_users_mapping, be_groups_required, be_groups_assigned,
					--div--;BE_GROUPS,
						be_groups_basedn, be_groups_filter, be_groups_mapping, be_groups_admin,
					--div--;FE_USERS,
						fe_users_basedn, fe_users_filter, fe_users_mapping, fe_groups_required, fe_groups_assigned,
					--div--;FE_GROUPS,
						fe_groups_basedn, fe_groups_filter, fe_groups_mapping,
					--div--;CAS,
						cas_host,cas_uri,cas_service_url, cas_port,cas_logout_url'
		),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
		'connection' => array(
			'showitem' => 'ldap_host, ldap_port, ldap_tls',
			'canNotCollapse' => 1,
		),
	),
	'columns' => array(
		'hidden' => array(
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array(
				'type'    => 'check',
				'default' => '0'
			)
		),
		'name' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.name',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'required,trim',
			)
		),
		'domains' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.domains',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'sys_domain',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 30,
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
				),
			)
		),
		'ldap_server' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_server',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_server.I.0', '0'),
					array('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_server.I.1', '1'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'ldap_protocol' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_protocol',
			'config' => array(
				'type' => 'select',
				'items' => array(
					array('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_protocol.I.0', '3'),
					array('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_protocol.I.1', '2'),
				),
				'size' => 1,
				'maxitems' => 1,
			)
		),
		'ldap_charset' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_charset',
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
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_host',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'ldap_port' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_port',
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
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_tls',
			'config' => array(
				'type' => 'check',
				'default' => '0',
			)
		),
		'ldap_binddn' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_binddn',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'ldap_password' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.ldap_password',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'password',
			)
		),
		'be_users_basedn' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_users_basedn',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'be_users_filter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_users_filter',
			'config' => array(
				'type' => 'text',
				'rows' => 3,
				'cols' => 30,
			)
		),
		'be_users_mapping' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_users_mapping',
			'config' => array(
				'type' => 'text',
				'rows' => 8,
				'cols' => 30,
			)
		),
		'be_groups_basedn' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups_basedn',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'be_groups_filter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups_filter',
			'config' => array(
				'type' => 'text',
				'rows' => 3,
				'cols' => 30,
			)
		),
		'be_groups_mapping' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups_mapping',
			'config' => array(
				'type' => 'text',
				'rows' => 8,
				'cols' => 30,
			)
		),
		'be_groups_required' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups_required',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'AND be_groups.tx_igldapssoauth_dn<>\'\' ORDER BY be_groups.title',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 30,
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
				),
			)
		),
		'be_groups_assigned' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups_assigned',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'AND be_groups.tx_igldapssoauth_dn=\'\' ORDER BY be_groups.title',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 30,
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
				),
			)
		),
		'be_groups_admin' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.be_groups_admin',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'be_groups',
				'foreign_table_where' => 'ORDER BY be_groups.title',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 30,
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
				),
			)
		),
		'fe_users_basedn' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_users_basedn',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'fe_users_filter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_users_filter',
			'config' => array(
				'type' => 'text',
				'rows' => 3,
				'cols' => 30,
			)
		),
		'fe_users_mapping' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_users_mapping',
			'config' => array(
				'type' => 'text',
				'rows' => 8,
				'cols' => 30,
			)
		),
		'fe_groups_basedn' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_groups_basedn',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'eval' => 'trim',
			)
		),
		'fe_groups_filter' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_groups_filter',
			'config' => array(
				'type' => 'text',
				'rows' => 3,
				'cols' => 30,
			)
		),
		'fe_groups_mapping' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_groups_mapping',
			'config' => array(
				'type' => 'text',
				'rows' => 8,
				'cols' => 30,
			)
		),
		'fe_groups_required' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_groups_required',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'AND fe_groups.tx_igldapssoauth_dn<>\'\' ORDER BY fe_groups.title',
				'size' => 6,
				'minitems' => 0,
				'maxitems' => 30,
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
				),
			)
		),
		'fe_groups_assigned' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.fe_groups_assigned',
			'config' => array(
				'type' => 'select',
				'foreign_table' => 'fe_groups',
				'foreign_table_where' => 'AND fe_groups.tx_igldapssoauth_dn=\'\' ORDER BY fe_groups.title',
				'size' => 10,
				'minitems' => 0,
				'maxitems' => 30,
				'wizards' => array(
					'_PADDING' => 4,
					'_VERTICAL' => 1,
					'suggest' => array(
						'type' => 'suggest'
					),
				),
			)
		),
		'cas_host' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.cas_host',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'cas_uri' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.cas_uri',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'cas_service_url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.cas_service_url',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
			)
		),
		'cas_port' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.cas_port',
			'config' => array(
				'type' => 'input',
				'size' => '5',
				'max' => '5',
				'eval' => 'int,trim',
			)
		),
		'cas_logout_url' => array(
			'exclude' => 1,
			'label' => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:tx_igldapssoauth_config.cas_logout_url',
			'config' => array(
				'type' => 'input',
				'size' => '30',
				'max' => '255',
				'eval' => 'trim',
			)
		),
	),
);
