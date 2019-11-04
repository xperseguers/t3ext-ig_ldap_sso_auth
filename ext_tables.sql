#
# Table structure for table 'tx_igldapssoauth_config'
#
CREATE TABLE tx_igldapssoauth_config (
	uid int(11) NOT NULL auto_increment,
	pid int(11) DEFAULT '0' NOT NULL,

	tstamp int(11) DEFAULT '0' NOT NULL,
	crdate int(11) DEFAULT '0' NOT NULL,
	cruser_id int(11) DEFAULT '0' NOT NULL,
	deleted tinyint(4) DEFAULT '0' NOT NULL,
	hidden tinyint(4) DEFAULT '0' NOT NULL,

	name varchar(255) DEFAULT '' NOT NULL,
	domains text NOT NULL,
	sites text NOT NULL,
	ldap_server int(11) DEFAULT '0' NOT NULL,
	ldap_charset varchar(255) DEFAULT '' NOT NULL,
	ldap_host varchar(255) DEFAULT '' NOT NULL,
	ldap_port int(11) DEFAULT '0' NOT NULL,
	ldap_tls tinyint(4) DEFAULT '0' NOT NULL,
	ldap_tls_reqcert  tinyint(4) DEFAULT '1' NOT NULL,
	ldap_ssl tinyint(4) DEFAULT '0' NOT NULL,
	ldap_binddn tinytext NOT NULL,
	ldap_password varchar(255) DEFAULT '' NOT NULL,
	group_membership tinyint(4) DEFAULT '0' NOT NULL,
	be_users_basedn varchar(255) DEFAULT '' NOT NULL,
	be_users_filter text,
	be_users_mapping text,
	be_groups_basedn varchar(255) DEFAULT '' NOT NULL,
	be_groups_filter text,
	be_groups_mapping text,
	be_groups_required varchar(100) DEFAULT '' NOT NULL,
	be_groups_assigned varchar(100) DEFAULT '' NOT NULL,
	be_groups_admin varchar(100) DEFAULT '' NOT NULL,
	fe_users_basedn varchar(255) DEFAULT '' NOT NULL,
	fe_users_filter text,
	fe_users_mapping text,
	fe_groups_basedn varchar(255) DEFAULT '' NOT NULL,
	fe_groups_filter text,
	fe_groups_mapping text,
	fe_groups_required text,
	fe_groups_assigned text,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
	tx_igldapssoauth_dn varchar(255) DEFAULT '' NOT NULL,

	KEY title (title),
	KEY tx_igldapssoauth_dn (tx_igldapssoauth_dn(64))
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_igldapssoauth_dn varchar(255) DEFAULT '' NOT NULL,
	tx_igldapssoauth_id int(11) unsigned DEFAULT '0' NOT NULL,

	KEY tx_igldapssoauth_dn (tx_igldapssoauth_dn(64))
);

#
# Table structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
	tx_igldapssoauth_dn varchar(255) DEFAULT '' NOT NULL,

	KEY title (title),
	KEY tx_igldapssoauth_dn (tx_igldapssoauth_dn(64))
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_igldapssoauth_dn varchar(255) DEFAULT '' NOT NULL,
	tx_igldapssoauth_id int(11) unsigned DEFAULT '0' NOT NULL,

	KEY tx_igldapssoauth_dn (tx_igldapssoauth_dn(64))
);
