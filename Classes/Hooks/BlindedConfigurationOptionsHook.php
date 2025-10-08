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

namespace Causal\IgLdapSsoAuth\Hooks;

class BlindedConfigurationOptionsHook
{
    /**
     * Blind password in ConfigurationOptions
     */
    public function modifyBlindedConfigurationOptions(array $blindedConfigurationOptions): array
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['ig_ldap_sso_auth']['configuration']['ldap_password'])) {
            $blindedConfigurationOptions['TYPO3_CONF_VARS']['EXTENSIONS']['ig_ldap_sso_auth']['configuration']['ldap_password'] = '******';
        }

        return $blindedConfigurationOptions;
    }
}
