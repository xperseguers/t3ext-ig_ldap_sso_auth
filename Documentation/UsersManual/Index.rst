.. _users-manual:

Users manual
============

.. only:: html

	This chapter describes how to use the extension from a user point of view.


When a user tries to log on, the extension passes her credentials to the LDAP
server(s) and verifies them. When a LDAP server can authenticate the user, she
is logged on. New users (in the directory but not in the TYPO3 database) are
imported after authentication. Records of existing users are updated.

In addition, imported users from the directory automatically get a random
password for their counterpart record in TYPO3, preventing them from bypassing
the LDAP authentication.

In case Single Sign-On (:term:`SSO`) has been activated, the typical login form
is made useless and users within your domain will be automatically
authenticated.
