<?php
# ====================================================================
# Copyright (C) BluePex Security Solutions - All rights reserved
# Unauthorized copying of this file, via any medium is strictly prohibited
# Proprietary and confidential
# Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
# ====================================================================

require_once("config.inc");
require_once("bluepex/bp_2af_functions.inc");

if (!isset($argv[1], $argv[2]) ||
    empty($argv[1]) ||
    empty($argv[2])) {
	echo "err";
	exit;
}

$action_local = (isset($argv[1]) && !empty($argv[1])) ? $argv[1] : '';
$user_local = (isset($argv[2]) && !empty($argv[2])) ? $argv[2] : '';
$code_2af = (isset($argv[3]) && !empty($argv[3])) ? $argv[3] : '';

if (isset($action_local) &&
    !empty($action_local) &&
    $action_local == "confirm" &&
    isset($user_local) &&
    !empty($user_local)) {
	echo bp_confirm_2af_user($user_local);
	exit;
}

if (isset($action_local) &&
    !empty($action_local) &&
    $action_local == "test" &&
    isset($user_local) &&
    !empty($user_local) &&
    isset($code_2af) &&
    !empty($code_2af)) {
	echo bp_checkshell_2afcode($user_local, $code_2af);
	exit;
}

echo "err";
exit;
