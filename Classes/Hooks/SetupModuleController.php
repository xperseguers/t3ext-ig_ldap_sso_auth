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

namespace Causal\IgLdapSsoAuth\Hooks;

/**
 * Hook into \TYPO3\CMS\Setup\Controller\SetupModuleController to prevent
 * the password to be modified for LDAP backend users.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class SetupModuleController
{
    /**
     * Pre-processes the submitted data.
     *
     * @param array $params
     * @param \TYPO3\CMS\Setup\Controller\SetupModuleController $pObj
     */
    public function preprocessData(array $params, \TYPO3\CMS\Setup\Controller\SetupModuleController $pObj)
    {
        if (empty($GLOBALS['BE_USER']->user['tx_igldapssoauth_dn'])) {
            return;
        }

        if (!empty($params['be_user_data']['password'])) {
            // Silently remove new password as we cannot send a flash message for
            // further information
            $params['be_user_data']['password'] = '';
            $params['be_user_data']['password2'] = '';
        }
    }

}
