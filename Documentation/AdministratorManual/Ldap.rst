.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual-ldap:

LDAP
----

The second tab is the global configurations about a single LDAP server.

- **Server:** Choose your LDAP type (OpenLDAP or Active Directory / Novell eDirectory).

- **Protocol:** Choose the LDAP protocol version (2 or 3). Recent LDAP use version 3

- **Character set:** Character set of your LDAP connection. Usually ``utf-8``.

- **Host:** Host of your LDAP. You may use either a host name / IP address or prefix it with a protocol such as
  ``ldap://<hostname>`` or ``ldaps://<hostname>`` (latter in case you want to connect with SSL).

- **Port:** Port your LDAP uses. Default LDAP port is 389 (``ldap://``) and 636 (``ldaps://``).

- **TLS:** Whether you want to use :abbr:`TLS (Transport Layer Security)`, that is typically start with an connection
  on default port 389 and then set up an encrypted connection.

  .. note:: More information on TLS may be found at http://www.openldap.org/doc/admin24/tls.html.

- **Bind DN:** :term:`DN` of the LDAP user you will use to connect to the LDAP server. The :term:`DN` is composed of a
  series of :abbr:`RDN (Relative Distinguished Names)`'s which are the unique (or unique'ish) attributes at each level
  in the :term:`DIT`. The following diagram illustrates building up the DN from the RDN's.

  .. figure:: ../Images/dit-dn-rdn.png
      :alt: DN is the sum of all RDNs

      Building up the DN (Distinguished Name) from the RDN's (Relative Distinguished Names)

  **Example:**

  ::

      cn=Robert Smith,ou=people,dc=example,dc=com

  .. note::
    Your LDAP user needs to be granted access to the directory where users and groups are stored and full read
    access to users and groups for all attributes you plan to fetch.

    When connecting to an Active Directory, this corresponds to a user account that has privileges to search for users.
    E.g., ``CN=Administrator,CN=Users,DC=mycompany,DC=com``. This user account must have at least domain user
    privileges.

- **Password:** This password is the same password used in association with the :term:`Bind DN` user account to connect
  to the LDAP server.
