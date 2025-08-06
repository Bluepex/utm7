<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Rewritten by Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2023
 *
 * ====================================================================
 *
 */

require_once('config.inc');
require_once('interfaces.inc');

define("LIMIT_HOSTS_FILE", "/etc/capacity-utm");
define("UTM_MODEL", "/etc/model");
define("TEMPMED", "/tmp/tempmed");

$limit_hosts = file_exists(LIMIT_HOSTS_FILE) ? intval(trim(file_get_contents(LIMIT_HOSTS_FILE))) : 20;
$utm_model = file_exists(UTM_MODEL) ? trim(file_get_contents(UTM_MODEL)) : "UTM 1000";
$tempmed = file_exists(TEMPMED) ? intval(trim(file_get_contents(TEMPMED))) : 0;

function bp_generate_qtd_unique_connections() {
	exec("/usr/sbin/arp -an", $outarp, $errarp);

	return (empty($errarp) && !empty($outarp)) ? count($outarp) : 0;
}

function bp_generation_qtd_sessions() {
	exec("/sbin/pfctl -ss | /usr/bin/wc -l | /usr/bin/sed 's/[[:space:]]//g'", $outpfctl, $errpfctl);

	if (!empty($errpfctl) ||
	    empty($outpfctl)) {
		$outpfctl = 0;
	} else {
		$outpfctl = intval($outpfctl[0]);
	}

	return $outpfctl;
}

function bp_get_capacity_hosts() {
	global $limit_hosts, $config;

	$count_hosts = bp_generate_qtd_unique_connections();

	$trans['lang1'] = "Máquinas";
	$trans['lang2'] = "Há dados aguardando";
	$trans['lang3'] = "de";

	if ($config['system']['language'] == 'en_US') {
		$trans['lang1'] = "Hosts";
		$trans['lang2'] = "There is data waiting";
		$trans['lang3'] = "of";
	}

	$return_json['text'] = $trans['lang1'] . " - " . $trans['lang2'];
	$return_json['value'] = $trans['lang2'];

	if ($limit_hosts > 0 &&
	    is_int($count_hosts)) {
		$return_json['text'] = $trans['lang1'] . " - " . $count_hosts . " " . $trans['lang3'] . " " . $limit_hosts;
		$return_json['value'] = round(($count_hosts / $limit_hosts) * 100, 2);
	}

	return json_encode($return_json);
}

function bp_generation_qtd_limit_sessions() {
	global $utm_model;

	switch ($utm_model) {
		case "UTM 1000":
		case "UTM 1500":
		case "UTM 2000":
			$limit_sessions = 100000;
			break;
		case "UTM 2500":
		case "UTM 3000":
			$limit_sessions = 200000;
			break;
		case "UTM 3500":
		case "UTM 4000":
		case "UTM 4500":
			$limit_sessions = 400000;
			break;
		case "UTM 5000":
		case "UTM 5500":
			$limit_sessions = 500000;
			break;
		case "UTM 6000":
			$limit_sessions = 600000;
			break;
		default:
			$limit_sessions = 100000;
			break;
	}

	return $limit_sessions;
}

function bp_get_capacity_sessions() {
	global $limit_hosts, $utm_model, $config;

	$count_sessions = bp_generation_qtd_sessions();
	$limit_sessions = bp_generation_qtd_limit_sessions();

	$trans['lang1'] = "Sessões";
	$trans['lang2'] = "Há dados aguardando";
	$trans['lang3'] = "de";

	if ($config['system']['language'] == 'en_US') {
		$trans['lang1'] = "Sessions";
		$trans['lang2'] = "There is data waiting";
		$trans['lang3'] = "of";
	}

	$return_json['text'] = $trans['lang1'] . " - " . $trans['lang2'];
	$return_json['value'] = $trans['lang2'];

	if ($limit_hosts > 0 &&
	    is_int($count_sessions)) {
		$return_json['text'] = $trans['lang1'] . " - " . $count_sessions . " " . $trans['lang3'] . " " . $limit_sessions;
		$return_json['value'] = round(($count_sessions / $limit_sessions) * 100, 2);
	}

	return json_encode($return_json);
}

if (isset($_POST["tempmed"])) {
	$translate = ($config['system']['language'] == 'en_US') ? "There is data waiting" : "Há dados aguardando";
	echo ($tempmed > 0) ? $tempmed : $translate;
	exit;
}

if (isset($_POST["utmcapacity"])) {
	echo bp_get_capacity_hosts();
	exit;
}

if (isset($_POST["utmcapacitysessions"])) {
	echo bp_get_capacity_sessions();
	exit;
}

if (isset($argv[1])) {
	if ($argv[1] == "hosts") {
		echo bp_generate_qtd_unique_connections();
		exit;
	} elseif ($argv[1] == "sessions") {
		echo bp_generation_qtd_sessions();
		exit;
	} elseif ($argv[1] == "limit_sessions") {
		echo bp_generation_qtd_limit_sessions();
		exit;
	}
}
