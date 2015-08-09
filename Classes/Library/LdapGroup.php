<?php
namespace Causal\IgLdapSsoAuth\Library;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class tx_igldapssoauth_typo3_group for the 'ig_ldap_sso_auth' extension.
 *
 * @author     Michael Gagnon <mgagnon@infoglobe.ca>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class LdapGroup
{

    /**
     * Returns LDAP group records based on a list of DNs provided as $membership,
     * taking group's baseDN and filter into consideration.
     *
     * @param array $membership
     * @param string $baseDn
     * @param string $filter
     * @param array $attributes
     * @param bool $extendedCheck true if groups should be actively checked against LDAP server, false to check against baseDN solely
     * @return array
     * @deprecated since 3.0, will be removed in 3.2, use selectFromMembership() instead
     */
    public static function select_from_membership($membership = array(), $baseDn = null, $filter = null, $attributes = array(), $extendedCheck = true)
    {
        GeneralUtility::logDeprecatedFunction();
        return static::selectFromMembership($membership, $baseDn, $filter, $attributes, $extendedCheck);
    }

    /**
     * Returns LDAP group records based on a list of DNs provided as $membership,
     * taking group's baseDN and filter into consideration.
     *
     * @param array $membership
     * @param string $baseDn
     * @param string $filter
     * @param array $attributes
     * @param bool $extendedCheck true if groups should be actively checked against LDAP server, false to check against baseDN solely
     * @return array
     */
    public static function selectFromMembership(array $membership = array(), $baseDn, $filter, array $attributes = array(), $extendedCheck = true)
    {
        $ldapGroups['count'] = 0;

        if (count($membership) === 0 || empty($filter)) {
            return $ldapGroups;
        }

        unset($membership['count']);

        foreach ($membership as $groupDn) {
            if (substr($groupDn, -strlen($baseDn)) !== $baseDn) {
                // Group $groupDn does not match the required baseDn for LDAP groups
                continue;
            }
            if ($extendedCheck) {
                $ldapGroup = Ldap::getInstance()->search($groupDn, $filter, $attributes);
            } else {
                $parts = explode(',', $groupDn);
                list($firstAttribute, $value) = explode('=', $parts[0]);
                $firstAttribute = strtolower($firstAttribute);
                $ldapGroup = array(
                    0 => array(
                        0 => $firstAttribute,
                        $firstAttribute => array(
                            0 => $value,
                            'count' => 1,
                        ),
                        'dn' => $groupDn,
                        'count' => 1,
                    ),
                    'count' => 1,
                );
            }
            if (!isset($ldapGroup['count']) || $ldapGroup['count'] == 0) {
                continue;
            }
            $ldapGroups['count']++;
            $ldapGroups[] = $ldapGroup[0];
        }

        return $ldapGroups;
    }

    /**
     * Returns groups associated to a given user (identified either by his DN or his uid attribute).
     *
     * @param string $baseDn
     * @param string $filter
     * @param string $userDn
     * @param string $userUid
     * @param array $attributes
     * @return array
     */
    public static function selectFromUser($baseDn, $filter = '', $userDn = '', $userUid = '', array $attributes = array())
    {
        $filter = str_replace('{USERDN}', Ldap::getInstance()->escapeDnForFilter($userDn), $filter);
        $filter = str_replace('{USERUID}', Ldap::getInstance()->escapeDnForFilter($userUid), $filter);

        $groups = Ldap::getInstance()->search($baseDn, $filter, $attributes);
        return $groups;
    }

    /**
     * Returns the membership information for a given user.
     *
     * @param array $ldap_user
     * @param array $mapping
     * @return array|bool
     * @deprecated since 3.0, will be removed in 3.2, use getMembership() instead
     */
    public static function get_membership($ldap_user = array(), $mapping = array())
    {
        GeneralUtility::logDeprecatedFunction();
        return static::getMembership($ldap_user, $mapping);
    }

    /**
     * Returns the membership information for a given user.
     *
     * @param array $ldapUser
     * @param array $mapping
     * @return array|bool
     */
    public static function getMembership(array $ldapUser = array(), array $mapping = array())
    {
        if (isset($mapping['usergroup']) && preg_match("`<([^$]*)>`", $mapping['usergroup'], $attribute)) {
            return $ldapUser[strtolower($attribute[1])];
        }

        return false;
    }

}
