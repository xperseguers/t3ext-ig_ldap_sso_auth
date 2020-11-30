.. include:: ../Includes.rst.txt
.. _sso-scenario:
.. _admin-manual-kerberos-apache-scenario:

Scenario
--------

Suppose you wish to restrict access to the website
``http://intranet.example.com``. Since users allowed to connect to this website
are managed in a central directory server (LDAP / Active Directory),
authentication is to be performed using :term:`Kerberos` and :term:`SPNEGO`.

How does it work with TYPO3? What we actually want to do is as follows, from a
TYPO3 point of view:

#. Delegate the authentication to the Apache web server, which should restrict
   access using Basic Authentication (theoretically by whatever means
   -- htpasswd file, ... -- in our case with an LDAP/Active Directory backend).
#. Trust the authenticated user whose username is sent to PHP as
   ``$_SERVER['REMOTE_USER']`` and rely on the TYPO3 authentication services (in
   our case the one provided by this extension) to retrieve additional user
   information and group membership without checking the password, since Apache
   did it already.
#. To ensure these tasks are executed transparently, without having to actively
   authenticate in TYPO3, this extension sets

   .. code-block:: php

       $GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']['FE_fetchUserIfNoSession'] = 1;
