<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  BluePex <desenvolvimento@bluepex.com>
 *
 * ====================================================================
 *
 */

function bp_ldap_import_users($authcfg, $searchfilter = NULL) {
	global $config;

	if (!function_exists("ldap_connect") || !isset($authcfg['type']) || 
	    ($authcfg['type'] == "Local Auth")) {
		log_error("ERROR! ldap_import_users() the currently configured authentication server is not an LDAP server.");
		return;
	}

	//file_put_contents("/etc/teste_ldap.txt", "1234567");

	$ldapproto          = strstr($authcfg['ldap_urltype'], "Standard") ? "ldap" : "ldaps";
	$ldapserver         = empty($authcfg['ldap_port']) ? "{$ldapproto}://{$authcfg['host']}" : "{$ldapproto}://{$authcfg['host']}:{$authcfg['ldap_port']}";
	$ldapbasedn         = $authcfg['ldap_basedn'];
	$ldapbindun         = $authcfg['ldap_binddn'];
	$ldapbindpw         = $authcfg['ldap_bindpw'];
	$ldapauthcont       = $authcfg['ldap_authcn'];
	$ldapnameattribute  = strtolower($authcfg['ldap_attr_user']);
	$ldapgroupattribute = $config['system']['ldapimport']['ldapgroupmemberattr'];
	$ldapfilter         = empty($searchfilter) ? "(&(objectCategory=person)(objectClass=user))" : $searchfilter;
	$ldapver            = $authcfg['ldap_protver'];
	$ldapanon           = (empty($ldapbindun) || empty($ldapbindpw)) ? true : false;
	$ldapname           = $authcfg['name'];
	$ldapscope          = $authcfg['ldap_scope'];

	// Convert attribute to lowercase. php ldap arrays put everything in lowercase
	$ldapgroupattribute = strtolower($ldapgroupattribute);

	// Setup CA environment if needed.
	ldap_setup_caenv($ldapname,$authcfg);

	// connect and see if server is up
	if (!($ldap = ldap_connect($ldapserver))) {
		log_error(sprintf(gettext("ERROR! ldap_import_users() Could not connect to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		return false;
	}

	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, $ldapver);

	file_put_contents('/etc/conf_ldap.txt', "{$ldapproto} --- {$ldapserver} --- {$ldapscope}", FILE_APPEND);


	// bind as user that has rights to read group attributes
	if ($ldapanon == true) {
		if (!($res = @ldap_bind($ldap))) {
			log_error(sprintf(gettext("ERROR! ldap_import_users() could not bind anonymously to server %s."), $ldapname));
			@ldap_close($ldap);
			return false;
		}
	} else if (!($res = @ldap_bind($ldap, $ldapbindun, $ldapbindpw))) {
		log_error(sprintf(gettext("ldap_import_users() could not bind to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		@ldap_close($ldap);
		return false;
	}

	/*
	 * get groups from DN found
	 * use ldap_read instead of search so we don't have to do a bunch of extra work
	 * since we know the DN is in $_SESSION['ldapdn']
	 */
	$ldapfunc = ($ldapscope == "one") ? "ldap_list" : "ldap_search";
	$search   = @$ldapfunc($ldap, $ldapbasedn, $ldapfilter, array($ldapnameattribute, "givenname", "sn", "objectGUID"));

	if (!$search) {
		log_error(sprintf(gettext("ldap_import_users() could not search to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		@ldap_close($ldap);
		return false;
	}

	if (!($info = @ldap_get_entries($ldap, $search))) {
		log_error(sprintf(gettext("ldap_import_users() could not get entries to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		@ldap_close($ldap);
		return false;
	}

	$userlist = array();

	for ($i=0; $i<$info['count']; $i++) {
		$user       = $info[$i];	
		$username   = $user[$ldapnameattribute][0];
		$objectguid = bin2hex($user['objectguid'][0]);
		if (empty($user['givenname'][0])) {
			$userdescr = $username;
		} else {
			$userdescr = preg_replace('/\s+/',' ',implode(" ", array($user['givenname'][0],$user['sn'][0])));
		}

		//Ignore import admin users to avoid dashboard crash
		if ((substr(strtolower($username), 0, 5) == "admin")) {
			continue;
		}

		$user       = str_replace(" ",".",strtolower($info[$i]));

		//$user       = utf8_encode($info[$i]);

		$username = str_replace(" ",".",strtolower($username));

		//$objectguid = bin2hex($username));

		//$username = utf8_encode($username);

		$userlist[] = array(
			"name" => $username,
			"descr" => $userdescr,
			"objectguid" => $objectguid,
		);
		//file_put_contents('/etc/teste_ldap.txt', "{$user} --- {$username} --- {$userdescr} --- {$objectguid}", FILE_APPEND);
	}

	// Close LDAP connection
	@ldap_unbind($ldap);
	unset($info);

	return $userlist;
}

function bp_ldap_import_groups($authcfg, $searchfilter = NULL) {
	global $config;

	if (!function_exists("ldap_connect") || !isset($authcfg['type']) || 
	    ($authcfg['type'] == "Local Auth")) {
		return;
	}

	$ldapproto           = strstr($authcfg['ldap_urltype'], "Standard") ? "ldap" : "ldaps";
	$ldapserver          = empty($authcfg['ldap_port']) ? "{$ldapproto}://{$authcfg['host']}" : "{$ldapproto}://{$authcfg['host']}:{$authcfg['ldap_port']}";
	$ldapbasedn          = $authcfg['ldap_basedn'];
	$ldapbindun          = $authcfg['ldap_binddn'];
	$ldapbindpw          = $authcfg['ldap_bindpw'];
	$ldapauthcont        = $authcfg['ldap_authcn'];
	$ldapnameattribute   = strtolower($authcfg['ldap_attr_user']);
	$ldapgroupattribute  = strtolower($authcfg['ldap_attr_group']);
	$ldapmemberattribute = $config['system']['ldapimport']['ldapgroupmemberattr'];
	$ldapfilter          = empty($searchfilter) ? "(objectClass=group)" : $searchfilter;
	$ldapver             = $authcfg['ldap_protver'];
	$ldapanon            = (empty($ldapbindun) || empty($ldapbindpw)) ? true : false;
	$ldapname            = $authcfg['name'];
	$ldapscope           = $authcfg['ldap_scope'];

	// Convert attribute to lowercase. php ldap arrays put everything in lowercase
	$ldapgroupattribute  = strtolower($ldapgroupattribute);
	$ldapmemberattribute = strtolower($ldapmemberattribute);

	// Setup CA environment if needed.
	ldap_setup_caenv($ldapname,$authcfg);

	// connect and see if server is up
	if (!($ldap = ldap_connect($ldapserver))) {
		log_error(sprintf(gettext("ERROR! bp_ldap_import_groups() Could not connect to server %s."), $ldapname));
		return false;
	}

	ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
	ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, (int)$ldapver);

	// bind as user that has rights to read group attributes
	if ($ldapanon == true) {
		if (!($res = @ldap_bind($ldap))) {
			log_error(sprintf(gettext("ERROR! bp_ldap_import_groups() could not bind anonymously to server %s."), $ldapname));
			@ldap_close($ldap);
			return false;
		}
	} elseif (!($res = @ldap_bind($ldap, $ldapbindun, $ldapbindpw))) {
		log_error(sprintf(gettext("bp_ldap_import_groups() could not bind to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		@ldap_close($ldap);
		return false;
	}

	/*
	 * get groups from DN found
	 * use ldap_read instead of search so we don't have to do a bunch of extra work
	 * since we know the DN is in $_SESSION['ldapdn']
	 */
	$ldapfunc  = ($ldapscope == "one") ? "ldap_list" : "ldap_search";
	$search    = @$ldapfunc($ldap, $ldapbasedn, $ldapfilter, array($ldapgroupattribute, $ldapmemberattribute, $ldapmemberattribute, "objectGUID"));

	if (!$search) {
		log_error(sprintf(gettext("bp_ldap_import_groups() could not search to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		@ldap_close($ldap);
		return false;
	}

	if (!($info = @ldap_get_entries($ldap, $search))) {
		log_error(sprintf(gettext("bp_ldap_import_groups() could not get entries to server %s Error: %s."), $ldapname, ldap_error($ldap)));
		@ldap_close($ldap);
		return false;
	}
	$grouplist = array();

	for ($i=0; $i<$info['count']; $i++) {
		$group        = $info[$i];
		$groupname    = $group[$ldapgroupattribute][0];
		$groupdescr   = empty($group["name"][0]) ? $groupname : $group["name"][0];
		$objectguid   = bin2hex($group['objectguid'][0]);
		$groupmembers = array();

		// Search for group members
		if (is_array($group[$ldapmemberattribute])) {

			for ($j=0; $j < $group[$ldapmemberattribute]['count']; $j++) {
				$member = $group[$ldapmemberattribute][$j];
				// group members attribute should contains the user DN in active directory
				if (stristr($member, "CN=") !== false) {

					$userfilter = $config['system']['ldapusersyncfilter'];
					if (empty($userfilter)) {
						$userfilter = "(objectclass=user)";
					}

					// We are looking for a specific user DN, so we define this DN as the ldap base
					$membersearch = @$ldapfunc($ldap, $member, $userfilter, array($ldapnameattribute));
					$memberinfo   = @ldap_get_entries($ldap, $membersearch);

					// Get the user name
					if ($memberinfo['count'] > 0) {
						//$groupmembers[] = $memberinfo[0][$ldapnameattribute][0];
						$groupmembers[] = str_replace(" ",".",strtolower($memberinfo[0][$ldapnameattribute][0]));
					}
				}
			}
		}

		$grouplist[] = array(
				"name" => $groupname,
				"descr" => $groupdescr,
				"members" => $groupmembers,
				"objectguid" => $objectguid,
		);
	}

	// Close LDAP connection
	@ldap_unbind($ldap);
	unset($info);

	return $grouplist;
}

?>
