<?php

########################################################################
# Extension Manager/Repository config file for ext: "ig_ldap_sso_auth"
#
# Auto generated 29-01-2009 08:50
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'LDAP / SSO Authentication',
	'description' => 'Enable LDAP/SSO authentication service.',
	'category' => 'services',
	'shy' => 0,
	'version' => '1.0.3',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Michael Gagnon,Michael Miousse',
	'author_email' => 'michael.miousse@infoglobe.ca',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:30:{s:21:"ext_conf_template.txt";s:4:"ce18";s:12:"ext_icon.gif";s:4:"6187";s:13:"ext_icon1.gif";s:4:"fd0c";s:17:"ext_localconf.php";s:4:"6153";s:14:"ext_tables.php";s:4:"05e1";s:14:"ext_tables.sql";s:4:"9bed";s:28:"ext_typoscript_constants.txt";s:4:"f6be";s:24:"ext_typoscript_setup.txt";s:4:"5b0b";s:13:"mod1/conf.php";s:4:"ebe5";s:14:"mod1/index.php";s:4:"4073";s:35:"lib/class.tx_igldapssoauth_auth.php";s:4:"99e0";s:37:"lib/class.tx_igldapssoauth_config.php";s:4:"61c1";s:35:"lib/class.tx_igldapssoauth_ldap.php";s:4:"4c95";s:41:"lib/class.tx_igldapssoauth_ldap_group.php";s:4:"f320";s:40:"lib/class.tx_igldapssoauth_ldap_user.php";s:4:"e7f3";s:42:"lib/class.tx_igldapssoauth_typo3_group.php";s:4:"1425";s:41:"lib/class.tx_igldapssoauth_typo3_user.php";s:4:"4639";s:34:"pi1/class.tx_igldapssoauth_pi1.php";s:4:"fee7";s:42:"pi1/class.tx_igldapssoauth_pi1_wizicon.php";s:4:"1551";s:14:"doc/manual.pdf";s:4:"7e61";s:14:"doc/manual.sxw";s:4:"f161";s:11:"res/cas.png";s:4:"edea";s:27:"res/cas_authentication.html";s:4:"175a";s:36:"res/icon_tx_igldapssoauth_config.png";s:4:"45e5";s:24:"res/locallang_csh_db.xml";s:4:"af20";s:20:"res/locallang_db.xml";s:4:"f80c";s:22:"res/locallang_mod1.xml";s:4:"d788";s:21:"res/locallang_pi1.xml";s:4:"278b";s:11:"res/tca.php";s:4:"603b";s:34:"sv1/class.tx_igldapssoauth_sv1.php";s:4:"e359";}',
);

?>