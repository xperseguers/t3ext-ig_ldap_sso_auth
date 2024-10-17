.. include:: ../Includes.rst.txt
.. _introduction:

Introduction
============

.. _what-it-does:

What does it do?
----------------

This extension enables import/update/deletion of users and groups (frontend,
backend or both) from a LDAP-directory and provides Single Sign-On (:term:`SSO`)
for frontend users. These features make it the perfect choice when deploying
TYPO3 as an intranet CMS.

In case the network topology makes it useful, this extension is able to work
with multiple LDAP server configurations, with a priority order based on the
manual sorting of configuration records.

This extension is known to work with OpenLDAP and Active Directory (various
versions).

Please consult the :ref:`faq` for additional information.


.. _screenshots:

Screenshots
-----------

.. figure:: ../Images/configuration-ldap.png
	:alt: Configuration of the LDAP server

	Configuration of the connection to the LDAP server


.. figure:: ../Images/configuration-fe-users.png
	:alt: Configuration of the frontend users

	Configuration of the frontend authentication, how to map LDAP attributes to
	TYPO3 fields and which groups are required or should be automatically
	assigned


.. figure:: ../Images/configuration-be-groups.png
	:alt: Configuration of backend groups

	Configuration of the retrieval of backend user groups


.. figure:: ../Images/status.png
	:alt: LDAP status

	Status of the LDAP connection


.. figure:: ../Images/search-wizard.png
	:alt: Search wizard

	Search wizard as backend module


.. _sponsorship:

Sponsorship
-----------

*Causal Sàrl*, a Swiss company actively contributing to TYPO3, has taken over
this extension which has been initially developed by *Infoglobe*, a former
Canadian company specialized in open-source software.

*Causal Sàrl* is regularly working on compatibility updates for new TYPO3
releases.

We would like to thank:

- Support for TYPO3 12 LTS would not have been possible without the support of
  many members of the TYPO3 community. We would like to thank all of them and
  their companies for their support.
- Migration wizard from the import scheduler task to the new Symfony command has
  been sponsored by the GYB (Gymnase intercantonal de la Broye) and
  hemmer.ch SA.
- Support for TYPO3 11 LTS has been sponsored by *TEC Competence UG*, Germany.
- All the various developers reportings bugs and providing patches in the
  bug tracker.

.. tabularcolumns:: |p{7.53cm}|p{7.53cm}|

+---------------------------------------------------+---------------------------------------------------+
| .. image:: ../Images/logo-causal.png              | .. image:: ../Images/logo-tec-competence.png      |
|     :alt: Causal Sàrl                             |     :alt: TEC Competence UG                       |
|     :width: 200px                                 |     :width: 200px                                 |
|                                                   |                                                   |
| Causal Sàrl                                       | TEC Competence UG (halftungsbeschränkt) & Co. KG  |
|                                                   |                                                   |
| https://www.causal.ch                             | https://www.tec-competence.com                    |
+---------------------------------------------------+---------------------------------------------------+


.. _support:

Support
-------

In case you need help to configure this extension, please ask for free support
in `TYPO3 Slack <https://typo3.slack.com/>`_ or contact the maintainer for paid
support.
