<?php
namespace Causal\IgLdapSsoAuth\Em;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

// Make sure that we are executed only in TYPO3 context
defined('TYPO3_MODE') or die();

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Class providing configuration checks for ig_ldap_sso_auth.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @author     Michael Miousse <michael.miousse@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ConfigurationHelper {

	/**
	 * @var integer
	 */
	protected $errorType = FlashMessage::OK;

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
	 * Initializes this object.
	 *
	 * @return void
	 */
	protected function init() {
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['ig_ldap_sso_auth']);
	}

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
				$this->errorType = FlashMessage::ERROR;
				$this->header = 'Errors found in your configuration';
				$this->preText = '<br />';
				break;
			case 'warning':
				if ($this->errorType < FlashMessage::ERROR) {
					$this->errorType = FlashMessage::WARNING;
					$this->header = 'Warnings about your configuration';
					$this->preText = '<br />';
				}
				break;
			case 'info':
				if ($this->errorType < FlashMessage::WARNING) {
					$this->errorType = FlashMessage::INFO;
					$this->header = 'Additional information';
					$this->preText = '<br />';
				}
				break;
			case 'ok':
				// TODO: Remove INFO condition as it has lower importance
				if ($this->errorType < FlashMessage::WARNING && $this->errorType != FlashMessage::INFO) {
					$this->errorType = FlashMessage::OK;
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
		/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
		$flashMessage = GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$message,
			$this->header,
			$this->errorType
		);

		return $flashMessage->render();
	}

}
