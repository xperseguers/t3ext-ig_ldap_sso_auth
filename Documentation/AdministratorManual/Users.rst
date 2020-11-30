.. include:: ../Includes.rst.txt
.. _admin-manual-feusers:
.. _admin-manual-beusers:


FE_USERS and BE_USERS
---------------------

The third and fifth tab can be filled exactly the same way. The only difference
between them is that FE_USERS stores the configuration options for the frontend
LDAP user authentication and BE_USERS stores the configuration options for the
backend LDAP user authentication.

You may only fill the sections you need; that is, FE_USERS if you need frontend
authentication and BE_USERS if you need backend authentication.

.. only:: html

	**Sections:**

	.. contents::
		:local:
		:depth: 2


.. _admin-manual-feusers-basedn:
.. _admin-manual-beusers-basedn:

Base DN
^^^^^^^

Full DN path of the directory containing all the users that you want to use with
your TYPO3 authentification.

**Example:**

::

	ou=people,dc=example,dc=com


.. _admin-manual-feusers-filter:
.. _admin-manual-beusers-filter:

Filter
^^^^^^

Filter is used to precise which LDAP attribute contains the username of your
users and which filter to apply when selecting users to import, either from the
backend module or from the Scheduler task.

Example: ``(uid={USERNAME})`` uid is the most common attribute used to keep the
username in LDAP but if you are in Active Directory, the field where the
username is stored is usually ``sAMAccountName`` instead.

You will also be able to add restrictions that allow you to exclude user from
specific properties. The syntax used in this field is the standard LDAP search
syntax.

**Example:**

::

	(&(uid={USERNAME})(objectClass=posixAccount))

.. note::

	The string ``{USERNAME}`` will be substituted by the username entered in the
	login form. In case the filter is used in the context of importing users, any
	placeholder will be replaced by an asterisk, thus effectively returning any
	record matching the filter.


.. _admin-manual-feusers-mapping:
.. _admin-manual-beusers-mapping:

Mapping
^^^^^^^

The mapping is used to fetch other attributes from the LDAP server that we would
like users to have. It is quite simple, each line is a new command. Each command
has two parts separated by a ``=`` (equal sign). the first part is the field
from the TYPO3 user that we want to fill and the second part is the value that
the field will have.

There are three possible value types you may use:

- a string;
- a LDAP attribute value;
- a custom marker;

.. Cross-linking does not seem to work when rendering as PDF, at least locally
   with EXT:sphinx

.. only:: html

	In addition, every field supports :ref:`t3tsref:stdWrap` properties.
	Multi-valued LDAP attributes are available using ``field`` where values have
	been joined together using a line-feed character (``\n``).

.. only:: latex

	In addition, every field supports stdWrap properties. Multi-valued LDAP
	attributes are available using ``field`` where values have been joined
	together using a line-feed character (``\n``).

.. warning::

	LDAP field names are always lowercase when accessed in TypoScript.


.. _admin-manual-feusers-mapping-string:
.. _admin-manual-beusers-mapping-string:

String
""""""

This will assign the value directly to the field

**Example:**

::

	email = user@domain.com


.. _admin-manual-feusers-mapping-ldapattribute:
.. _admin-manual-beusers-mapping-ldapattribute:

LDAP attribute
""""""""""""""

LDAP attributes will be recognized by the specific characters ``<>``.

**Example:**

::

	email = <mail>


This will set the field email of the TYPO3 user to the value of the attribute
``mail`` of the user fetched from the LDAP server.

.. tip::
	You may combine multiple markers as well, e.g., ::

		name = <sn>, <givenname>

.. _admin-manual-feusers-mapping-custommarker:
.. _admin-manual-beusers-mapping-custommarker:

Custom marker
"""""""""""""

Custom markers are markers created by the extension to assign specific type of
values. There are only four types of markers available at the moment:

- ``{DATE}``: the current timestamp;

- ``{RAND}``: a random number;

- ``{USERNAME}``: the username from the login form (the username will
  automatically fill the needed field. This marker is only used if you want to
  put the username in another field than the one by default);

- ``{hook parameters}``: will only be useful if an extension
  :ref:`hooks into ig_ldap_sso_auth <development-extramergefield>`.


.. _admin-manual-feusers-mapping-examples:
.. _admin-manual-beusers-mapping-examples:

Examples
""""""""

**BE_USERS**

::

	tstamp = {DATE}
	email = <mail>
	realName = <cn>
	lang = fr

**FE_USERS**

::

	pid = 45
	tstamp = {DATE}
	email = <mail>
	name = <cn>
	first_name = <givenname>
	last_name = <sn>
	title = <title>
	address = <street>
	zip = <postalcode>
	city = <l>
	telephone = <telephonenumber>



**Applying TypoScript .stdWrap properties**

Split a phone number

::

	name = <cn>
	name.wrap = |-LDAP

	telephone {
	    field = telephonenumber
	    split {
	        token.char = 10
	        cObjNum = 1
	        1.current = 1
	        1.noTrimWrap = ||, |
	    }
	    substring = 0,-2
	}

Fetch a specific element from a multi-valued list

::

	email {
	    field = mail
	    listNum = 3
	    listNum {
	        splitChar = 10
	    }
	}
