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

		// Process the context field
		$parameters = array(
			'field' => 'context',
			'label' => 'task.import_users.field.context',
			'options' => array(
				'both' => 'LLL:task.import_users.field.context.both',
				'FE' => 'LLL:task.import_users.field.context.fe',
				'BE' => 'LLL:task.import_users.field.context.be',
			),
			'value' => $task ? $task->getContext() : NULL,
		);
		$this->registerSelect($taskInfo, $schedulerModule->CMD, $parameters, $additionalFields);

		// Process the configuration field
		$parameters = array(
			'field' => 'configuration',
			'label' => 'task.import_users.field.configuration',
			'options' => array(
				0 => 'LLL:task.import_users.field.configuration.all',
			),
			'value' => $task ? $task->getConfiguration() : NULL,
		);
		// Get the existing LDAP configurations
		/** @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository $configurationRepository */
		$configurationRepository = GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Domain\\Repository\\ConfigurationRepository');
		$ldapConfigurations = $configurationRepository->findAll();
		foreach ($ldapConfigurations as $configuration) {
			$uid = $configuration->getUid();
			$parameters['options'][$uid] = $configuration->getName();
		}
		$this->registerSelect($taskInfo, $schedulerModule->CMD, $parameters, $additionalFields);

		// Process the missing user handling field
		$parameters = array(
			'field' => 'missinguserhandling',
			'label' => 'task.import_users.field.missinguserhandling',
			'options' => array(
				'nothing' => 'LLL:task.import_users.field.missinguserhandling.nothing',
				'disable' => 'LLL:task.import_users.field.missinguserhandling.disable',
				'delete' => 'LLL:task.import_users.field.missinguserhandling.delete',
			),
			'value' => $task ? $task->getMissingUsersHandling() : NULL,
		);
		$this->registerSelect($taskInfo, $schedulerModule->CMD, $parameters, $additionalFields);

		// Process the restored user handling field
		$parameters = array(
			'field' => 'restoreduserhandling',
			'label' => 'task.import_users.field.restoreduserhandling',
			'options' => array(
				'nothing' => 'LLL:task.import_users.field.restoreduserhandling.nothing',
				'enable' => 'LLL:task.import_users.field.restoreduserhandling.enable',
				'undelete' => 'LLL:task.import_users.field.restoreduserhandling.undelete',
				'both' => 'LLL:task.import_users.field.restoreduserhandling.both',
			),
			'value' => $task ? $task->getRestoredUsersHandling() : NULL,
		);
		$this->registerSelect($taskInfo, $schedulerModule->CMD, $parameters, $additionalFields);

		return $additionalFields;
	}

	/**
	 * Generates and registers a HTML select field.
	 *
	 * @param array $taskInfo Values of the fields from the add/edit task form
	 * @param string $command
	 * @param array $parameters
	 * @param array $additionalFields
	 * @return void
	 */
	protected function registerSelect(array &$taskInfo, $command, array $parameters, array &$additionalFields) {
		$languageService = $this->getLanguageService();
		$extensionKey = 'ig_ldap_sso_auth';
		$prefix = 'tx_igldapssoauth_';
		$localizationPrefix = 'LLL:EXT:' . $extensionKey . '/Resources/Private/Language/locallang.xlf:';

		$fieldName = $prefix . $parameters['field'];

		// Initialize extra field value, if not yet defined
		if (empty($taskInfo[$fieldName])) {
			if ($command === 'add') {
				$taskInfo[$fieldName] = current($parameters['options']);
			} elseif ($command === 'edit') {
				// In case of edit, set to internal value if no data was submitted already
				$taskInfo[$fieldName] = $parameters['value'];
			}
		}

		// Write the code for the field
		$fieldID = 'task_' . $fieldName;
		$fieldCode = '<select name="tx_scheduler[' . $fieldName . ']" id="' . $fieldID . '" class="form-control">';

		// Assemble selector options
		foreach ($parameters['options'] as $optionKey => $label) {
			$selected = '';
			if ((string)$taskInfo[$fieldName] === (string)$optionKey) {
				$selected = ' selected="selected"';
			}
			if (strpos($label, 'LLL:') === 0) {
				$optionLabel = $languageService->sL($localizationPrefix . substr($label, 4), TRUE);
			} else {
				$optionLabel = htmlspecialchars($label);
			}
			$fieldCode .= '<option value="' . htmlspecialchars($optionKey) . '"' . $selected . '>' . $optionLabel . '</option>';
		}

		$fieldCode .= '</select>';

		// Register the field
		$additionalFields[$fieldID] = array(
			'code'     => $fieldCode,
			'label'    => $localizationPrefix . $parameters['label'],
			'cshLabel' => $fieldID
		);
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
