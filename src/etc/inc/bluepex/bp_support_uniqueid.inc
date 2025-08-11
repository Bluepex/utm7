<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
 * ====================================================================
 *
 */

require_once('config.inc');
require_once('globals.inc');

define('KEY_FILE_UNIQUEID', "{$g['etc_path']}/key_uniqueid_api");

function bp_generate_uniqueid($confirm_file_exists = false) {
	if ($confirm_file_exists) {
		if (file_exists(KEY_FILE_UNIQUEID) &&
		    !empty(trim(file_get_contents(KEY_FILE_UNIQUEID)))) {
			return;
		}
	}

	$timeboot = shell_exec("/sbin/sysctl kern.boottime | /usr/bin/awk -F' sec = ' '{print $2}' | /usr/bin/awk -F',' '{print $1}'");
	$value_time_zone = date_default_timezone_get();
	date_default_timezone_set('America/Sao_Paulo');
	file_put_contents(KEY_FILE_UNIQUEID, $timeboot);
	date_default_timezone_set($value_time_zone);
}

function bp_get_uniqueid() {
	bp_generate_uniqueid(true);
	return trim(file_get_contents(KEY_FILE_UNIQUEID));
}
