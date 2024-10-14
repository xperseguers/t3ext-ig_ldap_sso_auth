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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render a conf* View helper which renders the flash messages (if there are any).
 *
 * Largely inspired by @see \TYPO3\CMS\Fluid\ViewHelpers\FlashMessagesViewHelper
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class FlashMessagesViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * ViewHelper outputs HTML therefore output escaping has to be disabled
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var string The message severity class names
     */
    protected static $classes = [
        FlashMessage::NOTICE => 'notice',
        FlashMessage::INFO => 'info',
        FlashMessage::OK => 'success',
        FlashMessage::WARNING => 'warning',
        FlashMessage::ERROR => 'danger'
    ];

    /**
     * @var string The message severity icon names
     */
    protected static $icons = [
        FlashMessage::NOTICE => 'lightbulb-o',
        FlashMessage::INFO => 'info',
        FlashMessage::OK => 'check',
        FlashMessage::WARNING => 'exclamation',
        FlashMessage::ERROR => 'times'
    ];

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('queueIdentifier', 'string', 'Flash-message queue to use');
        $this->registerArgument('as', 'string', 'The name of the current flashMessage variable for rendering inside');
    }

    /**
     * Renders FlashMessages and flushes the FlashMessage queue
     * Note: This disables the current page cache in order to prevent FlashMessage output
     * from being cached.
     *
     * @see \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::no_cache
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return mixed
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $as = $arguments['as'];
        $queueIdentifier = isset($arguments['queueIdentifier']) ? $arguments['queueIdentifier'] : null;
        $flashMessages = $renderingContext->getControllerContext()
            ->getFlashMessageQueue($queueIdentifier)->getAllMessagesAndFlush();
        if ($flashMessages === null || empty($flashMessages)) {
            return '';
        }

        if ($as === null) {
            $out = [];
            foreach ($flashMessages as $flashMessage) {
                $out[] = static::renderFlashMessage($flashMessage);
            }
            return implode(LF, $out);
        }
        $templateVariableContainer = $renderingContext->getVariableProvider();
        $templateVariableContainer->add($as, $flashMessages);
        $content = $renderChildrenClosure();
        $templateVariableContainer->remove($as);

        return $content;
    }

    /**
     * @param FlashMessage $flashMessage
     * @return string
     */
    protected static function renderFlashMessage(FlashMessage $flashMessage): string
    {
        $className = 'alert-' . static::$classes[$flashMessage->getSeverity()];
        $iconName = 'fa-' . static::$icons[$flashMessage->getSeverity()];

        $messageTitle = $flashMessage->getTitle();
        $markup = [];
        $markup[] = '<div class="alert ' . $className . '">';
        $markup[] = '    <div class="media">';
        $markup[] = '        <div class="media-left">';
        $markup[] = '            <span class="fa-stack fa-lg">';
        $markup[] = '                <i class="fa fa-circle fa-stack-2x"></i>';
        $markup[] = '                <i class="fa ' . $iconName . ' fa-stack-1x"></i>';
        $markup[] = '            </span>';
        $markup[] = '        </div>';
        $markup[] = '        <div class="media-body">';
        if (!empty($messageTitle)) {
            $markup[] = '            <h4 class="alert-title">' . htmlspecialchars($messageTitle) . '</h4>';
        }
        $markup[] = '            <p class="alert-message">' . $flashMessage->getMessage() . '</p>';
        $markup[] = '        </div>';
        $markup[] = '    </div>';
        $markup[] = '</div>';
        return implode('', $markup);
    }
}
