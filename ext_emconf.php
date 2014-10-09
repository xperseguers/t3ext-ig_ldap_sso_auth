<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "ig_ldap_sso_auth".
 *
 * Auto generated 25-08-2014 21:02
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'LDAP / SSO Authentication',
	'description' => 'Authenticate frontend and/or backend users using LDAP. Support for OpenLDAP, Active Directory and Novell eDirectory. Handle TLS and SSL (ldaps://)',
	'category' => 'services',
	'shy' => 0,
	'version' => '2.0.0-dev',
	'dependencies' => '',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'mod1',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => 'be_groups,be_users,fe_groups,fe_users',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Xavier Perseguers, Team Infoglobe',
	'author_email' => 'xavier@typo3.org',
	'author_company' => '',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-6.2.99',
			'php' => '5.2.0-5.6.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:102:{s:20:"class.ext_update.php";s:4:"8837";s:16:"ext_autoload.php";s:4:"61d0";s:21:"ext_conf_template.txt";s:4:"a8df";s:12:"ext_icon.gif";s:4:"6a85";s:12:"ext_icon.png";s:4:"4697";s:15:"ext_icon@2x.png";s:4:"4b00";s:17:"ext_localconf.php";s:4:"369f";s:14:"ext_tables.php";s:4:"5c88";s:14:"ext_tables.sql";s:4:"87d2";s:19:"Classes/CAS/CAS.php";s:4:"17db";s:22:"Classes/CAS/client.php";s:4:"3742";s:32:"Classes/CAS/domxml-php4-php5.php";s:4:"c680";s:33:"Classes/CAS/PGTStorage/pgt-db.php";s:4:"ccdf";s:35:"Classes/CAS/PGTStorage/pgt-file.php";s:4:"e439";s:35:"Classes/CAS/PGTStorage/pgt-main.php";s:4:"53cb";s:33:"Classes/CAS/languages/english.php";s:4:"ade3";s:32:"Classes/CAS/languages/french.php";s:4:"6ac0";s:32:"Classes/CAS/languages/german.php";s:4:"c536";s:31:"Classes/CAS/languages/greek.php";s:4:"fc88";s:34:"Classes/CAS/languages/japanese.php";s:4:"2217";s:35:"Classes/CAS/languages/languages.php";s:4:"eab4";s:28:"Classes/EM/Configuration.php";s:4:"4ab3";s:47:"Classes/Hooks/DatabaseRecordListIconUtility.php";s:4:"3b5f";s:29:"Classes/Hooks/DataHandler.php";s:4:"46c5";s:39:"Classes/Hooks/SetupModuleController.php";s:4:"020d";s:24:"Classes/Library/Auth.php";s:4:"f7e4";s:26:"Classes/Library/Config.php";s:4:"929e";s:24:"Classes/Library/Ldap.php";s:4:"e78a";s:29:"Classes/Library/LdapGroup.php";s:4:"aaf6";s:28:"Classes/Library/LdapUser.php";s:4:"f251";s:45:"Classes/Library/SchedulerSynchroniseusers.php";s:4:"3a40";s:30:"Classes/Library/Typo3Group.php";s:4:"2d44";s:29:"Classes/Library/Typo3User.php";s:4:"e57b";s:23:"Classes/Service/Sv1.php";s:4:"e65a";s:28:"Classes/Tca/Form/Suggest.php";s:4:"32bc";s:25:"Classes/Utility/Debug.php";s:4:"5844";s:24:"Classes/Utility/Ldap.php";s:4:"ab18";s:32:"Classes/Utility/Notification.php";s:4:"2449";s:28:"Configuration/TCA/Config.php";s:4:"c0a6";s:38:"Configuration/TypoScript/constants.txt";s:4:"4b4b";s:34:"Configuration/TypoScript/setup.txt";s:4:"cb88";s:26:"Documentation/Includes.txt";s:4:"ef74";s:23:"Documentation/Index.rst";s:4:"d078";s:23:"Documentation/Links.rst";s:4:"8b43";s:26:"Documentation/Settings.yml";s:4:"319e";s:25:"Documentation/Targets.rst";s:4:"e135";s:41:"Documentation/AdministratorManual/Cas.rst";s:4:"3c2f";s:45:"Documentation/AdministratorManual/General.rst";s:4:"e14a";s:44:"Documentation/AdministratorManual/Groups.rst";s:4:"56fd";s:43:"Documentation/AdministratorManual/Index.rst";s:4:"90cf";s:42:"Documentation/AdministratorManual/Ldap.rst";s:4:"71d6";s:57:"Documentation/AdministratorManual/SampleConfiguration.rst";s:4:"6c8c";s:43:"Documentation/AdministratorManual/Users.rst";s:4:"6452";s:27:"Documentation/Faq/Index.rst";s:4:"903a";s:32:"Documentation/Glossary/Index.rst";s:4:"bea7";s:37:"Documentation/Images/blank-record.png";s:4:"cf8c";s:48:"Documentation/Images/configuration-be-groups.png";s:4:"ed87";s:47:"Documentation/Images/configuration-fe-users.png";s:4:"7fa1";s:43:"Documentation/Images/configuration-ldap.png";s:4:"5249";s:35:"Documentation/Images/dit-dn-rdn.png";s:4:"1f9c";s:35:"Documentation/Images/new-record.png";s:4:"c194";s:38:"Documentation/Images/search-wizard.png";s:4:"ef81";s:31:"Documentation/Images/status.png";s:4:"c007";s:36:"Documentation/Introduction/Index.rst";s:4:"9865";s:35:"Documentation/UsersManual/Index.rst";s:4:"21bc";s:56:"Resources/Private/DebugScripts/ldap_active_directory.php";s:4:"bf8a";s:49:"Resources/Private/DebugScripts/ldap_open_ldap.php";s:4:"da76";s:40:"Resources/Private/Language/locallang.xml";s:4:"d3e8";s:47:"Resources/Private/Language/locallang_csh_db.xml";s:4:"84ea";s:43:"Resources/Private/Language/locallang_db.xml";s:4:"505e";s:45:"Resources/Private/Language/locallang_mod1.xml";s:4:"b439";s:44:"Resources/Private/Language/locallang_pi1.xml";s:4:"f2e9";s:51:"Resources/Private/Templates/cas_authentication.html";s:4:"175a";s:50:"Resources/Private/Templates/filter_be_groups_0.txt";s:4:"ad90";s:50:"Resources/Private/Templates/filter_be_groups_1.txt";s:4:"57df";s:49:"Resources/Private/Templates/filter_be_users_0.txt";s:4:"35ef";s:49:"Resources/Private/Templates/filter_be_users_1.txt";s:4:"7b00";s:50:"Resources/Private/Templates/filter_fe_groups_0.txt";s:4:"ad90";s:50:"Resources/Private/Templates/filter_fe_groups_1.txt";s:4:"57df";s:49:"Resources/Private/Templates/filter_fe_users_0.txt";s:4:"35ef";s:49:"Resources/Private/Templates/filter_fe_users_1.txt";s:4:"7b00";s:49:"Resources/Private/Templates/mapping_be_groups.txt";s:4:"0612";s:50:"Resources/Private/Templates/mapping_be_users_0.txt";s:4:"3439";s:50:"Resources/Private/Templates/mapping_be_users_1.txt";s:4:"621f";s:49:"Resources/Private/Templates/mapping_fe_groups.txt";s:4:"b2a8";s:50:"Resources/Private/Templates/mapping_fe_users_0.txt";s:4:"975e";s:50:"Resources/Private/Templates/mapping_fe_users_1.txt";s:4:"7b8a";s:30:"Resources/Public/Icons/cas.png";s:4:"edea";s:32:"Resources/Public/Icons/cross.png";s:4:"4249";s:55:"Resources/Public/Icons/icon_tx_igldapssoauth_config.png";s:4:"45e5";s:46:"Resources/Public/Icons/overlay-ldap-record.png";s:4:"f384";s:53:"Resources/Public/Icons/selicon_group_membership_1.png";s:4:"2e1f";s:53:"Resources/Public/Icons/selicon_group_membership_2.png";s:4:"f2ea";s:31:"Resources/Public/Icons/tick.png";s:4:"c9b5";s:13:"mod1/conf.php";s:4:"13da";s:14:"mod1/index.php";s:4:"f11f";s:22:"mod1/mod_template.html";s:4:"cdfa";s:29:"mod1/mod_template_v45-61.html";s:4:"77c3";s:34:"pi1/class.tx_igldapssoauth_pi1.php";s:4:"f206";s:42:"pi1/class.tx_igldapssoauth_pi1_wizicon.php";s:4:"32fd";s:20:"static/constants.txt";s:4:"d4fa";s:16:"static/setup.txt";s:4:"603b";}',
	'suggests' => array(
	),
);

?>