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

namespace Causal\IgLdapSsoAuth\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Abstract class AbstractUserGroupRepository when willing to work
 * with Extbase-based domain objects for Backend and Frontend user
 * groups before Extbase is even ready and only basic operations
 * are needed (no mapping nor fancy other stuff).
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 */
abstract class AbstractUserGroupRepository
{

    /**
     * @var string
     */
    protected $className = '';

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var string
     */
    protected $selectFields = 'uid,pid,title,hidden';

    /**
     * Default constructor enforcing properties has been defined in derived classes.
     */
    final public function __construct()
    {
        if (empty($this->className)) {
            throw new \LogicException(get_class($this) . ' must have a property $className', 1449144226);
        }
        if (empty($this->tableName)) {
            throw new \LogicException(get_class($this) . ' must have a property $tableName', 1449144585);
        }
    }

    /**
     * Returns a single backend/frontend user group.
     *
     * @param int $uid
     * @return \TYPO3\CMS\Extbase\Domain\Model\BackendUserGroup|\TYPO3\CMS\Extbase\Domain\Model\FrontendUserGroup|null
     */
    public function findByUid($uid)
    {
        $queryBuilder = $this->getQueryBuilder();
        $row = $queryBuilder
            ->select(...explode(',', $this->selectFields))
            ->from($this->tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    (int)$uid
                )
            )
            ->execute()
            ->fetchAll();

        if ($row) {
            $userGroup = GeneralUtility::makeInstance($this->className);
            $this->thawProperties($userGroup, $row);
        } else {
            $userGroup = null;
        }

        return $userGroup;
    }

    /**
     * Sets the given properties on the object.
     *
     * @param AbstractEntity $object The object to set properties on
     * @param array $row
     * @return void
     */
    protected function thawProperties(AbstractEntity $object, array $row)
    {
        foreach ($row as $field => $value) {
            $propertyName = GeneralUtility::underscoredToLowerCamelCase($field);
            $object->_setProperty($propertyName, $value);
        }
    }

    /**
     * Returns the query builder for the database connection.
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    protected static function getQueryBuilder()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_igldapssoauth_config');
        return $queryBuilder;
    }
}
