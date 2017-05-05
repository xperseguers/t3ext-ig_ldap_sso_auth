.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

.. include:: Includes.txt


.. _links:

Links
-----

:TER:
	https://typo3.org/extensions/repository/view/ig_ldap_sso_auth

:Packagist:
	https://packagist.org/packages/causal/ig_ldap_sso_auth

:Bug Tracker:
	https://forge.typo3.org/projects/extension-ig_ldap_sso_auth/issues

:Release Notes:
	https://forge.typo3.org/projects/extension-ig_ldap_sso_auth/wiki

:Git Repository:
	https://git.typo3.org/TYPO3CMS/Extensions/igLdapSsoAuth.git

:Translation:
	https://translation.typo3.org/projects/TYPO3.ext.ig_ldap_sso_auth/

:Contact:
	`@xperseguers <https://twitter.com/xperseguers>`__


.. _links-how-to-contribute:

How to contribute
^^^^^^^^^^^^^^^^^

This extension is using the same contribution workflow as for TYPO3 Core, using https://review.typo3.org for the review
process. Please read more about the workflow in the
`TYPO3 wiki <https://wiki.typo3.org/Contribution_Walkthrough_Life_Of_A_Patch>`__.


.. _links-how-to-contribute-tldr:

tl;dr
"""""

.. code-block:: bash

	cd /path/to/site/typo3conf/ext/

	# Replace TER version with a git clone (or use symbolic link if you prefer)
	rm -rf ig_ldap_sso_auth
	git clone https://git.typo3.org/TYPO3CMS/Extensions/igLdapSsoAuth.git
	cd ig_ldap_sso_auth

	# Setup your environment
	git config user.name "Your Name"
	git config user.email "your-email@example.com"
	git config branch.autosetuprebase remote
	# TODO: replace "xperseguers" with your own typo3.org username
	git config url."ssh://xperseguers@review.typo3.org:29418".pushInsteadOf https://git.typo3.org

	# Install commit hook (if this fails, please read "New to Gerrit?" below)
	# TODO: replace "xperseguers" with your own typo3.org username
	scp -p -P 29418 xperseguers@review.typo3.org:hooks/commit-msg .git/hooks/

You may now change, enhance and/or fix whatever you want, then submit your patch:

.. code-block:: bash

	cd /path/to/site/typo3conf/ext/ig_ldap_sso_auth
	# Stage changes for commit (don't add everything blindly of course!)
	git add .
	# Commit (or amend using "git commit --amend" if enhancing an existing patch)
	git commit
	# Send patch for review
	git push origin HEAD:refs/for/master

.. admonition:: New to Gerrit?
	:class: tip

	You may need to configure your Gerrit SSH key in order to push your patches.

	#. Open https://review.typo3.org and authenticate with your typo3.org username.
	#. Go to :menuselection:`Settings --> SSH public keys` and add your SSH public key.

	Please refer to https://wiki.typo3.org/Contribution_Walkthrough_Environment_Setup if you have problems.


.. _links-how-to-contribute-rules:

Contribution rules
""""""""""""""""""

- There must be a ticket in the project's bug tracker explaining the problem / the suggested enhancement
- `PSR-2`_ coding guidelines are enforced
- Commit message complies to the `format used by TYPO3`_ (the "releases:" line is useless here)
- Unique logical change per patch [#]_

.. _PSR-2: http://www.php-fig.org/psr/psr-2/
.. _format used by TYPO3: https://wiki.typo3.org/CommitMessage_Format_(Git)


.. rubric:: Footnotes

.. [#] The term "patch" is used in the sense of "patch set" in Gerrit, and may be the result of multiple
   (amended) commits.
