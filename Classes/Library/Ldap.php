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

namespace Causal\IgLdapSsoAuth\Library;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Causal\IgLdapSsoAuth\Utility\LdapUtility;

/**
 * Class Ldap for the 'ig_ldap_sso_auth' extension.
 *
 * @author        Xavier Perseguers <xavier@causal.ch>
 * @author        Michael Gagnon <mgagnon@infoglobe.ca>
 * @package       TYPO3
 * @subpackage    ig_ldap_sso_auth
 */
#[Autoconfigure(shared: false)]
class Ldap
{
    private static array $instances = [];

    /**
     * @var string
     */
    protected $lastBindDiagnostic = '';

    public function __construct(
        protected readonly LdapUtility $ldapUtility
    )
    {
    }

    function __destruct() {
        $this->disconnectAll();
    }

    /**
     * Returns an instance of this class.
     *
     * @return Ldap
     */
    public static function getInstance(?string $identifier = null): self
    {
        if ($identifier !== null && isset(static::$instances[$identifier])) {
            return static::$instances[$identifier];
        }

        $instance = GeneralUtility::makeInstance(__CLASS__);

        if ($identifier !== null) {
            static::$instances[$identifier] = $instance;
        }

        return $instance;
    }

    /**
     * Initializes a connection to the LDAP server.
     *
     * @param array $config
     * @return bool
     * @throws \Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException when LDAP extension for PHP is not available
     */
    public function connect(array $config = []): bool
    {
        $debugConfiguration = [
            'host' => $config['host'],
            'port' => $config['port'],
            'charset' => $config['charset'],
            'server' => $config['server'],
            'tls' => $config['tls'],
            'tlsReqcert' => $config['tlsReqcert'],
            'ssl' => $config['ssl'],
        ];
        // Connect to ldap server.
        if (!$this->ldapUtility->connect(
            $config['host'],
            $config['port'],
            3,
            $config['charset'],
            Configuration::getServerType((int)$config['server']),
            (bool)$config['tls'],
            (bool)$config['ssl'],
            (bool)$config['tlsReqcert']
        )) {
            static::getLogger()->error('Cannot connect', $debugConfiguration);
            return false;
        }

        $debugConfiguration['binddn'] = $config['binddn'];
        $debugConfiguration['password'] = $config['password'] !== '' ? '••••••••••••' : '';

        // Bind to ldap server.
        if (!$this->ldapUtility->bind($config['binddn'], $config['password'])) {
            $status = $this->ldapUtility->getStatus();
            $this->lastBindDiagnostic = $status['bind']['diagnostic'];

            if (!$this->lastBindDiagnostic) {
                $this->lastBindDiagnostic = $status['bind']['status'];
            }

            $message = 'Cannot bind to LDAP';
            if (!empty($this->lastBindDiagnostic)) {
                $message .= ': ' . $this->lastBindDiagnostic;
            }
            static::getLogger()->error($message, $debugConfiguration);

            $this->disconnect();

            return false;
        }

        static::getLogger()->debug('Successfully connected', $debugConfiguration);

        return true;
    }

    /**
     * Disconnects the LDAP server.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->ldapUtility->disconnect();
    }

    public function disconnectAll(): void
    {
        $this->disconnect();
        foreach (static::$instances as $instance) {
            $instance->disconnect();
        }
    }

    /**
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->ldapUtility->isConnected();
    }

    /**
     * Returns the corresponding DN if a given user is provided, otherwise false.
     *
     * @param string|null $username
     * @param string|null $password User's password. If null password will not be checked
     * @param string|null $baseDn
     * @param string|null $filter
     * @return bool|string
     */
    public function validateUser(
        ?string $username = null,
        #[\SensitiveParameter] ?string $password = null,
        ?string $baseDn = null,
        ?string $filter = null
    )
    {
        // If user found on ldap server.
        if ($this->ldapUtility->search(
            $baseDn,
            str_replace('{USERNAME}', ldap_escape($username, '', LDAP_ESCAPE_FILTER), $filter),
            ['dn']
        )) {
            // Validate with password.
            if ($password !== null) {
                // Bind DN of user with password.
                if (empty($password)) {
                    $this->lastBindDiagnostic = 'Empty password provided!';

                    return false;
                } elseif ($this->ldapUtility->bind($this->ldapUtility->getDn(), $password)) {
                    $dn = $this->ldapUtility->getDn();

                    // Restore last LDAP binding
                    $config = Configuration::getLdapConfiguration();
                    $this->ldapUtility->bind($config['binddn'], $config['password']);
                    $this->lastBindDiagnostic = '';

                    return $dn;
                } else {
                    $status = $this->ldapUtility->getStatus();
                    $this->lastBindDiagnostic = $status['bind']['diagnostic'];

                    return false;    // Password does not match
                }

                // If enabled, SSO authentication without password
            } elseif ($password === null && Configuration::getValue('SSOAuthentication')) {

                return $this->ldapUtility->getDn();

            } else {

                // User invalid. Authentication failed.
                return false;
            }

        }

        return false;
    }

    /**
     * Searches LDAP entries satisfying some filter.
     *
     * @param string|null $baseDn
     * @param string|null $filter
     * @param array $attributes
     * @param bool $firstEntry
     * @param int $limit
     * @param bool $continueLastSearch
     * @return array
     */
    public function search(
        ?string $baseDn = null,
        ?string $filter = null,
        array $attributes = [],
        bool $firstEntry = false,
        int $limit = 0,
        bool $continueLastSearch = false
    ): array
    {
        $result = [];
        $timeLimit = 0;
        $dereferenceAliases = LDAP_DEREF_NEVER;

        if ($this->ldapUtility->search(
            $baseDn,
            $filter,
            $attributes,
            false,
            $firstEntry ? 1 : $limit,
            $timeLimit,
            $dereferenceAliases,
            $continueLastSearch
        )) {
            if ($firstEntry) {
                $result = $this->ldapUtility->getFirstEntry();
                $result['dn'] = $this->ldapUtility->getDn();
                unset($result['count']);
            } else {
                $result = $this->ldapUtility->getEntries();
            }
        }

        return $result;
    }

    /**
     * Returns true if last call to @see search() returned a partial result set.
     * You should then call @see searchNext().
     *
     * @return bool
     */
    public function isPartialSearchResult(): bool
    {
        return $this->ldapUtility->hasMoreEntries();
    }

    /**
     * Returns the LDAP status.
     *
     * @return array
     */
    public function getStatus(): array
    {
        return $this->ldapUtility->getStatus();
    }

    /**
     * Returns the last ldap_bind() diagnostic (may be empty).
     *
     * @return string
     */
    public function getLastBindDiagnostic(): string
    {
        return $this->lastBindDiagnostic;
    }

    /**
     * Escapes a string for use in a LDAP filter statement.
     *
     * To find the groups of a user by filtering the groups where the
     * authenticated user is in the members list some characters
     * in the users distinguished name can make the filter expression
     * invalid.
     *
     * At the moment this problem was experienced with brackets which
     * are also used in the filter, e.g.:
     * (&(objectClass=group)(member={USERDN}))
     *
     * Additionally a single backslash (that is used for escaping special
     * characters like commas) needs to be escaped. E.g.:
     * CN=Lastname\, Firstname,DC=company,DC=tld needs to be escaped like:
     * CN=Lastname\\, Firstname,DC=company,DC=tld
     *
     * @param string $dn
     * @return string Escaped $dn
     */
    public function escapeDnForFilter(string $dn): string
    {
        $escapeCharacters = ['\\', '(', ')'];
        foreach ($escapeCharacters as $escapeCharacter) {
            $dn = str_replace($escapeCharacter, '\\' . $escapeCharacter, $dn);
        }
        return $dn;
    }

    /**
     * Returns a logger.
     *
     * @return LoggerInterface
     */
    protected static function getLogger(): LoggerInterface
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        static $logger = null;

        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        }

        return $logger;
    }
}
