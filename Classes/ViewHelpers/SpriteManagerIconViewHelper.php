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
        $this->registerArgument('size', 'string', 'Size of the icon', false, 'small');
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

        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() >= 13) {
            $iconSize = match($this->arguments['size']) {
                'small' => \TYPO3\CMS\Core\Imaging\IconSize::SMALL,
                'medium' => \TYPO3\CMS\Core\Imaging\IconSize::MEDIUM,
                'large' => \TYPO3\CMS\Core\Imaging\IconSize::LARGE,
                'mega' => \TYPO3\CMS\Core\Imaging\IconSize::MEGA,
                default => \TYPO3\CMS\Core\Imaging\IconSize::DEFAULT,
            };
        } else {
            $iconSize = match($this->arguments['size']) {
                'small' => \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL,
                'medium' => \TYPO3\CMS\Core\Imaging\Icon::SIZE_MEDIUM,
                'large' => \TYPO3\CMS\Core\Imaging\Icon::SIZE_LARGE,
                'mega' => \TYPO3\CMS\Core\Imaging\Icon::SIZE_MEGA,
                default => \TYPO3\CMS\Core\Imaging\Icon::SIZE_DEFAULT,
            };
        }

        /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $html = $iconFactory->getIcon($this->arguments['iconName'], $iconSize)->render();
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
