<?php
namespace Causal\IgLdapSsoAuth\ViewHelpers;

use TYPO3\CMS\Core\Messaging\FlashMessageRendererResolver;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FlashMessagesViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper
{
    /**
     * Renders the flash messages as unordered list
     *
     * @param array $flashMessages \TYPO3\CMS\Core\Messaging\FlashMessage[]
     * @return string
     */
    protected function renderDefault(array $flashMessages) : string
    {
        $content = GeneralUtility::makeInstance(FlashMessageRendererResolver::class)->resolve()->render($flashMessages);
        return $content;
    }
}
