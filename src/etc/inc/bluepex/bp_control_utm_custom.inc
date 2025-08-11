<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * ReWritten by Guilherme Brechot <guilherme.breachot@bluepex.com>, 2024
 *
 * ====================================================================
 *
 */

require_once("config.lib.inc");
require_once("pkg-utils.inc");
require_once("bp_webservice.inc");
require_once("bp_cipher_engine.inc");

define('FILE_UTM_CUSTOM', '/etc/utm_custom.txt');
define('FILE_UTM_CUSTOM_ENCRYPTED', '/etc/utm_custom');

function bp_action_utm_custom_file($action, $password) {
	if ($password != KEY_CIPHER_ACTION) {
		return;
	}

	switch ($action) {
		case "create":
			bp_create_utm_custom_file();
			break;
		case "remove":
			bp_remove_utm_custom_file();
			break;
		case "cripty":
			bp_cripty_utm_custom_file();
			break;
		case "decripty":
			bp_decripty_utm_custom_file();
			break;
		case "list":
			bp_list_utm_custom_file();
			break;
		case "restore":
			bp_restore_utm_custom_file();
			break;
		default:
			break;
	}

	return;
}

function bp_create_utm_custom_file() {
	foreach ([FILE_UTM_CUSTOM, FILE_UTM_CUSTOM_ENCRYPTED] as $work_files) {
		if (!file_exists($work_files)) {
			file_put_contents($work_files, '');
			echo "Create file ${work_files}" . PHP_EOL;
		} else {
			echo "File ${work_files} already exists" . PHP_EOL;
		}
	}
}

function bp_remove_utm_custom_file() {
	foreach ([FILE_UTM_CUSTOM, FILE_UTM_CUSTOM_ENCRYPTED] as $work_files) {
		if (file_exists($work_files)) {
			unlink($work_files);
			echo "Delete file ${work_files}" . PHP_EOL;
		} else {
			echo "File ${work_files} not found" . PHP_EOL;
		}
	}
}

function bp_check_files() {
	$default_return = false;

	foreach ([FILE_UTM_CUSTOM, FILE_UTM_CUSTOM_ENCRYPTED] as $work_files) {
		if (!file_exists($work_files)) {
			echo "File ${work_files} not found" . PHP_EOL;
			$default_return = true;
		}
	}

	return $default_return;
}

function bp_cripty_utm_custom_file() {
	if (bp_check_files()) {
		echo "Create the file with option 'create'" . PHP_EOL;
		return;
	}

	file_put_contents(FILE_UTM_CUSTOM_ENCRYPTED, bp_encrypt_action(file_get_contents(FILE_UTM_CUSTOM)));
	echo "Custom installation file has been encrypted" . PHP_EOL;
	echo "The generated file is " . FILE_UTM_CUSTOM_ENCRYPTED . PHP_EOL;
}

function bp_decripty_utm_custom_file() {
	if (bp_check_files()) {
		echo "Create the file with option 'create'" . PHP_EOL;
		return;
	}

	$decrypt_values = bp_decrypt_action(trim(file_get_contents(FILE_UTM_CUSTOM_ENCRYPTED)));

	if ($decrypt_values != trim(file_get_contents(FILE_UTM_CUSTOM))) {
		echo <<<EOF
The customization edit file is different from the original state of the encrypted custom state.
Cannot unzip again.

Possible actions:
* Restore the edited file with the restore parameter;
* Use the remove parameter and recreate all custom ones;
* Applies the current state and persists changes;
EOF;
		echo PHP_EOL;
		return;
	}

	file_put_contents(FILE_UTM_CUSTOM, $decrypt_values);
	echo "Custom installations file has been decrypted" . PHP_EOL;
	echo "To edit the file, go to " . FILE_UTM_CUSTOM . PHP_EOL;
}

function bp_restore_utm_custom_file() {
	if (bp_check_files()) {
		echo "Create the file with option 'create'" . PHP_EOL;
		return;
	}

	$decrypt_values = bp_decrypt_action(trim(file_get_contents(FILE_UTM_CUSTOM_ENCRYPTED)));

	if ($decrypt_values != trim(file_get_contents(FILE_UTM_CUSTOM))) {
		file_put_contents(FILE_UTM_CUSTOM, $decrypt_values);
		echo "The edit file " . FILE_UTM_CUSTOM . " has been restored with information based on the cipher" . PHP_EOL;
	} else {
		echo "It is not possible to restore the state of the file based on the encrypted one, as it has the same value" . PHP_EOL;
	}
}

function bp_list_utm_custom_file() {
	if (bp_check_files()) {
		echo "Create the file with option 'create'" . PHP_EOL;
		return;
	}

	echo "---------------------------------" . PHP_EOL;
	echo "Custom file -> " . FILE_UTM_CUSTOM . PHP_EOL;
	echo "---------------------------------" . PHP_EOL;

	$values = explode("\n", bp_decrypt_action(trim(file_get_contents(FILE_UTM_CUSTOM_ENCRYPTED))));

	foreach ($values as $lines) {
		if (!empty($lines)) {
			echo $lines . PHP_EOL;
		}
	}

	echo "---------------------------------" . PHP_EOL;
}
