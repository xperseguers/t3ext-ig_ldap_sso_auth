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

namespace Causal\IgLdapSsoAuth\Domain\Repository;

/**
 * Class FrontendUserGroupRepository when willing to work with
 * Extbase-based domain objects for Frontend user groups before
 * Extbase is even ready and only basic operations are needed
 * (no mapping nor fancy other stuff).
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class FrontendUserGroupRepository extends AbstractUserGroupRepository
{
    /**
     * @var string
     */
    protected $className = \Causal\IgLdapSsoAuth\Domain\Model\FrontendUserGroup::class;

    /**
     * @var string
     */
    protected $tableName = 'fe_groups';
}
