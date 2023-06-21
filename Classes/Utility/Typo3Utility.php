<?php

declare(strict_types=1);

namespace Causal\IgLdapSsoAuth\Utility;

/**
 * General utility class
 */
class Typo3Utility
{
	public const FE = 'FE';
	public const BE = 'BE';

	/**
	 * Returns the TYPO3 mode (FE or BE).
	 *
	 * @return string
	 */
	public static function getTypo3Mode(): string
	{
		if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof \Psr\Http\Message\ServerRequestInterface
			&& \TYPO3\CMS\Core\Http\ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isFrontend()
		) {
			return self::FE;
		}

		return self::BE;
	}
}
