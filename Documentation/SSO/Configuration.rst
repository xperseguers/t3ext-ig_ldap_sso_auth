.. include:: ../Includes.rst.txt
.. _sso-configuration:
.. _admin-manual-kerberos-apache-basic-configuration:

Basic Kerberos configuration of intranet.example.com
----------------------------------------------------

The method described here as five steps:

#. Install the ``mod_auth_kerb`` authentication module.
#. Create a :term:`service principal <Principal>` for the web server.
#. Create a keytab for the service principal.
#. Specify the authentication method to be used.
#. Specify a list of authorized users or user groups.

First of all, you should make sure the clocktime of :term:`KDC`, workstation and
web server is in sync (5 minutes are the highest difference you may allow for
Kerberos to work properly).

You may use `NTP <http://www.ntp.org/>`_ for that task.


.. _sso-configuration-module:
.. _admin-manual-kerberos-apache-basic-configuration-module:

Installing the mod_auth_kerb authentication module
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Within an ``intranet.example.com`` shell, install the package:

.. code-block:: bash

	$ sudo apt-get install libapache2-mod-auth-kerb krb5-user

.. hint::

	``krb5-user`` is not an actual requirement but it will provide handy
	command-line tools for Kerberos.

.. hint::

	In Debian 11 (Bullseye) the module ``libapache2-mod-auth-kerb`` is not
	available anymore. You can use ``libapache2-mod-auth-gssapi`` instead.

	``$ sudo apt-get install libapache2-mod-auth-gssapi``

In additional to ``libapache2-mod-auth-kerb``, this will install the dependency
package ``krb5-config`` and then show you a configuration wizard asking for:

- **Default Kerberos version 5 realm.** Use ``EXAMPLE.COM`` (in capital
  letters).
- **The KDC.** My Active Directory server is ``ws2008r2.example.com``, replace
  by your own. In a larger organization, you probably have two domain
  controllers, for redundancy reason.
- **The administration server.** This is typically the same as the LDAP/Active
  Directory server or in case of multiple domain controllers, this should be
  normally set to the master.

Settings you provide are then stored within configuration file
:file:`/etc/krb5.conf` which should look like this after the wizard
configuration:

.. code-block:: none

	[libdefaults]
	    default_realm    = EXAMPLE.COM

	[realms]
	    EXAMPLE.COM = {
	        kdc          = ws2008r2.example.com
	        #kdc          = other-kdc.example.com
	        #master_kdc   = ws2008r2.example.com
	        admin_server = ws2008r2.example.com
	    }

	[domain_realm]
	    .example.com     = EXAMPLE.COM

	[logging]
	    kdc              = SYSLOG:NOTICE
	    admin_server     = SYSLOG:NOTICE
	    default          = SYSLOG:NOTICE

.. note::
	A description of each section and the meaning of keys is available on
	`http://web.mit.edu/kerberos/krb5-1.5/krb5-1.5/doc/krb5-admin/krb5.conf.html`__.

You should now check that Kerberos works on ``intranet.example.com``. Do a basic
check using :command:`kinit`:

#. Ensure that ``intranet`` can reach :term:`KDC` ``ws2008rs2`` via the network
   (:command:`ping`, ...).
#. Have a username and password in Windows Domain ``EXAMPLE.COM``. In this
   example ``einstein`` is used as username.
#. Within the shell, type:

   .. code-block:: bash

       $ kinit einstein@EXAMPLE.COM

   If everything is OK the command will ask you for ``einstein``'s domain
   password and terminates without an error message.

   .. note::

       If command fails with

       .. code-block:: none

           kinit: Cannot resolve servers for KDC in realm "example.com" while
           getting initial credentials

       then it most probably means that you did not pay attention to writing the
       realm in CAPITAL LETTERS.

#. Finally use :command:`klist` to show the initial ticket you have got from the
   KDC:

   .. code-block:: bash

       $ klist
       Default principal: einstein@EXAMPLE.COM

       Valid starting    Expires           Service principal
       31/10/2014 13:12  31/10/2014 23:11  krbtgt/EXAMPLE.COM@EXAMPLE.COM
               renew until 01/11/2014 13:12


.. _sso-configuration-principal:
.. _admin-manual-kerberos-apache-basic-configuration-principal:

Creating a service principal for the web server
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

:term:`SPNEGO` requires that a Kerberos service principal be created for the web
server. The service name is defined to be ``HTTP``, so for the server
``intranet.example.com`` the required service principal name is
``HTTP/intranet.example.com@EXAMPLE.COM``.

#. Create a dummy account in Windows Domain ``EXAMPLE.COM``. It is used like a
   machine account **but is nevertheless a standard user account**. In this
   example the name of dummy account is ``kerbdummy1``.

#. Log in to the domain controller ``ws2008r2`` and use the Windows command line
   tool :command:`ktpass` to map the dummy account ``kerbdummy1`` to the service
   principal ``HTTP/intranet.example.com@EXAMPLE.COM``. You need that service
   principal to *kerberize* host ``intranet``:

   .. code-block:: none

       C:\>ktpass
         -princ HTTP/intranet.example.com@EXAMPLE.COM
         -mapuser kerbdummy1@EXAMPLE.COM
         -crypto AES256-SHA1
         -ptype KRB5_NT_PRINCIPAL
         -pass very!$longp@ssw0rd
         -out C:\temp\intranetkeytab

   .. note::

       If you have to target Windows XP machines, ``AES256-SHA1`` is not
       supported. Use the legacy crypto ``RC4-HMAC-NT`` instead.

   .. warning::

       Even if you target recent machines such as running Windows 8.x,
       ``AES256-SHA1`` may not be supported either. Please check section
       :ref:`sso-pitfalls-basic-authentication` for details.


#. Copy file :file:`C:\\temp\\intranetkeytab` from the domain controller
   ``ws2008r2`` to the location where it should reside on host ``intranet``, in
   our example :file:`/etc/apache2/http_intranet.keytab` and make ``www-data``
   its owner.

   .. note::

       An alternate way to create the needed keytab file is with the help of
       :command:`kadmin` directly on your Linux machine. Please refer to
       `www.microhowto.info`_ for instructions.

   .. _www.microhowto.info: http://www.microhowto.info/howto/configure_apache_to_use_kerberos_authentication.html#idp145152

#. Check if the KDC sends correct tickets by checking in detail:

   - ticket's kvno **must** match kvno in keytab
   - principal name in ticket **must** match the principal name in keytab

   .. code-block:: bash

       $ kvno HTTP/intranet.example.com@EXAMPLE.COM
       HTTP/intranet.example.com@EXAMPLE.COM: kvno = 4

       $ klist -e
       Ticket cache: FILE:/tmp/krb5cc_0
       Default principal: HTTP/intranet.example.com@EXAMPLE.COM

       Valid starting    Expires           Service principal
       31/10/2014 14:53  01/11/2014 00:52  krbtgt/EXAMPLE.COM@EXAMPLE.COM
               renew until 01/11/2014 14:53, Etype (skey, tkt): aes256-cts-hmac-sha1-96, ...
       31/10/2014 15:09  01/11/2014 00:52  HTTP/intranet.example.com@EXAMPLE.COM
               renew until 01/11/2014 14:53, Etype (skey, tkt): arcfour-hmac, arcfour-hmac

       $ klist -e -k -t /etc/apache2/http_intranet.keytab
       Keytab name: FILE:http_intranet.keytab
       KVNO Timestamp        Principal
       ---- ---------------- ---------------------------------------------------------
          4 01/01/1970 01:00 HTTP/intranet.example.com@EXAMPLE.COM (aes256-cts-hmac-sha1-96)

#. Check that the key has been correctly added to the keytab by attempting to
   use it to authenticate as the service principal, then view the resulting
   ticket-granting ticket using :command:`klist`:

   .. code-block:: bash

       $ kinit -k -t /etc/apache2/http_intranet.keytab HTTP/intranet.example.com
       $ klist
       Ticket cache: FILE:/tmp/krb5cc_0
       Default principal: HTTP/intranet.example.com@EXAMPLE.COM

       Valid starting    Expires           Service principal
       31/10/2014 14:11  01/11/2014 00:10  krbtgt/EXAMPLE.COM@EXAMPLE.COM
               renew until 01/11/2014 14:11

   .. note::

       if command fails with

       .. code-block:: none

           kinit: Generic preauthentication failure while getting initial credentials

       It *may* be related to using a legacy crypto. Try to edit file
       :file:`/etc/krb5.conf` and update it to actively specify older cryptos:

       .. code-block:: ini

           [libdefaults]
               default_realm = EXAMPLE.COM
               default_tkt_enctypes = rc4-hmac des-cbc-crc des-cbc-md5
               default_tgs_enctypes = rc4-hmac des-cbc-crc des-cbc-md5


.. _sso-configuration-authentication:
.. _admin-manual-kerberos-apache-basic-configuration-authentication:

Specifying the authentication method to be used
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Apache must be told which parts of which web sites are to use authentication
provided by ``mod_auth_kerb``. This is done using the ``AuthType`` directive
with a value of ``Kerberos``.

In order to protect the whole TYPO3 website, add following snippet to your
virtual host configuration:

.. code-block:: apache

	<Location />
	    AuthType Kerberos
	    AuthName "Intranet of example.com"
	    KrbMethodNegotiate on
	    KrbMethodK5Passwd off
	    # Allow shorter username (without realm):
	    KrbAuthRealms EXAMPLE.COM
	    KrbServiceName HTTP
	    Krb5Keytab /etc/apache2/http_intranet.keytab

	    # Disable the verification tickets against local keytab to
	    # prevent KDC spoofing attacks
	    # It should be used only for testing purposes
	    KrbVerifyKDC off
	</Location>

**If you are using ``libapache2-mod-auth-gssapi``, add the following snippet instead:**

.. code-block:: apache

	<Location />
			SSLRequireSSL
			AuthType GSSAPI
			AuthName "Intranet of example.com"
			GssapiBasicAuth On
			GssapiCredStore keytab:/etc/apache2/http_intranet.keytab
			GssapiLocalName On
			require valid-user
	</Location>

.. note::
	Other configuration options are available on http://modauthkerb.sourceforge.net/configure.html.

.. note::

	If there is a need for the web site to be accessible to its authorized users
	from machines that are not part on the Kerberos realm, you may let
	``mod_auth_kerb`` ask the user for her password using basic authentication
	and then validate that password by attempting to authenticate to the KDC.
	Please note however that this is significantly less secure than true Kerberos
	authentication:

	.. image:: ../Images/basic-authentication.png
		:alt: Basic Authentication

	To do so, change:

	.. code-block:: apache

		KrbMethodK5Passwd on

.. warning::

    If you need to enable fallback to basic authentication, you should do that
    in conjunction with SSL since the password is sent Base64-encoded, that is,
    as readable as clear text. The use of SSL encryption is also recommended if
    you are using the Negotiate method.


.. _sso-configuration-authorization:
.. _admin-manual-kerberos-apache-basic-configuration-authorization:

Specifying a list of authorized users or user groups
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Having an authentication method does not by itself restrict access to the web
site until you disallow access by anonymous users using ``Require`` directive:

.. code-block:: apache

	<Location />
	    # ...
	    Require valid-user
	</Location>

Please refer to the `Apache documentation`_ if you want to restrict access to
certain users or groups (if so, you will certainly need to use another
authorization module such as ``mod_authnz_ldap``).

.. _Apache documentation: https://httpd.apache.org/docs/2.2/howto/auth.html

Final step is to reload the Apache configuration:

.. code-block:: bash

	$ sudo apache2ctl configtest
	Syntax OK
	$ sudo service apache2 force-reload

You will need to access your website from a machine within your domain or by
authenticating with the basic authentication dialog, if enabled. TYPO3 will then
read the authenticated username from ``$_SERVER['REMOTE_USER']`` and silently
create the frontend user session, if it does not exist yet. You do not need any
frontend login plugin for your website.

.. note::

    If you are not using Microsoft Internet Explorer, you may need to configure
    your browser to enable Single Sign-On. Please refer to
    `https://wiki.shibboleth.net/confluence/display/SHIB2/Single+sign-on+Browser+configuration`__.
