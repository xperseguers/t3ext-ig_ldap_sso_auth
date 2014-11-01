.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual-import-tables:

Importing data to other tables
==============================

This extension does not directly provide a mechanism for mapping LDAP data to other tables than those related to groups
and users. However it is possible to make it so that some data is not mapped to the users table but passed instead to a
hook, enabling a separate extension to handle that data.

Defining such data is easy. With the usual mapping syntax::

	local_field = ldap_field

Any "local_field" that does not exist in the mapped users table will be considered "extra data". The "local_field" may
have any special syntax that suits your needs (for example "table.field") as long as it does not mess with the mappings
configuration itself.

All fields considered to be extra data will be mapped to LDAP values as any other field, but stored separately and
passed to the hook.

In your extension (in the :file:`ext_localconf.php` file), register with the hook using a code like:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraDataProcessing'][]
		= 'EXT:extension/Path/To/Class/ClassName.php:ClassName';

Your class has to implement the :code:`Tx_IgLdapSsoAuth_Utility_ExtraDataProcessorInterface` interface. This implies
implementing a method called :code:`processExtraData` which will receive the following arguments:

$userTable
  The name of the user table that was just handled
  (should normally be "fe_users" or "be_users").

$user
  Full user record plus the extra data to handle in your extension.
  The extra data is located in :code:`$user['__extraData']`. It
  is up to your extension to handle it as needed.

.. note:: This process exists only for users, not for groups.
