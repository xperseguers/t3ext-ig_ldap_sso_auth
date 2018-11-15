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

namespace Causal\IgLdapSsoAuth\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render a configuration table
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ConfigurationTableViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{
    use CompileWithRenderStatic;
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('data', 'mixed', '', true, null);
        $this->registerArgument('humanKeyNames', 'bool', '', false, false);
    }

    /**
     * Renders a configuration table.
     *
     * @param array $arguments
     * @param callable|\Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $data = $arguments['data'];
        $humanKeyNames = $arguments['humanKeyNames'];
        $hasError = false;
        $content = (new self)->renderTable($data, $humanKeyNames, 1, $hasError, $renderingContext->getControllerContext());

        return $content;
    }

    /**
     * Renders a configuration table.
     *
     * @param array|string $data
     * @param bool $humanKeyNames
     * @param int $depth
     * @param bool &$hasError
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return string
     */
    protected function renderTable($data, $humanKeyNames, $depth, &$hasError, $controllerContext)
    {
        if (!is_array($data)) {
            return htmlspecialchars($data);
        } elseif (count($data) === 0) {
            return '<em>' . htmlspecialchars($this->translate('module_status.messages.empty', null, $controllerContext)) . '</em>';
        }

        $tableClass = 'table table-striped table-hover';
        $trClass = '';

        $content = [];
        foreach ($data as $key => $value) {
            $hasValueError = false;
            $valueCell = $this->renderValueCell($value, $key, $depth, $hasValueError, $controllerContext);
            $class = 'key';
            if ($hasValueError) {
                $hasError = true;
                $class .= ' error';
            }
            if ($humanKeyNames) {
                $key = $this->processKey($key, $controllerContext);
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
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return string
     */
    protected function renderValueCell($value, $key, $depth, &$hasError, $controllerContext)
    {
        if ($key === '__errors') {
            $hasError = true;
        }
        if (is_array($value)) {
            return sprintf('<td>%s</td>', $this->renderTable($value, false, $depth + 1, $hasError, $controllerContext));
        }

        /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
        static $iconFactory = null;
        if ($iconFactory === null) {
            $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        }

        $class = 'value-default';

        if (is_bool($value)) {
            if ($value === true) {
                $icon = 'actions-edit-hide';
                $messageId = 'module_status.messages.enabled';
                $class = 'value-enabled';
            } else {
                $icon = 'actions-edit-unhide';
                $messageId = 'module_status.messages.disabled';
                $class = 'value-disabled';
            }
            $value = $iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            $value .=  ' ' . htmlspecialchars($this->translate($messageId, null, $controllerContext));
        } elseif ($depth > 1 && $key === 'status') {
            $label = $value;
            if ($value === 'Success') {
                $icon = 'status-dialog-ok';
                $class = 'value-success';
            } else {
                $icon = 'status-dialog-warning';
                $class = 'value-error';
                $hasError = true;
            }
            $value = $iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            $value .=  ' ' . htmlspecialchars($label);
        } elseif ($value instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
            if ($value instanceof \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup) {
                $icon = 'status-user-group-backend';
                $table = 'be_groups';
            } else {
                $icon = 'status-user-group-frontend';
                $table = 'fe_groups';
            }
            $options = [
                'title' => 'id=' . $value->getUid(),
            ];
            /** @var \Causal\IgLdapSsoAuth\Hooks\IconFactory $iconFactoryHook */
            static $iconFactoryHook = null;
            if ($iconFactoryHook === null) {
                $iconFactoryHook = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Hooks\IconFactory::class);
            }
            $overlay = $iconFactoryHook->postOverlayPriorityLookup(
                $table,
                ['uid' => $value->getUid()],
                [],
                null
            );
            $value = $iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL, $overlay)->render() . ' ' . htmlspecialchars($value->getTitle());
            $value = str_replace('<img src=', '<img title="' . htmlspecialchars($options['title']) . '" src=', $value);
        } else {
            $value = htmlspecialchars($value);
        }

        return sprintf('<td class="%s">%s</td>', $class, $value);
    }

    /**
     * Returns an meaningful description out of a configuration array key.
     *
     * @param string $key
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return string
     */
    protected function processKey($key, $controllerContext)
    {
        return $this->translate('module_status.configuration.' . $key, null, $controllerContext);
    }

    /**
     * Translates a label.
     *
     * @param string $id
     * @param array $arguments
     * @param \TYPO3\CMS\Extbase\Mvc\Controller\ControllerContext $controllerContext
     * @return string|null
     */
    protected function translate($id, array $arguments = null, $controllerContext)
    {
        $request = $controllerContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($id, $extensionName, $arguments);
        return $value !== null ? $value : $id;
    }

}
