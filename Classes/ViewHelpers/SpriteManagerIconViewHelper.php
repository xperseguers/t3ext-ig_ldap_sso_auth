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
 * Displays sprite icon identified by iconName key
 *
 * @author     Felix Kopp <felix-source@phorax.com>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class SpriteManagerIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper
{

    /**
     * Prints sprite icon html for $iconName key.
     *
     * @param string $iconName
     * @param array $options
     * @param int $uid
     * @return string
     */
    public function render($iconName, $options = array(), $uid = 0)
    {
        if (!isset($options['title']) && $uid > 0) {
            $options['title'] = 'id=' . $uid;
        }
        if (version_compare(TYPO3_version, '7.6', '>=')) {
            /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
            $iconFactory = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Imaging\\IconFactory');
            $html = $iconFactory->getIcon($iconName, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
            if (!empty($options)) {
                $attributes = '';
                foreach ($options as $key => $value) {
                    $attributes .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '" ';
                }
                $html = str_replace('<img src=', '<img ' . $attributes . 'src=', $html);
            }
        } else {
            $html = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($iconName, $options);
        }

        return $html;
    }

}
