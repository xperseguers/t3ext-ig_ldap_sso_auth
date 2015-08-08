<?php
namespace Causal\IgLdapSsoAuth\ViewHelpers;

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
        return \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($iconName, $options);
    }

}
