<?php
namespace Causal\IgLdapSsoAuth\ViewHelpers;

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

use TYPO3\CMS\Backend\Utility\IconUtility;

/**
 * Render a configuration table
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ConfigurationTableViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Renders a configuration table.
	 *
	 * @param array|string $data
	 * @param bool $humanKeyNames
	 * @return string
	 */
	public function render($data, $humanKeyNames = FALSE) {
		$hasError = FALSE;
		$content = $this->renderTable($data, $humanKeyNames, 1, $hasError);
		return $content;
	}

	/**
	 * Renders a configuration table.
	 *
	 * @param array|string $data
	 * @param bool $humanKeyNames
	 * @param int $depth
	 * @param bool &$hasError
	 * @return string
	 */
	protected function renderTable($data, $humanKeyNames, $depth, &$hasError) {
		if (!is_array($data)) {
			return htmlspecialchars($data);
		} elseif (count($data) === 0) {
			return '<em>' . htmlspecialchars($this->translate('module_status.messages.empty')) . '</em>';
		}

		if (version_compare(TYPO3_version, '7.0', '<')) {
			$tableClass = 'typo3-dblist';
			$trClass = 'db_list_normal';
		} else {
			$tableClass = 'table table-striped table-hover';
			$trClass = '';
		}

		$content = array();
		foreach ($data as $key => $value) {
			$hasValueError = FALSE;
			$valueCell = $this->renderValueCell($value, $key, $depth, $hasValueError);
			$class = 'key';
			if ($hasValueError) {
				$hasError = TRUE;
				$class .= ' error';
			}
			if ($humanKeyNames) {
				$key = $this->processKey($key);
			}
			$content[] = sprintf('<tr class="' . $trClass . '"><td class="' . $class . '">%s</td>%s</tr>', htmlspecialchars($key), $valueCell);
		}

		return '<table class="' . $tableClass . '">' . implode($content, LF) . '</table>';
	}

	/**
	 * Renders a configuration value in a table cell.
	 *
	 * @param mixed $value
	 * @param string $key
	 * @param int $depth
	 * @param bool &$hasError
	 * @return string
	 */
	protected function renderValueCell($value, $key, $depth, &$hasError) {
		if ($key === '__errors') {
			$hasError = TRUE;
		}
		if (is_array($value)) {
			return sprintf('<td>%s</td>', $this->renderTable($value, FALSE, $depth + 1, $hasError));
		}

		$class = 'value-default';

		if (is_bool($value)) {
			if ($value === TRUE) {
				$icon = 'actions-edit-hide';
				$messageId = 'module_status.messages.enabled';
				$class = 'value-enabled';
			} else {
				$icon = 'actions-edit-unhide';
				$messageId = 'module_status.messages.disabled';
				$class = 'value-disabled';
			}
			$value = IconUtility::getSpriteIcon($icon) . ' ' . htmlspecialchars($this->translate($messageId));
		} elseif ($depth > 1 && $key === 'status') {
			if ($value === 'Success') {
				$icon = 'status-dialog-ok';
				$class = 'value-success';
			} else {
				$icon = 'status-dialog-warning';
				$class = 'value-error';
				$hasError = TRUE;
			}
			$value = IconUtility::getSpriteIcon($icon) . ' ' . htmlspecialchars($value);
		} elseif ($value instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
			$icon = $value instanceof \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup
				? 'status-user-group-backend'
				: 'status-user-group-frontend';
			$value = IconUtility::getSpriteIcon($icon, array('title' => 'id=' . $value->getUid())) . ' ' . htmlspecialchars($value->getTitle());
		} else {
			$value = htmlspecialchars($value);
		}

		return sprintf('<td class="%s">%s</td>', $class, $value);
	}

	/**
	 * Returns an meaningful description out of a configuration array key.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function processKey($key) {
		return $this->translate('module_status.configuration.' . $key);
	}

	/**
	 * Translates a label.
	 *
	 * @param string $id
	 * @param array $arguments
	 * @return NULL|string
	 */
	protected function translate($id, array $arguments = NULL) {
		$request = $this->controllerContext->getRequest();
		$extensionName = $request->getControllerExtensionName();
		$value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($id, $extensionName, $arguments);
		return $value !== NULL ? $value : $id;
	}

}
