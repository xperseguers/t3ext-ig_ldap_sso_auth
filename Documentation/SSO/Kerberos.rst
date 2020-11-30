.. include:: ../Includes.rst.txt
.. _sso-kerberos:
.. _admin-manual-kerberos-apache-concepts:

Understanding Kerberos concepts
-------------------------------

Kerberos Version 5 is a standard on all versions of Windows 2000 and ensures the
highest level of security to network resources. The Kerberos protocol name is
based on the three-headed dog figure from Greek mythology known as Kerberos.
The three heads of Kerberos comprise the Key Distribution Center (:term:`KDC`),
the client user and the server with the desired service to access. The KDC is
installed as part of the domain controller and performs two service functions:

- the Authentication Service (AS) and
- the Ticket-Granting Service (TGS).

.. figure:: ../Images/kerberos-ticket-exchange.png
	:alt: Kerberos ticket exchange

	Three exchanges are involved when the client initially access a server
	resource: AS exchange (circles 1 and 2), TGS exchange (circles 3 and 4) and
	finally a client/server exchange (request shown as circle 5).


.. _sso-kerberos-as:
.. _admin-manual-kerberos-apache-concepts-as:

AS exchange
^^^^^^^^^^^

When initially logging on to a network, users must negotiate access by providing
a login name and password in order to be verified by the AS portion of a
:term:`KDC`. The KDC has access to Active Directory user account information.
Once successfully authenticated, the user is granted a Ticket to Get Tickets
(TGT) that is valid for the local domain (in our example, for the realm
``example.com``). The TGT has a default lifetime of 10 hours and may be renewed
throughout the user's log-on session without requiring the user to re-enter her
password.

If the KDC approves the client's request for a TGT, the reply (referred to as
the AS reply) will include two sections: a TGT encrypted with a key that only
the KDC (TGS) can decrypt and a session key encrypted with the user's password
hash to handle future communications with the KDC. Because the client system
cannot read the TGT contents, it must blindly present the ticket to the TGS for
service tickets. The TGT includes time to live parameters, authorization data, a
session key to use when communicating with the client and the client's name.


.. _sso-kerberos-tgs:
.. _admin-manual-kerberos-apache-concepts-tgs:

TGS exchange
^^^^^^^^^^^^

The user presents the TGT to the TGS portion of the :term:`KDC` when desiring
access to a server service. The TGS on the KDC authenticates the user's TGT and
creates a ticket and session key for both the client and the remote server. This
information, known as the service ticket, is then cached locally on the client
machine.

The TGS receives the client's TGT and reads it using its own key. If the TGS
approves of the client's request, a service ticket is generated for both the
client and the target server. The client reads its portion using the TGS session
key retrieved earlier from the AS reply. The client presents the server portion
of the TGS reply to the target server in the client/server exchange coming next.


.. _sso-kerberos-cs:
.. _admin-manual-kerberos-apache-concepts-cs:

Client/server exchange
^^^^^^^^^^^^^^^^^^^^^^

Once the client user has the client/server service ticket, he can establish the
session with the server service. The server can decrypt the information coming
indirectly from the TGS using its own long-term key with the :term:`KDC`. The
service ticket is then used to authenticate the client user and establish a
service session between the server and client. After the ticket's lifetime is
exceeded, the service ticket must be renewed to use the service.
