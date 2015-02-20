<?php
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
 * Centralizes the code for importing users from LDAP/AD sources.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class Tx_IgLdapSsoAuth_Utility_UserImport {
	/**
	 * Synchronization context (may be FE, BE or both).
	 *
	 * @var string
	 */
	protected $context;

	/**
	 * Selected LDAP configuration.
	 *
	 * @var array
	 */
	protected $configuration;

	/**
	 * Which table to import users into.
	 *
	 * @var string
	 */
	protected $userTable;

	/**
	 * Which table to import groups into.
	 *
	 * @var string
	 */
	protected $groupTable;

	/**
	 * Total users added (for reporting).
	 *
	 * @var int
	 */
	protected $usersAdded = 0;

	/**
	 * Total users updated (for reporting).
	 *
	 * @var int
	 */
	protected $usersUpdated = 0;

	public function __construct($configurationId, $context) {
		// Load the configuration
		tx_igldapssoauth_config::init(
			$context,
			$configurationId
		);
		// Store current context and get related configuration
		$this->context = $context;
		$this->configuration = ($context === 'be')
			? tx_igldapssoauth_config::getBeConfiguration()
			: tx_igldapssoauth_config::getFeConfiguration();
		// Define related tables
		if ($context === 'be') {
			$this->userTable = 'be_users';
			$this->groupTable = 'be_groups';
		} else {
			$this->userTable = 'fe_users';
			$this->groupTable = 'fe_groups';
		}
	}

	/**
	 * Disables all users related to the current configuration.
	 *
	 * @return void
	 */
	public function disableUsers() {
		tx_igldapssoauth_typo3_user::disableForConfiguration(
			$this->userTable,
			tx_igldapssoauth_config::getUid()
		);
	}

	/**
	 * Deletes all users related to the current configuration.
	 *
	 * @return void
	 */
	public function deleteUsers() {
		tx_igldapssoauth_typo3_user::deleteForConfiguration(
			$this->userTable,
			tx_igldapssoauth_config::getUid()
		);
	}

	/**
	 * Fetches all possible LDAP/AD users for a given configuration and context.
	 *
	 * @param bool $partial TRUE to fetch remaining entries when a partial result set was returned
	 * @return array
	 */
	public function fetchLdapUsers($partial = FALSE) {

		// Get the users from LDAP/AD server
		$ldapUsers = array();
		if (!empty($this->configuration['users']['basedn'])) {
			if (!$partial) {
				$filter = tx_igldapssoauth_config::replace_filter_markers($this->configuration['users']['filter']);
				$attributes = tx_igldapssoauth_config::get_ldap_attributes($this->configuration['users']['mapping']);
				$ldapUsers = tx_igldapssoauth_ldap::search($this->configuration['users']['basedn'], $filter, $attributes);
			} else {
				$ldapUsers = tx_igldapssoauth_ldap::searchNext();
			}
			unset($ldapUsers['count']);
		}

		return $ldapUsers;
	}

	/**
	 * Returns TRUE is a previous call to Tx_IgLdapSsoAuth_Utility_UserImport::fetchLdapUsers() returned
	 * a partial result set.
	 *
	 * @return bool
	 */
	public function hasMoreLdapUsers() {
		return tx_igldapssoauth_ldap::isPartialSearchResult();
	}

	/**
	 * Fetches all existing TYPO3 users related to the given LDAP/AD users.
	 *
	 * @param array $ldapUsers List of LDAP/AD users
	 * @return array
	 */
	public function fetchTypo3Users($ldapUsers) {

		// Populate an array of TYPO3 users records corresponding to the LDAP users
		// If a given LDAP user has no associated user in TYPO3, a fresh record
		// will be created so that $ldapUsers[i] <=> $typo3Users[i]
		$typo3UserPid = tx_igldapssoauth_config::get_pid($this->configuration['users']['mapping']);
		$typo3Users = tx_igldapssoauth_auth::get_typo3_users(
			$ldapUsers,
			$this->configuration['users']['mapping'],
			$this->userTable,
			$typo3UserPid
		);
		return $typo3Users;
	}

	/**
	 * Imports a given user to the TYPO3 database.
	 *
	 * @param array $user Local user information
	 * @param array $ldapUser LDAP user information
	 * @param string $restoreBehavior How to restore users (only for update)
	 * @return array Modified user data
	 * @throws Exception
	 */
	public function import($user, $ldapUser, $restoreBehavior = 'both') {
		// Store the extra data for later restore and remove it
		if (isset($user['__extraData'])) {
			$extraData = $user['__extraData'];
			unset($user['__extraData']);
		}

		if (empty($user['uid'])) {
			// Set other necessary information for a new user
			// First make sure to be acting in the right context
			tx_igldapssoauth_config::setTypo3Mode($this->context);
			$user['username'] = tx_igldapssoauth_typo3_user::setUsername($user['username']);
			$user['password'] = tx_igldapssoauth_typo3_user::setRandomPassword();
			$typo3Groups = tx_igldapssoauth_auth::get_user_groups($ldapUser, $this->configuration, $this->groupTable);
			if ($typo3Groups === NULL) {
				// Required LDAP groups are missing: quit!
				return $user;
			}
			$user = tx_igldapssoauth_typo3_user::set_usergroup($typo3Groups, $user, NULL, $this->groupTable);

			$user = tx_igldapssoauth_typo3_user::add($this->userTable, $user);
			$this->usersAdded++;
		} else {
			// Restore user that may have been previously deleted or disabled, depending on chosen behavior
			// (default to both undelete and re-enable)
			switch ($restoreBehavior) {
				case 'enable':
					$user[$GLOBALS['TCA'][$this->userTable]['ctrl']['enablecolumns']['disabled']] = 0;
					break;
				case 'undelete':
					$user[$GLOBALS['TCA'][$this->userTable]['ctrl']['delete']] = 0;
					break;
				case 'nothing':
					break;
				default:
					$user[$GLOBALS['TCA'][$this->userTable]['ctrl']['enablecolumns']['disabled']] = 0;
					$user[$GLOBALS['TCA'][$this->userTable]['ctrl']['delete']] = 0;
			}
			$typo3Groups = tx_igldapssoauth_auth::get_user_groups($ldapUser, $this->configuration, $this->groupTable);
			$user = tx_igldapssoauth_typo3_user::set_usergroup(
				($typo3Groups === NULL) ? array() : $typo3Groups,
				$user,
				NULL,
				$this->groupTable
			);
			$success = tx_igldapssoauth_typo3_user::update($this->userTable, $user);
			if ($success) {
				$this->usersUpdated++;
			}
		}

		// Restore the extra data and trigger a signal
		if (isset($extraData)) {
			$user['__extraData'] = $extraData;

			// Hook for processing the extra data
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraDataProcessing'])) {
				foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['extraDataProcessing'] as $className) {
					/** @var $postProcessor Tx_IgLdapSsoAuth_Utility_ExtraDataProcessorInterface */
					$postProcessor = t3lib_div::getUserObj($className);
					if ($postProcessor instanceof Tx_IgLdapSsoAuth_Utility_ExtraDataProcessorInterface) {
						$postProcessor->processExtraData($this->userTable, $user);
					} else {
						throw new Exception(
							sprintf(
								'Invalid post-processing class %s. It must implement the Tx_IgLdapSsoAuth_Utility_ExtraDataProcessorInterface interface',
								$className
							),
							1414136057
						);
					}
				}
			}
		}

		return $user;
	}

	/**
	 * Returns the current configuration.
	 *
	 * @return array
	 */
	public function getConfiguration() {
		return $this->configuration;
	}

	/**
	 * Returns the number of users added during the importer's lifetime.
	 *
	 * @return int
	 */
	public function getUsersAdded() {
		return $this->usersAdded;
	}

	/**
	 * Returns the number of users updated during the importer's lifetime.
	 *
	 * @return int
	 */
	public function getUsersUpdated() {
		return $this->usersUpdated;
	}
}
