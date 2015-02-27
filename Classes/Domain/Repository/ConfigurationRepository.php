<?php
namespace Causal\IgLdapSsoAuth\Domain\Repository;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Utility class for fetching LDAP configurations.
 *
 * NOTE: this is a not a true Extbase repository.
 *
 * @author     Francois Suter <typo3@cobweb.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ConfigurationRepository {

	/**
	 * @var bool Set to TRUE to also fetch disabled records (according to TCA enable fields)
	 */
	protected $fetchDisabledRecords = FALSE;

	/**
	 * Returns all available LDAP configurations.
	 *
	 * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration[]
	 */
	public function fetchAll() {
		$where = '1=1' . BackendUtility::deleteClause('tx_igldapssoauth_config');
		if (!$this->fetchDisabledRecords) {
			$where .= BackendUtility::BEenableFields('tx_igldapssoauth_config');
		}
		$rows = static::getDatabaseConnection()->exec_SELECTgetRows(
			'*',
			'tx_igldapssoauth_config',
			$where,
			'',
			'sorting'
		);

		$configurations = array();
		foreach ($rows as $row) {
			/** @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration */
			$configuration = GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Domain\\Model\\Configuration');
			$this->thawProperties($configuration, $row);
			$configurations[] = $configuration;
		}

		return $configurations;
	}

	/**
	 * Returns a single LDAP configuration.
	 *
	 * @param integer $uid Primary key to look up
	 * @return \Causal\IgLdapSsoAuth\Domain\Model\Configuration
	 */
	public function fetchByUid($uid) {
		$where = 'uid = ' . intval($uid) . BackendUtility::deleteClause('tx_igldapssoauth_config');
		if (!$this->fetchDisabledRecords) {
			$where .= BackendUtility::BEenableFields('tx_igldapssoauth_config');
		}
		$row = static::getDatabaseConnection()->exec_SELECTgetSingleRow(
			'*',
			'tx_igldapssoauth_config',
			$where
		);
		if ($row) {
			/** @var \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration */
			$configuration = GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Domain\\Model\\Configuration');
			$this->thawProperties($configuration, $row);
		} else {
			$configuration = NULL;
		}

		return $configuration;
	}

	/**
	 * Sets the flag for fetching disabled records or not.
	 *
	 * @param boolean $flag Set to TRUE to enable fetching of disabled record
	 * @return void
	 */
	public function setFetchDisabledRecords($flag) {
		$this->fetchDisabledRecords = (bool)$flag;
	}

	/**
	 * Returns the database connection.
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	static protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * Sets the given properties on the object.
	 *
	 * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $object The object to set properties on
	 * @param array $row
	 * @return void
	 */
	protected function thawProperties(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $object, array $row) {
		$object->_setProperty('uid', (int)$row['uid']);

		// Mapping for properties to be set without any transformation
		$mapping = array(
			'name'               => 'name',
			'domains'            => 'domains',
			'ldap_charset'       => 'ldapCharset',
			'ldap_host'          => 'ldapHost',
			'ldap_binddn'        => 'ldapBindDn',
			'ldap_password'      => 'ldapPassword',
			'be_users_basedn'    => 'backendUsersBaseDn',
			'be_users_filter'    => 'backendUsersFilter',
			'be_users_mapping'   => 'backendUsersMapping',
			'be_groups_basedn'   => 'backendGroupsBaseDn',
			'be_groups_filter'   => 'backendGroupsFilter',
			'be_groups_mapping'  => 'backendGroupsMapping',
			'be_groups_required' => 'backendGroupsRequired',
			'be_groups_assigned' => 'backendGroupsAssigned',
			'be_groups_admin'    => 'backendGroupsAdministrator',
			'fe_users_basedn'    => 'frontendUsersBaseDn',
			'fe_users_filter'    => 'frontendUsersFilter',
			'fe_users_mapping'   => 'frontendUsersMapping',
			'fe_groups_basedn'   => 'frontendGroupsBaseDn',
			'fe_groups_filter'   => 'frontendGroupsFilter',
			'fe_groups_mapping'  => 'frontendGroupsMapping',
			'fe_groups_required' => 'frontendGroupsRequired',
			'fe_groups_assigned' => 'frontendGroupsAssigned',
		);

		foreach ($mapping as $fieldName => $propertyName) {
			$object->_setProperty($propertyName, $row[$fieldName]);
		}

		$object->_setProperty('ldapServer', (int)$row['ldap_server']);
		$object->_setProperty('ldapProtocol', (int)$row['ldap_protocol']);
		$object->_setProperty('ldapPort', (int)$row['ldap_port']);
		$object->_setProperty('ldapTls', (bool)$row['ldap_tls']);
		$object->_setProperty('groupMembership', (int)$row['group_membership']);
	}

}
