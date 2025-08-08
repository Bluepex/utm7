#!/usr/local/bin/php -f
<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Bruno B. Stein <bruno.stein@bluepex.com>, 2012
 * Written by  Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */
require("config.inc");
require("bp_webservice.inc");

$total_args = count($argv);
if ($total_args == 1) {
	return;
}
if (!isset($config['bp_webservice'])) {
	$config['bp_webservice'] = array();
}
for ($i=1; $i<=$total_args; $i++) {
	if ($argv[$i] == "get_news") {
		get_news_wsutm();
	} elseif ($argv[$i] == "send_backup") {
		send_bkp_wsutm();
	}
}

function send_bkp_wsutm() {
	global $config, $g;

	$info = array();

	// Get Capacity UTM
	if (file_exists("/etc/capacity-utm"))
		$info['capacity_utm'] = trim(file_get_contents('/etc/capacity-utm'));

	// Get Total hosts connected
	$info['total_hosts_connected'] = trim(exec('/usr/sbin/arp -an | /usr/bin/wc -l'));

	// GET Version
	if (file_exists('/etc/version'))
		$info['version'] = trim(file_get_contents('/etc/version'));

	// Get platform
	$info['platform'] = php_uname();

	$config['bp_webservice']['useful_informations'] = $info;
	write_config(gettext('Added useful informations.'));

	$backup_file = $g['conf_path'] . "/config.xml";
	if (!file_exists($backup_file)) {
		log_error(sprintf(gettext("File '%s' not found!"), $backup_file));
		return;
	}
	// Get Backup
	$gc = exec("/usr/local/bin/xmllint --noout {$backup_file}", $out, $err);
	if ($err == 0) {
		$backup = base64_encode(file_get_contents($backup_file));
		$resp = do_webservice_request('backup', 'insert-backup', array('backup' => $backup));
		if ($resp->status != 'ok' && isset($config['backup_cloud_enabled']))
			log_error(gettext("BluePex UTM Webservice: Backup in cloud: Could not to send the backup file!"));
	}
}

function get_news_wsutm() {
	global $config;

	$res = do_webservice_request("news-utm", "list");
	if (empty($res) || $res->status != 'ok') {
		log_error(gettext("Could not get the news from BluePexUTM Webservice!"));
		return;
	}
	if (!isset($res->data->news)) {
		return;
	}
	$news = array();
	foreach ($res->data->news as $new_utm) {
		$news['item'][] = array(
			"title" => htmlentities($new_utm->title),
			"description" => $new_utm->description,
			"link" => $new_utm->link
		);
	}
	if (empty($news) && isset($config['bp_webservice']['news'])) {
		unset($config['bp_webservice']['news']);
	} else {
		$config['bp_webservice']['news'] = $news;
	}
	write_config(gettext("BluePex UTM Webservice: News changed successfully!"));
}

?>
