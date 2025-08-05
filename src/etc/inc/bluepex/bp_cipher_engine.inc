<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
 * ====================================================================
 *
 */

require_once("/usr/local/bin/bp-manager/vendor/autoload.php");

define('KEY_CIPHER_ACTION', 'e3e6e3560746b431dcd6b880894d912b354e0e7a0903c7688d489886d6108453');

function bp_encrypt_action($value) {
	if (empty($value)) {
		return;
	}

	return trim(\mervick\aesEverywhere\AES256::encrypt($value, KEY_CIPHER_ACTION));
}

function bp_decrypt_action($value) {
	if (empty($value)) {
		return;
	}

	return trim(\mervick\aesEverywhere\AES256::decrypt($value, KEY_CIPHER_ACTION));
}
