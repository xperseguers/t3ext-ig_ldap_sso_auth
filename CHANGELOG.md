# Changelog for `EXT:ig_ldap_sso_auth`

Versions before 4.0.0 are skipped because they are identical to the versions in https://github.com/xperseguers/t3ext-ig_ldap_sso_auth.


## Version 4.0.0

- c24704e [TASK] Fix some DI problems which are mainly cause by the massive use of static methods.
- b7be06f [TASK] Use the LDAP instance we already have.
- b693c1d [BUGFIX] Unset connection after disconnect.
- a62c50e [TASK] Streamline handling of LDAP connection classes a bit by using more DI.
- dff44a4 [TASK] Switch from view in controller to module template to get the backend module mostly working.
- 9ba3da9 [TASK] Use better way for checking empty values.
- 2cfa1e6 [TASK] Use modern string functions (makes code more readable).
- d234d6c [TASK] Simplify code by removing unused variables only used for return values.
- 084269f [TASK] Simplify some checks.
- 633cc80 [TASK] Remove unused variable.
- e52a81b [TASK] Remove unused imports.
- 372c4c8 [TASK] Fix type annotations for classes removed in TYPO3 core.
- 2932484 [TASK] Remove useless casts.
- 24414d1 [TASK] Remove IMHO useless memory freeing.
- 9f7c4b5 [TASK] Merge calls to str_replace() for better performance; reformat code.
- 5a62207 [TASK] Remove view helpers no longer present in TYPO3.
- 79a2916 [TASK] Add correct response for all action methods.
- 30ddd25 [TASK] Add check for null values.
- 6645c97 [TASK] Port over classes removed from the TYPO3 core (from TYPO3 v11).
- 9483433 [TASK] Remove override of view helper (is no longer possible with TYPO3 v12).
- 1778ec3 [TASK] Modernize module registration.
- 7c1d144 [TASK] Modernize TYPO3 parsing.
- 52420f9 [TASK] Comment-out deprecated code that I'm not sure whether it is needed.
- 8022e14 [TASK] Remove access to remove property.
- 6b32367 [TASK] Fix use of remove controller context.
- 8ee79f9 [TASK] Remove bootstrapping as that should no longer be required in TYPO3.
- 85ff913 [TASK] Remove checks for PHP 7.4: as we only support TYPO3 v12, we're also only supporting PHP 8.1and upwards.
- f4bfe06 [TASK] Remove check for no longer existing setting.
- 7f5bde3 [TASK] Fix access to extension configuration.
- df2ff24 [TASK] Ignore false positives in the extension scanner.
- c5fcfb7 [TASK] Remove check for non-existent property and use DI instead.
- 5a99b6b [TASK] Replace non-supported hook by event listener.
- 04a887c [TASK] Fix registration of type converter.
- d8cdbf2 [TASK] Replace signal-slot stuff with events.
- d516801 [TASK] Fix base class for tests.
- de052aa [TASK] Fix return type docs.
- 4515b33 [TASK] Remove context-sensitive help that no longer exists in TYPO3.
- 9630f0b [TASK] Add comments, re-order code.
- a70a2e0 [TASK] Enable dependency injection.
- 419e5f7 [TASK] Fix password handling.
- de8c9cc [TASK] Remove all version-switches.
- c741e9f [TASK] Fix use of TYPO3_MODE constant.
- 4bfce28 [TASK] Remove use of Extbase object manager.
- baaf598 [TASK] Remove custom viewhelper for flash messages as core viewhelpers are final now; also, fix the severity constants which are now an enum.
- 5b0642d [TASK] Remove use of deprecated stuff for database handling/Doctrine.
- 9f7f955 [TASK] Update compatibility information; we're dropping everything below v12 to simplify things.
- 7236e5f Merge pull request #165 from lucmuller/patch-2
- 1b90638 [TASK] Add hint about global configuration
- 8369d16 [BUGFIX] Fix unclickable links
- b6167ec [code formating] better conditions in Classes/Hooks/IconFactory.php
- 94bd8c5 Code Formatting
- 0518178 [BUGFIX] Prevent PHP warnings in ext_localconf.php
- eca7985 Fixes warning on inexistants keys
- 9f06c08 Merge pull request #163 from bnf/ldap-search-php72
- fc8ecae [TASK] Fix ldap_search controls PHP 7.2 incompatibility
- e39b039 [TASK] Move disable mapping to keep "admin = 1" at the end
- 182b60b [TASK] Adapt link to the translation server (now: Crowdin)
- d33827b Update Crowdin configuration file
- bc47383 [BUGFIX] Fix PHP 8 warning with IconFactory for new records
