<?php
/*
 * service-utils.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2005-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2022 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2005-2006 Colin Smith (ethethlay@gmail.com)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

function return_swap_permission() {
	if (intval(trim(shell_exec("mount -v | grep -i \" on / \" | awk -F'(' '{print $2}' | awk -F',' '{print $1}' | grep zfs | wc -l"))) != 0) {
		return true;
	} else {
		return false;
	}
}

function get_services_bp() {
	global $config;

	/*
	 * Add services that are in the base.
	 */
	 $wf = $config['system']['webfilter'];
	 if ($wf['webfilter']['config'][0]['enable_squid']) {
		$pconfig = array();
		$pconfig['name'] = "webfilter";
		$pconfig['description'] = gettext("WF Proxy Server");
		$pconfig['status'] = 'stop';
	 	$pconfig['check'] = 'false';
		$services[] = $pconfig;
	 }

	 if ($wf['webfilter']['config'][0]['enable_squid'] && $wf['squidantivirus']['config'][0]['enable']) {
		$pconfig = array();
		$pconfig['name'] = "antivirus";
		$pconfig['description'] = gettext("Antivirus");
		$pconfig['status'] = 'stop';
	 	$pconfig['check'] = 'false';
		$services[] = $pconfig;
	 }

	 $pconfig = array();
	 $pconfig['name'] = "firewallapp";
	 $pconfig['description'] = gettext("FirewallApp");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "syslogd";
	 $pconfig['description'] = gettext("System Logger Daemon");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "wfrotated";
	 $pconfig['description'] = gettext("LOGS Rotation");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "mysql";
	 $pconfig['description'] = gettext("Database server");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "redis-server";
	 $pconfig['description'] = gettext("Redis Database server");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "bpmonitor";
	 $pconfig['description'] = gettext("Communication Agent with the Suite");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 if (return_swap_permission()) {
		$pconfig = array();
		$pconfig['name'] = "swapfile_upgrade";
		$pconfig['description'] = gettext("Upgrade SwapFile for tunning");
		$pconfig['status'] = 'stop';
		$pconfig['check'] = 'false';
		$services[] = $pconfig;
	}

	 $pconfig = array();
	 #$pconfig['name'] = "samba";
	 $pconfig['name'] = "smbd";
	 $pconfig['description'] = gettext("File Sharing Service");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "active-protection";
	 $pconfig['description'] = gettext("Active Protection");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "nginx";
	 $pconfig['description'] = gettext("Nginx PHP-FPM");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	 $pconfig = array();
	 $pconfig['name'] = "dnsprotection";
	 $pconfig['description'] = gettext("DNS Protection Service");
	 $pconfig['status'] = 'stop';
	 $pconfig['check'] = 'false';
	 $services[] = $pconfig;

	return $services;
}

function getInterfacesInGatewaysWithNoExceptionsNew() {

	global $g, $config;

	init_config_arr(array('installedpackages', 'suricata', 'rule'));
	init_config_arr(array('gateways', 'gateway_item'));
	$a_gtw = &$config['gateways']['gateway_item'];
	$a_gateways = return_gateways_array(true, false, true, true);

	$all_gtw = [];
	if (file_exists('/etc/if_ex_wan.conf')) {
		$if_ex_wan = explode(",",file_get_contents("/etc/if_ex_wan.conf"));
		foreach($a_gateways as $gtw_rules) {
			$if = get_real_interface($gtw_rules['interface']);
			if (!in_array($if, $if_ex_wan,true)) {
				$all_gtw[] = $if;
			}
		}
	}

	if (file_exists("/etc/if_ex_lan.conf")) {
		$if_ex_lan = explode(",",file_get_contents("/etc/if_ex_lan.conf"));
		foreach($if_ex_lan as $if_ex_lan_now) {
			$all_gtw[] = $if_ex_lan_now;
		}
	}

	return array_unique($all_gtw);
}

function getInterfaceNewAcp2() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$all_gtw = getInterfacesInGatewaysWithNoExceptionsNew();

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$ret = 0;

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    //if ($suricatacfg['interface'] === 'wan') {
		    if (in_array($if, $all_gtw,true)) {
					$ret++;
			}
		}

	}

	return $ret;

}

function getInterfaceNewFapp2() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$all_gtw = getInterfacesInGatewaysWithNoExceptionsNew();

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$ret = 0;

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    //if ($suricatacfg['interface'] != 'wan') {
		    if (!in_array($if, $all_gtw,true)) {
					$ret++;
			}
		}

	}

	return $ret;

}

function getStatusNewFapp2() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$all_gtw = getInterfacesInGatewaysWithNoExceptionsNew();

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$ret = 0;

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    //if ($suricatacfg['interface'] != 'wan') {
		    if (!in_array($if, $all_gtw,true)) {

		    	if (intval(trim(shell_exec("ps aux | grep suricata_{$uuid}_{$if} | grep -v ps | grep -v grep | wc -l"))) > 0) {
		    		$ret++;
				}

		    }
		}

	}

	return $ret;

}

function getStatusNewAcp2() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$all_gtw = getInterfacesInGatewaysWithNoExceptionsNew();

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$ret = 0;

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    //if ($suricatacfg['interface'] === 'wan') {
		    if (in_array($if, $all_gtw,true)) {

		    	if (intval(trim(shell_exec("ps aux | grep suricata_{$uuid}_{$if} | grep -v ps | grep -v grep | wc -l"))) > 0) {
		    		$ret++;
				}

		    }
		}

	}

	return $ret;

}

?>
