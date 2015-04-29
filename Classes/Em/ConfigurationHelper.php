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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Class providing configuration checks for ig_ldap_sso_auth.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
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

		if ($loginSecurityLevelFE === 'challenged' || $loginSecurityLevelFE === 'superchallenged' || $loginSecurityLevelFE === '') {
			$this->setErrorLevel($errorlevel);

			$problems[] = $this->translate('settings.errors.invalidFrontendSecurityLevel');
			$problems[] = $this->translate('settings.errors.currentSecurityLevel', array($loginSecurityLevelFE, $loginSecurityLevelBE));
		} elseif ($loginSecurityLevelBE === 'challenged' || $loginSecurityLevelBE === 'superchallenged' || $loginSecurityLevelBE === '') {
			$this->setErrorLevel($errorlevel);

			$problems[] = $this->translate('settings.errors.invalidBackendSecurityLevel');
			$problems[] = $this->translate('settings.errors.currentSecurityLevel', array($loginSecurityLevelFE, $loginSecurityLevelBE));
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
				$this->header = $this->translate('settings.errors.error');
				$this->preText = '<br />';
				break;
			case 'warning':
				if ($this->errorType < FlashMessage::ERROR) {
					$this->errorType = FlashMessage::WARNING;
					$this->header = $this->translate('settings.errors.warning');
					$this->preText = '<br />';
				}
				break;
			case 'ok':
			default:
				if ($this->errorType < FlashMessage::WARNING) {
					$this->errorType = FlashMessage::OK;
					$this->header = $this->translate('settings.errors.ok');
					$this->preText = $this->translate('settings.errors.success');
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

	/**
	 * Translates a label.
	 *
	 * @param string $id
	 * @param array $arguments
	 * @return string
	 */
	protected function translate($id, array $arguments = NULL) {
		$value = $this->getLanguageService()->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:' . $id);
		$value = empty($value) ? $id : $value;

		if (is_array($arguments) && $value !== NULL) {
			return vsprintf($value, $arguments);
		} else {
			return $value;
		}
	}

	/**
	 * Returns the LanguageService.
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
