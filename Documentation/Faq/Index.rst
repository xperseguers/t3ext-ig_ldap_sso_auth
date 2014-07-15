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

-------

.. question

**Is it possible to have both users manually defined (thus authenticated with a TYPO3 password) and users authenticated
with LDAP?**

.. answer

*Yes. Both for frontend and backend authentication it is possible to manually define users unrelated to your LDAP
server(s). When a user is manually defined, the record's column ``tx_igldapssoauth_dn`` is empty and thus, since the
LDAP authentication will fail, it will automatically fall back to the standard TYPO3 authentication service.*

-------


.. _faq-groups:

Groups
------

-------

.. question

**Can I import user groups automatically?**

.. answer

*Yes you can.*

-------

.. question

**Which servers support the "memberOf/groupMembership" attribute?**

.. answer

*Windows 2000 and above Active Directory and Novell e-directory definitely support this attribute.*

-------


.. _faq-security:

Security
--------

-------

.. question

**Can I encrypt my connection to the LDAP server?**

.. answer

*Yes. This extension is supporting SSL-encrypted connection to the LDAP server (typically on port 636) as well as TLS
-based connection.*

-------
