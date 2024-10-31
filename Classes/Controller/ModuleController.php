<?php
declare(strict_types=1);

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

use Causal\IgLdapSsoAuth\Exception\InvalidHostnameException;
use Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException;
use Causal\IgLdapSsoAuth\Utility\CompatUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3GroupRepository;
use Causal\IgLdapSsoAuth\Domain\Repository\Typo3UserRepository;
use Causal\IgLdapSsoAuth\Library\Authentication;
use Causal\IgLdapSsoAuth\Library\Configuration;
use Causal\IgLdapSsoAuth\Library\Ldap;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Module controller.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ModuleController extends ActionController
{
    protected ?ModuleTemplate $moduleTemplate = null;

    /**
     * @param ModuleTemplateFactory $moduleTemplateFactory
     * @param ConfigurationRepository $configurationRepository
     * @param Ldap $ldap
     */
    public function __construct(
        private readonly ModuleTemplateFactory $moduleTemplateFactory,
        private readonly ConfigurationRepository $configurationRepository,
        private readonly Ldap $ldap
    )
    {
    }

    public function initializeAction(): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([]);
    }

    /**
     * Redirects to the last saved action if needed.
     *
     * @return ResponseInterface|null
     */
    protected function restoreLastAction(): ?ResponseInterface
    {
        if (is_array($GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'] ?? null)) {
            $previousSelection = $GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'];
            if (($previousSelection['action'] ?? '') === 'index') {
                return null;
            }
            if (!empty($previousSelection['action']) && !empty($previousSelection['configuration'])) {
                return $this->redirect(
                    $previousSelection['action'],
                    'Module',
                    null,
                    [
                        'configuration' => $previousSelection['configuration'],
                    ]
                );
            }
        }

        return null;
    }

    /**
     * Index action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @param bool $skipLastAction
     * @return ResponseInterface
     */
    public function indexAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null,
        bool $skipLastAction = false
    ): ResponseInterface
    {
        if (!$skipLastAction) {
            $lastAction = $this->restoreLastAction();
            if ($lastAction !== null) {
                return $lastAction;
            }
        }

        $this->saveState($configuration);
        $this->populateView($configuration);

        $typo3Version = (new Typo3Version())->getMajorVersion();
        if ($typo3Version < 12) {
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        return $this->moduleTemplate->renderResponse('Module/Index');
    }

    /**
     * Status action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function statusAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): ResponseInterface
    {
        // If configuration has been deleted
        if ($configuration === null) {
            return $this->redirect('index', null, null, ['skipLastAction' => true]);
        }
        $this->saveState($configuration);

        Configuration::initialize(CompatUtility::getTypo3Mode(), $configuration);
        $this->populateView($configuration);

        $typo3Version = (new Typo3Version())->getMajorVersion();
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
                    $typo3Version >= 12
                        ? ContextualFeedbackSeverity::ERROR
                        : \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
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

        $values = [
            'configuration' => [
                'domains' => Configuration::getDomains(),
                'ldap' => $ldapConfiguration,
                'connection' => $connectionStatus,
                'frontend' => $frontendConfiguration,
                'backend' => $backendConfiguration,
            ],
        ];

        if ($typo3Version < 12) {
            $this->view->assignMultiple($values);
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $this->moduleTemplate->assignMultiple($values);
        return $this->moduleTemplate->renderResponse('Module/Status');
    }

    /**
     * Search action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function searchAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): ResponseInterface
    {
        // If configuration has been deleted
        if ($configuration === null) {
            return $this->redirect('index', null, null, ['skipLastAction' => true]);
        }
        $this->saveState($configuration);

        Configuration::initialize(CompatUtility::getTypo3Mode(), $configuration);
        $this->populateView($configuration);
        $this->loadJavaScriptModule('search');

        $typo3Version = (new Typo3Version())->getMajorVersion();
        $frontendConfiguration = Configuration::getFrontendConfiguration();

        $values = [
            'baseDn' => $frontendConfiguration['users']['basedn'],
            'filter' => $frontendConfiguration['users']['filter'],
        ];

        if ($typo3Version < 12) {
            $this->view->assignMultiple($values);
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $this->moduleTemplate->assignMultiple($values);
        return $this->moduleTemplate->renderResponse('Module/Search');
    }

    /**
     * Import frontend users action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function importFrontendUsersAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): ResponseInterface
    {
        // If configuration has been deleted
        if ($configuration === null) {
            return $this->redirect('index', null, null, ['skipLastAction' => true]);
        }
        $this->saveState($configuration);

        Configuration::initialize('fe', $configuration);
        $this->populateView($configuration);

        $typo3Version = (new Typo3Version())->getMajorVersion();
        $values = [];
        if ($this->checkLdapConnection()) {
            $this->loadJavaScriptModule('import');

            $users = $this->getAvailableUsers($configuration, 'fe');
            $values['users'] = $users;
        }

        if ($typo3Version < 12) {
            $this->view->assignMultiple($values);
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $this->moduleTemplate->assignMultiple($values);
        return $this->moduleTemplate->renderResponse('Module/ImportFrontendUsers');
    }

    /**
     * Import backend users action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function importBackendUsersAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): ResponseInterface
    {
        // If configuration has been deleted
        if ($configuration === null) {
            return $this->redirect('index', null, null, ['skipLastAction' => true]);
        }
        $this->saveState($configuration);

        Configuration::initialize('be', $configuration);
        $this->populateView($configuration);

        $typo3Version = (new Typo3Version())->getMajorVersion();
        $values = [];
        if ($this->checkLdapConnection()) {
            $this->loadJavaScriptModule('import');

            $users = $this->getAvailableUsers($configuration, 'be');
            $values['users'] = $users;
        }

        if ($typo3Version < 12) {
            $this->view->assignMultiple($values);
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $this->moduleTemplate->assignMultiple($values);
        return $this->moduleTemplate->renderResponse('Module/ImportBackendUsers');
    }

    /**
     * Import frontend user groups action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function importFrontendUserGroupsAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): ResponseInterface
    {
        // If configuration has been deleted
        if ($configuration === null) {
            return $this->redirect('index', null, null, ['skipLastAction' => true]);
        }
        $this->saveState($configuration);

        Configuration::initialize('fe', $configuration);
        $this->populateView($configuration);

        $typo3Version = (new Typo3Version())->getMajorVersion();
        $values = [];
        if ($this->checkLdapConnection()) {
            $this->loadJavaScriptModule('import');

            $groups = $this->getAvailableUserGroups($configuration, 'fe');
            $values['groups'] = $groups;
        }

        if ($typo3Version < 12) {
            $this->view->assignMultiple($values);
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $this->moduleTemplate->assignMultiple($values);
        return $this->moduleTemplate->renderResponse('Module/ImportFrontendUserGroups');
    }

    /**
     * Import backend user groups action.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     * @return ResponseInterface
     */
    public function importBackendUserGroupsAction(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): ResponseInterface
    {
        // If configuration has been deleted
        if ($configuration === null) {
            return $this->redirect('index', null, null, ['skipLastAction' => true]);
        }
        $this->saveState($configuration);

        Configuration::initialize('be', $configuration);
        $this->populateView($configuration);

        $typo3Version = (new Typo3Version())->getMajorVersion();
        $values = [];
        if ($this->checkLdapConnection()) {
            $this->loadJavaScriptModule('import');

            $groups = $this->getAvailableUserGroups($configuration, 'be');
            $values['groups'] = $groups;
        }

        if ($typo3Version < 12) {
            $this->view->assignMultiple($values);
            $this->moduleTemplate->setContent($this->view->render());
            return $this->htmlResponse($this->moduleTemplate->renderContent());
        }

        $this->moduleTemplate->assignMultiple($values);
        return $this->moduleTemplate->renderResponse('Module/ImportBackendUserGroups');
    }

    /**
     * Updates the search option using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxUpdateForm(ServerRequestInterface $request): ResponseInterface
    {
        $params = (new Typo3Version())->getMajorVersion() >= 12
            ? $request->getParsedBody()
            : $request->getQueryParams();

        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);

        $configuration = $configurationRepository->findByUid((int)$params['configuration']);
        list($mode, $key) = explode('_', $params['type'], 2);

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        $payload = [
            'success' => true,
            'configuration' => $config[$key],
        ];

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Actual search action using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxSearch(ServerRequestInterface $request): ResponseInterface
    {
        $params = (new Typo3Version())->getMajorVersion() >= 12
            ? $request->getParsedBody()
            : $request->getQueryParams();

        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $ldap = GeneralUtility::makeInstance(Ldap::class);

        $configuration = $configurationRepository->findByUid((int)$params['configuration']);
        list($mode, $key) = explode('_', $params['type'], 2);

        Configuration::initialize($mode, $configuration);
        $config = ($mode === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $success = false;
        }

        $template = GeneralUtility::getFileAbsFileName('EXT:ig_ldap_sso_auth/Resources/Private/Templates/Ajax/Search.html');
        $view = GeneralUtility::makeInstance(\TYPO3\CMS\Fluid\View\StandaloneView::class);
        $view->setFormat('html');
        $view->setTemplatePathAndFilename($template);

        if ((bool)($params['showStatus'] ?? false)) {
            $view->assign('status', $ldap->getStatus());
        }

        if ($success) {
            $firstEntry = (bool)($params['firstEntry'] ?? false);
            $filter = Configuration::replaceFilterMarkers($params['filter']);
            if ($firstEntry) {
                $attributes = [];
            } else {
                $attributes = Configuration::getLdapAttributes($config[$key]['mapping']);
                if (str_contains($config[$key]['filter'], '{USERUID}')) {
                    $attributes[] = 'uid';
                    $attributes = array_unique($attributes);
                }
            }

            $resultset = $ldap->search($params['baseDn'], $filter, $attributes, $firstEntry, 100);

            // With PHP 5.4 and above this could be renamed as
            // ksort_recursive($result, SORT_NATURAL)
            if (is_array($resultset)) {
                $this->uksort_recursive($resultset, 'strnatcmp');
            }

            $view->assign('resultset', $resultset);

            if ($firstEntry && is_array($resultset) && count($resultset) > 1) {
                if ($key === 'users') {
                    $mapping = $config['users']['mapping'];
                    $blankTypo3Record = Typo3UserRepository::create($params['type']);
                } else {
                    $mapping = $config['groups']['mapping'];
                    $blankTypo3Record = Typo3GroupRepository::create($params['type']);
                }
                $preview = Authentication::merge($resultset, $blankTypo3Record, $mapping, true);

                // Remove empty lines
                $keys = array_keys($preview);
                foreach ($keys as $key) {
                    if (empty($preview[$key])) {
                        unset($preview[$key]);
                    }
                }
                $view->assign('preview', $preview);
            }
        }

        $html = $view->render();

        $payload = [
            'success' => $success,
            'html' => $html,
        ];

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Actual import of users using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxUsersImport(ServerRequestInterface $request): ResponseInterface
    {
        $params = (new Typo3Version())->getMajorVersion() >= 12
            ? $request->getParsedBody()
            : $request->getQueryParams();

        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $ldap = GeneralUtility::makeInstance(Ldap::class);

        $configuration = $configurationRepository->findByUid((int)$params['configuration']);

        /** @var \Causal\IgLdapSsoAuth\Utility\UserImportUtility $importUtility */
        $importUtility = GeneralUtility::makeInstance(
            \Causal\IgLdapSsoAuth\Utility\UserImportUtility::class,
            $configuration,
            $params['mode']
        );
        $data = [];

        Configuration::initialize($params['mode'], $configuration);
        $config = ($params['mode'] === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            $success = false;
        }

        if ($success) {
            // If we assume that DN is
            // CN=Mustermann\, Max (LAN),OU=Users,DC=example,DC=com
            list($filter, $baseDn) = Authentication::getRelativeDistinguishedNames($params['dn'], 2);
            // ... we need to properly escape $filter "CN=Mustermann\, Max (LAN)" as "CN=Mustermann, Max \28LAN\29"
            list($key, $value) = explode('=', $filter, 2);
            // 1) Unescape the comma
            $value = str_replace('\\', '', $value);
            // 2) Create a proper search filter
            $searchFilter = '(' . $key . '=' . ldap_escape($value, '', LDAP_ESCAPE_FILTER) . ')';
            $attributes = Configuration::getLdapAttributes($config['users']['mapping']);
            $ldapUser = $ldap->search($baseDn, $searchFilter, $attributes, true);
            $typo3Users = $importUtility->fetchTypo3Users([$ldapUser]);

            // Merge LDAP and TYPO3 information
            $disableField = $GLOBALS['TCA'][$importUtility->getUserTable()]['ctrl']['enablecolumns']['disabled'] ?? '';
            $user = Authentication::merge(
                $ldapUser,
                $typo3Users[0],
                $config['users']['mapping'],
                false,
                $disableField
            );

            // Import the user
            $user = $importUtility->import($user, $ldapUser, 'both', $disableField);

            $data['id'] = (int)$user['uid'];
        }

        $payload = array_merge($data, ['success' => $success]);

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Actual import of user groups using AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function ajaxGroupsImport(ServerRequestInterface $request): ResponseInterface
    {
        $params = (new Typo3Version())->getMajorVersion() >= 12
            ? $request->getParsedBody()
            : $request->getQueryParams();

        $configurationRepository = GeneralUtility::makeInstance(ConfigurationRepository::class);
        $ldap = GeneralUtility::makeInstance(Ldap::class);

        $configuration = $configurationRepository->findByUid((int)$params['configuration']);

        $data = [];

        Configuration::initialize($params['mode'], $configuration);
        $config = ($params['mode'] === 'be')
            ? Configuration::getBackendConfiguration()
            : Configuration::getFrontendConfiguration();

        try {
            $success = $ldap->connect(Configuration::getLdapConfiguration());
        } catch (\Exception $e) {
            $data['message'] = $e->getMessage();
            $success = false;
        }

        if ($success) {
            list($filter, $baseDn) = explode(',', $params['dn'], 2);
            $attributes = Configuration::getLdapAttributes($config['groups']['mapping']);
            $ldapGroup = $ldap->search($baseDn, '(' . $filter . ')', $attributes, true);

            $pid = Configuration::getPid($config['groups']['mapping']);
            $table = $params['mode'] === 'be' ? 'be_groups' : 'fe_groups';
            $typo3Groups = Authentication::getTypo3Groups(
                [$ldapGroup],
                $table,
                $pid
            );

            // Merge LDAP and TYPO3 information
            $group = Authentication::merge($ldapGroup, $typo3Groups[0], $config['groups']['mapping']);

            if ((int)($group['uid'] ?? 0) === 0) {
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
                            $params['mode']
                        );
                    }
                }
            }

            $data['id'] = (int)$group['uid'];
        }

        $payload = array_merge($data, ['success' => $success]);

        $response = (new JsonResponse())->setPayload($payload);

        return $response;
    }

    /**
     * Sets the parent groups for a given TYPO3 user group record.
     *
     * @param array $ldapParentGroups
     * @param string $fieldParent
     * @param int $childUid
     * @param int $pid
     * @param string $mode
     * @throws \Causal\IgLdapSsoAuth\Exception\InvalidUserGroupTableException
     */
    protected function setParentGroup(
        array $ldapParentGroups,
        string $fieldParent,
        int $childUid,
        int $pid,
        string $mode
    ): void
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

                if (!empty($ldapGroups)) {
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
    protected function getAvailableUsers(
        \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration,
        string $mode
    ): array
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
        $userTable = $importUtility->getUserTable();

        do {
            $numberOfUsers += count($ldapUsers);
            $typo3Users = $importUtility->fetchTypo3Users($ldapUsers);
            foreach ($ldapUsers as $index => $ldapUser) {
                // Merge LDAP and TYPO3 information
                $disableField = $GLOBALS['TCA'][$userTable]['ctrl']['enablecolumns']['disabled'] ?? '';
                $user = Authentication::merge(
                    $ldapUser,
                    $typo3Users[$index],
                    $config['users']['mapping'],
                    false,
                    $disableField
                );

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
        } while (!empty($ldapUsers));

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
    protected function getAvailableUserGroups(
        \Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration,
        string $mode
    ): array
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
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     */
    protected function populateView(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): void
    {
        $typo3Version = (new Typo3Version())->getMajorVersion();
        $thisUri = $this->uriBuilder->reset()->uriFor(null, ['configuration' => $configuration]);
        $editLink = '';

        $configurationRecords = $this->configurationRepository->findAll();

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $editRecordModuleUrl = $uriBuilder->buildUriFromRoute('record_edit');

        if (empty($configurationRecords)) {
            $newRecordUri = $editRecordModuleUrl . '&returnUrl=' . urlencode($thisUri) . '&edit[tx_igldapssoauth_config][0]=new';

            $message = $this->translate(
                'configuration_missing.message',
                [
                    'https://docs.typo3.org/p/causal/ig_ldap_sso_auth/main/en-us/AdministratorManual/Index.html',
                    $newRecordUri,
                ]
            );
            $this->addFlashMessage(
                $message,
                $this->translate('configuration_missing.title'),
                $typo3Version >= 12
                    ? ContextualFeedbackSeverity::WARNING
                    : \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
            );
        } else {
            if ($configuration == null) {
                $configuration = $configurationRecords[0];
            }
            $editUri = $editRecordModuleUrl . '&returnUrl=' . urlencode($thisUri) . '&edit[tx_igldapssoauth_config][' . $configuration->getUid() . ']=edit';
            /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
            $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
            $icon = $iconFactory->getIcon('actions-document-open', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            $editLink = sprintf(
                ' <a href="%s" title="uid=%s" class="btn btn-default btn-sm" style="vertical-align: inherit;">' . $icon . '</a>',
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

        $values = [
            'action' => $this->request->getControllerActionName(),
            'configurationRecords' => $configurationRecords,
            'currentConfiguration' => $configuration,
            'mode' => Configuration::getMode(),
            'editLink' => $editLink,
            'menu' => $menu,
        ];

        if ($typo3Version >= 12) {
            $this->moduleTemplate->assignMultiple($values);
        } else {
            $this->view->assignMultiple($values);
        }
    }

    /**
     * Checks the LDAP connection and prepares a Flash message if unavailable.
     *
     * @return bool
     */
    protected function checkLdapConnection(): bool
    {
        try {
            $success = $this->ldap->connect(Configuration::getLdapConfiguration());
        } catch (UnresolvedPhpDependencyException $e) {
            // Possible known exception: 1409566275, LDAP extension is not available for PHP
            $this->addFlashMessage(
                $e->getMessage(),
                'Error ' . $e->getCode(),
                (new Typo3Version())->getMajorVersion() >= 12
                    ? ContextualFeedbackSeverity::ERROR
                    : \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            return false;
        } catch (InvalidHostnameException $e) {
            $this->addFlashMessage(
                $e->getMessage(),
                'Error ' . $e->getCode(),
                (new Typo3Version())->getMajorVersion() >= 12
                    ? ContextualFeedbackSeverity::ERROR
                    : \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
            );
            return false;
        }
        return $success;
    }

    /**
     * Translates a label.
     *
     * @param string $id
     * @param array|null $arguments
     * @return string
     */
    protected function translate(string $id, ?array $arguments = null): string
    {
        $value = LocalizationUtility::translate($id, 'ig_ldap_sso_auth', $arguments);
        return $value ?? $id;
    }

    /**
     * Saves current state.
     *
     * @param \Causal\IgLdapSsoAuth\Domain\Model\Configuration|null $configuration
     */
    protected function saveState(
        ?\Causal\IgLdapSsoAuth\Domain\Model\Configuration $configuration = null
    ): void
    {
        $GLOBALS['BE_USER']->uc['ig_ldap_sso_auth']['selection'] = [
            'action' => $this->request->getControllerActionName(),
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
    protected function uksort_recursive(array &$array, $key_compare_func): bool
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
     * @param string $module
     */
    private function loadJavaScriptModule(string $module): void
    {
        /** @var PageRenderer $pageRenderer */
        $pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);

        if ((new Typo3Version())->getMajorVersion() >= 12) {
            $pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@causal/ig-ldap-sso-auth/' . $module . '.js')
                    ->invoke('create', [
                        // options go here...
                    ])
            );
        } else {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/IgLdapSsoAuth/' . ucfirst($module));
        }
    }
}
