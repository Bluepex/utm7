#!/usr/local/bin/php -f
<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Marcos Claudiano <marcos.claudiano@bluepex.com>, 2024
 *
 * ====================================================================
 *
 */

require_once('config.inc');
require_once('auth.inc');
require_once('pkg-utils.inc');
require_once('bp-utils.inc');

if (file_exists('/usr/local/pkg/nf_config.inc')) {
	require_once("nf_config.inc");
}

import_entraid_users();
import_entraid_groups();

function scape_func($string) {
	$special_characters_filter = array(
	    '\\',
	    '/',
	    '"',
	    "'",
	    '?',
	    '<',
	    '>',
	    '|',
	    ':',
	    '*',
	    '.',
	    ',',
	    ';',
	    '!',
	    '@',
	    '#',
	    '$',
	    '%',
	    '^',
	    '&',
	    '(',
	    ')',
	    '-',
	    '_',
	    '=',
	    '+',
	    '{',
	    '}',
	    '[',
	    ']',
	    '`',
	    '~'
	);

	foreach ($special_characters_filter as $character_filter) {
		$string = str_replace($character_filter, '\\' . $character_filter, $string);
	}

	return $string;
}

function bp_entraid_import_users() {
	global $config;

	$user = $config['system']['webfilter']['instance']['config'][0]['server']['entra_id_username'];
	$pass = scape_func($config['system']['webfilter']['instance']['config'][0]['server']['entra_id_pass']);

	exec('/usr/local/bin/python3.8 /usr/local/bin/vpn_entraid_params.py users', $ret, $retval);

	$output = implode("", $ret);

	$info = json_decode($output, true);

	foreach ($info as $id => $users_data) {
		$user = $users_data['userPrincipalName'];
		$username = $users_data['displayName'];
		$objectguid = $users_data['id'];

		$userlist[] = array(
		    "name" => $user,
		    "descr" => $username,
		    "objectguid" => $objectguid,
		);
	}

	return $userlist;
}

function bp_entraid_import_groups() {
	global $config;

	$user = $config['system']['webfilter']['instance']['config'][0]['server']['entra_id_username'];
	$pass = scape_func($config['system']['webfilter']['instance']['config'][0]['server']['entra_id_pass']);

	exec('/usr/local/bin/python3.8 /usr/local/bin/vpn_entraid_params.py users_groups', $ret, $retval);

	$output = implode("", $ret);

	$info = json_decode($output, true);

	foreach ($info as $id => $groups_data) {
		$group = $groups_data['displayName'];
		$groupname = $groups_data['displayName'];
		$groupdescr = $groups_data['description'];
		$objectguid = $groups_data['objectguid'];
		$groupmembers = $groups_data['members'];

		$grouplist[] = array(
		    "name" => $groupname,
		    "descr" => $groupdescr,
		    "members" => $groupmembers,
		    "objectguid" => $objectguid,
		);
	}

	return $grouplist;
}

function check_my_entraid_process() {
	if (isvalidpid(MYPIDFILE)) {
		echo "Script 'entraid_sync.php' is already running!\n";
		exit;
	}

	file_put_contents(MYPIDFILE, getmypid());
}

function import_entraid_users() {
	global $config, $authcfg;

	$userlist = bp_entraid_import_users();
	$objectguid = array();

	if (!empty($userlist) &&
	    is_array($userlist)) {
		foreach ($userlist as $user) {
			$objectguid[] = $user['objectguid'];

			if (check_user_group_by_objectguid("user", $user)) {
				continue;
			}

			$user['scope'] = 'user';
			$user['uid'] = $config['system']['nextuid']++;
			$user['objectguid'] = $user['objectguid'];
			$user['imported'] = true;

			$config['system']['user'][] = $user;
		}

		removeUsersGroupsByObjectguid("user", $objectguid);
		groups_users_sort("user");
		write_config("BluePexUTM: EntraID Sync: Users imported successfully!");
		unset($userlist, $objectguid, $user);
	}
}

function import_entraid_groups() {
	global $config;

	$grouplist = bp_entraid_import_groups();
	$objectguid = array();

	if (!empty($grouplist) &&
	    is_array($grouplist)) {
		foreach ($grouplist as $group) {
			$objectguid[] = $group['objectguid'];

			if (check_user_group_by_objectguid("group", $group)) {
				continue;
			}

			$members = array();

			if (is_array($group['members']) &&
			    count($group['members']) > 0) {
				foreach ($group['members'] as $member) {
					$userent = getUserEntry($member);
					$members[] = $userent['id'];
				}

				unset($group['members']);
			}

			$group['gid'] = $config['system']['nextgid']++;
			$group['objectguid'] = $group['objectguid'];
			$group['imported'] = true;

			if (!empty($members)) {
				$group['member'] = $members;
			}

			$config['system']['group'][] = $group;
		}

		removeUsersGroupsByObjectguid("group", $objectguid);
		groups_users_sort("group");
		write_config("BluePexUTM: EntraID Sync: Groups imported successfully!");
		unset($grouplist, $objectguid, $group, $members);
	}
}

function check_user_group_by_objectguid($sys_usr_grp, $user_group) {
	global $config;

	$sys_user_group = &$config['system'][$sys_usr_grp];
	$total = count($sys_user_group);

	for ($i=0; $i < $total; $i++) {
		if ($sys_user_group[$i]['objectguid'] == $user_group['objectguid']) {
			if ($user_group['name'] != $sys_user_group[$i]['name']) {
				$sys_user_group[$i]['name'] = $user_group['name'];
				$sys_user_group[$i]['descr'] = $user_group['descr'];
			}

			if ($sys_usr_grp == "group") {
				$members = array();

				foreach ($user_group['members'] as $member) {
					$userent = getUserEntry($member);
					$members[] = $userent['uid'];
				}

				$sys_user_group[$i]['member'] = $members;
			}

			unset($sys_user_group, $user_group, $members);

			return true;
		}
	}
}

function removeUsersGroupsByObjectguid($key, $objectguid) {
	global $config;

	foreach ($config['system'][$key] as $idx => $info) {
		if (isset($info['imported']) &&
		    !in_array($info['objectguid'], $objectguid)) {
			unset($config['system'][$key][$idx]);
		}
	}
}

function groups_users_sort($group_user) {
	global $config;

	if (!is_array($config['system'][$group_user])) {
		return;
	}

	usort($config['system'][$group_user], cmp_by_name);
}

function cmp_by_name($a, $b) {
	return strcasecmp($a['name'], $b['name']);
}

?>
