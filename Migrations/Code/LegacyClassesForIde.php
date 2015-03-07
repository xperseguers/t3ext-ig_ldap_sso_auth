<?php
die('Access denied');

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_auth extends \Causal\IgLdapSsoAuth\Library\Authentication {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_config extends \Causal\IgLdapSsoAuth\Library\Configuration {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_emconfhelper extends \Causal\IgLdapSsoAuth\Em\ConfigurationHelper {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_ldap {

	/**
	 * Initializes a connection to the LDAP server.
	 *
	 * @param array $config
	 * @return bool
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->connect() instead
	 */
	static public function connect(array $config = array()) {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->connect($config);
	}

	/**
	 * Disconnects the LDAP server.
	 *
	 * @return void
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->disconnect() instead
	 */
	static public function disconnect() {
		\Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->disconnect();
	}

	/**
	 * Returns the corresponding DN if a given user is provided, otherwise FALSE.
	 *
	 * @param string $username
	 * @param string $password User's password. If NULL password will not be checked
	 * @param string $basedn
	 * @param string $filter
	 * @return bool|string
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->validateUser() instead
	 */
	static public function valid_user($username = NULL, $password = NULL, $basedn = NULL, $filter = NULL) {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->validateUser($username, $password, $basedn, $filter);
	}

	/**
	 * Searches LDAP entries satisfying some filter.
	 *
	 * @param string $basedn
	 * @param string $filter
	 * @param array $attributes
	 * @param bool $first_entry
	 * @param int $limit
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->search() instead
	 */
	static public function search($basedn = NULL, $filter = NULL, $attributes = array(), $first_entry = FALSE, $limit = 0) {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->search($basedn, $filter, $attributes, $first_entry, $limit);
	}

	/**
	 * Returns TRUE if last call to @see search() returned a partial result set.
	 * You should then call @see searchNext().
	 *
	 * @return bool
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->isPartialSearchResult() instead
	 */
	static public function isPartialSearchResult() {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->isPartialSearchResult();
	}

	/**
	 * Returns the next block of entries satisfying a previous call to @see search().
	 *
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->searchNext() instead
	 */
	static public function searchNext() {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->searchNext();
	}

	/**
	 * Returns the LDAP status.
	 *
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->getStatus() instead
	 */
	static public function get_status() {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->getStatus();
	}

	/**
	 * Returns the last ldap_bind() diagnostic (may be empty).
	 *
	 * @return string
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->getLastBindDiagnostic() instead
	 */
	static public function getLastBindDiagnostic() {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->getLastBindDiagnostic();
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
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->escapeDnForFilter() instead
	 */
	static public function escapeDnForFilter($dn) {
		return \Causal\IgLdapSsoAuth\Library\Ldap::getInstance()->escapeDnForFilter($dn);
	}

}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_ldap_group extends \Causal\IgLdapSsoAuth\Library\LdapGroup {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_sv1 extends \Causal\IgLdapSsoAuth\Service\AuthenticationService {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_typo3_group extends \Causal\IgLdapSsoAuth\Domain\Repository\Typo3GroupRepository {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_typo3_user extends \Causal\IgLdapSsoAuth\Domain\Repository\Typo3UserRepository {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class tx_igldapssoauth_utility_Ldap {

	/**
	 * @var \Causal\IgLdapSsoAuth\Utility\LdapUtility
	 */
	static private $instance = NULL;

	/**
	 * Returns an instance of the parent class (singleton pattern).
	 *
	 * @return \Causal\IgLdapSsoAuth\Utility\LdapUtility
	 */
	static private function getInstance() {
		if (static::$instance === NULL) {
			static::$instance = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Causal\\IgLdapSsoAuth\\Utility\\LdapUtility');
		}
	}

	/**
	 * Connects to an LDAP server.
	 *
	 * @param string $host
	 * @param integer $port
	 * @param integer $protocol Either 2 or 3
	 * @param string $charset
	 * @param integer $serverType 0 = OpenLDAP, 1 = Active Directory / Novell eDirectory
	 * @param bool $tls
	 * @return bool TRUE if connection succeeded.
	 * @throws \Exception when LDAP extension for PHP is not available
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::connect() instead
	 */
	static public function connect($host = NULL, $port = NULL, $protocol = NULL, $charset = NULL, $serverType = 0, $tls = FALSE) {
		return static::getInstance()->connect($host, $port, $protocol, $charset, $serverType, $tls);
	}

	/**
	 * Returns TRUE if connected to the LDAP server, @see connect().
	 *
	 * @return bool
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::isConnected() instead
	 */
	static public function is_connect() {
		return static::getInstance()->isConnected();
	}

	/**
	 * Disconnects from the LDAP server.
	 *
	 * @return void
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::disconnect() instead
	 */
	static public function disconnect() {
		static::getInstance()->disconnect();
	}

	/**
	 * Binds to the LDAP server.
	 *
	 * @param string $dn
	 * @param string $password
	 * @return bool TRUE if bind succeeded
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::bind() instead
	 */
	static public function bind($dn = NULL, $password = NULL) {
		return static::getInstance()->bind($dn, $password);
	}

	/**
	 * Performs a search on the LDAP server.
	 *
	 * @param string $basedn
	 * @param string $filter
	 * @param array $attributes
	 * @param int $attributes_only
	 * @param int $size_limit
	 * @param int $time_limit
	 * @param int $deref
	 * @return bool
	 * @see http://ca3.php.net/manual/fr/function.ldap-search.php
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::search() instead
	 */
	static public function search($basedn = NULL, $filter = NULL, $attributes = array(), $attributes_only = 0, $size_limit = 0, $time_limit = 0, $deref = LDAP_DEREF_NEVER) {
		return static::getInstance()->search($basedn, $filter, $attributes, $attributes_only == 1, $size_limit, $time_limit, $deref);
	}

	/**
	 * Returns up to 1000 LDAP entries corresponding to a filter prepared by a call to
	 * @see search().
	 *
	 * @param resource $previousEntry Used to get the remaining entries after receiving a partial result set
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::getEntries() instead
	 */
	static public function get_entries($previousEntry = NULL) {
		return static::getInstance()->getEntries($previousEntry);
	}

	/**
	 * Returns next LDAP entries corresponding to a filter prepared by a call to
	 * @see search().
	 *
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::getNextEntries() instead
	 */
	static public function get_next_entries() {
		return static::getInstance()->getNextEntries();
	}

	/**
	 * Returns TRUE if last call to @see get_entries()
	 * returned a partial result set.
	 *
	 * @return bool
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::hasMoreEntries() instead
	 */
	static public function has_more_entries() {
		return static::getInstance()->hasMoreEntries();
	}

	/**
	 * Returns the first entry.
	 *
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::getFirstEntry() instead
	 */
	static public function get_first_entry() {
		return static::getInstance()->getFirstEntry();
	}

	/**
	 * Returns the DN.
	 *
	 * @return string
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::getDn() instead
	 */
	static public function get_dn() {
		return static::getInstance()->getDn();
	}

	/**
	 * Returns the attributes.
	 *
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::getAttributes() instead
	 */
	static public function get_attributes() {
		return static::getInstance()->getAttributes();
	}

	/**
	 * Returns the LDAP status.
	 *
	 * @return array
	 * @deprecated since 3.0 will be removed in 3.2, use \Causal\IgLdapSsoAuth\Utility\LdapUtility::getStatus() instead
	 */
	static public function get_status() {
		return static::getInstance()->getStatus();
	}

}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Domain_Repository_ConfigurationRepository extends \Causal\IgLdapSsoAuth\Domain\Repository\ConfigurationRepository {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Hooks_DataHandler extends \Causal\IgLdapSsoAuth\Hooks\DataHandler {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Hooks_DatabaseRecordListIconUtility extends \Causal\IgLdapSsoAuth\Hooks\DatabaseRecordListIconUtility {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Hooks_SetupModuleController extends \Causal\IgLdapSsoAuth\Hooks\SetupModuleController {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Task_ImportUsers extends \Causal\IgLdapSsoAuth\Task\ImportUsers {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Task_ImportUsersAdditionalFields extends \Causal\IgLdapSsoAuth\Task\ImportUsersAdditionalFields {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Tca_Form_Suggest extends \Causal\IgLdapSsoAuth\Tca\Form\SuggestWizard {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Utility_Debug extends \Causal\IgLdapSsoAuth\Utility\DebugUtility {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
interface Tx_IgLdapSsoAuth_Utility_ExtraDataProcessorInterface extends \Causal\IgLdapSsoAuth\Utility\ExtraDataProcessorInterface {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Utility_Notification extends \Causal\IgLdapSsoAuth\Utility\NotificationUtility {}

/**
 * @deprecated since 3.0 will be removed in 3.2
 */
class Tx_IgLdapSsoAuth_Utility_UserImport extends \Causal\IgLdapSsoAuth\Utility\UserImportUtility {}