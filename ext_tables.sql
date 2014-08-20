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
	domains varchar(100) DEFAULT '' NOT NULL,
	cas_host varchar(255) DEFAULT '' NOT NULL,
	cas_port int(11) DEFAULT '0' NOT NULL,
	cas_logout_url varchar(255) DEFAULT '' NOT NULL,
	cas_service_url varchar(255) DEFAULT '' NOT NULL,
	ldap_server int(11) DEFAULT '0' NOT NULL,
	ldap_charset varchar(255) DEFAULT '' NOT NULL,
	ldap_protocol int(11) DEFAULT '0' NOT NULL,
	ldap_host varchar(255) DEFAULT '' NOT NULL,
	cas_uri varchar(255) DEFAULT '' NOT NULL,
	ldap_port int(11) DEFAULT '0' NOT NULL,
	ldap_tls tinyint(4) DEFAULT '0' NOT NULL,
	ldap_binddn tinytext NOT NULL,
	ldap_password varchar(255) DEFAULT '' NOT NULL,
	group_membership tinyint(4) DEFAULT '0' NOT NULL,
	be_users_basedn tinytext NOT NULL,
	be_users_filter text NOT NULL,
	be_users_mapping text NOT NULL,
	be_groups_basedn tinytext NOT NULL,
	be_groups_filter text NOT NULL,
	be_groups_mapping text NOT NULL,
	be_groups_required varchar(100) DEFAULT '' NOT NULL,
	be_groups_assigned varchar(100) DEFAULT '' NOT NULL,
	be_groups_admin varchar(100) DEFAULT '' NOT NULL,
	fe_users_basedn tinytext NOT NULL,
	fe_users_filter text NOT NULL,
	fe_users_mapping text NOT NULL,
	fe_groups_basedn tinytext NOT NULL,
	fe_groups_filter text NOT NULL,
	fe_groups_mapping text NOT NULL,
	fe_groups_required text DEFAULT '' NOT NULL,
	fe_groups_assigned text DEFAULT '' NOT NULL,
	sorting int(11) unsigned DEFAULT '0' NOT NULL,

	PRIMARY KEY (uid),
	KEY parent (pid)
);

#
# Table structure for table 'be_groups'
#
CREATE TABLE be_groups (
	tx_igldapssoauth_dn tinytext NOT NULL
);

#
# Table structure for table 'be_users'
#
CREATE TABLE be_users (
	tx_igldapssoauth_dn tinytext NOT NULL
);

#
# Table structure for table 'fe_groups'
#
CREATE TABLE fe_groups (
	tx_igldapssoauth_dn tinytext NOT NULL
);

#
# Table structure for table 'fe_users'
#
CREATE TABLE fe_users (
	tx_igldapssoauth_dn tinytext NOT NULL
);
