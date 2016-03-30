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

/**
 * Render a conf* View helper which renders the flash messages (if there are any).
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class FlashMessagesViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper
{
    /**
     * @param string $as The name of the current flashMessage variable for rendering inside
     * @return string rendered Flash Messages, if there are any.
     */
    public function render($as = null)
    {
        if (version_compare(TYPO3_branch, '7', '>=')) {
            return parent::render();
        } else {
            return parent::render(\TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper::RENDER_MODE_DIV);
        }
    }
}
