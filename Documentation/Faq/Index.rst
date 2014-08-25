.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _faq:

Frequently Asked Questions
==========================

.. only:: html

	**Categories:**

	.. contents::
		:local:
		:depth: 1


.. _faq-users:

Users
-----

.. question

**Is it possible to have both users manually defined (thus authenticated with a TYPO3 password) and users authenticated
with LDAP?**

.. answer

*Yes. Both for frontend and backend authentication it is possible to manually define users unrelated to your LDAP
server(s). When a user is manually defined, the record's column* ``tx_igldapssoauth_dn`` *is empty and thus, since the
LDAP authentication will fail, it will automatically fall back to the standard TYPO3 authentication service. This
behaviour may be enabled/disabled globally for backend and/or frontend within Extension Manager.*

-------

.. question

**I have a local user with the same username than a LDAP user but which has been manually created in TYPO3. Which
password will be taken into account?**

.. answer

*When you manually create a user in TYPO3, it is not related to LDAP. This local user may authenticate with the
password you set. However is the user matches a LDAP user* **and the password provided** *results into a successful
LDAP authentication, the manually created user will be automatically linked to the LDAP user.* **Afterwards, only
the LDAP password will be valid**.

-------

.. question

**I would like to silently and automatically authenticate my users in frontend (Single Sign On). Since Apache is
configured to restrict access using Kerberos, no login form should be needed in my website. Is this possible with this
extension?**

.. answer

*Not currently, despite the label "SSO" in this extension's title. At the moment, Single Sign On (SSO) is possible
solely with CAS. This feature is currently in the backlog for a future version. In the mean time, you may have a look
at* :ter:`woehrl_sso_intranet`.

-------


.. _faq-groups:

Groups
------

.. question

**Can I import user groups automatically?**

.. answer

*Yes you can.*

-------

.. question

**May I manually tweak the name or configuration of imported user groups?**

.. answer

*Yes. To do so, you should enable the global option in Extension Manager preventing the automatic synchronization of
groups (may be configured separately for backend and frontend). In order to import new groups manually, use the LDAP /
SSO backend module. Once imported, you may change their name to fit your needs and conventions.*

-------

.. question

**My server is providing a hierarchy of groups. Is it possible to automatically mirror this structure in TYPO3?**

.. answer

*Yes. You should provide a be_groups and/or fe_groups mapping instruction for the LDAP attribute holding the reference
to the parent group. E.g.,* ::

	parentGroup = <memberOf>

-------

.. question

**Which servers support the "memberOf" / "groupMembership" attribute?**

.. answer

*Windows 2000 and above Active Directory and Novell eDirectory definitely support this attribute.*

-------


.. _faq-security:

Security
--------

.. question

**Can I encrypt my connection to the LDAP server?**

.. answer

*Yes. This extension is supporting SSL-encrypted connection to the LDAP server as well as TLS-based connection.*

-------

.. question

**Which port number is my LDAP server listening on?**

.. answer

*It is not possible to answer without knowing your infrastructure but it is worth to mention that*

- **389** *is the industry standard port for LDAP connections over TCP/IP, and*
- **636** *is the industry standard port for LDAP connections over SSL.*

-------


.. _faq-migration:

Migration
---------

.. question

**I am currently using another LDAP extension to authenticate my users, namely** :ter:`eu_ldap`. **Does this extension
at least provide the same feature set?**

.. answer

*Yes. And in order to ease the migration, this extension is able to migrate your legacy eu_ldap configuration records
to be compatible with ig_ldap_sso_auth using the "UPDATE!" script in Extension Manager (EM). You should first configure
global options for ig_ldap_sso_auth in EM and only then migrate your legacy eu_ldap configuration records. This will
ensure possible configuration issues to be detected.*

*The migration wizard does not require extension eu_ldap to be* **loaded** *but only expects the corresponding database
table to be present.*
