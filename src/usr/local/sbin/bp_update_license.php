<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
 *
 * ====================================================================
 *
 */

require_once("config.inc");
require_once("util.inc");
require_once("bp_webservice.inc");

if (get_serial_status() !== "ok") {
	sleep(rand(1, 840));
	unlink_if_exists(WSUTM_CACHE_FILE);
	create_wsutm_cache_file();
}
