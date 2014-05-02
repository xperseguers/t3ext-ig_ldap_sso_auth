.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _users-manual:

Users manual
============

.. only:: html

	This chapter describes how to use the extension from a user point of view.


When a user tries to log on the extension passes the credentials to
the LDAP server(s) and verifies them. When a LDAP server can
authenticate the user he is logged on. New users (in the directory but
not in the TYPO3 database) are imported after authentication. Records
of existing users are updated.

For new users imported from the directory random passwords will be
inserted!
