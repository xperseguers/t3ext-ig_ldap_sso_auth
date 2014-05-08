<?php

// remove security stop
exit();

$server = "ldap://...";

//using ldap bind anonymously // connect to ldap server
$ds = ldap_connect($server)
	or die("Could not connect to LDAP server.");

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);

if ($ds) {
	// binding anonymously
	$ldapbind = ldap_bind($ds);

	if ($ldapbind) {
		echo "LDAP bind anonymous successful...";
	}
	else {
		echo "LDAP bind anonymous failed...";
	}
}

$basedn = '';
$username = 'username'; // Username to be search in the LDAP directory
$password = '';

// search in the directory
$r = ldap_search($ds, $basedn, 'uid=' . $username);

if ($r) {
	$result = ldap_get_entries($ds, $r);
	if ($result[0]) {
		if (ldap_bind($ds, $result[0]['dn'], $password)) {
			#print_r('... successfully identified');
			#print_r($result);
			#print_r($result[0]);
		}
	}
}

?>