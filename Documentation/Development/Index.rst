.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _development:

Development
===========

This chapter describes some internals of the ig_ldap_sso_auth extension to let you extend it easily.


.. _development-hooks:

Hooks
-----

.. toctree::
	:maxdepth: 1

	Hooks/AttributesProcessing
	Hooks/ExtraDataProcessing
	Hooks/ExtraMergeField
	Hooks/GetGroupsProcessing

.. _development-continuous-deployment:

Continuous Deployment
---------------------

To support continuous deployment, it is possible to set a configuration via PHP through the following code either in
:file:`AdditionalConfiguration.php` or in :file:`ext_localconf.php` within your extension.

.. code-block:: php

   $ldapConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);

   $ldapConfig['useExtConfConfiguration'] = 1;
   $ldapConfig['configuration'] = [
       'uid' => '999',
       'pid' => '0',
       'tstamp' => '1521027046',
       'crdate' => '1521027046',
       'cruser_id' => '1',
       'deleted' => '0',
       'hidden' => '0',
       'name' => 'Some Name',
       'domains' => '',
       'ldap_server' => '0',
       'ldap_charset' => 'utf-8',
       'ldap_host' => 'ldap.some.domain',
       'ldap_port' => '389',
       'ldap_tls' => '0',
       'ldap_ssl' => '0',
       'ldap_binddn' => 'CN=example',
       'ldap_password' => 'example',
       'group_membership' => '1',
       'be_users_basedn' => '',
       'be_users_filter' => '',
       'be_users_mapping' => '',
       'be_groups_basedn' => '',
       'be_groups_filter' => '',
       'be_groups_mapping' => '',
       'be_groups_required' => '',
       'be_groups_assigned' => '',
       'be_groups_admin' => '',
       'fe_users_basedn' => '',
       'fe_users_filter' => '',
       'fe_users_mapping' => '',
       'fe_groups_basedn' => '',
       'fe_groups_filter' => '',
       'fe_groups_mapping' => '',
       'fe_groups_required' => '',
       'fe_groups_assigned' => '',
       'sorting' => '256',
   ];

   $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth'] = serialize($ldapConfig);

