#!/usr/local/bin/php -f
<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2023
 * ====================================================================
 *
 */

ini_set('memory_limit', '512M');
require_once("bp_access_denials_logs_functions.inc");

bp_push_filtred_access_denials();
