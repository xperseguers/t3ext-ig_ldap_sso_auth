.. _breaking-changes:

Breaking Changes
================

.. _breaking-changes-v400:

Version 4.0.0
-------------

- Configuration option ``useExtConfConfiguration`` has been removed completely.
  This option was used to enable configuring LDAP settings via PHP code instead
  of a database record. In an attempt to streamline and be more consistent with
  internal types, this option has been removed. If you were using this option,
  you will need to migrate your custom code to use the PSR-14 event
  `ConfigurationLoadedEvent <https://github.com/xperseguers/t3ext-ig_ldap_sso_auth/blob/master/Classes/Event/CustomConfigurationEvent.php>`__
  instead.
