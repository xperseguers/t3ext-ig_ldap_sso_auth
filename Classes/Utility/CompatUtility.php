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

use TYPO3\CMS\Core\Http\ApplicationType;

final class CompatUtility
{
    public static function getTypo3Mode(string $mode = ''): string
    {
        if ($mode !== '') {
            if (str_ends_with($mode, 'FE')) {
                return 'FE';
            }
            return 'BE';
        }

        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request !== null) {
            // At least in TYPO3 v11 the request is not always available, although in
            // an actual BE/FE context...
            return ApplicationType::fromRequest($request)->isFrontend()
                ? 'FE'
                : 'BE';
        }

        return 'BE';
    }
}
