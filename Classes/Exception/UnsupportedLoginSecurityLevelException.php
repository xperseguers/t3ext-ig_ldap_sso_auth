<?php
namespace Causal\IgLdapSsoAuth\Exception;

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

/**
 * An exception when an unusupported login security level is
 * detected in either $GLOBALS['TYPO3_CONF_VARS']['BE']['loginSecurityLevel']
 * or $GLOBALS['TYPO3_CONF_VARS']['FE']['loginSecurityLevel'].
 *
 * @author Xavier Perseguers <xavier@causal.ch>
 */
class UnsupportedLoginSecurityLevelException extends IgLdapSsoAuthException
{

}
