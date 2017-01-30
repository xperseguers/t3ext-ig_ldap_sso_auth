.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _introduction:

Introduction
============

.. _what-it-does:

What does it do?
----------------

This extension enables import/update/deletion of users and groups (frontend, backend or both) from a LDAP-directory and
provides Single Sign-On (:term:`SSO`) for frontend users. These features make it the perfect choice when deploying TYPO3
as an intranet CMS.

In case the network topology makes it useful, this extension is able to work with multiple LDAP server configurations,
with a priority order based on the manual sorting of configuration records.

This extension is known to work with OpenLDAP and Active Directory (various versions).

Please consult the :ref:`faq` for additional information.


.. _screenshots:

Screenshots
-----------

.. figure:: ../Images/configuration-ldap.png
	:alt: Configuration of the LDAP server

	Configuration of the connection to the LDAP server


.. figure:: ../Images/configuration-fe-users.png
	:alt: Configuration of the frontend users

	Configuration of the frontend authentication, how to map LDAP attributes to TYPO3 fields and which groups are
	required or should be automatically assigned


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

*Causal Sàrl*, a Swiss company actively contributing to TYPO3, has taken over this extension which has been initially
developed by *Infoglobe*, a Canadian company specialized in open-source software.

We would like to thank:

- Support for TYPO3 8 LTS and some general bug fixes have been sponsored by *elementare teilchen*, Germany.
- Support for TYPO3 6.2 LTS and some further enhancements have been sponsored by the *Centre électronique de gestion
  (CEG)*, technically the IT department of the Swiss city Neuchâtel.
- Full user import was sponsored by the *Ecole d'Etudes Sociales et Pédagogiques*, in Lausanne, Switzerland.

.. tabularcolumns:: |p{7.53cm}|p{7.53cm}|

+---------------------------------------------------+---------------------------------------------------+
| .. image:: ../Images/logo-causal.png              | .. image:: ../Images/logo-ceg.png                 |
|     :alt: Causal Sàrl                             |     :alt: CEG                                     |
|     :width: 200px                                 |     :width: 200px                                 |
|                                                   |                                                   |
| Causal Sàrl                                       | Centre électronique de gestion (CEG)              |
|                                                   |                                                   |
| https://www.causal.ch                             | http://www.neuchatelville.ch/ceg                  |
+---------------------------------------------------+---------------------------------------------------+
| .. image:: ../Images/logo-elementare-teilchen.png | .. image:: ../Images/logo-eesp.png                |
|     :alt: elementare teilchen GmbH                |     :alt: EESP                                    |
|     :width: 200px                                 |     :width: 200px                                 |
|                                                   |                                                   |
| elementare teilchen GmbH                          | Ecole d'Etudes Sociales et Pédagogiques (EESP)    |
|                                                   |                                                   |
| http://www.elementare-teilchen.de                 | https://www.eesp.ch                               |
+---------------------------------------------------+---------------------------------------------------+
| .. image:: ../Images/logo-infoglobe.png           |                                                   |
|     :alt: Infoglobe                               |                                                   |
|     :width: 200px                                 |                                                   |
|                                                   |                                                   |
| Infoglobe                                         |                                                   |
|                                                   |                                                   |
| http://www.infoglobe.ca                           |                                                   |
+---------------------------------------------------+---------------------------------------------------+


.. _support:

Support
-------

In case you need help to configure this extension, please ask for free support in
`TYPO3 mailing lists <https://forum.typo3.org/index.php/f/10/>`_ or contact the maintainer for paid support.
