<?php

/*
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once('globals.inc');
require_once('config.inc');
require_once('pfsense-utils.inc');
require_once('service-utils.inc');
require_once("cg2_util.inc");
require_once("nf_config.inc");
require_once('nf_db.inc');
require_once("squid.inc");
require_once("util.inc");

function webfilter_install() {
	global $g, $config;

	init_config_arr(array('custom', 'package'));

	foreach($config['custom']['package'] as $key => $package) {
		if ($package['name'] == 'webfilter') {
			unset($config['custom']['package'][$key]);

			break;
		}
	}

	$config['custom']['package'][] = array(
		'name' => 'webfilter',
		'filter_rule_function' => 'squid_generate_rules',
		'include_file' => '/usr/local/pkg/squid.inc'
	);

	$squid_found = false;
	$wflogs_found = false;
	$wft_log_found = false;
	
	if (isset($config['installedpackages']['package'])) {
		foreach ($config['installedpackages']['package'] as $package) {
			switch ($package['name']) {
			case 'squid':
				$squid_found = true;
				break;
			case 'wflogs':
				$wflogs_found = true;
				break;
			case 'wft_log':
				$wft_log_found = true;
				break;
			}
			if ($squid_found && $wflogs_found && $wft_log_found) {
				break;
			}
		}
	}
	
	if (!$squid_found) {
		$config['installedpackages']['package'][] = array(
			'name' => 'squid',
			'logging' => array(
				'appname' => '!*squid*,redirector',
				'facilityname' => 'local2.info',
				'logfilename' => 'squid.log'
			)
		);
	}
	if (!$wflogs_found) {
		$config['installedpackages']['package'][] = array(
			'name' => 'wflogs',
			'logging' => array(
				'appname' => '!*wfrotated',
				'facilityname' => 'local6.info',
				'logfilename' => 'wflogs.log'
			)
		);
	}
	if (!$wft_log_found) {
		$config['installedpackages']['package'][] = array(
			'name' => 'wft_log',
			'logging' => array(
				'appname' => '!*squid*,redirector',
				'facilityname' => 'local2.info',
				'logfilename' => '|exec /usr/local/bin/wft_log.sh'
			)
		);
	}
	if (!$squid_found || $wflogs_found || $wft_log_found) {
		write_config(dgettext("BluePexWebFilter",
		    "Write packages logging config on xml."));
	}

	foreach($config['installedpackages']['package'] as $key => &$package) {
		switch ($package['name']){
			case "squid":
				$config['installedpackages']['package'][$key]['name'] = 'squid';
				$config['installedpackages']['package'][$key]['logging'] = array(
					'appname' => '!*squid*,redirector',
					'facilityname' => 'local2.info',
					'logfilename' => 'squid.log'
				);
				break;
			case "wflogs":
				$config['installedpackages']['package'][$key]['name'] = 'wflogs';
				$config['installedpackages']['package'][$key]['logging'] = array(
					'appname' => '!*wfrotated',
					'facilityname' => 'local6.info',
					'logfilename' => 'wflogs.log'
				);
				break;
			case "wft_log":
				$config['installedpackages']['package'][$key]['name'] = 'wft_log';
				$config['installedpackages']['package'][$key]['logging'] = array(
					'appname' => '!*squid*,redirector',
					'facilityname' => 'local2.info',
					'logfilename' => '|exec /usr/local/bin/wft_log.sh'
				);
				break;
		}
	}

	write_config(dgettext("BluePexWebFilter", "Write packages logging config on xml."));

	NetfilterContentSettingsResync(0);

	/*
	// Skip categories download if running on embedded appliances
	if ($g["platform"] == "BluePexUTM") {
		if (count(glob(NETFILTER_LISTS_DIR . "/*")) == 0) {
			download_file("http://updates.bluepex.com/webfilter/categories.tbz", "/usr/local/pkg/categories.tbz");
			if (!is_dir(NETFILTER_LISTS_DIR))
				mkdir(NETFILTER_LISTS_DIR, 0755, true);
			mwexec("tar -C " . NETFILTER_LISTS_DIR . " -xzf /usr/local/pkg/categories.tbz");
		} else {
			mwexec_bg("/usr/local/bin/update_content_lists.php");
		}
	}
	*/

	mwexec_bg("php /usr/local/bin/update_content_lists.php");

	/*
	if (is_dir('/usr/local/netfilter')) {
		mwexec("/bin/rm -rf /usr/local/netfilter");
	}
	if (is_link('/var/db/netfilter')) {
		unlink("/var/db/netfilter");
	}
	*/

	$rcconf = file_get_contents("/etc/rc.conf");
	
	if (!is_dir("/usr/local/etc/netfilter"))
		mkdir("/usr/local/etc/netfilter", 0755, true);
	if (!file_exists("/usr/local/etc/netfilter/groups.conf"))
		mwexec("/usr/bin/touch /usr/local/etc/netfilter/groups.conf");
	if (!file_exists("/usr/local/etc/netfilter/rules0.conf"))
		mwexec("/usr/bin/touch /usr/local/etc/netfilter/rules0.conf");
	if (!file_exists("/usr/local/etc/netfilter/whitelist0.conf"))
		mwexec("/usr/bin/touch /usr/local/etc/netfilter/whitelist0.conf");
	if (!file_exists("/usr/local/etc/netfilter/blacklist0.conf"))
		mwexec("/usr/bin/touch /usr/local/etc/netfilter/blacklist0.conf");
	
	if (!preg_match("/interface_enable/", $rcconf))
		file_put_contents("/etc/rc.conf", "interface_enable=\"YES\"\n", FILE_APPEND);
	if (!preg_match("/smdb_enable/", $rcconf))
		file_put_contents("/etc/rc.conf", "smdb_enable=\"YES\"\n", FILE_APPEND);
	if (!preg_match("/nmdb_enable/", $rcconf))
		file_put_contents("/etc/rc.conf", "nmdb_enable=\"YES\"\n", FILE_APPEND);
	if (!preg_match("/winbindd_enable/", $rcconf))
		file_put_contents("/etc/rc.conf", "winbindd_enable=\"YES\"\n", FILE_APPEND);
	if (!preg_match("/wfrotated_enable/", $rcconf))
		file_put_contents("/etc/rc.conf", "wfrotated_enable=\"YES\"\n", FILE_APPEND);
	if (!is_dir("/var/run/squid")) {
		mkdir("/var/run/squid", 0755, true);
		mwexec("/usr/sbin/chown squid:squid /var/run/squid");
	}
	
	if (!is_dir("/var/run/samba"))
		mkdir("/var/run/samba", 0755, true);
	if (!is_dir("/var/run/samba4"))
		mkdir("/var/run/samba4", 0755, true);
	if (!is_dir("/var/run/samba4/winbindd"))
		mkdir("/var/run/samba4/winbindd", 0755, true);
	if (!is_dir("/var/run/samba4/winbindd_privileged"))
		mkdir("/var/run/samba4/winbindd_privileged", 0755, true);
	
	if (!file_exists("/usr/local/lib/libgnutls.so.26"))
		mwexec("/bin/ln -s /usr/local/lib/libgnutls.so.28 /usr/local/lib/libgnutls.so.26");
	if (!file_exists("/usr/local/lib/libtasn1.so.7"))
		mwexec("/bin/ln -s /usr/local/lib/libtasn1.so.6 /usr/local/lib/libtasn1.so.7");
	if (!file_exists("/lib/libreadline.so.6"))
		mwexec("/bin/ln -s /lib/libreadline.so.8 /lib/libreadline.so.6");
	
	webfilter_install_cron();
	webfilter_install_services();
	
	if ($g['platform'] == "BluePexUTM") {
		webfilter_install_mysql("/usr/local/etc/wfmysqlcred");
	} else {
		if (!is_array($config['system']['webfilter']['nf_reports_settings']['element0'])) {
			$dbconfig = array(
				'remote_reports' => 'off',
				'reports_type' => 'mysql',
				'reports_ip' => '127.0.0.1',
				'reports_port' => '3306',
				'reports_user' => 'webfilter',
				'reports_password' => 'webfilter',
				'reports_db' => 'webfilter'
			);
			
			set_element_config('nf_reports_settings', 0, $dbconfig);
			write_config(dgettext("BluePexWebFilter", "Write database reports config on xml."));
		}
	}
	
	system_syslogd_start();
	
	squid_resync("", "yes");
	
	if (is_service_running('interface')) {
		mwexec("/usr/local/etc/rc.d/interface restart");
	} else {
		mwexec("/usr/local/etc/rc.d/interface start");
	}
}


function webfilter_deinstall() {
	global $config;

	webfilter_deinstall_cron();

	if (!isset($config['installedpackages']['package'])) {
		return;
	}

	$idx_to_remove = array();
	foreach ($config['installedpackages']['package'] as $idx => $package) {
		switch ($package['name']) {
		case 'squid':
		case 'wflogs':
		case 'wft_log':
			array_unshift($idx_to_remove, $idx);
			break;
		}
	}

	foreach ($idx_to_remove as $idx) {
		unset($config['installedpackages']['package'][$idx]);
	}

	if (count($idx_to_remove)) {
		write_config(dgettext("BluePexWebFilter",
		    "Remove packages logging config from xml."));
	}
}

function remove_instance_rules($rule_id) {
	global $config;
	if (is_array($config['system']['webfilter']['nf_content_rules']['element0']['item'])) {
		foreach ($config['system']['webfilter']['nf_content_rules']['element0']['item'] as $id => $rules) {
			if ($rules['instance_id'] == $rule_id) {
				unset($config['system']['webfilter']['nf_content_rules']['element0']['item'][$id]);
			}
		}
	}
}

function webfilter_install_mysql($mysql_credential_file) {
	global $g, $config;
	
	webfilter_create_my_cnf($mysql_credential_file);
	
	$rcconf = file_get_contents("/etc/rc.conf");
	if (!preg_match("/mysql_enable/", $rcconf)) {
		file_put_contents("/etc/rc.conf", "mysql_enable=\"YES\"\n", FILE_APPEND);
	}
	
	$i = 0;
	while (!is_process_running("mysqld") && $i < 3) {
		mwexec("/usr/local/etc/rc.d/mysql-server start");
		$i++;
	}
	
	if (mwexec("/usr/local/bin/mysql --defaults-extra-file=" . $mysql_credential_file . " -e \"show databases\"", true) != 0) {
		mwexec("/usr/local/bin/mysqladmin -u root password 123");
	} else {
		$mysql_upgrade = true;
	}
	
	mwexec("/usr/local/bin/mysql_upgrade --defaults-extra-file=" . $mysql_credential_file . " --force");
	
	if (!$mysql_upgrade || (mwexec("/usr/local/bin/mysql --defaults-extra-file=" . $mysql_credential_file . " -e \"use webfilter\"", true) != 0)) {
		mwexec("/usr/local/bin/mysql --defaults-extra-file=" . $mysql_credential_file . " < /usr/local/pkg/webfilter_createdb_mysql.sql");
		mwexec("/usr/local/bin/mysql --defaults-extra-file=" . $mysql_credential_file . " < /usr/local/www/webfilter/webfilter_mysql.sql");
	}
	
	mwexec("/usr/local/bin/mysql --defaults-extra-file=" . $mysql_credential_file . " < /usr/local/www/webfilter/webfilter_mysql_upgrade.sql");
	
	if (!is_array($config['system']['webfilter']['nf_reports_settings']['element0'])) {
		$dbconfig = array(
			'remote_reports' => 'off',
			'reports_type' => 'mysql',
			'reports_ip' => '127.0.0.1',
			'reports_port' => '3306',
			'reports_user' => 'webfilter',
			'reports_password' => 'webfilter',
			'reports_db' => 'webfilter'
		);
		
		set_element_config('nf_reports_settings', 0, $dbconfig);
		write_config(dgettext("BluePexWebFilter", "Write database config on xml."));
	}
	
	$nf_config = get_element_config('nf_reports_settings');
	if (is_array($nf_config) && !empty($nf_config)) {
		if ($nf_config['remote_reports'] == 'on') {
			mwexec("/usr/local/bin/mysql -u {$nf_config['reports_user']} -p{$nf_config['reports_password']} -h {$nf_config['reports_ip']} -P {$nf_config['reports_port']} {$nf_config['reports_db']} < /usr/local/www/webfilter/webfilter_mysql.sql");
			mwexec("/usr/local/bin/mysql -u {$nf_config['reports_user']} -p{$nf_config['reports_password']} -h {$nf_config['reports_ip']} -P {$nf_config['reports_port']} {$nf_config['reports_db']} < /usr/local/www/webfilter/webfilter_mysql_upgrade.sql");
		}
		
		if ($g["platform"] == "BluePexUTM") {
			$db = new MysqlBackend();
			$db->connect('127.0.0.1', '3306', 'root', '123', 'webfilter');
		} else {
			$db = new NetfilterDatabase();
		}
		
		$host = ($g["platform"] == "BluePexUTM" ? 'localhost' : '%');
		
		$sql = "GRANT ALL PRIVILEGES ON webfilter.* TO '{$nf_config['reports_user']}'@'{$host}' IDENTIFIED BY '{$nf_config['reports_password']}'";
		$res = $db->Query($sql);
		if ($res) {
			$db->FreeRes($res);
		} else {
			log_error("WebFilter: Error executing GRANT: " . $db->GetErrorString());
		}
		
		$sql = "GRANT SUPER ON *.* TO '{$nf_config['reports_user']}'@'{$host}' IDENTIFIED BY '{$nf_config['reports_password']}'";
		$res = $db->Query($sql);
		if ($res) {
			$db->FreeRes($res);
		} else {
			log_error("WebFilter: Error executing GRANT SUPER: " . $db->GetErrorString());
		}
	}
	
	mwexec_bg("/usr/local/etc/rc.d/mysql_server restart");
	unlink($mysql_credential_file);
}

function webfilter_create_my_cnf($mysql_credential_file) {
	$mycnf = <<<EOF
[mysqld]
key_buffer = 16K
max_allowed_packet = 1M
sort_buffer_size = 64K
read_buffer_size = 256K
read_rnd_buffer_size = 256K
net_buffer_length = 2K
server-id    = 1
innodb_file_per_table = 1
event_scheduler=ON

[mysqldump]
quick
max_allowed_packet = 16M

[mysql]
no-auto-rehash

[isamchk]
key_buffer = 8M
sort_buffer_size = 8M

[myisamchk]
key_buffer = 8M
sort_buffer_size = 8M

[mysqlhotcopy]
interactive-timeout
EOF;
	
	if (!is_dir("/var/db/mysql")) {
		mkdir("/var/db/mysql", 0755);
	}
	
	file_put_contents("/var/db/mysql/my.cnf", $mycnf);
	
	$mysqlCredentials = <<<EOF
[client]
user=root
password=123
EOF;


	file_put_contents($mysql_credential_file, $mysqlCredentials);
}

function is_cron_installed($taskname) {
	global $config;
	$is_installed = false;
	foreach ($config['cron']['item'] as $item) {
		if (strstr($item['task_name'], $taskname)) {
			$is_installed = true;
			break;
		}
	}
	return $is_installed;
}

function webfilter_install_cron() {
	global $config, $g;
	
	if (!is_cron_installed('uncategorized_rotate') && $g["platform"] == "BluePexUTM") {
		// Rotate Uncategorized URLs
		$cron_item                = array();
		$cron_item['task_name']   = "uncategorized_rotate";
		$cron_item['minute']      = "*/5";
		$cron_item['hour']        = "*";
		$cron_item['mday']        = "*";
		$cron_item['month']       = "*";
		$cron_item['wday']        = "*";
		$cron_item['who']         = "root";
		$cron_item['command']     = "/usr/local/bin/uncategorized_rotate.php";
		$config['cron']['item'][] = $cron_item;
	}
	/*
	if (!is_cron_installed('webfilter_update_categories') && $g["platform"] == "BluePexUTM") {
		// Update category lists
		$cron_item                = array();
		$cron_item['task_name']   = "webfilter_update_categories";
		$cron_item['minute']      = "0";
		$cron_item['hour']        = "2";
		$cron_item['mday']        = "*";
		$cron_item['month']       = "*";
		$cron_item['wday']        = "*";
		$cron_item['who']         = "root";
		$cron_item['command']     = "/usr/local/bin/update_content_lists.php";
		$config['cron']['item'][] = $cron_item;
	}
	*/
	if (!is_cron_installed('update_webfilter_db')) {
		// Update log database
		$cron_item                = array();
		$cron_item['task_name']   = "update_webfilter_db";
		$cron_item['minute']      = "*/15";
		$cron_item['hour']        = "*";
		$cron_item['mday']        = "*";
		$cron_item['month']       = "*";
		$cron_item['wday']        = "*";
		$cron_item['who']         = "root";
		$cron_item['command']     = "/usr/local/bin/update_webfilter_db.php";
		$config['cron']['item'][] = $cron_item;
	}
	
	if (!is_cron_installed('wf_monitor')) {
		// WebFilter monitor
		$cron_item                = array();
		$cron_item['task_name']   = "wf_monitor";
		$cron_item['minute']      = "*/15";
		$cron_item['hour']        = "*";
		$cron_item['mday']        = "*";
		$cron_item['month']       = "*";
		$cron_item['wday']        = "*";
		$cron_item['who']         = "root";
		$cron_item['command']     = "/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py";
		$config['cron']['item'][] = $cron_item;
	}
	
	parse_config(true);
	write_config(dgettext('BluePexWebFilter', "Adding tasks to crontab"));
	configure_cron();
}

function is_service_installed($service_name) {
	global $config;
	
	if (is_array($config['system']['webfilter']['services'])) {
		foreach ($config['system']['webfilter']['services']['service'] as $service) {
			if ($service['name'] == $service_name)
				return True;
		}
		return False;
	}
}

function webfilter_install_services() {
	global $config;
	
	if (!is_array($config['system']['webfilter']['services'])) {
		$config['system']['webfilter']['services'] == array();
	}
	
	$service_list = array(
		"WF Proxy Server,squid.sh,squid,Web Filter Proxy server Service",
		"WF Interface,interface,interface,Web Filter Interface Service",
		"WF Rotate Logs,wfrotated,wfrotated,Web Filter Rotate Logs Service",
		"WF MySQL Database,mysql-server,myslqd,Web Filter MySQL database Service"
	);
	
	foreach ($service_list as $service) {
		$s_service = explode(",", $service);
		if ($s_service[2] == "mysqld" && $g['platform'] != "BluePexUTM")
			continue;
		if (!is_service_installed($s_service[0])) {
			$config['system']['webfilter']['services']['service'][] = array(
				'name' => $s_service[0],
				'rcfile' => $s_service[1],
				'executable' => $s_service[2],
				'description' => $s_service[3]
			);
		}
	}
	
	write_config(dgettext("BluePexWebFilter", "Installed BluePex Web Filter services on XML file."));
}

function webfilter_deinstall_cron() {
	global $config;
	
	$cron =& $config['cron']['item'];
	
	// Task Webfilter Cron Job
	$task_name = array(
		"uncategorized_rotate",
		"update_webfilter_db",
		"webfilter_update_categories",
		"webfilter_rotate_logs"
	);
	
	if (!empty($cron)) {
		foreach ($cron as $i => $item) {
			if (in_array($item['task_name'], $task_name))
				unset($cron[$i]);
		}
		
		write_config(dgettext("BluePexWebFilter", "Uninstall WebFilter Cron Job."));
		configure_cron();
	}
}

function sync_dataclickagent() {
	global $g, $config;
	
	$settings = $config['system']['webfilter']['bluepexdataclickagent']['config'][0];
	if ($settings['enable'] != 'on') {
		return;
	}
	
	$interfaces = explode(',', $settings['interfaces']);
	$interfaces = array_map('get_real_interface', $interfaces);
	
	foreach ($interfaces as $iface) {
		
		$subnet_bits = find_interface_subnet($iface);
		$netmask     = long2ip(bindec(str_repeat('1', $subnet_bits) . str_repeat('0', 32 - $subnet_bits)));
		$ipaddress   = gen_subnet(find_interface_ip($iface), $subnet_bits);
		
		if ($g['platform'] == 'BluePexUTM') {
			$db = new MysqlBackend();
			$db->Connect('127.0.0.1', '3306', 'root', '123', 'webfilter');
			$db->Query("GRANT SELECT, EXECUTE ON {$conf['reports_db']}.* TO '{$conf['reports_user']}'@'{$ipaddress}/{$netmask}' IDENTIFIED BY PASSWORD '*F173D0793381C6DC13136F4FFB46FE933D81F464'");
		} else {
			$conf = get_element_config('nf_reports_settings');
			if (!empty($conf)) {
				$db = new NetfilterDatabase();
				$db->Query("GRANT SELECT, EXECUTE ON {$conf['reports_db']}.* TO '{$conf['reports_user']}'@'{$ipaddress}/{$netmask}' IDENTIFIED BY '{$conf['reports_password']}'");
			}
		}
	}
}

function is_webfilter_crl($refid) {
	global $config;
	$instances = $config['system']['webfilter']['instance']['config'];
	if (!is_array($instances)) {
		return;
	}
	foreach ($instances as $inst) {
		if (!empty($inst['server']['dca']) && $inst['server']['dca'] == $refid) {
			return true;
		}
	}
	return false;
}

function add_instances_info() {
	global $config;
	
	if (!isset($config['system']['webfilter']['instance']['config'])) {
		$config['system']['webfilter']['instance']['config'] = array();
	}
	$wf_instances =& $config['system']['webfilter']['instance']['config'];
	
	$instance_id_name = $interface_instance_id_name = array();
	
	foreach ($wf_instances as $instance_id => $instance) {
		$instance_id_name[] = "{$instance_id}:{$instance['server']['name']}";
		if (!is_numeric($instance['server']['parent_rules'])) {
			$interface_instance_id_name[] = "{$instance_id}:{$instance['server']['name']}";
			continue;
		}
		foreach ($wf_instances as $_instance_id => $_instance) {
			if (is_numeric($_instance['server']['parent_rules']) && $_instance['server']['parent_rules'] == $instance_id) {
				$interface_instance_id_name[] = "{$instance_id}:{$instance['server']['name']}";
				break;
			}
		}
	}
	$config['system']['webfilter']['instance']['info'] = array(
		"wf_instances_total" => count($wf_instances),
		"wf_instances" => implode("|", $instance_id_name),
		"wf_instances_interface" => implode("|", $interface_instance_id_name)
	);
}

function disable_webfilter_instance($ref_instance_id = -1) {
	global $g, $wf_instances;
	
	if (empty($wf_instances)) {
		return;
	}
	$instances_disabled = array();
	foreach ($wf_instances as $instance_id => $wf_instance) {
		if ($ref_instance_id >= 0 && $ref_instance_id != $instance_id) {
			continue;
		}
		$instances_disabled[]                                 = $wf_instances[$instance_id]['server']['name'];
		$wf_instances[$instance_id]['server']['enable_squid'] = "off";
		$instance_pid_file                                    = "{$g['varrun_path']}/squid/squid{$instance_id}.pid";
		if (isvalidpid($instance_pid_file)) {
			sigkillbypid($instance_pid_file, "TERM");
		}
		if ($ref_instance_id >= 0) {
			break;
		}
	}
	write_config(sprintf(dgettext("BluePexWebFilter", "WebFilter disabled for instance: '%s'!"), implode(", ", $instances_disabled)));
}

?>
