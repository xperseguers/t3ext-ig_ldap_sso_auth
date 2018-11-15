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

use Causal\IgLdapSsoAuth\Exception\UnresolvedPhpDependencyException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class LdapUtility.
 *
 * @package     TYPO3
 * @subpackage  ig_ldap_sso_auth
 * @author      Xavier Perseguers <xavier@causal.ch>
 * @author      Michael Gagnon <mgagnon@infoglobe.ca>
 * @copyright    (c) 2011-2018 Xavier Perseguers <xavier@causal.ch>
 * @copyright    (c) 2007-2010 Michael Gagnon <mgagnon@infoglobe.ca>
 * @see http://www-sop.inria.fr/semir/personnel/Laurent.Mirtain/ldap-livre.html
 *
 * Status | Operation | LDAP description
 * -----------------------------------------------------------------------
 * done     Search      Search in the object directory using a DN and/or a filter
 *          Compare     Compare contents of two objects
 *          Add         Add an entry
 *          Modify      Modify contents of an entry
 *          Delete      Removes an object
 *          Rename      Modify DN of an entry
 * done     Connect     Connect to the server
 * done     Bind        Authenticate with the server
 * done     Disconnect  Disconnect from the server
 *          Abandon     Abandon an operation in progress
 *          Extended    Extended operations (v3)
 */
class LdapUtility
{

    const PAGE_SIZE = 100;

    /**
     * Only used if pagination fails to be initialized
     */
    const MAX_ENTRIES = 500;

    /**
     * LDAP Server charset
     * @var string
     */
    protected $ldapCharacterSet;

    /**
     * Local character set (TYPO3)
     * @var string
     */
    protected $typo3CharacterSet;

    /**
     * LDAP Server Connection ID
     * @var resource
     */
    protected $connection;

    /**
     * LDAP Server Search ID
     * @var resource
     */
    protected $searchResult;

    /**
     * LDAP First Entry ID
     * @var resource
     */
    protected $firstResultEntry;

    /**
     * LDAP server status
     * @var array
     */
    protected $status;

    /**
     * 0 = OpenLDAP, 1 = Active Directory
     * @var int
     */
    protected $serverType;

    /**
     * @var bool
     */
    protected $hasPagination;

    /**
     * @var string
     */
    protected $paginationCookie = null;

    /**
     * Connects to an LDAP server.
     *
     * @param string $host
     * @param int $port
     * @param int $protocol 3 for LDAP v3
     * @param string $characterSet
     * @param int $serverType 0 = OpenLDAP, 1 = Active Directory
     * @param bool $tls
     * @param bool $ssl
     * @return bool true if connection succeeded.
     * @throws UnresolvedPhpDependencyException when LDAP extension for PHP is not available
     */
    public function connect($host = null, $port = null, $protocol = null, $characterSet = null, $serverType = 0, $tls = false, $ssl = false)
    {
        // Valid if php load ldap module.
        if (!extension_loaded('ldap')) {
            throw new UnresolvedPhpDependencyException('Your PHP version seems to lack LDAP support. Please install/activate the extension.', 1409566275);
        }

        // Connect to ldap server.
        $this->status['connect']['host'] = $host;
        $this->status['connect']['port'] = $port;
        $this->serverType = (int)$serverType;

        if ($ssl) {
            $this->status['option']['ssl'] = 'Enable';
            $this->connection = @ldap_connect('ldaps://' . $host . ':' . $port);
        } else {
            $this->connection = @ldap_connect($host, $port);
        }

        if (!$this->connection) {
            // This block should never be reached according to PHP documentation
            // but it was reported to be executed anyway, see https://forge.typo3.org/issues/81005
            $this->connection = false;
            $this->status['connect']['status'] = 'Could not connect to ldap server';
            return false;
        }

        $this->status['connect']['status'] = ldap_error($this->connection);

        // Set configuration
        $this->initializeCharacterSet($characterSet);

        // We only support LDAP v3 from now on
        $protocol = 3;
        @ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $protocol);

        // Active Directory (User@Domain) configuration
        if ($serverType == 1) {
            @ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);
        }

        if ($tls && !$ssl) {
            if (!@ldap_start_tls($this->connection)) {
                $this->status['option']['tls'] = 'Disable';
                $this->status['option']['status'] = ldap_error($this->connection);
                return false;
            }

            $this->status['option']['tls'] = 'Enable';
            $this->status['option']['status'] = ldap_error($this->connection);
        }

        return true;
    }

    /**
     * Returns true if connected to the LDAP server, @see connect().
     *
     * @return bool
     */
    public function isConnected()
    {
        return (bool)$this->connection;
    }

    /**
     * Disconnects from the LDAP server.
     *
     * @return void
     */
    public function disconnect()
    {
        if ($this->connection) {
            @ldap_close($this->connection);
        }
    }

    /**
     * Binds to the LDAP server.
     *
     * @param string $dn
     * @param string $password
     * @return bool true if bind succeeded
     */
    public function bind($dn = null, $password = null)
    {
        // LDAP_OPT_DIAGNOSTIC_MESSAGE gets the extended error output
        // from the ldap_get_option() function
        if (!defined('LDAP_OPT_DIAGNOSTIC_MESSAGE')) {
            define('LDAP_OPT_DIAGNOSTIC_MESSAGE', 0x0032);
        }

        $this->status['bind']['dn'] = $dn;
        $this->status['bind']['password'] = $password ? '••••••••••••' : null;
        $this->status['bind']['diagnostic'] = '';

        if (!(@ldap_bind($this->connection, $dn, $password))) {
            // Could not bind to server
            $this->status['bind']['status'] = ldap_error($this->connection);

            if ($this->serverType === 1) {
                // We need to get the diagnostic message right after the call to ldap_bind(),
                // before any other LDAP operation
                ldap_get_option($this->connection, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extendedError);
                if (!empty($extendedError)) {
                    $this->status['bind']['diagnostic'] = $this->extractDiagnosticMessage($extendedError);
                }
            }

            return false;
        }

        // Bind successful
        $this->status['bind']['status'] = ldap_error($this->connection);
        return true;
    }

    /**
     * Extracts the diagnostic message returned by an Active Directory server
     * when ldap_bind() failed.
     *
     * The format of the diagnostic message is (actual examples from W2003 and W2008):
     * "80090308: LdapErr: DSID-0C090334, comment: AcceptSecurityContext error, data 52e, vece"  (WS 2003)
     * "80090308: LdapErr: DSID-0C090334, comment: AcceptSecurityContext error, data 773, vece"  (WS 2003)
     * "80090308: LdapErr: DSID-0C0903AA, comment: AcceptSecurityContext error, data 52e, v1771" (WS 2008)
     * "80090308: LdapErr: DSID-0C0903AA, comment: AcceptSecurityContext error, data 773, v1771" (WS 2008)
     *
     * @param string $message
     * @return string Diagnostic message, in English
     * @see http://www-01.ibm.com/support/docview.wss?uid=swg21290631
     */
    protected function extractDiagnosticMessage($message)
    {
        $diagnostic = '';
        $codeMessages = [
            '525' => 'The specified account does not exist.',
            '52e' => 'Logon failure: unknown user name or bad password.',
            '530' => 'Logon failure: account logon time restriction violation.',
            '531' => 'Logon failure: user not allowed to log on to this computer.',
            '532' => 'Logon failure: the specified account password has expired.',
            '533' => 'Logon failure: account currently disabled.',
            '534' => 'The user has not been granted the requested logon type at this machine.',
            '701' => 'The user\'s account has expired.',
            '773' => 'The user\'s password must be changed before logging on the first time.',
            '775' => 'The referenced account is currently locked out and may not be logged on to.',
        ];

        $parts = explode(',', $message);
        if (preg_match('/data ([0-9a-f]+)/i', trim($parts[2]), $matches)) {
            $code = $matches[1];
            $diagnostic = isset($codeMessages[$code])
                ? sprintf('%s (%s)', $codeMessages[$code], $code)
                : sprintf('Unknown reason. (%s)', $code);
        }

        return $diagnostic;
    }

    /**
     * Performs a search on the LDAP server.
     *
     * @param string $baseDn
     * @param string $filter
     * @param array $attributes
     * @param bool $attributesOnly
     * @param int $sizeLimit
     * @param int $timeLimit
     * @param int $dereferenceAliases
     * @param bool $continueLastSearch
     * @return bool
     * @see http://ca3.php.net/manual/fr/function.ldap-search.php
     */
    public function search($baseDn = null, $filter = null, $attributes = [], $attributesOnly = false, $sizeLimit = 0, $timeLimit = 0, $dereferenceAliases = LDAP_DEREF_NEVER, $continueLastSearch = false)
    {
        if (!$baseDn) {
            $this->status['search']['basedn'] = 'No valid base DN';
            return false;
        }
        if (!$filter) {
            $this->status['search']['filter'] = 'No valid filter';
            return false;
        }

        if ($this->connection) {
            if (!$continueLastSearch) {
                // Reset the pagination cookie
                $this->paginationCookie = null;
            }

            // It was reported that ldap_control_paged_result() may not be available;
            // we thus check for existence before proceeding
            $this->hasPagination = function_exists('ldap_control_paged_result') &&
                @ldap_control_paged_result(
                    $this->connection,
                    static::PAGE_SIZE,
                    false,  // Pagination is not critical for search to work anyway
                    $this->paginationCookie
                );
            if (!($this->searchResult = @ldap_search($this->connection, $baseDn, $filter, $attributes, $attributesOnly, $sizeLimit, $timeLimit, $dereferenceAliases))) {
                // Search failed.
                $this->status['search']['status'] = ldap_error($this->connection);
                return false;
            }

            $this->firstResultEntry = @ldap_first_entry($this->connection, $this->searchResult);
            $this->status['search']['status'] = ldap_error($this->connection);
            return true;
        }

        // No connection identifier (cid).
        $this->status['search']['status'] = ldap_error($this->connection);
        return false;
    }

    /**
     * Returns up to MAX_ENTRIES (500) LDAP entries corresponding to a filter prepared by a call to
     * @see search().
     *
     * @return array
     * @throws \RuntimeException
     */
    public function getEntries()
    {
        $entries = ['count' => 0];
        $entry = @ldap_first_entry($this->connection, $this->searchResult);

        if (!$entry) {
            return $entries;
        }

        do {
            $attributes = ldap_get_attributes($this->connection, $entry);
            $attributes['dn'] = ldap_get_dn($this->connection, $entry);

            // Hook for processing the attributes
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['attributesProcessing'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ig_ldap_sso_auth']['attributesProcessing'] as $className) {
                    /** @var \Causal\IgLdapSsoAuth\Utility\AttributesProcessorInterface $postProcessor */
                    $postProcessor = GeneralUtility::makeInstance($className);
                    if ($postProcessor instanceof \Causal\IgLdapSsoAuth\Utility\AttributesProcessorInterface) {
                        $postProcessor->processAttributes($this->connection, $entry, $attributes);
                    } else {
                        throw new \RuntimeException('Processor ' . get_class($postProcessor) . ' must implement the \\Causal\\IgLdapSsoAuth\\Utility\\AttributesProcessorInterface interface', 1430307683);
                    }
                }
            }

            $tempEntry = [];
            foreach ($attributes as $key => $value) {
                $tempEntry[strtolower($key)] = $value;
            }

            $entries[] = $tempEntry;
            $entries['count']++;

            // Should never happen unless pagination is not supported, for some odd reason
            if ($entries['count'] == static::MAX_ENTRIES) {
                break;
            }
        } while ($entry = @ldap_next_entry($this->connection, $entry));

        if ($this->hasPagination) {
            ldap_control_paged_result_response($this->connection, $this->searchResult, $this->paginationCookie);
        }

        $this->status['get_entries']['status'] = ldap_error($this->connection);

        return $entries['count'] > 0
            // Convert LDAP result character set -> local character set
            ? $this->convertCharacterSetForArray($entries, $this->ldapCharacterSet, $this->typo3CharacterSet)
            : [];
    }

    /**
     * Returns true if last call to @see getEntries()
     * returned a partial result set.
     *
     * @return bool
     */
    public function hasMoreEntries()
    {
        return !empty($this->paginationCookie);
    }

    /**
     * Returns the first entry.
     *
     * @return array
     */
    public function getFirstEntry()
    {
        $this->status['get_first_entry']['status'] = ldap_error($this->connection);
        $attributes = @ldap_get_attributes($this->connection, $this->firstResultEntry);
        $tempEntry = [];
        foreach ($attributes as $key => $value) {
            $tempEntry[strtolower($key)] = $value;
        }
        $entry = $this->convertCharacterSetForArray($tempEntry, $this->ldapCharacterSet, $this->typo3CharacterSet);
        return $entry;
    }

    /**
     * Returns the DN.
     *
     * @return string
     */
    public function getDn()
    {
        return @ldap_get_dn($this->connection, $this->firstResultEntry);
    }

    /**
     * Returns the attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return @ldap_get_attributes($this->connection, $this->firstResultEntry);
    }

    /**
     * Returns the LDAP status.
     *
     * @return array
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Initializes the character set.
     *
     * @param string $characterSet
     * @return void
     */
    protected function initializeCharacterSet($characterSet = null)
    {
        /** @var \TYPO3\CMS\Core\Charset\CharsetConverter $csObj */
        $csObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);

        // LDAP server charset
        $this->ldapCharacterSet = $csObj->parse_charset($characterSet ? $characterSet : 'utf-8');

        // TYPO3 charset
        $this->typo3CharacterSet = 'utf-8';
    }

    /**
     * Converts entries from one character set to another.
     *
     * @param array|mixed $arr
     * @param string $fromCharacterSet Source character set
     * @param string $toCharacterSet Target character set
     * @return array|mixed
     */
    protected function convertCharacterSetForArray($arr, $fromCharacterSet, $toCharacterSet)
    {
        /** @var \TYPO3\CMS\Core\Charset\CharsetConverter $csObj */
        static $csObj = null;

        if (!is_array($arr)) {
            return $arr;
        }

        if ($csObj === null) {
            $csObj = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Charset\CharsetConverter::class);
        }

        foreach ($arr as $k => $val) {
            if (is_array($val)) {
                $arr[$k] = $this->convertCharacterSetForArray($val, $fromCharacterSet, $toCharacterSet);
            } else {
                $arr[$k] = $csObj->conv($val, $fromCharacterSet, $toCharacterSet);
            }
        }

        return $arr;
    }

}
