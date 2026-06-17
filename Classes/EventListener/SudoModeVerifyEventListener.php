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

namespace Causal\IgLdapSsoAuth\EventListener;

use Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository;
use Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException;
use Causal\IgLdapSsoAuth\Library\Configuration;
use Causal\IgLdapSsoAuth\Library\Ldap;
use Causal\IgLdapSsoAuth\Utility\CompatUtility;
use TYPO3\CMS\Backend\Security\SudoMode\Event\SudoModeVerifyEvent;

final class SudoModeVerifyEventListener
{
    public function __construct(
        protected ConfigurationRepository $configurationRepository,
        protected Ldap $ldapInstance,
        protected array $extensionConfiguration,
    ) {
    }

    public function __invoke(SudoModeVerifyEvent $event): void
    {
        // SSO for BE means the user has to use the install tool password
        if (!$event->isUseInstallToolPassword() && !$this->extensionConfiguration['enableBESSO']) {
            foreach ($this->configurationRepository->findAll() as $configurationRecord) {
                Configuration::initialize(CompatUtility::getTypo3Mode('BE'), $configurationRecord);
                if (!Configuration::isEnabledForCurrentHost()) {
                    continue;
                }

                try {
                    $username = $GLOBALS['BE_USER']->user[$GLOBALS['BE_USER']->username_column ?? 'username'];
                    if ($username && Configuration::getValue('forceLowerCaseUsername')) {
                        // Possible enhancement: use \TYPO3\CMS\Core\Charset\CharsetConverter::conv_case instead
                        $username = strtolower($username);
                    }
                    if ($username && $this->ldapInstance->connect(Configuration::getLdapConfiguration())) {
                        $isValid = (false !== $this->ldapInstance->validateUser(
                            $username,
                            $event->getPassword(),
                            Configuration::getBackendConfiguration()['users']['basedn'] ?? '',
                            Configuration::getBackendConfiguration()['users']['filter'] ?? ''
                        ));
                        if (true === $isValid) {
                            $event->setVerified(true);
                            break;
                        }
                    }
                } catch (UnresolvedPhpDependencyException $e) {
                }
            }
        }
    }
}
