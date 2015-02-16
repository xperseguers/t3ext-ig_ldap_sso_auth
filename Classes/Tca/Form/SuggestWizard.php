<?php
namespace Causal\IgLdapSsoAuth\Tca\Form;

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

/**
 * Suggest wizard.
 *
 * @author     Xavier Perseguers <xavier@typo3.org>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class SuggestWizard {

	/**
	 * Renders a suggestion for the mapping.
	 *
	 * @param array $PA
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $pObj
	 * @return string
	 */
	public function render(array &$PA, \TYPO3\CMS\Backend\Form\FormEngine $pObj) {
		$serverType = (int)$PA['row']['ldap_server'];

		if (substr($PA['field'], -7) === '_basedn') {
			$suggestion = $this->suggestBaseDn($PA);
		} else {
			$suggestion = $this->suggestMappingOrFilter($serverType, $PA);
		}

		if (!empty($suggestion)) {
			$out[] = '<div style="margin:-2.5em 0 0 1em;">';
			$out[] = '<strong>' . $GLOBALS['LANG']->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:suggestion.server.' . $serverType, TRUE) . '</strong>';

			$suggestId = 'tx_igldapssoauth_suggest_' . $PA['field'];
			$out[] = '<pre style="margin:1em 0;" id="' . $suggestId . '">';

			$suggestion = htmlentities($suggestion);

			// Support for basic styling (BBCode)
			$suggestion = preg_replace('#\\[i\\](.*)\\[/i\\]#', '<em>\\1</em>', $suggestion);

			$out[] = $suggestion . '</pre>';

			$onclick = "var node=document.getElementById('$suggestId');document.{$PA['formName']}['{$PA['itemName']}'].value=(node.innerText || node.textContent);";
			$onclick .= implode('', $PA['fieldChangeFunc']);	// Necessary to tell TCEforms that the value is updated
			$button = '<input type="button" value="' . $GLOBALS['LANG']->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xml:suggestion.copy', TRUE) . '" onclick="' . htmlspecialchars($onclick) . '" class="formField" />';
			$out[] = $button;

			$out[] = '</div>';

			$suggestion = implode(LF, $out);
		}

		return $suggestion;
	}

	/**
	 * Suggests a base DN.
	 *
	 * @param array $PA
	 * @return string
	 */
	protected function suggestBaseDn(array $PA) {
		$bindDnParts = explode(',', $PA['row']['ldap_binddn']);
		$suggestion = count($bindDnParts) > 2
			? implode(',', array_slice($bindDnParts, -2))
			: '';
		return $suggestion;
	}

	/**
	 * Suggests a mapping or a filter.
	 *
	 * @param int $serverType
	 * @param array $PA
	 * @return string
	 */
	protected function suggestMappingOrFilter($serverType, array $PA) {
		if (substr($PA['field'], -8) === '_mapping') {
			$prefix = 'mapping_';
			$table = substr($PA['field'], 0, -8);
		} else {
			$prefix = 'filter_';
			$table = substr($PA['field'], 0, -7);
		}

		$templatePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ig_ldap_sso_auth') . 'Resources/Private/Templates/';
		// Try a specific configuration for this server
		$templateFileName = $templatePath . $prefix . $table . '_' . $serverType . '.txt';
		if (!is_file($templateFileName)) {
			// Try a generic configuration
			$templateFileName = $templatePath . $prefix . $table . '.txt';
			if (!is_file($templateFileName)) {
				// No suggestion available
				return '';
			}
		}

		$content = file_get_contents($templateFileName);
		return trim($content);
	}

}
