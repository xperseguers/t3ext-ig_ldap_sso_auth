.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual-beusers:
.. _admin-manual-feusers:

BE_USERS and FE_USERS
---------------------

The third tab and the fifth tabs can be fill exactly the same way. The only difference between them is that BE_USERS
stores the configuration options for the backend LDAP user authentication and FE_USERS stores the configuration options
for the frontend LDAP user authentication.

You may only fill the section you need. That is, BE_USERS if you need backend authentication and FE_USERS if you need
frontend authentication.

- **Base DN:** Full DN path of the directory containing all the users that you want to use with your TYPO3
  authentification.

  **Example:**

  ::

      ou=people,dc=example,dc=com

- **Filter:** is used to precise which LDAP attribute contains the username of your users.

  Example: ``(uid={USERNAME})`` uid is the most common attribute used to keep the username in LDAP but if you are in
  Active Directory, the field where the username is stored is usually ``sAMAccountName`` instead.

  You will also be able to add restrictions that allow you to exclude user from specific properties. The syntax used in
  this field is the standard LDAP search syntax.

  **Example:**

  ::

      (&(uid={USERNAME})(objectClass=posixAccount)

  .. note:: The string ``{USERNAME}`` will be substituted by the username entered in the login form.

- **Mapping:** Used to fetch other attributes from the LDAP server that we would like users to have. It is quite simple,
  each line is a new command. Each command has two parts separated by a ``=`` (equal sign). the first part is the field
  from the TYPO3 user that we want to fill and the second part is the value that the field will have. There are three
  possible value types you may use:

  - a string: this will assign the value directly to the field

  - a LDAP attribute value: LDAP attributes will be recognized by the specific characters ``<>``.

    **Example:**

    ::

        email = <mail>


    This will set the field email of the TYPO3 user to the value of the attributes mail of the user fetch from the LDAP
    server.

  - a custom marker: custom markers are markers created by the extension to assign specific type of values. There are
    only four markers available at the moment:

    - ``{DATE}``: the current timestamp

    - ``{RAND}``: a random number

    - ``{USERNAME}``: the username from the login form ( the username will
      automatically fill the needed field. This markers is only used if you
      want to put the username in an other field than the one by default )

    - ``{hook parameters}``: will only be useful if an extension hooks into ig_ldap_sso_auth

  **Example (BE_USERS):**

  ::

      tstamp = {DATE}
      email = <mail>
      realName = <cn>
      lang = fr

  **Example (FE_USERS):**

  ::

      pid = 45
      tstamp = {DATE}
      email = <mail>
      name = <cn>
      first_name = <givenName>
      last_name = <sn>
      title = <title>
      address = <street>
      zip = <postalCode>
      city = <l>
      telephone = <telephoneNumber>

  .. tip::
      You may combine multiple markers as well, e.g., ::

          name = <sn>, <givenName>
