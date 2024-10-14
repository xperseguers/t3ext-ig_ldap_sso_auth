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

namespace Causal\IgLdapSsoAuth\Utility;

use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TypoScript utility class.
 *
 * @author     Xavier Perseguers <xavier@causal.ch>
 * @package    TYPO3
 * @subpackage ig_ldap_sso_auth
 */
class TypoScriptUtility
{
    /**
     * Loads and parses TypoScript from a string.
     *
     * @param string|null $typoScript
     * @return array
     */
    public static function loadTypoScript(?string $typoScript): array
    {
        if (empty($typoScript)) {
            return [];
        }

        if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 12) {
            $typoScriptParser = static::getTypoScriptParser();
            $typoScriptParser->parse($typoScript);
            return $typoScriptParser->setup;
        }

        $typoScriptStringFactory = static::getTypoScriptStringFactory();
        $rootNode = $typoScriptStringFactory->parseFromStringWithIncludes(md5($typoScript), $typoScript);

        return $rootNode->toArray();
    }

    /**
     * Returns a clean TypoScript parser.
     *
     * @return TypoScriptParser
     * @deprecated since TYPO3 v12
     */
    protected static function getTypoScriptParser(): TypoScriptParser
    {
        /** @var TypoScriptParser $typoScriptParser */
        static $typoScriptParser = null;

        if ($typoScriptParser === null) {
            $typoScriptParser = GeneralUtility::makeInstance(TypoScriptParser::class);
        }

        // Reset the parser state
        $typoScriptParser->setup = [];

        return $typoScriptParser;
    }

    /**
     * Gets the TypoScript parsing tool.
     *
     * @return TypoScriptStringFactory
     */
    protected static function getTypoScriptStringFactory(): TypoScriptStringFactory
    {
        /** @var TypoScriptStringFactory $typoScriptStringFactory */
        static $typoScriptStringFactory = null;

        if (static::$typoScriptStringFactory === null) {
            static::$typoScriptStringFactory = GeneralUtility::makeInstance(TypoScriptStringFactory::class);
        }

        return static::$typoScriptStringFactory;
    }
}
