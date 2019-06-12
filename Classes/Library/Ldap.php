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

namespace Causal\IgLdapSsoAuth\Library;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Ldap for the 'ig_ldap_sso_auth' extension.
 *
 * @author        Xavier Perseguers <xavier@causal.ch>
 * @author        Michael Gagnon <mgagnon@infoglobe.ca>
 * @package       TYPO3
 * @subpackage    ig_ldap_sso_auth
 */
class Ldap
{

    /**
     * @var string
     */
    protected $lastBindDiagnostic = '';

    /**
     * @var \Causal\IgLdapSsoAuth\Utility\LdapUtility
     * @inject
     */
    protected $ldapUtility;

    /**
     * Returns an instance of this class.
     *
     * @return Ldap
     */
    public static function getInstance()
    {
        /** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
        static $objectManager = null;
        if ($objectManager === null) {
            $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        }
        return $objectManager->get(__CLASS__);
    }

    /**
     * Initializes a connection to the LDAP server.
     *
     * @param array $config
     * @return bool
     * @throws \Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException when LDAP extension for PHP is not available
     */
    public function connect(array $config = [])
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
        if (!$this->ldapUtility->connect($config['host'], $config['port'], $config['protocol'], $config['charset'], $config['server'], $config['tls'], $config['ssl'], $config['tlsReqcert'])) {
            static::getLogger()->error( 'Cannot connect', $debugConfiguration);
            return false;
        }

        $debugConfiguration['binddn'] = $config['binddn'];
        $debugConfiguration['password'] = $config['password'] !== '' ? '••••••••••••' : '';

        // Bind to ldap server.
        if (!$this->ldapUtility->bind($config['binddn'], $config['password'])) {
            $status = $this->ldapUtility->getStatus();
            $this->lastBindDiagnostic = $status['bind']['diagnostic'];

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
    public function disconnect()
    {
        $this->ldapUtility->disconnect();
    }

    /**
     * Returns the corresponding DN if a given user is provided, otherwise false.
     *
     * @param string $username
     * @param string $password User's password. If null password will not be checked
     * @param string $baseDn
     * @param string $filter
     * @return bool|string
     */
    public function validateUser($username = null, $password = null, $baseDn = null, $filter = null)
    {
        // If user found on ldap server.
        if ($this->ldapUtility->search($baseDn, str_replace('{USERNAME}', $username, $filter), ['dn'])) {

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

                // If enable, SSO authentication without password
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
     * @param string $baseDn
     * @param string $filter
     * @param array $attributes
     * @param bool $firstEntry
     * @param int $limit
     * @param bool $continueLastSearch
     * @return array
     */
    public function search($baseDn = null, $filter = null, $attributes = [], $firstEntry = false, $limit = 0, $continueLastSearch = false)
    {
        $result = [];
        $timeLimit = 0;
        $dereferenceAliases = LDAP_DEREF_NEVER;

        if ($this->ldapUtility->search($baseDn, $filter, $attributes, false, $firstEntry ? 1 : $limit, $timeLimit, $dereferenceAliases, $continueLastSearch)) {
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
    public function isPartialSearchResult()
    {
        return $this->ldapUtility->hasMoreEntries();
    }

    /**
     * Returns the LDAP status.
     *
     * @return array
     */
    public function getStatus()
    {
        return $this->ldapUtility->getStatus();
    }

    /**
     * Returns the last ldap_bind() diagnostic (may be empty).
     *
     * @return string
     */
    public function getLastBindDiagnostic()
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
    public function escapeDnForFilter($dn)
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
     * @return \TYPO3\CMS\Core\Log\Logger
     */
    protected static function getLogger()
    {
        /** @var \TYPO3\CMS\Core\Log\Logger $logger */
        static $logger = null;
        if ($logger === null) {
            $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
        }

        return $logger;
    }

}
