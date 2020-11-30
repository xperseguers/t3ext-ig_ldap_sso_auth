.. include:: ../../Includes.rst.txt
.. _development-extramergefield:

Custom processing for mapping
=============================

A hook lets you create custom
:ref:`mapping markers <admin-manual-feusers-mapping-custommarker>`::

	name = {hookName|myCustomHook;param1|value1;param2|<cn>}


.. _development-extramergefield-syntax:

Syntax
------

- Your custom marker should be surrounded by opening and closing curly braces
  ``{...}``.
- Parameters are delimited by semi-colons (``;``).
- A parameter is a pair of parameter name and parameter value delimited by a
  pipe (``|``).
- First parameter should always be ``hookName``.
- Parameter value may contain references to LDAP attributes using the standard
  syntax with ``<>``.


.. _development-extramergefield-registration:

Registration
------------

In your extension (in the :file:`ext_localconf.php` file), register the hook
using a code like:

.. code-block:: php

	$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraMergeField']['myCustomHook']
		= 'EXT:extension/Path/To/Class/ClassName.php:VendorName\\ClassName';

where ``myCustomHook`` corresponds to one of the ``hookName`` parameters you use
in your mapping.


Your class has to implement a method ``extraMerge`` which will receive the
following arguments:

$field
	The name of the corresponding field in the mapping.

$typo3Record
	An array containing the current user/group record from TYPO3.

$ldapRecord
	An array containing the current user/group record from LDAP.

$ldapAttributes
	An array containing LDAP attributes found in your mapping definition.

$hookParameters
	An associative array with all hook parameters found in your mapping
	definition (thus including ``hookName``).

Your method should simply return a string for the value to be given to
``$field``.

.. note::

    It is worth mentioning that in ``$hookParameters`` the LDAP attributes are
    not replaced yet. And this can quickly be done by using::

        \Causal\IgLdapSsoAuth\Library\Authentication::replaceLdapMarkers($hookParameters['param2'], $ldapRecord);
