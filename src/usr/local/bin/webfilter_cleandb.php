#!/usr/local/bin/php -f
<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once("util.inc");
require_once("nf_config.inc");
require_once('nf_db.inc');

$config = get_element_config('nf_reports_settings');
if($config['remote_reports'] == 'on') {

	$db = new NetfilterDatabase();
	$res = $db->Query("SELECT version FROM version");
	if($res) {
		echo "Web Filter database is already on the newest version.\n";
		exit(0);
	}
	$db->FreeRes($res);
	$backupfile = "/var/tmp/backup_webfilter_" . date("YmdHis") . ".sql";
	$cmd = "/usr/local/bin/mysqldump -u {$config['reports_user']} -p{$config['reports_password']} -h {$config['reports_ip']} -P {$config['reports_port']} {$config['reports_db']} > {$backupfile}";
	$retval = NULL;
        $output = NULL;
	echo "Creating a backup for WebFilter database\n";
	exec($cmd,$output,$retval);
	if($retval == 0) {
		echo "Backup successfully created at {$backupfile}\n";
	} else {
		if(count($output) > 0) {
			foreach($output as $out) log_error("WebFilter Migration Backup: {$out}");
			print_r($output);
		}
		exit(1);
	}

	echo "Cleaning up Web Filter database\n";
	$tables = array("accesses","hosts","paths","queries","schemes","urls");
	foreach($tables as $table) {
		echo "Truncating table {$table}\n";
		$sql = "TRUNCATE TABLE {$table}";
		$db->Query($sql);
	}
}
?>

