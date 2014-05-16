<?php

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Xavier Perseguers <xavier@typo3.org>
 *  (c) Michael Miousse <michael.miousse@infoglobe.ca>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

// Make sure that we are executed only in TYPO3 context
if (!defined('TYPO3_MODE')) {
	die('Access denied.');
}

/**
 * Class providing configuration checks for ig_ldap_sso_auth.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Miousse <michael.miousse@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class tx_igldapssoauth_emconfhelper {

	/**
	 * @var integer
	 */
	protected $errorType = t3lib_FlashMessage::OK;

	/**
	 * @var string
	 */
	protected $header;

	/**
	 * @var string
	 */
	protected $preText;

	/*
	 * @var array
	 */
	protected $problems = array();

	/**
	 * Checks the backend configuration and shows a message if necessary.
	 *
	 * @param array $params Field information to be rendered
	 * @param \TYPO3\CMS\Extensionmanager\ViewHelpers\Form\TypoScriptConstantsViewHelper $pObj: The calling parent object.
	 * @return string Messages as HTML if something needs to be reported
	 */
	public function checkConfiguration(array $params, $pObj) {
		$this->init();
		$problems = array();

		// Configuration of authentication service.
		$loginSecurityLevelBE = $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'];
		$loginSecurityLevelFE = $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'];

		$errorlevel = 'error';
		if ($loginSecurityLevelBE === 'rsa' || $loginSecurityLevelBE === 'normal' ||
			$loginSecurityLevelFE === 'rsa' || $loginSecurityLevelFE === 'normal') {

			$errorlevel = 'warning';
		}

		if ($loginSecurityLevelBE === 'challenged' || $loginSecurityLevelBE === 'superchallenged' || $loginSecurityLevelBE === '') {
			$this->setErrorLevel($errorlevel);

			$problems[] = <<<EOT
LDAP authentification for backend is not compatible with loginSecurityLevel set to "challenged" or "superchallenged" since the real password can never be sent against the LDAP repository.

Value of loginSecurityLevel should be changed manually to "normal" or even better "rsa" in the Install Tool.<br/><br/>

	\$GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] = 'normal';<br/>
	\$GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel'] = 'rsa';
EOT;

			$problems[] = 'Current value for backend is: "' . $loginSecurityLevelBE . '"' . "<br />\n" .
				'Current value for frontend is: "' . $loginSecurityLevelFE . '"';

		} elseif ($loginSecurityLevelFE === 'challenged' || $loginSecurityLevelFE === 'superchallenged' || $loginSecurityLevelFE === '') {
			$this->setErrorLevel($errorlevel);

			$problems[] = <<< EOT
LDAP authentification for Website-Users (FE) is not compatible with loginSecurityLevel set to "challenged" or "superchallenged" since the real password can never be sent against the LDAP repository.

Value of loginSecurityLevel should be changed manually to "normal" or even better "rsa" in the Install Tool.<br/><br/>

	\$GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'] = 'normal';<br/>
	\$GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'] = 'rsa';
EOT;

			$problems[] = 'Current value for backend is: "' . $loginSecurityLevelBE . '"' . "<br />\n" .
				'Current value for frontend is: "' . $loginSecurityLevelFE . '"';

		} else {
			$this->setErrorLevel('ok');
			$problems = array();
		}

		$this->problems = $problems;

		return $this->renderFlashMessage();
	}

	/**
	 * Sets the error level if no higher level is set already.
	 *
	 * @param string $level one out of "error", "ok", "warning", "info"
	 * @return void
	 */
	protected function setErrorLevel($level) {
		switch ($level) {
			case 'error':
				$this->errorType = t3lib_FlashMessage::ERROR;
				$this->header = 'Errors found in your configuration';
				$this->preText = '<br />';
				break;
			case 'warning':
				if ($this->errorType < t3lib_FlashMessage::ERROR) {
					$this->errorType = t3lib_FlashMessage::WARNING;
					$this->header = 'Warnings about your configuration';
					$this->preText = '<br />';
				}
				break;
			case 'info':
				if ($this->errorType < t3lib_FlashMessage::WARNING) {
					$this->errorType = t3lib_FlashMessage::INFO;
					$this->header = 'Additional information';
					$this->preText = '<br />';
				}
				break;
			case 'ok':
				// TODO: Remove INFO condition as it has lower importance
				if ($this->errorType < t3lib_FlashMessage::WARNING && $this->errorType != t3lib_FlashMessage::INFO) {
					$this->errorType = t3lib_FlashMessage::OK;
					$this->header = 'No errors were found';
					$this->preText = 'Configuration has been configured correctly.<br />';
				}
				break;
		}
	}

	/**
	 * Renders the flash messages if problems have been found.
	 *
	 * @return string The flash message as HTML.
	 */
	protected function renderFlashMessage() {
		$message = '';

		// If there are problems, render them into an unordered list
		if (count($this->problems) > 0) {
			$message = <<<EOT
<ul>
	<li>###PROBLEMS###</li>
</ul>
EOT;
			$message = str_replace('###PROBLEMS###', implode('<br />&nbsp;</li><li>', $this->problems), $message);
		}

		if (empty($message)) {
			$this->setErrorLevel('ok');
		}

		$message = $this->preText . $message;
		$flashMessage = t3lib_div::makeInstance('t3lib_FlashMessage', $message, $this->header, $this->errorType);

		return $flashMessage->render();
	}

	/**
	 * Initializes this object.
	 *
	 * @return void
	 */
	protected function init() {
		$requestSetup = $this->processPostData((array) $_REQUEST['data']);
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
	}

	/**
	 * Processes the information submitted by the user using a POST request and
	 * transforms it to a TypoScript node notation.
	 *
	 * @param array $postArray Incoming POST information
	 * @return array Processed and transformed POST information
	 */
	protected function processPostData(array $postArray = array()) {
		foreach ($postArray as $key => $value) {
			// TODO: Explain
			$parts = explode('.', $key, 2);

			if (count($parts) == 2) {
				// TODO: Explain
				$value = $this->processPostData(array($parts[1] => $value));
				$postArray[$parts[0] . '.'] = array_merge((array) $postArray[$parts[0] . '.'], $value);
			} else {
				// TODO: Explain
				$postArray[$parts[0]] = $value;
			}
		}

		return $postArray;
	}

}
