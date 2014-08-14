.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: ../Includes.txt


.. _admin-manual-cas:

CAS
---

The last tab is for CAS configurations. You only have to fill it if you want to use a CAS server to implement some
single sign on (SSO).

- **Host:** Host of your CAS server

- **URI:** Path to append to the host used if the CAS sever is not at the root of your host.

  Example: ``/userSSo/cas`` in the string ``localhost/userSSo/cas``

- **Service URL:** Specific url for your CAS

- **Port:** Port on which you CAS is configure

- **Back URL:** URL to return to in case of a CAS login
