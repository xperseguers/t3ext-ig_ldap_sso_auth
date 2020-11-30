.. include:: ../Includes.rst.txt
.. _admin-manual-sample:

Sample Configuration
--------------------

`Forum Systems <http://www.forumsys.com>`_ is providing a free online LDAP test
server that you can use for testing. If all you need is to test connectivity and
do a proof of concept of connecting TYPO3 with an LDAP server, this is the right
place to eliminate the need to download, install and configure an LDAP server
just for testing.
`read more <http://www.forumsys.com/tutorials/integration-how-to/ldap/online-ldap-test-server/>`_.


.. only:: html

	**Sections:**

	.. contents::
		:local:
		:depth: 1


.. _admin-manual-sample-ldap:

LDAP
^^^^

.. tabularcolumns:: |p{7.53cm}|p{7.53cm}|

=================================== ===========================================
Option                              Value
=================================== ===========================================
Server Type                         OpenLDAP
Host                                ldap.forumsys.com
Port                                389
Bind DN                             cn=read-only-admin,dc=example,dc=com
Password                            password
Relation between groups and users   Group contains the list of its members
=================================== ===========================================


.. _admin-manual-sample-beusers:

BE_USERS
^^^^^^^^

.. tabularcolumns:: |p{2.5cm}|p{12.56cm}|

=================================== ===========================================
Option                              Value
=================================== ===========================================
Base DN                             dc=example,dc=com
Filter                              (uid={USERNAME})
Mapping                             .. code-block:: typoscript

                                        email = <mail>
                                        realName = <cn>
                                        tstamp = {DATE}
                                        admin = 1

=================================== ===========================================

.. warning::

	In this example, the last mapping line will automatically promote every LDAP
	user as TYPO3 administrator. This should of course be enabled only for quick
	testing without having to bother with available modules.


.. _admin-manual-sample-begroups:

BE_GROUPS
^^^^^^^^^

.. tabularcolumns:: |p{2.5cm}|p{12.56cm}|

=================================== ===========================================
Option                              Value
=================================== ===========================================
Base DN                             dc=example,dc=com
Filter                              (&(uniqueMember={USERDN})(ou=*))
Mapping                             .. code-block:: typoscript

                                        title = <cn>
                                        tstamp = {DATE}

=================================== ===========================================

.. _admin-manual-sample-feusers:

FE_USERS
^^^^^^^^

.. tabularcolumns:: |p{2.5cm}|p{12.56cm}|

=================================== ===========================================
Option                              Value
=================================== ===========================================
Base DN                             dc=example,dc=com
Filter                              (uid={USERNAME})
Mapping                             .. code-block:: typoscript

                                        pid = *id of your storage folder*
                                        tstamp = {DATE}
                                        email = <mail>
                                        name = <cn>
                                        last_name = <sn>

                                        # <cn> is of the form "Albert Einstein"
                                        # Extract first name as what comes
                                        # before last "word"/blank space
                                        first_name = <cn>
                                        first_name.replacement.10 {
                                            search = /^(.*) ([^ ]+)$/
                                            replace = $1
                                            useRegExp = 1
                                        }

=================================== ===========================================

.. _admin-manual-sample-testusersgroups:

Test Users and Groups
^^^^^^^^^^^^^^^^^^^^^

As of April 2015, four groups and a few users are available:

- **Mathematicians**

  - **euclid**    (Euclid)
  - **euler**     (Leonhard Euler)
  - **gauss**     (Carl Friedrich Gauss)
  - **riemann**   (Bernhard Riemann)

- **Scientists**

  - **einstein**  (Albert Einstein)
  - **galieleo**  (Galileo Galilei)
  - **newton**    (Issac Newton) -- *known typo: (Isaac)*
  - **tesla**     (Nikola Tesla)

- **Italians**

  - **tesla**     (Nikola Tesla)

- **Chemists**

  - **boyle**     (Robert Boyle)
  - **curie**     (Marie Curie)
  - **nobel**     (Alfred Nobel)
  - **pastuer**   (Louis Pastuer) -- *known typo: (Pasteur)*


.. note:: All user passwords are "password".
