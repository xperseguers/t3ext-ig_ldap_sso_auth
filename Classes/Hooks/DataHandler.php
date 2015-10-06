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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Library\Configuration;

/**
 * Hook into \TYPO3\CMS\Core\DataHandling\DataHandler.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class DataHandler
{

    /**
     * Hooks into \TYPO3\CMS\Core\DataHandling\DataHandler after records have been saved to the database.
     *
     * @param string $operation
     * @param string $table
     * @param mixed $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $pObj
     * @return void
     */
    public function processDatamap_afterDatabaseOperations($operation, $table, $id, array $fieldArray, \TYPO3\CMS\Core\DataHandling\DataHandler $pObj)
    {
        if ($table !== 'tx_igldapssoauth_config') {
            // Early return
            return;
        }
        if ($operation === 'new' && !is_numeric($id)) {
            $id = $pObj->substNEWwithIDs[$id];
        }

        $row = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $id);
        if ($row['group_membership'] == Configuration::GROUP_MEMBERSHIP_FROM_MEMBER) {
            $warningMessageKeys = array();

            if (!empty($row['be_users_basedn']) && !empty($row['be_groups_basedn'])) {
                // Check backend mapping
                $mapping = Configuration::parseMapping($row['be_users_mapping']);
                if (!isset($mapping['usergroup'])) {
                    $warningMessageKeys[] = 'tx_igldapssoauth_config.group_membership.fe.missingUsergroupMapping';
                }
            }
            if (!empty($row['fe_users_basedn']) && !empty($row['fe_groups_basedn'])) {
                // Check frontend mapping
                $mapping = Configuration::parseMapping($row['fe_users_mapping']);
                if (!isset($mapping['usergroup'])) {
                    $warningMessageKeys[] = 'tx_igldapssoauth_config.group_membership.be.missingUsergroupMapping';
                }
            }

            foreach ($warningMessageKeys as $key) {
                /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
                $flashMessage = GeneralUtility::makeInstance(
                    'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
                    $this->getLanguageService()->sL('LLL:EXT:ig_ldap_sso_auth/Resources/Private/Language/locallang_db.xlf:' . $key, true),
                    '',
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING,
                    true
                );
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }
    }

    /**
     * Returns the LanguageService.
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

}
