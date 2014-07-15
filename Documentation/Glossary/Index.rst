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