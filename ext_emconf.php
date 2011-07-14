<?php

########################################################################
# Extension Manager/Repository config file for ext "ig_ldap_sso_auth".
#
# Auto generated 14-07-2011 15:37
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'LDAP / SSO Authentication',
	'description' => 'Enable LDAP/SSO authentication service.',
	'category' => 'services',
	'shy' => 0,
	'version' => '1.1.0',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'beta',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'be_groups,be_users,fe_groups,fe_users',
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
	'_md5_values_when_last_written' => 'a:52:{s:9:"ChangeLog";s:4:"d478";s:39:"class.tx_igldapssoauth_emconfhelper.php";s:4:"a562";s:16:"ext_autoload.php";s:4:"0128";s:21:"ext_conf_template.txt";s:4:"07a8";s:12:"ext_icon.gif";s:4:"6187";s:13:"ext_icon1.gif";s:4:"fd0c";s:17:"ext_localconf.php";s:4:"85a3";s:14:"ext_tables.php";s:4:"35de";s:14:"ext_tables.sql";s:4:"9961";s:13:"locallang.xml";s:4:"0619";s:53:"classes/utility/class.tx_igldapssoauth_utility_db.php";s:4:"144f";s:56:"classes/utility/class.tx_igldapssoauth_utility_debug.php";s:4:"20ba";s:55:"classes/utility/class.tx_igldapssoauth_utility_ldap.php";s:4:"56f9";s:31:"debug/ldap_active_directory.php";s:4:"1907";s:24:"debug/ldap_open_ldap.php";s:4:"5c79";s:14:"doc/manual.pdf";s:4:"37f2";s:14:"doc/manual.sxw";s:4:"b405";s:21:"framework/CAS/CAS.php";s:4:"e980";s:24:"framework/CAS/client.php";s:4:"30f4";s:34:"framework/CAS/domxml-php4-php5.php";s:4:"c680";s:35:"framework/CAS/PGTStorage/pgt-db.php";s:4:"ccdf";s:37:"framework/CAS/PGTStorage/pgt-file.php";s:4:"e439";s:37:"framework/CAS/PGTStorage/pgt-main.php";s:4:"ec15";s:35:"framework/CAS/languages/english.php";s:4:"ade3";s:34:"framework/CAS/languages/french.php";s:4:"6ac0";s:34:"framework/CAS/languages/german.php";s:4:"c536";s:33:"framework/CAS/languages/greek.php";s:4:"fc88";s:36:"framework/CAS/languages/japanese.php";s:4:"2217";s:37:"framework/CAS/languages/languages.php";s:4:"eab4";s:35:"lib/class.tx_igldapssoauth_auth.php";s:4:"a068";s:37:"lib/class.tx_igldapssoauth_config.php";s:4:"13a4";s:35:"lib/class.tx_igldapssoauth_ldap.php";s:4:"f15b";s:41:"lib/class.tx_igldapssoauth_ldap_group.php";s:4:"2647";s:40:"lib/class.tx_igldapssoauth_ldap_user.php";s:4:"b569";s:57:"lib/class.tx_igldapssoauth_scheduler_synchroniseusers.php";s:4:"eedd";s:42:"lib/class.tx_igldapssoauth_typo3_group.php";s:4:"ebf3";s:41:"lib/class.tx_igldapssoauth_typo3_user.php";s:4:"c2d7";s:13:"mod1/conf.php";s:4:"ebe5";s:14:"mod1/index.php";s:4:"4107";s:34:"pi1/class.tx_igldapssoauth_pi1.php";s:4:"e990";s:42:"pi1/class.tx_igldapssoauth_pi1_wizicon.php";s:4:"a842";s:11:"res/cas.png";s:4:"edea";s:27:"res/cas_authentication.html";s:4:"175a";s:36:"res/icon_tx_igldapssoauth_config.png";s:4:"45e5";s:24:"res/locallang_csh_db.xml";s:4:"af20";s:20:"res/locallang_db.xml";s:4:"ab56";s:22:"res/locallang_mod1.xml";s:4:"d788";s:21:"res/locallang_pi1.xml";s:4:"278b";s:11:"res/tca.php";s:4:"796d";s:20:"static/constants.txt";s:4:"f6be";s:16:"static/setup.txt";s:4:"ec97";s:34:"sv1/class.tx_igldapssoauth_sv1.php";s:4:"324c";}',
	'suggests' => array(
	),
);

?>