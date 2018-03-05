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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render an item of the menu
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 */
class ActionMenuItemViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractTagBasedViewHelper
{
    use CompileWithRenderStatic;

    /**
     * @var string
     */
    protected $tagName = 'option';

    /**
     * @return void
     */
    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('label', 'string', '', true, null);
        $this->registerArgument('controller', 'string', '', true, null);
        $this->registerArgument('action', 'string', '', true, null);
        $this->registerArgument('arguments', 'array', '', false, []);
        $this->registerArgument('tag', '\TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder', '', false, $this->tag);
    }

    /**
     * Renders an ActionMenu option tag
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
        $label = $arguments['label'];
        $controller = $arguments['controller'];
        $action = $arguments['action'];
        $tag = $arguments['tag'];
        $arguments = $arguments['arguments'];
        $uriBuilder = $renderingContext->getControllerContext()->getUriBuilder();
        $uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
        $tag->addAttribute('value', $uri);
        $currentRequest = $renderingContext->getControllerContext()->getRequest();
        $currentController = $currentRequest->getControllerName();
        $currentAction = $currentRequest->getControllerActionName();

        if ($action === $currentAction && $controller === $currentController) {
            $tag->addAttribute('selected', 'selected');
        }
        $tag->setContent($label);
        return $tag->render();
    }
}
