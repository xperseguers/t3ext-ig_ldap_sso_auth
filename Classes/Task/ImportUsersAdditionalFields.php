<?php
namespace Causal\IgLdapSsoAuth\Task;

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

/**
 * Provides additional fields to the "Synchronize Users" Scheduler task.
 *
 * @author     Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ImportUsersAdditionalFields implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface {

	/**
	 * Gets additional fields to render in the form to add/edit a task.
	 *
	 * Two extra fields are provided. One is used to define the context (FE, BE or both)
	 * and one to select a LDAP configuration (or all).
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task The task object being edited. Null when adding a task!
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		/** @var \Causal\IgLdapSsoAuth\Task\ImportUsers $task */
		$additionalFields = array();
		$languageService = $this->getLanguageService();

		// Process the context field
		$fieldName = 'tx_igldapssoauth_context';
		// Initialize extra field value, if not yet defined
		if (empty($taskInfo[$fieldName])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo[$fieldName] = 'both';
			} elseif ($schedulerModule->CMD == 'edit') {
				// In case of edit, set to internal value if no data was submitted already
				$taskInfo[$fieldName] = $task->getContext();
			}
		}

		// Write the code for the field
		$fieldID = 'task_' . $fieldName;
		$fieldCode  = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldID . '" class="form-control">';
		// Assemble selector options
		$selected = '';
		if ($taskInfo[$fieldName] == 'both') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="both"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.context.both', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'FE') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="FE"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.context.fe', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'BE') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="BE"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.context.be', TRUE) . '</option>';
		$fieldCode .= '</select>';
		// Register the field
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.context',
			'cshLabel' => $fieldID
		);

		// Process the configuration field
		$fieldName = 'tx_igldapssoauth_configuration';
		// Initialize extra field value, if not yet defined
		if (empty($taskInfo[$fieldName])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo[$fieldName] = 0;
			} elseif ($schedulerModule->CMD == 'edit') {
				// In case of edit, set to internal value if no data was submitted already
				$taskInfo[$fieldName] = $task->getConfiguration();
			}
		}

		// Write the code for the field
		$fieldID = 'task_' . $fieldName;
		$fieldCode  = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldID . '" class="form-control">';
		// Assemble selector options
		$selected = '';
		$taskInfo[$fieldName] = intval($taskInfo[$fieldName]);
		if ($taskInfo[$fieldName] === 0) {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="0"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.configuration.all', TRUE) . '</option>';
		// Get the existing LDAP configurations
		/** @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository */
		$configurationRepository = GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Domain\\Repository\\ConfigurationRepository');
		$ldapConfigurations = $configurationRepository->findAll();
		foreach ($ldapConfigurations as $configuration) {
			$uid = $configuration->getUid();
			$selected = '';
			if ($taskInfo[$fieldName] == $uid) {
				$selected = ' selected="selected"';
			}
			$fieldCode .= '<option value="' . $uid . '"' . $selected . '>' . htmlspecialchars($configuration->getName()) . '</option>';
		}
		$fieldCode .= '</select>';
		// Register the field
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.configuration',
			'cshLabel' => $fieldID
		);

		// Process the missing user handling field
		$fieldName = 'tx_igldapssoauth_missinguserhandling';
		// Initialize extra field value, if not yet defined
		if (empty($taskInfo[$fieldName])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo[$fieldName] = 'nothing';
			} elseif ($schedulerModule->CMD == 'edit') {
				// In case of edit, set to internal value if no data was submitted already
				$taskInfo[$fieldName] = $task->getMissingUsersHandling();
			}
		}

		// Write the code for the field
		$fieldID = 'task_' . $fieldName;
		$fieldCode  = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldID . '" class="form-control">';
		// Assemble selector options
		$selected = '';
		if ($taskInfo[$fieldName] == 'disable') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="disable"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.missinguserhandling.disable', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'delete') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="delete"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.missinguserhandling.delete', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'nothing') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="nothing"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.missinguserhandling.nothing', TRUE) . '</option>';
		$fieldCode .= '</select>';
		// Register the field
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.missinguserhandling',
			'cshLabel' => $fieldID
		);

		// Process the restored user handling field
		$fieldName = 'tx_igldapssoauth_restoreduserhandling';
		// Initialize extra field value, if not yet defined
		if (empty($taskInfo[$fieldName])) {
			if ($schedulerModule->CMD == 'add') {
				$taskInfo[$fieldName] = 'nothing';
			} elseif ($schedulerModule->CMD == 'edit') {
				// In case of edit, set to internal value if no data was submitted already
				$taskInfo[$fieldName] = $task->getRestoredUsersHandling();
			}
		}

		// Write the code for the field
		$fieldID = 'task_' . $fieldName;
		$fieldCode  = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldID . '" class="form-control">';
		// Assemble selector options
		$selected = '';
		if ($taskInfo[$fieldName] == 'enable') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="enable"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.restoreduserhandling.enable', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'undelete') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="undelete"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.restoreduserhandling.undelete', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'both') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="both"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.restoreduserhandling.both', TRUE) . '</option>';
		$selected = '';
		if ($taskInfo[$fieldName] == 'nothing') {
			$selected = ' selected="selected"';
		}
		$fieldCode .= '<option value="nothing"' . $selected . '>' . $languageService->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.restoreduserhandling.nothing', TRUE) . '</option>';
		$fieldCode .= '</select>';
		// Register the field
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => 'LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang.xlf:task.import_users.field.restoreduserhandling',
			'cshLabel' => $fieldID
		);

		return $additionalFields;
	}

	/**
	 * Validates the additional fields' values.
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the scheduler backend module
	 * @return boolean TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule) {
		// Since only valid values could be chosen from the selectors, always return true
		return TRUE;
	}

	/**
	 * Takes care of saving the additional fields' values in the task's object.
	 *
	 * @param array $submittedData An array containing the data submitted by the add/edit task form
	 * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the scheduler backend module
	 * @return void
	 */
	public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task) {
		/** @var \Causal\IgLdapSsoAuth\Task\ImportUsers $task */
		$task->setContext($submittedData['tx_igldapssoauth_context']);
		$task->setConfiguration($submittedData['tx_igldapssoauth_configuration']);
		$task->setMissingUsersHandling($submittedData['tx_igldapssoauth_missinguserhandling']);
		$task->setRestoredUsersHandling($submittedData['tx_igldapssoauth_restoreduserhandling']);
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
