<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Bruno B. Stein <bruno.stein@bluepex.com>, 2012
 * Written by  Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 *	====================================================================
 *
 */

require_once("service-utils.inc");
require_once("services.inc");
require_once("util.inc");
require_once("config.inc");
require_once("pkg-utils.inc");

global $config;

require_once("nf_config.inc");
require_once("nf_db.inc");

define("DIR", "/usr/local/www/");
define("DIRSQL", "/usr/local/www/webfilter/");

$rrddbpath = "/var/db/rrd";
$rrdtool = "/usr/bin/nice -n20 /usr/local/bin/rrdtool";

if (!isset($config['modules']['schedules_bpx']['transfer'])) {
	init_config_arr(array('modules', 'schedules_bpx', 'transfer'));
	$data_transfer = &$config['modules']['schedules_bpx']['transfer'];
} else {
	$data_transfer = &$config['modules']['schedules_bpx']['transfer'];
}

if (!isset($config['modules']['schedules_bpx'])) {
	init_config_arr(array('modules', 'schedules_bpx'));
	$data_sched = &$config['modules']['schedules_bpx'];
} else {
	$data_sched = &$config['modules']['schedules_bpx'];
}

if (isset($config['system']['webfilter']['nf_reports_settings']['element0']))
	$conn = $config['system']['webfilter']['nf_reports_settings']['element0'];

function backup_utm($name, $type) {
	global $config, $g, $DIR;

	$config_backup = DIR . "config.xml";
	$data = "";

	/* backup entire configuration */
	$data = file_get_contents("{$g['conf_path']}/config.xml");
	$files[] = $config_backup;

	if ($type == "all-rrd") {
		/* backup entire configuration with RRD Data */
		$rrd_data_xml = rrd_data_xml();
		$closing_tag = "</" . $g['xml_rootobj'] . ">";
		$data = str_replace($closing_tag, $rrd_data_xml . $closing_tag, $data);
	}

	$fh = fopen($config_backup, 'w');

	if (!$fh) {
		log_error(dgettext("BPBackup", "Could not open the file for backup."));
		return false;
	}

	fwrite($fh, $data);
	fclose($fh);
	if (!compress_files($name, $files)) {
		log_error(dgettext("BPBackup", "Error Backup: It was not possible to compress backup files."));
	}
}

function rrd_data_xml() {
	global $rrddbpath;
	global $rrdtool;

	$result = "\t<rrddata>\n";
	$rrd_files = glob("{$rrddbpath}/*.rrd");
	$xml_files = array();
	foreach ($rrd_files as $rrd_file) {
		$basename = basename($rrd_file);
		$xml_file = preg_replace('/\.rrd$/', ".xml", $rrd_file);
		exec("$rrdtool dump '{$rrd_file}' '{$xml_file}'");
		$xml_data = file_get_contents($xml_file);
		unlink($xml_file);
		if ($xml_data !== false) {
			$result .= "\t\t<rrddatafile>\n";
			$result .= "\t\t\t<filename>{$basename}</filename>\n";
			$result .= "\t\t\t<xmldata>" . base64_encode(gzdeflate($xml_data)) . "</xmldata>\n";
			$result .= "\t\t</rrddatafile>\n";
		}
	}
	$result .= "\t</rrddata>\n";
	return $result;
}

function backup_webfilter($name, $type) {
	$config_backup = DIR . "webfilter_config_backup.xml";
	$sql_name = DIR . date("YmdHis")."-webfilter-database" . ".sql.gz";

	if ($type == "config") {
		backup_webfilter_conf($config_backup);
		$files[] = $config_backup;
	} elseif ($type == "database") {
		backup_webfilter_db($sql_name);
		$files[] = $sql_name;
	} elseif ($type == "all") {
		backup_webfilter_conf($config_backup);
		backup_webfilter_db($sql_name);
		$files = array($config_backup, $sql_name);
	}

	if (!compress_files($name, $files)) {
		log_error(dgettext("BPBackup", "Error Backup: It was not possible to compress backup files."));
	}
}

function backup_webfilter_conf($config_backup) {
	global $config, $g;

	$arrayconfig = array();

	if (isset($config['system']['webfilter'])) {
		$arrayconfig['system']['webfilter'] = $config['system']['webfilter'];
	}

	$xmlconfig = dump_xml_config($arrayconfig, $g['xml_rootobj']);
	$xmlconfig = str_replace("<?xml version=\"1.0\"?>", "", $xmlconfig);
	$xmlconfig .= $tag_content;

	$fh = fopen($config_backup, 'w');

	if (!$fh) {
		return false;
	}

	fwrite($fh, $xmlconfig);
	fclose($fh);

	return true;
}

function backup_webfilter_db($sql_name) {
	global $config, $g, $DIR, $conn;

	if (!is_process_running("mysqld")) {
		log_error(dgettext("BPBackup", "Error Backup: Service mysql is not running."));
		return false;
	}

	$cmd = '/usr/local/bin/mysqldump -uroot -p123' .
		' --single-transaction --routines -B webfilter' .
		' | gzip > ' . $sql_name;

	exec($cmd, $output, $rc);

	if ($rc != 0) {
		if (file_exists($sql_name))
			unlink($sql_name);

		return false;
	}

	return true;
}

if (!function_exists('backup_open_vpn_db')) {
	function backup_open_vpn_db($name) {
		global $config, $g, $DIR, $conn;        

		$bkp_name = DIR . date("YmdHis")."-openvpn-control.db";
		$db_src = "/var/db/openvpn_control.db";        

		if (!file_exists($db_src)) {
				log_error(dgettext("BPBackup", "Error Backup: There isn't openvpn_control.db file."));
				return false;
		}else{
					
				if(!copy($db_src, $bkp_name)){
						log_error(dgettext("BPBackup", "Error OpenVPN Backup: openvpn_control.db copies file was not possible."));
						return false;
				}else{
						$files[] = $bkp_name;
				}
		}        

		if ((!isset($files)) || (!compress_files($name, $files))) {
				log_error(dgettext("BPBackup", "Error Backup: It was not possible to compress backup files."));
		}   

		return true;
	}
}

function compress_files($name, $files=array()) {
	$tgz_name = DIR . $name . ".tar.gz";

	$cmd = "/usr/bin/tar czf " . $tgz_name;

	foreach($files as $file) {
		$cmd .= " ".$file;
	}

	exec($cmd, $output, $rc);

	if ($rc != 0) {
		if (file_exists($tgz_name))
			unlink($tgz_name);

		return false;
	} else {
		foreach($files as $file) {
			unlink($file);
		}
	}

	return true;
}

function restore_webfilter($file) {
	global $DIR, $DIRSQL, $config, $g, $conn;

	$config_backup = DIR . "webfilter_config_backup.xml";
	$sql_name = "";

	if (!file_exists($file)) {
		log_error(dgettext("BPBackup", "Error Restore: file not found."));
		return false;
	}

	$cmd = "/usr/bin/tar xzpfC " . $file . " /";
	exec($cmd, $output, $rc);

	if ($rc != 0) {
		return false;
	}

	if (preg_match("/[0-9]{14}-(webfilter-all|webfilter-database)\.tar\.gz$/", $file)) {
		$patterns = array('/all\.tar\.gz$/', '/database\.tar\.gz$/');
		$sql_name = preg_replace($patterns, 'database.sql.gz', $file);
	}

	if (!empty($sql_name) && file_exists($sql_name)) {

		if (!is_process_running("mysqld")) {
			log_error("Error Restore: Service mysql is not running.");
			return false;
		}

		$cmd = '/usr/bin/gunzip -c ' . $sql_name . ' | ' .
			'/usr/local/bin/mysql -uroot -p123';

		exec($cmd, $output, $rc);

		unlink($sql_name);

		if ($rc != 0) {
			log_error(dgettext("BPBackup", "Error Restore: error restoring database."));
			return false;
		}

		if (file_exists(DIRSQL . "webfilter_mysql_upgrade.sql")) {
			$cmd = "/usr/local/bin/mysql -u ".$conn['reports_user']." -p".
			$conn['reports_password']." < ".
			DIRSQL . "webfilter_mysql_upgrade.sql";
			mwexec($cmd);
		}
	}

	if (file_exists($config_backup)) {
		$section_xml = parse_xml_config($config_backup, $g["xml_rootobj"]);

		if ($section_xml != -1) {
			if (isset($section_xml['system']['webfilter'])) {
				$config['system']['webfilter'] = $section_xml['system']['webfilter'];
			}
		}
		unlink($config_backup);

		write_config(dgettext("BPBackup", "Restored webfilter section of config file"));
		// Resync conf squid
		squid_resync();
		// Netfilter resync
		NetfilterContentSettingsResync(false);
		NetfilterContentRulesResync(false);
		NetfilterWhitelistResync(false);
		NetfilterBlacklistResync(false);
		NetfilterGroupsResync(false);
		NetfilterProtocolRulesResync(false);
		disable_security_checks();
		conf_mount_ro();
	}

	return true;
}

function restore_utm($file) {
	global $DIR, $config, $g;

	if (!file_exists($file)) {
		log_error(dgettext("BPBackup", "Error Restore: file not found."));
		return false;
	}

	$cmd = "/usr/bin/tar xzpfC " . $file . " /";
	exec($cmd, $output, $rc);

	if ($rc != 0) {
		log_error(dgettext("BPBackup", "Error Descompact backup file."));
		return false;
	}

	//file after unzipping proccess
	$file = DIR . "config.xml";

	$data = file_get_contents($file);

	if (empty($data)) {
		log_error(sprintf(dgettext("BPBackup", "Warning, could not read file %s"), $_FILES['conffile']['tmp_name']));
		return false;
	}

	if (!stristr($data, "<" . $g['xml_rootobj'] . ">")) {
		$input_errors[] = sprintf(dgettext("BPBackup", "You have selected to restore the full configuration but we could not locate a %s tag."), $g['xml_rootobj']);
		log_error(sprintf(dgettext("BPBackup", "You have selected to restore the full configuration but we could not locate a %s tag."), $g['xml_rootobj']));
	} else {
		/* restore the entire configuration */
		$config_install = config_install($file);

		if ($config_install == 0) {
			/* this will be picked up by /index.php */
			conf_mount_rw();
			mark_subsystem_dirty("restore");
			touch("/conf/needs_package_sync");
			/* remove cache, we will force a config reboot */
			if (file_exists("{$g['tmp_path']}/config.cache")) {
				unlink("{$g['tmp_path']}/config.cache");
			}
			$config = parse_config(true);
			if (file_exists("/boot/loader.conf")) {
				$loaderconf = file_get_contents("/boot/loader.conf");
				if (strpos($loaderconf, "console=\"comconsole")) {
					$config['system']['enableserial'] = true;
					write_config(dgettext("BPBackup", "Restore serial console enabling in configuration."));
				}
				unset($loaderconf);
			}
			/* extract out rrd items, unset from $config when done */
			if ($config['rrddata']) {
				restore_rrddata();
				unset($config['rrddata']);
				unlink_if_exists("{$g['tmp_path']}/config.cache");
				write_config('Itens rdd extracted');
				add_base_packages_menu_items();
				convert_config();
				conf_mount_ro();
			}
			if ($m0n0wall_upgrade == true) {
				if ($config['system']['gateway'] <> "") {
					$config['interfaces']['wan']['gateway'] = $config['system']['gateway'];
				}
				unset($config['shaper']);
				/* optional if list */
				$ifdescrs = get_configured_interface_list(true, true);
				/* remove special characters from interface descriptions */
				if (is_array($ifdescrs)) {
					foreach ($ifdescrs as $iface) {
						$config['interfaces'][$iface]['descr'] = remove_bad_chars($config['interfaces'][$iface]['descr']);
					}
				}
				/* check for interface names with an alias */
				if (is_array($ifdescrs)) {
					foreach ($ifdescrs as $iface) {
						if (is_alias($config['interfaces'][$iface]['descr'])) {
							// Firewall rules
							$origname = $config['interfaces'][$iface]['descr'];
							$newname = $config['interfaces'][$iface]['descr'] . "Alias";
							update_alias_names_upon_change(array('filter', 'rule'), array('source', 'address'), $newname, $origname);
							update_alias_names_upon_change(array('filter', 'rule'), array('destination', 'address'), $newname, $origname);
							// NAT Rules
							update_alias_names_upon_change(array('nat', 'rule'), array('source', 'address'), $newname, $origname);
							update_alias_names_upon_change(array('nat', 'rule'), array('destination', 'address'), $newname, $origname);
							update_alias_names_upon_change(array('nat', 'rule'), array('target'), $newname, $origname);
							// Alias in an alias
							update_alias_names_upon_change(array('aliases', 'alias'), array('address'), $newname, $origname);
						}
					}
				}
				unlink_if_exists("{$g['tmp_path']}/config.cache");
				// Reset configuration version to something low
				// in order to force the config upgrade code to
				// run through with all steps that are required.
				$config['system']['version'] = "1.0";
				// Deal with descriptions longer than 63 characters
				for ($i = 0; isset($config["filter"]["rule"][$i]); $i++) {
					if (count($config['filter']['rule'][$i]['descr']) > 63) {
						$config['filter']['rule'][$i]['descr'] = substr($config['filter']['rule'][$i]['descr'], 0, 63);
					}
				}
				// Move interface from ipsec to enc0
				for ($i = 0; isset($config["filter"]["rule"][$i]); $i++) {
					if ($config['filter']['rule'][$i]['interface'] == "ipsec") {
						$config['filter']['rule'][$i]['interface'] = "enc0";
					}
				}
				// Convert icmp types
				// http://www.openbsd.org/cgi-bin/man.cgi?query=icmp&sektion=4&arch=i386&apropos=0&manpath=OpenBSD+Current
				for ($i = 0; isset($config["filter"]["rule"][$i]); $i++) {
					if ($config["filter"]["rule"][$i]['icmptype']) {
						switch ($config["filter"]["rule"][$i]['icmptype']) {
							case "echo":
								$config["filter"]["rule"][$i]['icmptype'] = "echoreq";
								break;
							case "unreach":
								$config["filter"]["rule"][$i]['icmptype'] = "unreach";
								break;
							case "echorep":
								$config["filter"]["rule"][$i]['icmptype'] = "echorep";
								break;
							case "squench":
								$config["filter"]["rule"][$i]['icmptype'] = "squench";
								break;
							case "redir":
								$config["filter"]["rule"][$i]['icmptype'] = "redir";
								break;
							case "timex":
								$config["filter"]["rule"][$i]['icmptype'] = "timex";
								break;
							case "paramprob":
								$config["filter"]["rule"][$i]['icmptype'] = "paramprob";
								break;
							case "timest":
								$config["filter"]["rule"][$i]['icmptype'] = "timereq";
								break;
							case "timestrep":
								$config["filter"]["rule"][$i]['icmptype'] = "timerep";
								break;
							case "inforeq":
								$config["filter"]["rule"][$i]['icmptype'] = "inforeq";
								break;
							case "inforep":
								$config["filter"]["rule"][$i]['icmptype'] = "inforep";
								break;
							case "maskreq":
								$config["filter"]["rule"][$i]['icmptype'] = "maskreq";
								break;
							case "maskrep":
								$config["filter"]["rule"][$i]['icmptype'] = "maskrep";
								break;
						}
					}
				}
				$config['diag']['ipv6nat'] = true;
				write_config('The m0n0wall configuration has been restored to BluePexUTM.');
				add_base_packages_menu_items();
				convert_config();
				conf_mount_ro();
				$savemsg = dgettext("BPBackup", "The m0n0wall configuration has been restored and upgraded to BluePexUTM.");
				mark_subsystem_dirty("restore");
			}
			if (is_array($config['captiveportal'])) {
				foreach ($config['captiveportal'] as $cp) {
					if (isset($cp['enable'])) {
						/* for some reason ipfw doesn't init correctly except on bootup sequence */
						mark_subsystem_dirty("restore");
						break;
					}
				}
			}
			/* 
			 * NOTE: Changed name of function setup_serial_port for console_configure
			 * setup_serial_port();
			 * console_configure();
			 * changed function in pack garga pwif
			 * console_configure();
			 */
			if (is_interface_mismatch() == true) {
				touch("/var/run/interface_mismatch_reboot_needed");
				clear_subsystem_dirty("restore");
				convert_config();
				header("Location: interfaces_assign.php");
				exit;
			}
			if (is_interface_vlan_mismatch() == true) {
				touch("/var/run/interface_mismatch_reboot_needed");
				clear_subsystem_dirty("restore");
				convert_config();
				header("Location: interfaces_assign.php");
				exit;
			}
		} else {
			$input_errors[] = dgettext("BPBackup", "The configuration could not be restored.");
			log_error(dgettext("BPBackup", "The configuration could not be restored."));
		}
	}

	return true;
}

function remove_files_bkp() {
	$bkp_type = array("all", "database", "config", "all-with-rrd");
	foreach($bkp_type as $type) {
		$files_bkp = array();
		$files = files_down();

		//Webfilter backup files
		foreach($files as $file) {
			if (preg_match("/[0-9]{14}-webfilter-".$type.".*/", $file)) {
				$files_bkp[] = $file;
			} elseif (preg_match("/[0-9]{14}-utm-".$type.".*/", $file)) {
				$files_bkp[] = $file;
			}
		}

		rsort($files_bkp);
		$total = count($files_bkp);
		for($i=0; $i < $total;$i++) {
			if ($i > 2) {
				unlink(DIR . $files_bkp[$i]);
			}
		}
	}
}

function files_down() {
	global $DIR;

	foreach(scandir(DIR) as $out) {
		if (preg_match('/([0-9]{14})-([a-z]{3,9})-([^}]*).([a-z]{3}).([a-z]{2})$/', $out)) {
			$files[] = $out;
		}
	}
	return $files;
}

function syncCron() {
	global $config, $data_sched;

	$total = count($config['cron']['item']);
	for($x=0; $x < $total; $x++)
		if (preg_match('/.*modules_backup_restore\.php/', $config['cron']['item'][$x]['command']))
			unset($config['cron']['item'][$x]);

	if ($data_sched['schedule']) {
		foreach($data_sched['schedule'] as $sched) {

			if ($sched['rule']) {
				$module = array();			
				foreach($sched['rule'] as $rule) {
					if ($rule['type'] == "clearlog") {
						$module['clearlog']['name'][] = $rule['name'];
						$module['clearlog']['storage'][] = $rule['storage'];
					}
					if ($rule['type'] == "backup")
						$module['backup']['name'][] = $rule['name'];
				}

				$area['id_scheduling'] = (isset($sched['id_scheduling']) && !empty($sched['id_scheduling'])) ? "id_scheduling='" . $sched['id_scheduling'] . "'" : 'id_scheduling=""';

				if ($module['clearlog']) {
					$area['clearlog'] = "clearlog='".implode(", ", $module['clearlog']['name'])."' storage='".implode(", ", $module['clearlog']['storage'])."'";
				}
				if ($module['backup']) {
					$area['backup'] = "backup='".implode(", ", $module['backup']['name'])."'";
				}

				$min_hour = explode(":", $sched['time']);
				if (!empty($area['clearlog'])) {
					$command = '/usr/local/www/modules_backup_restore.php ' . $area['clearlog'] . ' ' . $area['backup'] . ' ' . $area['id_scheduling'];
				} else {
					$command = '/usr/local/www/modules_backup_restore.php ' . $area['backup'] . ' ' . $area['id_scheduling'];
				}
				install_cron_job("cd /usr/local/www/ && ".$command,"true",$min_hour[1],$min_hour[0], "*","*",daysWeek($sched['period']));
			}
		}
	}
	write_config(dgettext('BPBackup', 'Schedule backup at cron.'));
	configure_cron();
}

function daysWeek($days_week) {
	$days = array(
			"Sunday" => "0",
			"Monday" => "1",
			"Tuesday" => "2",
			"Wednesday" => "3",
			"Thursday" => "4",
			"Friday" => "5",
			"Saturday" => "6"
			);

	$days_array = explode(", ", $days_week);
	foreach($days_array as $day_week) {
		$wday[] = $days[$day_week];
	}
	$wday = implode(",", $wday);
	return $wday;
}

function transferFtp($mode, $file, $use_sftp=false) {
	global $DIR, $data_transfer;

	$ip_address = $data_transfer['ipaddress'];
	$user = $data_transfer['username'];
	$password = $data_transfer['password'];

	if ($mode == "test") {
		$file = "test_ftp.txt";
	}
	if ($mode == "test" || $mode == "getfile") {
		$fp = fopen(DIR . $file, "w");
	} elseif ($mode == "putfile") {
		$fp = fopen(DIR . $file, "r");
	}

	$ch = curl_init();

	if ($use_sftp) {
		curl_setopt($ch, CURLOPT_URL, "sftp://" . $user . ":" . $password . '@' . $ip_address . "/" . $file);
		curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_SFTP);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	} else {
		curl_setopt($ch, CURLOPT_URL, "ftp://" . $user . ":" . $password . '@' . $ip_address . "/" . $file);
	}

	if ($mode == "getfile") {
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FILE, $fp);
	} else {
		curl_setopt($ch, CURLOPT_UPLOAD, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 8);
		curl_setopt($ch, CURLOPT_INFILE, $fp);
		curl_setopt($ch, CURLOPT_INFILESIZE, filesize(DIR . $file));
	}

	$response = curl_exec($ch);
	$curl_error = curl_errno($ch);

	if ($curl_error != 0) {
		log_error("Transfer error = " . $curl_error);
	}

	curl_close($ch);
	fclose($fp);

	if ($mode == "test" && file_exists(DIR . "test_ftp.txt")) {
		unlink(DIR . "test_ftp.txt");
	}

	if ($response == 1) {
		if ($mode == "test") {
			log_error(dgettext("BPBackup", "Connection Test successfully!"));
		} elseif ($mode == "putfile") {
			log_error(dgettext("BPBackup", "The file was uploaded in the FTP server successfully!"));
		} elseif ($mode == "getfile") {
			log_error(dgettext("BPBackup", "The file was downloaded from FTP server successfully!"));
		}
		return true;
	}

	if ($mode == "test") {
		log_error(dgettext("BPBackup", "Connection Test failed!"));
	} elseif ($mode == "putfile") {
		log_error(dgettext("BPBackup", "Could not uploaded the file in the FTP server!"));
	} elseif ($mode == "getfile") {
		log_error(dgettext("BPBackup", "Could not downloaded the file in the FTP server!"));
	}
}

function transferShare($mode, $file) {
	global $DIR, $data_transfer;

	$ip_address = $data_transfer['ipaddress'];
	$user = $data_transfer['username'];
	$password = $data_transfer['password'];
	$dir = $data_transfer['directory'];

	if ($mode == "test") {
		$open = fopen(DIR . "test_share.txt", w);
		fwrite($open, "Test Share BluePex UTM");
		fclose($open);

		$cmd = "/usr/bin/cd " . DIR . "; /usr/local/bin/smbclient //".$ip_address."/".$dir." -U '".$user."' --pass '".$password."' -c 'put test_share.txt'";
		exec($cmd, $output, $rc);

		$cmd = "/usr/bin/cd " . DIR . "; /usr/local/bin/smbclient //".$ip_address."/".$dir." -U '".$user."' --pass '".$password."' -c 'rm test_share.txt'";
		exec($cmd, $output, $rc);
	} elseif ($mode == "putfile") {
		if (file_exists(DIR . $file)) {
			$cmd = "/usr/bin/cd " . DIR . "; /usr/local/bin/smbclient //".$ip_address."/".$dir." -U '".$user."' --pass '".$password."' -c 'put ".$file."'";
			exec($cmd, $output, $rc);
		} else {
			return false;
		}
	} elseif ($mode == "getfile") {
		$cmd = "/usr/bin/cd " . DIR . "; /usr/local/bin/smbclient //".$ip_address."/".$dir." -U '".$user."' --pass '".$password."' -c 'get ".$file."'";
		exec($cmd, $output, $rc);
	}

	if ($rc != 0) {
		if (file_exists(DIR . "test_share.txt"))
			unlink(DIR . "test_share.txt");

		return false;
	} else {
		if (file_exists(DIR . "test_share.txt"))
			unlink(DIR . "test_share.txt");

		return true;
	}
}

function sent_email_backup($message, $title) {
	global $config, $g;
	require_once("sasl.inc");
	require_once("smtp.inc");

	$notice = $config['notifications']['smtp'];

	if (isset($notice['disable']) && isset($notice['ipaddress']) && isset($notice['notifyemailaddress'])) {
		return;
	}

	$message .= sprintf(dgettext("BPBackup", "%sDate: %s.%s"), "<p>", date("d/m/Y H:i:s"), "</p>");

	$smtp = new smtp_class;

	$from = "pfsense@{$config['system']['hostname']}.{$config['system']['domain']}";
	$to   = $notice['notifyemailaddress'];

	$smtp->host_name = $notice['ipaddress'];
	$smtp->host_port = empty($notice['port']) ? 25 : $notice['port'];
	$smtp->direct_delivery = 0;
	$smtp->ssl = ($notice['ssl'] == "checked") ? 1 : 0;
	$smtp->localhost=$config['system']['hostname'].".".$config['system']['domain'];

	if ($notice['fromaddress']) {
		$from = $notice['fromaddress'];
	}

	// Use SMTP Auth if fields are filled out
	if ($notice['username'] && $notice['password']) {
		$smtp->authentication_mechanism = "PLAIN";
		$smtp->user = $notice['username'];
		$smtp->password = $notice['password'];
	}

	$headers = array(
		"MIME-Version: 1.0",
		"Content-type: text/html; charset=iso-8859-1",
		"From: {$from}",
		"To: {$to}",
		"Subject: {$config['system']['hostname']}.{$config['system']['domain']} - {$title}",
		"Date: ".strftime("%a, %d %b %Y %H:%M:%S %Z")
	);

	if ($smtp->SendMessage($from, array($to), $headers, $message)) {
		log_error(sprintf(dgettext("BPBackup", "Message sent to %s OK"), $to));
		return;
	} else {
		log_error(sprintf(dgettext('BPBackup', 'Could not send the message to %1$s -- Error: %2$s'), $to, $smtp->error));
		return(sprintf(dgettext('BPBackup', 'Could not send the message to %1$s -- Error: %2$s'), $to, $smtp->error));
	}
}

if (!function_exists('restore_rrddata')) {
	function restore_rrddata() {
		global $config, $g, $rrdtool, $input_errors;
		foreach ($config['rrddata']['rrddatafile'] as $rrd) {
			if ($rrd['xmldata']) {
				$rrd_file = "{$g['vardb_path']}/rrd/{$rrd['filename']}";
				$xml_file = preg_replace('/\.rrd$/', ".xml", $rrd_file);
				if (file_put_contents($xml_file, gzinflate(base64_decode($rrd['xmldata']))) === false) {
					log_error(sprintf(dgettext("BPBackup", "Cannot write %s"), $xml_file));
					continue;
				}
				$output = array();
				$status = null;
				exec("$rrdtool restore -f '{$xml_file}' '{$rrd_file}'", $output, $status);
				if ($status) {
					log_error("rrdtool restore -f '{$xml_file}' '{$rrd_file}' failed returning {$status}.");
					continue;
				}
				unlink($xml_file);
			} else if ($rrd['data']) {
				$rrd_file = "{$g['vardb_path']}/rrd/{$rrd['filename']}";
				$rrd_fd = fopen($rrd_file, "w");
				if (!$rrd_fd) {
					log_error(sprintf(dgettext("BPBackup", "Cannot write %s"), $rrd_file));
					continue;
				}
				$data = base64_decode($rrd['data']);
				/* Try to decompress the data. */
				$dcomp = @gzinflate($data);
				if ($dcomp) {
					/* If the decompression worked, write the decompressed data */
					if (fwrite($rrd_fd, $dcomp) === false) {
						log_error(sprintf(dgettext("BPBackup", "fwrite %s failed"), $rrd_file));
						continue;
					}
				} else {
					/* If the decompression failed, it wasn't compressed, so write raw data */
					if (fwrite($rrd_fd, $data) === false) {
						log_error(sprintf(dgettext("BPBackup", "fwrite %s failed"), $rrd_file));
						continue;
					}
				}
				if (fclose($rrd_fd) === false) {
					log_error(sprintf(dgettext("BPBackup", "fclose %s failed"), $rrd_file));
					continue;
				}
			}
		}
	}
}

function add_base_packages_menu_items() {
	global $g, $config;
	$base_packages = explode(",", $g['base_packages']);
	$modified_config = false;
	foreach ($base_packages as $bp) {
		$basepkg_path = "/usr/local/pkg/{$bp}";
		$tmpinfo = pathinfo($basepkg_path, PATHINFO_EXTENSION);
		if ($tmpinfo['extension'] == "xml" && file_exists($basepkg_path)) {
			$pkg_config = parse_xml_config_pkg($basepkg_path, "packagegui");
			if ($pkg_config['menu'] != "") {
				if (is_array($pkg_config['menu'])) {
					foreach ($pkg_config['menu'] as $menu) {
						if (is_array($config['installedpackages']['menu'])) {
							foreach ($config['installedpackages']['menu'] as $amenu) {
								if ($amenu['name'] == $menu['name']) {
									continue;
								}
							}
						}
						$config['installedpackages']['menu'][] = $menu;
						$modified_config = true;
					}
				}
			}
		}
	}
	if ($modified_config) {
		write_config(dgettext("BPBackup", "Restored base_package menus after configuration restore."));
		$config = parse_config(true);
	}
}

?>
