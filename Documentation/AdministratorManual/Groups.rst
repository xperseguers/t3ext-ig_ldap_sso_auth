.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual-fegroups:
.. _admin-manual-begroups:

FE_GROUPS and BE_GROUPS
-----------------------

The fourth and sixth tabs can be filled exactly the same way. The only difference between them is that FE_GROUPS stores
the configurations for the frontend LDAP user group association and BE_GROUPS stores the configurations for the backend
LDAP user group association.

You may only fill the sections you need; that is, FE_GROUPS if you need frontend authentication and BE_GROUPS if you
need backend authentication.

You should skip this entire section if you just want to validate the authentication and do not want to use any groups
coming from LDAP.

.. only:: html

	**Sections:**

	.. contents::
		:local:
		:depth: 1


.. _admin-manual-fegroups-basedn:
.. _admin-manual-begroups-basedn:

Base DN
^^^^^^^

Full DN path of the directory containing all the groups that are related to your LDAP users and you want to use in your
TYPO3 website.

**Example:**

::

	ou=groups,dc=example,dc=com

.. caution::
	Be sure you do **not** include any blank space between the :term:`DN`'s arguments, e.g., ::

		ou=groups, dc=example, dc=com

	because this will break the membership since LDAP servers are returning a :term:`DN` without any blank space and
	this extension is doing a simple string comparison as a security measure.


.. _admin-manual-fegroups-filter:
.. _admin-manual-begroups-filter:

Filter
^^^^^^

Filter to be used to add restrictions that allow you to exclude objects from specific properties. The syntax used in
this field is the standard LDAP search syntax.

This field should not be left empty although it is not marked as required. Be sure to always double-check your
configuration using the wizard in the backend module.

**Example:**

::

	(objectClass=posixGroup)

.. note::
	The string ``{USERDN}`` will be substituted by the Distinguished Name (DN) of the authenticated user, the string
	``{USERUID}`` will be substituted by the uid attribute of the authenticated user.

.. hint::
	When using OpenLDAP, the group membership is usually stored within attribute ``memberUid`` of the group itself,
	and not within attribute ``memberOf`` of the user. In order to properly retrieve and associate groups for the user,
	you should use a filter of the form::

		(&(memberUid={USERUID})(objectClass=posixGroup))

.. warning::
	When using ActiveDirectory, you typically set the option ``Relation between groups and users`` to
	``User contains the list of its associated groups``. If so, you **must** add a line mapping the "usergroup" for
	your user. This mapping *will not* be actually used by TYPO3 but will let the LDAP engine know which attribute is
	used to evaluate the group membership.

	Example::

		usergroup = <memberOf>

	**Important:** This is a user mapping instruction.

	If you forget to do so, every group in LDAP will be imported to TYPO3 **and** your user will be a member of each
	and every one.


.. _admin-manual-fegroups-mapping:
.. _admin-manual-begroups-mapping:

Mapping
^^^^^^^

Mapping to be used to fetch other attributes form the LDAP server that we would like groups to have. Please see syntax
and examples in the :ref:`description of mapping for users <admin-manual-feusers-mapping>`.
