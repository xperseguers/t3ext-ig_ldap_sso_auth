.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _glossary:

Glossary
========

.. glossary::
	:sorted:

	DN
		Distinguished Name, LDAP "primary key"; not indexed. Used in this extension to relate local users/groups to
		their LDAP counterpart. This represents the "path to the root" of a node in the :term:`DIT`.
		E.g., ``cn=Robert Smith,ou=people,dc=example,dc=com``.

	RDN
		Relative Distinguished Name. Think of it as the relative path in its parent folder for a given file path. E.g.,
		if ``/foo/bar/myfile.txt`` were the :term:`DN` then ``myfile.txt`` would be the RDN.

	CN
		Common Name, typically the full name for users or the name of a group.

	Bind DN
		:term:`DN` of the LDAP user you will use to connect to the LDAP server.

	OU
		Organizational Unit.

	DC
		Domain Component. Usually the two last parts of a :term:`DN`. E.g., ``dc=example,dc=com``.

	DIT
		Directory Information Tree, a.k.a the naming-context.

	LDAP
		Lightweight Directory Access Protocol. Open, vendor-neutral, industry standard application protocol for
		accessing and maintaining distributed directory information services.

	LDIF
		LDAP Data Interchange Format. Standard plain text data interchange format for representing :term`LDAP` directory
		content and update requests. LDIF conveys directory content as a set of records, one record for each object (or
		entry). It also represents update requests, such as Add, Modify, Delete and Rename, as a set of records, one
		record for each update request.

	SSO
		Single Sign-On is a process that permits a user to access multiple services after going through user
		authentication (i.e., loggin in) only once. This involves authentication into all services the user has given
		permission to, after logging into a primary service. Among other benefits, SSO avoids the monotonous task of
		confirming identity over and over again through passwords or other authentication systems.

	SPNEGO
		Simple and Protected GSSAPI Negotiation Mechanism, often pronounced "spen-go", is a :term:`GSSAPI`
		pseudo-mechanism used by client-server software to negotiate the choice of security technology. SPNEGO is used
		when a client application wants to authenticate to a remote server, but neither end is sure what authentication
		protocols the other supports. The pseudo-mechanism uses a protocol to determine what common GSSAPI mechanisms
		are available, selects one and then dispatches all further security operations to it.

	GSSAPI
		The Generic Security Service Application Program Interface is an application programming interface (API) for
		programs to access security services. The GSSAPI is an IETF standard. It does not, by itself, provide any
		security. Instead, security-service vendors provide GSSAPI *implementations*. The definitive feature of GSSAPI
		applications is the exchange of opaque messages (tokens) which hide the implementation detail from the
		higher-level application.

	Kerberos
		Kerberos is an authentication protocol that supports the concept of Single Sign-On (:term:`SSO`). In the case of
		HTTP, support for Kerberos is usually provided using the :term:`SPNEGO` authentication mechanism. Apache does
		not itself support SPNEGO, but support can be added by means of the ``mod_auth_kerb`` authentication module.

	KDC
		A Key Distribution Center is a network service that supplies tickets and temporary sessions keys; or an instance
		of that service or the host on which it runs. The KDC services both initial ticket and ticket-granting requests.
		The initial ticket portion is sometimes referred to as the Authentication Server (or service). The
		ticket-granting ticket portion is sometimes referred to as the ticket-granting server (or service).

	Principal
		A principal is someone or something you authenticate or authenticate to. Types of principals are:

		user-principals
			:term:`Kerberos` representation of people sitting at a machine. Example: ``einstein@EXAMPLE.COM``.

		service-principals
			E.g., :term:`Kerberos` representation of a web server. Example: ``HTTP/intranet.example.com@EXAMPLE.COM``.
