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

namespace Causal\IgLdapSsoAuth\ViewHelpers;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper;

/**
 * Displays sprite icon identified by iconName key
 *
 * @author     Felix Kopp <felix-source@phorax.com>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class SpriteManagerIconViewHelper extends AbstractBackendViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('iconName', 'string', 'Icon to use', true);
        $this->registerArgument('options', 'array', 'Additional tag attributes', false, []);
        $this->registerArgument('uid', 'int', 'UID of the record', false, 0);
    }

    /**
     * Prints sprite icon html for $iconName key.
     *
     * @return string
     */
    public function render(): string
    {
        if (!isset($this->arguments['options']['title']) && $this->arguments['uid'] > 0) {
            $this->arguments['options']['title'] = 'id=' . $this->arguments['uid'];
        }
        /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $html = $iconFactory->getIcon($this->arguments['iconName'], \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
        if (!empty($this->arguments['options'])) {
            $attributes = '';
            foreach ($this->arguments['options'] as $key => $value) {
                $attributes .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '" ';
            }
            $html = str_replace('<img src=', '<img ' . $attributes . 'src=', $html);
        }

        return $html;
    }
}
