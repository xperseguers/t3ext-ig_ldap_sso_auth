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
     * Loads and parses TypoScript from a file.
     *
     * @param string $filePath
     * @return array
     */
    public static function loadTypoScriptFromFile(string $filePath): array
    {
        $fileName = GeneralUtility::getFileAbsFileName($filePath);
        $typoScript = file_get_contents($fileName);
        return static::loadTypoScript($typoScript);
    }

    /**
     * Loads and parses TypoScript from a string.
     *
     * @param string $typoScript
     * @return array
     */
    public static function loadTypoScript(string $typoScript): array
    {
        $typoScriptParser = static::getTypoScriptParser();
        $typoScriptParser->parse($typoScript);
        return $typoScriptParser->setup;
    }

    /**
     * Returns a clean TypoScript parser.
     *
     * @return TypoScriptParser
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
}
