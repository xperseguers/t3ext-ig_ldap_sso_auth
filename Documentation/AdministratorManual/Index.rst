.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual:

Administrator manual
====================

.. only:: html

	This chapter describes how to manage the extension from a superuser point of view.

First step for configuring your LDAP authentication is to create one or more server configurations.

All server configurations have to be stored on the root level of your TYPO3 website.
Create a Configuration LDAP / SSO records. These records have 7 tabs for specific
configuration types.


General
-------

- The only thing you have to fill is the configuration name. This is
  only to name the records you have just created.

Global Configuration
--------------------

The second tab is the Global configurations about a single LDAP (not
that you can create multiple configuration records with the same LDAP).

- **Server:** Choose your LDAP type (OpenLDAP or eDirectory).

  Note that if you are using Active directory, your LDAP type is
  eDirectory

- **Protocol:** Choose the LDAP protocol version (2 or 3). Recent LDAP
  use version 3

- **Charset:** is the character set of your LDAP. Usually ``utf-8``

- **Host:** is the host of your LDAP

- **Port:** is the port your LDAP use. Default LDAP port is 389

- **Bind DN:** is the full DN of the LDAP user you will use to connect
  to the LDAP

  note that your LDAP user need access to the directory where users and
  groups are stored and full read access to users and groups.

  Example: ``cn=admin,dc=example,dc=com``

- **Password:** Password of the user used to connect to the LDAP


BE_USERS and FE_USERS
---------------------

The third tab and the fifth tabscan be fill exactly the same way. The
only difference between them is that BE\_USERS store the
configurations for the backend LDAP userauthenticationand FE\_USERS
store the configurations for the frontend LDAP user authenticate.You
will only fill the section you need,BE\_USERSif you need
backendauthenticationandFE\_USERSif you need frontendauthentication.

- **Base DN:** is the full DN path of the directory containing all the
  users that you want to use with you TYPO3 authentification.

  Example: ``ou=Users,dc=example,dc=com``

- **Filter:** is used to precise which LDAP attribute contain the
  username of your users.

  Example: ``(uid={USERNAME})`` uid is the most common attribute used to keep
  the username in LDAP but if you are in an Active directory, the field
  where the username is stored is usually ``sAMAccountName`` instead.

  Note that the string ``{USERNAME}`` will be substituted by the username
  entered in the login form.

  You will also be able to add restrictions that allow you to exclude
  user from specific properties. The syntax used in this field is the
  LDAP search syntax.

  Example: ``(&(uid={USERNAME})(objectClass=user)``

- **Mapping:** is used to fetch other attributes form the LDAP that we
  would likeusersto have. It is quite simple, each line is a new
  command. Each command have 2 parts separated by a =. the first part is
  the field form the TYPO3 user that we want to fill and the second part
  is the value we that the field will have. There is 3 possible value
  type you could use:

  - a string: this will assign the value directly to the field

  - a LDAP attribute value: LDAP attributes will be recognized by the
    specific characters ``<>``.

    Example: ``email = <email>`` this will set the field email of the TYPO3
    user to the value of the attributes email of the user fetch from the
    LDAP

  - a custom marker: custom markers are markers create by the extension to
    assgin specific type of values. There are only4 markers available for
    the moment:

    - ``{DATE}``: the current timestamp

    - ``{RAND}``: a random number

    - ``{USERNAME}``: the username from the login form ( the username will
      automatically fill the needed field. This markers is only used if you
      want to put the username in an other field than the one by default )

    - ``{hook parameters}``: will only be usefull if an extension il hooked on
      ig\_ldap\_sso\_auth

  Example::

      tstamp = {DATE}
      realName = <cn>
      email = <email>
      lang = fr


BE_GROUPS and FE_GROUPS
-----------------------

The fourth and sixth tabs can be fill exactly the same way. The only
difference between them is that BE\_GROUPS stores the configurations for
the backend LDAP usergroup association and FE\_GROUPS stores the
configurations for the frontend LDAP user group association. You will only
fill the sections you need, BE\_GROUPS if you need backend authentication and
FE\_GROUPS if you need frontend authentication. You can skip this entire section
if you just want to validate the authentication and do not want to use groups from
LDAP.

- **Base DN:** is the full DN path of the directory containing all
  the groups that are related to your LDAP users and you want to use in
  your TYPO3.

  Example: ``ou=Groups,dc=example,dc=com``

- **Filter:** You will only by used to add restrictions that allow you to
  exclude obects from specific properties. The syntax used in this field
  is the LDAP search syntax.

  Example: ``&(objectClass=posixGroup)``

- **Mapping :** is used to fetch other attributes form the LDAP that we
  would like groups to have. It is quite simple, each line is a new
  command. Each command have 2 parts separated by a ``=`` (equal sign).
  the first part is the field form the TYPO3 group that we want to fill and the second part
  is the value we that the field will have. There is 3 possible value
  type you could use:

  - a string: this will assign the value directly to the field

  - a LDAP attribute value: LDAP attributes will be recognized by the
    specific characters ``<>``.

    Example: ``email = <email>`` this will set the field email of the
    TYPO3 group to the value of the attributes email of the user fetch from
    the LDAP

  - a custom marker: custom markers are markers create by the extension to
    assgin specific type of values. There are only 4 markers available for
    the moment:

    - ``{DATE}``: the current timestamp

    - ``{RAND}``: a random number

    - ``{USERNAME}``: the username from the login form ( the username will
      automatically fill the needed field. This markers is only used if you
      want to put the username in an other field than the one by default )

    - ``{hook parameters}``: will only be usefull if an extension il hooked on
      ig\_ldap\_sso\_auth

  Example::

      tstamp = {DATE}


CAS
---

The last tab is for CAS configurations. You only have to fill it if
you want to use a CAS server to implement some single sing on (SSO)

- **Host:** is the host of your CAS server

- **URI:** path to postpend to the host used if the CAS sever is
  not at the root of your host

  Example: ``/userSSo/cas`` in the string ``localhost/userSSo/cas``

- **Service URL:** this is a specific url for your CAS

- **Port:** port on which you CAS is configure

- **Back URL:** Url to return to in case of a CAS login
