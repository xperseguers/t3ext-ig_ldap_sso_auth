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

/**
 * Render a configuration table
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class ConfigurationTableViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper
{

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Renders a configuration table.
     *
     * @param array|string $data
     * @param bool $humanKeyNames
     * @return string
     */
    public function render($data, $humanKeyNames = false)
    {
        $hasError = false;
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
    protected function renderTable($data, $humanKeyNames, $depth, &$hasError)
    {
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
            $hasValueError = false;
            $valueCell = $this->renderValueCell($value, $key, $depth, $hasValueError);
            $class = 'key';
            if ($hasValueError) {
                $hasError = true;
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
    protected function renderValueCell($value, $key, $depth, &$hasError)
    {
        if ($key === '__errors') {
            $hasError = true;
        }
        if (is_array($value)) {
            return sprintf('<td>%s</td>', $this->renderTable($value, false, $depth + 1, $hasError));
        }

        if (version_compare(TYPO3_version, '7.6', '>=')) {
            /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
            static $iconFactory = null;
            if ($iconFactory === null) {
                $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
            }
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
            if (version_compare(TYPO3_version, '7.6', '>=')) {
                $value = $iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            } else {
                $value = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon);
            }
            $value .=  ' ' . htmlspecialchars($this->translate($messageId));
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
            if (version_compare(TYPO3_version, '7.6', '>=')) {
                $value = $iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            } else {
                $value = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon);
            }
            $value .=  ' ' . htmlspecialchars($label);
        } elseif ($value instanceof \TYPO3\CMS\Extbase\DomainObject\AbstractEntity) {
            if ($value instanceof \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup) {
                $icon = 'status-user-group-backend';
                $table = 'be_groups';
            } else {
                $icon = 'status-user-group-frontend';
                $table = 'fe_groups';
            }
            $options = array(
                'title' => 'id=' . $value->getUid(),
            );
            if (version_compare(TYPO3_version, '7.6', '>=')) {
                /** @var \Causal\IgLdapSsoAuth\Hooks\IconFactory $iconFactoryHook */
                static $iconFactoryHook = null;
                if ($iconFactoryHook === null) {
                    $iconFactoryHook = GeneralUtility::makeInstance(\Causal\IgLdapSsoAuth\Hooks\IconFactory::class);
                }
                $overlay = $iconFactoryHook->postOverlayPriorityLookup(
                    $table,
                    array('uid' => $value->getUid()),
                    array(),
                    null
                );
                $value = $iconFactory->getIcon($icon, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL, $overlay)->render() . ' ' . htmlspecialchars($value->getTitle());
                $value = str_replace('<img src=', '<img title="' . htmlspecialchars($options['title']) . '" src=', $value);
            } else {
                $value = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, $options) . ' ' . htmlspecialchars($value->getTitle());
            }
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
    protected function processKey($key)
    {
        return $this->translate('module_status.configuration.' . $key);
    }

    /**
     * Translates a label.
     *
     * @param string $id
     * @param array $arguments
     * @return string|null
     */
    protected function translate($id, array $arguments = null)
    {
        $request = $this->controllerContext->getRequest();
        $extensionName = $request->getControllerExtensionName();
        $value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($id, $extensionName, $arguments);
        return $value !== null ? $value : $id;
    }

}
