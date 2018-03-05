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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Displays sprite icon identified by iconName key
 *
 * @author     Felix Kopp <felix-source@phorax.com>
 */
class SpriteManagerIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('iconName', 'string', '', true, null);
        $this->registerArgument('options', 'array', '', false, []);
        $this->registerArgument('uid', 'int', '', false, 0);
    }

    /**
     * Prints sprite icon html for $iconName key.
     *
     * @param array $arguments
     * @param callable|\Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        $iconName = $arguments['iconName'];
        $options = $arguments['options'];
        $uid = $arguments['uid'];

        if (!isset($options['title']) && $uid > 0) {
            $options['title'] = 'id=' . $uid;
        }

        /** @var \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory */
        $iconFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconFactory::class);
        $html = $iconFactory->getIcon($iconName, \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL)->render();
        if (!empty($options)) {
            $attributes = '';
            foreach ($options as $key => $value) {
                $attributes .= htmlspecialchars($key) . '="' . htmlspecialchars($value) . '" ';
            }
            $html = str_replace('<img src=', '<img ' . $attributes . 'src=', $html);
        }

        return $html;
    }
}
