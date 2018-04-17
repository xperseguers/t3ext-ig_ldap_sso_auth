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

namespace Causal\IgLdapSsoAuth\Controller;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3GroupRepository;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3UserRepository;
use Causal\IgLdapSsoAuth\Library\Authentication;
use Causal\IgLdapSsoAuth\Library\Configuration;
use Causal\IgLdapSsoAuth\Library\Ldap;

/**
 * Module controller.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ModuleController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{

    /**
     * @var \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository
     * @inject
     */
    protected $configurationRepository;

    /**
     * @var \Causal\IgLdapSsoAuth\Library\Ldap
     * @inject
     */
    protected $ldap;

    /**
     * Redirects to the saved action.
     *
     * @return void
     */
    public function initializeAction()
    {
        $vars = GeneralUtility::_GET('tx_igldapssoauth_system_igldapssoauthtxigldapssoauthm1');
        if (!isset($vars['redirect']) && !isset($vars['action']) && is_array($GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'])) {
            $previousSelection = $GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'];
            if (!empty($previousSelection['action']) && !empty($previousSelection['configuration'])) {
                $this->redirect($previousSelection['action'], 'Module', null, ['configuration' => $previousSelection['configuration'], 'redirect' => 1]);
            } else {
                $this->redirect('index');
            }
        }
    }

    /**
     * Index action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function indexAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        $this->saveState($configuration);
        $this->populateView($configuration);
    }

    /**
     * Status action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function statusAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        // If configuration has been deleted
        if ($configuration === null) {
            $this->redirect('index');
        }
        $this->saveState($configuration);

        Configuration::initialize(TYPO3_MODE, $configuration);
        $this->populateView($configuration);

        $ldapConfiguration = Configuration::getLdapConfiguration();
        $connectionStatus = [];

        if ($ldapConfiguration['host'] !== '') {
            $ldapConfiguration['server'] = Configuration::getServerType($ldapConfiguration['server']);

            try {
                $this->ldap->connect($ldapConfiguration);
            } catch (\Exception $e) {
                // Possible known exception: 1409566275, LDAP extension is not available for PHP
                $this->addFlashMessage(
                    $e->getMessage(),
                    'Error ' . $e->getCode(),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
                );
            }

            // Never ever show the password as plain text
            $ldapConfiguration['password'] = $ldapConfiguration['password'] ? '••••••••••••' : null;

            $connectionStatus = $this->ldap->getStatus();
        } else {
            $ldapConfiguration = $this->translate('module_status.messages.ldapDisable');
        }

        $frontendConfiguration = Configuration::getFrontendConfiguration();
        if ($frontendConfiguration['LDAPAuthentication'] === false) {
            // Remove every other info since authentication is disabled for this mode
            $frontendConfiguration = ['LDAPAuthentication' => false];
        }
        $backendConfiguration = Configuration::getBackendConfiguration();
        if ($backendConfiguration['LDAPAuthentication'] === false) {
            // Remove every other info since authentication is disabled for this mode
            $backendConfiguration = ['LDAPAuthentication' => false];
        }

        $this->view->assign('configuration', [
            'domains' => Configuration::getDomains(),
            'ldap' => $ldapConfiguration,
            'connection' => $connectionStatus,
            'frontend' => $frontendConfiguration,
            'backend' => $backendConfiguration,
        ]);
    }

    /**
     * Search action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function searchAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        // If configuration has been deleted
        if ($configuration === null) {
            $this->redirect('index');
        }
        $this->saveState($configuration);

        Configuration::initialize(TYPO3_MODE, $configuration);
        $this->populateView($configuration);

        $frontendConfiguration = Configuration::getFrontendConfiguration();
        $this->view->assignMultiple([
            'baseDn' => $frontendConfiguration['users']['basedn'],
            'filter' => $frontendConfiguration['users']['filter'],
        ]);
    }

    /**
     * Updates the search option using AJAX.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $type
     * @return void
     */
    public function updateSearchAjaxAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration, $type)
    {
        list($mode, $key) = explode('_', $type, 2);

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        $this->returnAjax([
            'success' => true,
            'configuration' => $config[$key],
        ]);
    }

    /**
     * Actual search action using AJAX.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $type
     * @param bool $firstEntry
     * @param bool $showStatus
     * @param string $baseDn
     * @param string $filter
     * @return void
     */
    public function searchAjaxAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null, $type, $firstEntry, $showStatus, $baseDn, $filter)
    {
        list($mode, $key) = explode('_', $type, 2);

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $success = false;
        }

        if ($showStatus) {
            $this->view->assign('status', $this->ldap->getStatus());
        }

        if ($success) {
            $filter = Configuration::replaceFilterMarkers($filter);
            if ($firstEntry) {
                $attributes = [];
            } else {
                $attributes = Configuration::getLdapAttributes($config[$key]['mapping']);
                if (strpos($config[$key]['filter'], '{USERUID}') !== false) {
                    $attributes[] = 'uid';
                    $attributes = array_unique($attributes);
                }
            }

            $resultset = $this->ldap->search($baseDn, $filter, $attributes, $firstEntry, 100);

            // With PHP 5.4 and above this could be renamed as
            // ksort_recursive($result, SORT_NATURAL)
            if (is_array($resultset)) {
                $this->uksort_recursive($resultset, 'strnatcmp');
            }

            $this->view->assign('resultset', $resultset);

            if ($firstEntry && is_array($resultset) && count($resultset) > 1) {
                if ($key === 'users') {
                    $mapping = $config['users']['mapping'];
                    $blankTypo3Record = Typo3UserRepository::create($type);
                } else {
                    $mapping = $config['groups']['mapping'];
                    $blankTypo3Record = Typo3GroupRepository::create($type);
                }
                $preview = Authentication::merge($resultset, $blankTypo3Record, $mapping, true);

                // Remove empty lines
                $keys = array_keys($preview);
                foreach ($keys as $key) {
                    if (empty($preview[$key])) {
                        unset($preview[$key]);
                    }
                }
                $this->view->assign('preview', $preview);
            }
        }

        $this->returnAjax([
            'success' => $success,
            'html' => $this->view->render()
        ]);
    }

    /**
     * Import frontend users action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function importFrontendUsersAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        // If configuration has been deleted
        if ($configuration === null) {
            $this->redirect('index');
        }
        $this->saveState($configuration);

        Configuration::initialize('fe', $configuration);
        $this->populateView($configuration);

        if (!$this->checkLdapConnection()) {
            return;
        }

        $users = $this->getAvailableUsers($configuration, 'fe');
        $this->view->assign('users', $users);
    }

    /**
     * Import backend users action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function importBackendUsersAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        // If configuration has been deleted
        if ($configuration === null) {
            $this->redirect('index');
        }
        $this->saveState($configuration);

        Configuration::initialize('be', $configuration);
        $this->populateView($configuration);

        if (!$this->checkLdapConnection()) {
            return;
        }

        $users = $this->getAvailableUsers($configuration, 'be');
        $this->view->assign('users', $users);
    }

    /**
     * Actual import of users using AJAX.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $mode
     * @param string $dn
     * @return void
     */
    public function importUsersAjaxAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null, $mode, $dn)
    {
        /** @var \Causal\IgLdapSsoAuth\Utility\UserImportUtility $importUtility */
        $importUtility = GeneralUtility::makeInstance(
            \Causal\IgLdapSsoAuth\Utility\UserImportUtility::class,
            $configuration,
            $mode
        );
        $data = [];

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            $success = false;
        }

        if ($success) {
            list($filter, $baseDn) = Authentication::getRelativeDistinguishedNames($dn, 2);
            $attributes = Configuration::getLdapAttributes($config['users']['mapping']);
            $ldapUser = $this->ldap->search($baseDn, '(' . $filter . ')', $attributes, true);
            $typo3Users = $importUtility->fetchTypo3Users([$ldapUser]);

            // Merge LDAP and TYPO3 information
            $user = Authentication::merge($ldapUser, $typo3Users[0], $config['users']['mapping']);

            // Import the user
            $user = $importUtility->import($user, $ldapUser);

            $data['id'] = (int)$user['uid'];
        }

        $this->returnAjax(array_merge($data, ['success' => $success]));
    }

    /**
     * Import frontend user groups action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function importFrontendUserGroupsAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        // If configuration has been deleted
        if ($configuration === null) {
            $this->redirect('index');
        }
        $this->saveState($configuration);

        Configuration::initialize('fe', $configuration);
        $this->populateView($configuration);

        if (!$this->checkLdapConnection()) {
            return;
        }

        $groups = $this->getAvailableUserGroups($configuration, 'fe');
        $this->view->assign('groups', $groups);
    }

    /**
     * Import backend user groups action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    public function importBackendUserGroupsAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        // If configuration has been deleted
        if ($configuration === null) {
            $this->redirect('index');
        }
        $this->saveState($configuration);

        Configuration::initialize('be', $configuration);
        $this->populateView($configuration);

        if (!$this->checkLdapConnection()) {
            return;
        }

        $groups = $this->getAvailableUserGroups($configuration, 'be');
        $this->view->assign('groups', $groups);
    }

    /**
     * Actual import of user groups using AJAX.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $mode
     * @param string $dn
     * @return void
     */
    public function importUserGroupsAjaxAction(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null, $mode, $dn)
    {
        $data = [];

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            $success = false;
        }

        if ($success) {
            list($filter, $baseDn) = explode(',', $dn, 2);
            $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);
            $ldapGroup = $this->ldap->search($baseDn, '(' . $filter . ')', $attributes, true);

            $pid = Configuration::getPid($config['groups']['mapping']);
            $table = $mode === 'be' ? 'be_groups' : 'fe_groups';
            $typo3Groups = Authentication::getTypo3Groups(
                [$ldapGroup],
                $table,
                $pid
            );

            // Merge LDAP and TYPO3 information
            $group = Authentication::merge($ldapGroup, $typo3Groups[0], $config['groups']['mapping']);

            if ((int)$group['uid'] === 0) {
                $group = Typo3GroupRepository::add($table, $group);
            } else {
                // Restore group that may have been previously deleted
                $group['deleted'] = 0;
                $success = Typo3GroupRepository::update($table, $group);
            }

            if (!empty($config['groups']['mapping']['parentGroup'])) {
                $fieldParent = $config['groups']['mapping']['parentGroup'];
                if (preg_match("`<([^$]*)>`", $fieldParent, $attribute)) {
                    $fieldParent = $attribute[1];

                    if (is_array($ldapGroup[$fieldParent])) {
                        unset($ldapGroup[$fieldParent]['count']);

                        $this->setParentGroup(
                            $ldapGroup[$fieldParent],
                            $fieldParent,
                            $group['uid'],
                            $pid,
                            $mode
                        );
                    }
                }
            }

            $data['id'] = (int)$group['uid'];
        }

        $this->returnAjax(array_merge($data, ['success' => $success]));
    }

    /**
     * Sets the parent groups for a given TYPO3 user group record.
     *
     * @param array $ldapParentGroups
     * @param string $fieldParent
     * @param int $childUid
     * @param int $pid
     * @param string $mode
     * @return void
     * @throws \Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException
     */
    protected function setParentGroup(array $ldapParentGroups, $fieldParent, $childUid, $pid, $mode)
    {
        $subGroupList = [];
        if ($mode === 'be') {
            $table = 'be_groups';
            $config = Configuration::getBackendConfiguration();
        } else {
            $table = 'fe_groups';
            $config = Configuration::getFrontendConfiguration();
        }

        foreach ($ldapParentGroups as $parentDn) {
            $typo3ParentGroup = Typo3GroupRepository::fetch($table, false, $pid, $parentDn);

            if (is_array($typo3ParentGroup[0])) {
                if (!empty($typo3ParentGroup[0]['subgroup'])) {
                    $subGroupList = GeneralUtility::trimExplode(',', $typo3ParentGroup[0]['subgroup']);
                }

                $subGroupList[] = $childUid;
                $subGroupList = array_unique($subGroupList);
                $typo3ParentGroup[0]['subgroup'] = implode(',', $subGroupList);
                Typo3GroupRepository::update($table, $typo3ParentGroup[0]);
            } else {
                $filter = '(&' . Configuration::replaceFilterMarkers($config['groups']['filter']) . '&(distinguishedName=' . $parentDn . '))';
                $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);

                $ldapInstance = Ldap::getInstance();
                $ldapInstance->connect(Configuration::getLdapConfiguration());
                $ldapGroups = $ldapInstance->search($config['groups']['basedn'], $filter, $attributes);
                $ldapInstance->disconnect();
                unset($ldapGroups['count']);

                if (count($ldapGroups) > 0) {
                    $pid = Configuration::getPid($config['groups']['mapping']);

                    // Populate an array of TYPO3 group records corresponding to the LDAP groups
                    // If a given LDAP group has no associated group in TYPO3, a fresh record
                    // will be created so that $ldapGroups[i] <=> $typo3Groups[i]
                    $typo3Groups = Authentication::getTypo3Groups(
                        $ldapGroups,
                        $table,
                        $pid
                    );

                    foreach ($ldapGroups as $index => $ldapGroup) {
                        $typo3Group = Authentication::merge($ldapGroup, $typo3Groups[$index], $config['groups']['mapping']);
                        $typo3Group['subgroup'] = $childUid;
                        $typo3Group = Typo3GroupRepository::add($table, $typo3Group);

                        if (is_array($ldapGroup[$fieldParent])) {
                            unset($ldapGroup[$fieldParent]['count']);

                            $this->setParentGroup(
                                $ldapGroup[$fieldParent],
                                $fieldParent,
                                $typo3Group['uid'],
                                $pid,
                                $mode
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Returns the LDAP users with information merged with local TYPO3 users.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $mode
     * @return array
     */
    protected function getAvailableUsers(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration, $mode)
    {
        /** @var \Causal\IgLdapSsoAuth\Utility\UserImportUtility $importUtility */
        $importUtility = GeneralUtility::makeInstance(
            \Causal\IgLdapSsoAuth\Utility\UserImportUtility::class,
            $configuration,
            $mode
        );

        $ldapInstance = Ldap::getInstance();
        $ldapInstance->connect(Configuration::getLdapConfiguration());
        $ldapUsers = $importUtility->fetchLdapUsers(false, $ldapInstance);

        $users = [];
        $numberOfUsers = 0;
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        do {
            $numberOfUsers += count($ldapUsers);
            $typo3Users = $importUtility->fetchTypo3Users($ldapUsers);
            foreach ($ldapUsers as $index => $ldapUser) {
                // Merge LDAP and TYPO3 information
                $user = Authentication::merge($ldapUser, $typo3Users[$index], $config['users']['mapping']);

                // Attempt to free memory by unsetting fields which are unused in the view
                $keepKeys = ['uid', 'pid', 'deleted', 'admin', 'name', 'realName', 'tx_igldapssoauth_dn'];
                $keys = array_keys($user);
                foreach ($keys as $key) {
                    if (!in_array($key, $keepKeys)) {
                        unset($user[$key]);
                    }
                }

                $users[] = $user;
            }

            // Free memory before going on
            $typo3Users = null;
            $ldapUsers = null;

            // Current Extbase implementation does not properly handle
            // very large data sets due to memory consumption and waiting
            // time until the list starts to be "displayed". Instead of
            // waiting forever or drive code to a memory exhaustion, better
            // stop sooner than later
            if (count($users) >= 2000) {
                break;
            }

            $ldapUsers = $importUtility->hasMoreLdapUsers($ldapInstance)
                ? $importUtility->fetchLdapUsers(true, $ldapInstance)
                : [];
        } while (count($ldapUsers) > 0);

        $ldapInstance->disconnect();

        return $users;
    }

    /**
     * Returns the LDAP user groups with information merged with local TYPO3 user groups.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @param string $mode
     * @return array
     */
    protected function getAvailableUserGroups(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration, $mode)
    {
        $userGroups = [];
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        $ldapGroups = [];
        if (!empty($config['groups']['basedn'])) {
            $filter = Configuration::replaceFilterMarkers($config['groups']['filter']);
            $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);
            $ldapInstance = Ldap::getInstance();
            $ldapInstance->connect(Configuration::getLdapConfiguration());
            $ldapGroups = $ldapInstance->search($config['groups']['basedn'], $filter, $attributes);
            $ldapInstance->disconnect();
            unset($ldapGroups['count']);
        }

        // Populate an array of TYPO3 group records corresponding to the LDAP groups
        // If a given LDAP group has no associated group in TYPO3, a fresh record
        // will be created so that $ldapGroups[i] <=> $typo3Groups[i]
        $typo3GroupPid = Configuration::getPid($config['groups']['mapping']);
        $table = ($mode === 'be') ? 'be_groups' : 'fe_groups';
        $typo3Groups = Authentication::getTypo3Groups(
            $ldapGroups,
            $table,
            $typo3GroupPid
        );

        foreach ($ldapGroups as $index => $ldapGroup) {
            $userGroup = Authentication::merge($ldapGroup, $typo3Groups[$index], $config['groups']['mapping']);

            // Attempt to free memory by unsetting fields which are unused in the view
            $keepKeys = ['uid', 'pid', 'deleted', 'title', 'tx_igldapssoauth_dn'];
            $keys = array_keys($userGroup);
            foreach ($keys as $key) {
                if (!in_array($key, $keepKeys)) {
                    unset($userGroup[$key]);
                }
            }

            $userGroups[] = $userGroup;
        }

        return $userGroups;
    }

    /**
     * Populates the view with general objects.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    protected function populateView(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $thisUri = $uriBuilder->reset()->uriFor(null, ['configuration' => $configuration]);
        $editLink = '';

        $configurationRecords = $this->configurationRepository->findAll();
        if (version_compare(TYPO3_version, '7.5', '<')) {
            $editRecordModuleUrl = 'alt_doc.php?';
        } else {
            $editRecordModuleUrl = BackendUtility::getModuleUrl('record_edit') . '&amp;';
        }

        if (count($configurationRecords) === 0) {
            $newRecordUri = $editRecordModuleUrl . 'returnUrl=' . urlencode($thisUri) . '&amp;edit[tx_igldapssoauth_config][0]=new';

            $message = $this->translate(
                'configuration_missing.message',
                [
                    'https://docs.typo3.org/typo3cms/extensions/ig_ldap_sso_auth/AdministratorManual/Index.html',
                    $newRecordUri,
                ]
            );
            $this->addFlashMessage(
                $message,
                $this->translate('configuration_missing.title'),
                \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
        } else {
            if ($configuration == null) {
                $configuration = $configurationRecords[0];
            }
            $editUri = $editRecordModuleUrl . 'returnUrl=' . urlencode($thisUri) . '&amp;edit[tx_igldapssoauth_config][' . $configuration->getUid() . ']=edit';
            if (version_compare(TYPO3_version, '7.6', '>=')) {
                /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
                $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
                $icon = $iconFactory->getIcon('actions-document-open', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            } else {
                $icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open');
            }
            $editLink = sprintf(
                ' <a href="%s" title="uid=%s">' . $icon . '</a>',
                $editUri,
                $configuration->getUid()
            );
        }

        $menu = [
            [
                'action' => 'status',
                'titleKey' => 'module_status',
                'iconName' => 'status-dialog-information',
            ],
            [
                'action' => 'search',
                'titleKey' => 'module_search',
                'iconName' => 'apps-toolbar-menu-search',
            ],
            [
                'action' => 'importFrontendUsers',
                'titleKey' => 'module_import_users_fe',
                'iconName' => 'status-user-frontend',
            ],
            [
                'action' => 'importFrontendUserGroups',
                'titleKey' => 'module_import_groups_fe',
                'iconName' => 'status-user-group-frontend',
            ],
            [
                'action' => 'importBackendUsers',
                'titleKey' => 'module_import_users_be',
                'iconName' => 'status-user-backend',
            ],
            [
                'action' => 'importBackendUserGroups',
                'titleKey' => 'module_import_groups_be',
                'iconName' => 'status-user-group-backend',
            ],
        ];

        if (version_compare(TYPO3_version, '7.0', '<')) {
            $tableClass = 'typo3-dblist';
            $trClass = 'db_list_normal';
        } else {
            $tableClass = 'table table-striped table-hover';
            $trClass = '';
        }

        $this->view->assignMultiple([
            'action' => $this->getControllerContext()->getRequest()->getControllerActionName(),
            'configurationRecords' => $configurationRecords,
            'currentConfiguration' => $configuration,
            'mode' => Configuration::getMode(),
            'editLink' => $editLink,
            'menu' => $menu,
            'classes' => [
                'table' => $tableClass,
                'tableRow' => $trClass,
            ]
        ]);
    }

    /**
     * Checks the LDAP connection and prepares a Flash message if unavailable.
     *
     * @return bool
     */
    protected function checkLdapConnection()
    {
        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException $e) {
            // Possible known exception: 1409566275, LDAP extension is not available for PHP
            $this->addFlashMessage(
                $e->getMessage(),
                'Error ' . $e->getCode(),
                \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            return false;
        }
        return $success;
    }

    /**
     * Translates a label.
     *
     * @param string $id
     * @param array $arguments
     * @return null|string
     */
    protected function translate($id, array $arguments = null)
    {
        $request = $this->controllerContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($id, $extensionName, $arguments);
        return $value !== null ? $value : $id;
    }

    /**
     * Saves current state.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration
     * @return void
     */
    protected function saveState(\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null)
    {
        $GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'] = [
            'action' => $this->getControllerContext()->getRequest()->getControllerActionName(),
            'configuration' => $configuration !== null ? $configuration->getUid() : 0,
        ];
        $GLOBALS['BE_USER']->writeUC();
    }

    /**
     * Sort recursively an array by keys using a user-defined comparison function.
     *
     * @param array $array The input array
     * @param callable $key_compare_func The comparison function must return an integer less than, equal to, or greater than zero if the first argument is considered to be respectively less than, equal to, or greater than the second
     * @return bool Returns true on success or false on failure
     */
    protected function uksort_recursive(array &$array, $key_compare_func)
    {
        $ret = uksort($array, $key_compare_func);
        if ($ret) {
            foreach ($array as &$arr) {
                if (is_array($arr) && !$this->uksort_recursive($arr, $key_compare_func)) {
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * Returns an AJAX response.
     *
     * @param array $response
     * @param bool $wrapForIframe see http://cmlenz.github.io/jquery-iframe-transport/#section-13
     * return void
     */
    protected function returnAjax(array $response, $wrapForIframe = false)
    {
        $payload = json_encode($response);
        if (!$wrapForIframe) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/html');
            $payload = '<textarea data-type="application/json">' . $payload . '</textarea>';
        }
        echo $payload;
        exit;
    }

}
