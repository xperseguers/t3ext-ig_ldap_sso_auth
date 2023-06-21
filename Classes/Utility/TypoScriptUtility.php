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

namespace Causal\IgLdapSsoAuth\Utility;

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
	 * Tool for TypoScript parsing
	 *
	 * @var \TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory|null
	 */
	protected static ?\TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory $typoScriptStringFactory = null;

    /**
     * Loads and parses TypoScript from a string.
     *
     * @param string|null $typoScript
     * @return array
     */
    public static function loadTypoScript(?string $typoScript = null): array
    {
		if ($typoScript === null) {
			return [];
		}

        $typoScriptStringFactory = static::getTypoScriptStringFactory();
        $rootNode = $typoScriptStringFactory->parseFromStringWithIncludes(md5($typoScript), $typoScript);

		return $rootNode->toArray();
    }

    /**
     * Get the TypoScript parsing tool.
     *
     * @return \TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory
     */
    protected static function getTypoScriptStringFactory(): \TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory
    {
		if (static::$typoScriptStringFactory === null) {
			static::$typoScriptStringFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptStringFactory::class);
		}

		return static::$typoScriptStringFactory;
    }
}
