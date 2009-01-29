<?php
if (!defined ('TYPO3_MODE')) die ('Access denied.');

$TCA["tx_igldapssoauth_config"] = array (

	"ctrl" => $TCA["tx_igldapssoauth_config"]["ctrl"],
	"interface" => array (
		"showRecordFieldList" => "hidden,name,ldap_server,ldap_protocol,ldap_charset, ldap_host,ldap_port,ldap_binddn,ldap_password,be_users_basedn,be_users_filter,be_users_mapping,be_groups_basedn,be_groups_filter,be_groups_mapping,fe_users_basedn,fe_users_filter,fe_users_mapping,fe_groups_basedn,fe_groups_filter,fe_groups_mapping,cas_host,cas_port,cas_logout_url"

	),

	"feInterface" => $TCA["tx_igldapssoauth_config"]["feInterface"],
	"columns" => array (
		'hidden' => array (
			'exclude' => 1,
			'label'   => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
			'config'  => array (
				'type'    => 'check',
				'default' => '0'
			)
		),
		"name" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.name",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "required,trim",
			)
		),
		"ldap_server" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_server",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_server.I.0", "0"),
					Array("LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_server.I.1", "1"),
				),
				"size" => 1,
				"maxitems" => 1,
			)
		),
		"ldap_protocol" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_protocol",
			"config" => Array (
				"type" => "select",
				"items" => Array (
					Array("LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_protocol.I.0", "3"),
					Array("LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_protocol.I.1", "2"),
				),
				"size" => 1,
				"maxitems" => 1,
			)
		),
		"ldap_charset" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_charset",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "255",
				"eval" => "trim",
				"default" => "utf-8",
			)
		),
		"ldap_host" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_host",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "255",
				"eval" => "trim",
			)
		),
		"ldap_port" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_port",
			"config" => Array (
				"type" => "input",
				"size" => "5",
				"max" => "5",
				"eval" => "int,trim",
				"default" => "389",
			)
		),
		"ldap_binddn" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_binddn",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"ldap_password" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.ldap_password",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "255",
				"eval" => "password",
			)
		),
		"be_users_basedn" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.be_users_basedn",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"be_users_filter" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.be_users_filter",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"be_users_mapping" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.be_users_mapping",
			"config" => Array (
				"type" => "text",
				"eval" => "trim",
			)
		),
		"be_groups_basedn" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.be_groups_basedn",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"be_groups_filter" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.be_groups_filter",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"be_groups_mapping" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.be_groups_mapping",
			"config" => Array (
				"type" => "text",
				"eval" => "trim",
			)
		),
		"fe_users_basedn" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.fe_users_basedn",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"fe_users_filter" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.fe_users_filter",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"fe_users_mapping" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.fe_users_mapping",
			"config" => Array (
				"type" => "text",
				"eval" => "trim",
			)
		),
		"fe_groups_basedn" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.fe_groups_basedn",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"fe_groups_filter" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.fe_groups_filter",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"eval" => "trim",
			)
		),
		"fe_groups_mapping" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.fe_groups_mapping",
			"config" => Array (
				"type" => "text",
				"eval" => "trim",
			)
		),
		"cas_host" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.cas_host",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "255",
				"eval" => "trim",
			)
		),
		"cas_port" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.cas_port",
			"config" => Array (
				"type" => "input",
				"size" => "5",
				"max" => "5",
				"eval" => "int,trim",
			)
		),
		"cas_logout_url" => Array (
			"exclude" => 1,
			"label" => "LLL:EXT:ig_ldap_sso_auth/res/locallang_db.xml:tx_igldapssoauth_config.cas_logout_url",
			"config" => Array (
				"type" => "input",
				"size" => "30",
				"max" => "255",
				"eval" => "trim",
			)
		),
	),
	"types" => array (
		"0" => array("showitem" => "--div--;GENERAL,name, --div--;LDAP, ldap_server, ldap_protocol, ldap_charset, ldap_host, ldap_port, ldap_binddn, ldap_password,--div--;BE_USERS, be_users_basedn, be_users_filter, be_users_mapping, --div--;BE_GROUPS, be_groups_basedn, be_groups_filter, be_groups_mapping, --div--;FE_USERS,fe_users_basedn, fe_users_filter, fe_users_mapping, --div--;FE_GROUPS,fe_groups_basedn, fe_groups_filter, fe_groups_mapping, --div--;CAS, cas_host, cas_port,cas_logout_url")

	),
	"palettes" => array (
		"1" => array("showitem" => "")
	)
);

?>