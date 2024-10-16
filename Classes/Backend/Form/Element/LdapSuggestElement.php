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

namespace Causal\IgLdapSsoAuth\Backend\Form\Element;

use Causal\IgLdapSsoAuth\Library\Configuration;
use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Backend\Form\Element\InputTextElement;
use TYPO3\CMS\Backend\Form\Element\TextElement;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Suggest wizard.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class LdapSuggestElement extends AbstractFormElement
{
    /**
     * Renders a suggestion for the mapping.
     *
     * @return array
     */
    public function render(): array
    {
        $elementType = $this->data['parameterArray']['fieldConf']['config']['type'];
        switch ($elementType) {
            case 'input':
                $baseElementClass = InputTextElement::class;
                break;
            case 'text':
                $baseElementClass = TextElement::class;
                break;
            default:
                throw new \RuntimeException('Suggest wizard is not configured for type "' . $elementType . '"', 1553522818);
        }

        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 12) {
            /** @var AbstractNode $baseElement */
            $baseElement = GeneralUtility::makeInstance($baseElementClass);
            $baseElement->setData($this->data);
        } else {
            $baseElement = GeneralUtility::makeInstance($baseElementClass, $this->nodeFactory, $this->data);
        }

        $resultArray = $baseElement->render();

        $serverType = !empty($this->data['databaseRow']['ldap_server'])
            ? (int)$this->data['databaseRow']['ldap_server'][0]
            : Configuration::SERVER_OPENLDAP;

        if (str_ends_with($this->data['fieldName'], '_basedn')) {
            $suggestion = $this->suggestBaseDn();
        } else {
            $suggestion = $this->suggestMappingOrFilter($serverType);
        }

        if (!empty($suggestion)) {
            $suggestId = 'tx_igldapssoauth_suggest_' . $this->data['fieldName'];

            $out[] = '<div style="margin:1em 0 0 1em; font-size:11px;">';
            $fieldJs = '$("[data-formengine-input-name=\'data' . $this->data['elementBaseName'] . '\'").first()';
            $onclick = "var node=document.getElementById('$suggestId');$fieldJs.val(node.innerText || node.textContent);$fieldJs.trigger('change');";
            $out[] = '<strong>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:suggestion.server.' . $serverType)) . '</strong>';

            $out[] = '<pre style="margin:1em 0;" id="' . $suggestId . '">';
            $suggestion = htmlentities($suggestion);
            // Support for basic styling (BBCode)
            $suggestion = preg_replace('#\\[i\\](.*)\\[/i\\]#', '<em>\\1</em>', $suggestion);
            $out[] = $suggestion . '</pre>';

            // Prepare the "copy" button
            $button = '<input type="button" value="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:suggestion.copy')) . '" onclick="' . htmlspecialchars($onclick) . '" class="btn btn-default btn-sm" />';
            $out[] = $button;

            $out[] = '</div>';

            $suggestion = implode(LF, $out);
        }

        $resultArray['html'] .= $suggestion;
        return $resultArray;
    }

    /**
     * Suggests a base DN.
     *
     * @return string
     */
    protected function suggestBaseDn(): string
    {
        $bindDnParts = explode(',', $this->data['databaseRow']['ldap_binddn']);
        $suggestion = count($bindDnParts) > 2
            ? implode(',', array_slice($bindDnParts, -2))
            : '';
        return $suggestion;
    }

    /**
     * Suggests a mapping or a filter.
     *
     * @param int $serverType
     * @return string
     */
    protected function suggestMappingOrFilter(int $serverType): string
    {
        if (str_ends_with($this->data['fieldName'], '_mapping')) {
            $prefix = 'mapping_';
            $table = substr($this->data['fieldName'], 0, -8);
        } else {
            $prefix = 'filter_';
            $table = substr($this->data['fieldName'], 0, -7);
        }

        $templatePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('ig_ldap_sso_auth') . 'Resources/Private/Templates/TCA/';
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
