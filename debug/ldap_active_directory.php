<?php

// remove security stop
exit();

# Example inspired from 
# http://www.php.net/manual/en/function.ldap-search.php#93793

$ldap_url = 'domain.local';
$ldap_domain = 'domain.local';

$ds = ldap_connect( $ldap_url );

ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
ldap_set_option($ds, LDAP_OPT_REFERRALS, 0);


$ldap_dn = "CN=Name,CN=Users,DC=domain,DC=local";
$username = "username";
$password = "password"; //must always check that password length > 0 

// now try a real login
$login = ldap_bind( $ds, "$username@$ldap_domain", $password ); 
echo '- Logged In Successfully';

try{
	$attributes = array(
		"displayname", 
		"mail",
		"department",
		"title",
		"physicaldeliveryofficename",
		"manager"
	);
	$filter = "(&(objectCategory=person)(sAMAccountName=$username))";
	
	$result = ldap_search($ds, $ldap_dn, $filter, $attributes);
	$entries = ldap_get_entries($ds, $result);
	
	print_r($entries);
	exit();
	if($entries["count"] > 0){
		//echo print_r($entries[$i],1) . "<br />";
		echo "<b>User Information:</b>\n";
		echo "displayName: " . $entries[0]['displayname'][0] . "\n";
		echo "email: " . $entries[0]['mail'][0] . "\n";
		echo "department: " . $entries[0]['department'][0] . "\n";
		echo "title: " . $entries[0]['title'][0] . "\n";
		echo "office: " . $entries[0]['physicaldeliveryofficename'][0] . "\n";
		//echo "manager: " . $entries[$i]['manager'][0] . "\n";
		$manager_result = ldap_search($ds,
		$entries[0]['manager'][0],
		'(objectCategory=person)',
		array("displayname"));
		
		$manager_entries = ldap_get_entries($ds, $manager_result);
		if($manager_entries["count"] > 0){
			echo "manager: " .  $manager_entries[0]['displayname'][0];
		}
	}
}
catch(Exception $e){
	ldap_unbind($ds);
	return;
}
	
ldap_unbind($ds);
echo '\n\n- Logged Out';
?>