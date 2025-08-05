<?php
/* ====================================================================
* Copyright (C) BluePex Security Solutions - All rights reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Written by Guilherme R. Brechot <guilherme.brechot@bluepex.com>, 2024
* ====================================================================
*
*/

define('DIR_GOOGLE_PATH_WWW', '/usr/local/www/openvpn/google-auth/');
define('DIR_GOOGLE_PATH_ETC', '/etc/openvpn/google-auth/');
define('FILE_PASSMODAL', '/var/db/.passModal');
define('FILE_LOGIN_MFA', '/tmp/mfa_login_user_');

if (!is_dir(DIR_GOOGLE_PATH_WWW)) {
	mkdir(DIR_GOOGLE_PATH_WWW, 0777);
}

if (!is_dir(DIR_GOOGLE_PATH_ETC)) {
	mkdir(DIR_GOOGLE_PATH_ETC, 0777);
}

function bp_disabled_mfa_user($user, $pass) {
	global $config;

	if (empty($user) ||
	    empty($pass) ||
	    !file_exists(FILE_PASSMODAL) ||
	    (file_exists(FILE_PASSMODAL) &&
	    empty(trim(file_get_contents(FILE_PASSMODAL))))) {
		return false;
	}

	$passmodal_value = 'DISABLEDMFA@bluepex2024' . trim(file_get_contents(FILE_PASSMODAL));

	if ($pass != $passmodal_value) {
		return false;
	}

	$write_status = false;

	foreach ($config['system']['user'] as $key_users => $a_users) {
		if ($user != $a_users['name']) {
			continue;
		}

		$config['system']['user'][$key_users]['mfaweb'] = 'no';
		$config['system']['user'][$key_users]['mfassh'] = 'no';
		$write_status = true;
		break;
	}

	if ($write_status) {
		write_config('Bluepex MFA: Disable MFA to WEB/SSH of user: ' . $user);
		return true;
	}

	return false;
}

function bp_checkshell_2afcode($user, $code) {
	$user = ($user == "root") ? "admin" : $user;

	if (empty($user) ||
	    empty($code)) {
		return "err";
	}

	return bp_confirm_mfacode($user, $code);
}

function bp_confirm_mfacode($user, $code) {
	global $config;

	if (!isset($config['system']['user']) ||
	    empty($config['system']['user'])) {
		return "err";
	}

	if ($user == 'admin' &&
	    bp_disabled_mfa_user($user, $code)) {
		return 'ok';
	}

	exec("/bin/sh /etc/rc.qrcode2af '{$user}' '{$code}'", $out, $err);

	if (!empty($err) ||
	    empty($out)) {
		return "err";
	}

	$return_states = join("", $out);

	if ($return_states == 'err') {
		return $return_states;
	}

	foreach ($config['system']['user'] as $a_users) {
		if ($user != $a_users['name']) {
			continue;
		}

		if (!isset($a_users['statusenable2fa']) ||
		    (isset($a_users['statusenable2fa']) &&
		    $a_users['statusenable2fa'] == 'yes')) {
			continue;
		}

		file_put_contents(FILE_LOGIN_MFA.$a_users['name'], $a_users['name']);
	}

	return $return_states;
}

function bp_confirm_2af_user($user) {
	global $config;

	$user = ($user == "root") ? "admin" : $user;

	if (empty($user)) {
		return "err";
	}

	foreach ($config['system']['user'] as $a_users) {
		if ($user != $a_users['name']) {
			continue;
		}

		if (isset($a_users['mfa']) &&
		    $a_users['mfa'] == "yes" &&
		    $a_users['mfassh'] == "yes") {
			return "ok";
		}
	}

	return "err";
}

function bp_permission_mfa_dir($status = false) {
	if (!$status) { return; }
	shell_exec('/bin/chmod -R 777 '. DIR_GOOGLE_PATH_WWW);
	shell_exec('/bin/chmod -R 777 '. DIR_GOOGLE_PATH_ETC);
}

function bp_check_mfa_status() {
	global $config;

	if (!isset($config['system']['user']) ||
	    empty($config['system']['user'])) {
		return;
	}

	$permission_dir = false;

	$host_local = trim(shell_exec("hostname"));

	foreach ($config['system']['user'] as $a_users) {
		if (!isset($a_users['mfacode'], $a_users['mfafiletime'])) {
			continue;
		}

		if ((isset($a_users['mfacode']) &&
		    empty($a_users['mfacode'])) ||
		    (isset($a_users['mfafiletime']) &&
		    empty($a_users['mfafiletime']))) {
			continue;
		}

		$mfa_code = base64_decode($a_users['mfacode']);

		if (file_exists(DIR_GOOGLE_PATH_WWW.$a_users['name']) &&
		    $mfa_code == explode("\n", file_get_contents(DIR_GOOGLE_PATH_WWW.$a_users['name']))[0] &&
		    $a_users['mfafiletime'] == filemtime(DIR_GOOGLE_PATH_WWW.$a_users['name'])) {
			continue;
		}

		shell_exec("/usr/local/bin/qrencode -o '" . DIR_GOOGLE_PATH_WWW . "{$a_users['name']}.png' 'otpauth://totp/{$a_users['name']}@{$host_local}?secret={$mfa_code}&issuer=openvpn'");
		file_put_contents(DIR_GOOGLE_PATH_WWW.$a_users['name'], $mfa_code);

		copy(DIR_GOOGLE_PATH_WWW.$a_users['name'], DIR_GOOGLE_PATH_ETC.$a_users['name']);
		copy(DIR_GOOGLE_PATH_WWW.$a_users['name'].'.png', DIR_GOOGLE_PATH_ETC.$a_users['name'].'.png');

		if (!$permission_dir) {
			$permission_dir = true;
		}
	}

	bp_permission_mfa_dir($permission_dir);

	$write_status = false;

	foreach ($config['system']['user'] as $key_users => $a_users) {
		if (!file_exists(DIR_GOOGLE_PATH_WWW.$a_users['name'])) {
			continue;
		}

		$key_code_mfa = explode("\n", file_get_contents(DIR_GOOGLE_PATH_WWW.$a_users['name']))[0];
		$filetime_key_code_mfa = filemtime(DIR_GOOGLE_PATH_WWW.$a_users['name']);

		if (isset($a_users['mfacode'], $a_users['mfafiletime']) &&
		    !empty($a_users['mfacode']) &&
		    !empty($a_users['mfafiletime']) &&
		    base64_decode($a_users['mfacode']) == $key_code_mfa &&
		    $a_users['mfafiletime'] == $filetime_key_code_mfa) {
			continue;
		}

		$config['system']['user'][$key_users]['mfacode'] = base64_encode($key_code_mfa);
		$config['system']['user'][$key_users]['mfafiletime'] = $filetime_key_code_mfa;

		if (!$write_status) {
			$write_status = true;
		}
	}

	foreach ($config['system']['user'] as $key_users => $a_users) {
		if (!file_exists(FILE_LOGIN_MFA.$a_users['name'])) {
			continue;
		}

		$config['system']['user'][$key_users]['statusenable2fa'] = 'yes';
		unlink_if_exists(FILE_LOGIN_MFA.$a_users['name']);

		if (!$write_status) {
			$write_status = true;
		}
	}

	if ($write_status) {
		write_config('BluePex MFA: MFA status changes');
	}
}

function bp_remove_files_mfa($username) {
	unlink_if_exists(FILE_LOGIN_MFA.$username);
	unlink_if_exists(DIR_GOOGLE_PATH_WWW.$username);
	unlink_if_exists(DIR_GOOGLE_PATH_WWW.$username.'.png');
	unlink_if_exists(DIR_GOOGLE_PATH_ETC.$username);
	unlink_if_exists(DIR_GOOGLE_PATH_ETC.$username.'.png');
}

function bp_move_files_mfa($old_username, $new_username) {
	if (file_exists(FILE_LOGIN_MFA.$old_username)) { copy(FILE_LOGIN_MFA.$old_username, FILE_LOGIN_MFA.$new_username); }
	if (file_exists(DIR_GOOGLE_PATH_WWW.$old_username)) { copy(DIR_GOOGLE_PATH_WWW.$old_username, DIR_GOOGLE_PATH_WWW.$new_username); }
	if (file_exists(DIR_GOOGLE_PATH_WWW.$old_username.'.png')) { copy(DIR_GOOGLE_PATH_WWW.$old_username.'.png', DIR_GOOGLE_PATH_WWW.$new_username.'.png'); }
	if (file_exists(DIR_GOOGLE_PATH_ETC.$old_username)) { copy(DIR_GOOGLE_PATH_ETC.$old_username, DIR_GOOGLE_PATH_ETC.$new_username); }
	if (file_exists(DIR_GOOGLE_PATH_ETC.$old_username.'.png')) { copy(DIR_GOOGLE_PATH_ETC.$old_username.'.png', DIR_GOOGLE_PATH_ETC.$new_username.'.png'); }
	bp_permission_mfa_dir(true);
}
