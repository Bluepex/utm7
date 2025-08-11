<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Marcos V. Claudiano <marcos.claudiano@bluepex.com>, 20
 *
 * ====================================================================
 *
 */

require_once("bp_auditing.inc");

//This function is apart in /etc/inc/filter.inc
function getInterfacesInGatewaysWithNoExceptions() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));
	init_config_arr(array('gateways', 'gateway_item'));
	$a_gtw = &$config['gateways']['gateway_item'];
	$a_gateways = return_gateways_array(true, false, true, true);
	$gtw_exceptions = [];
	if (file_exists('/etc/if_ex_wan.conf')) {
		$gtw_exceptions = array_unique(array_filter(explode(",",file_get_contents("/etc/if_ex_wan.conf"))));
	}

	$all_gtw = [];

	foreach($a_gateways as $gtw_rules) {
		$gtw_interface = get_real_interface($gtw_rules['interface']);
		if (!in_array($gtw_interface, $gtw_exceptions)) {
			$all_gtw[] = $gtw_interface;
		}
	}

	if (file_exists("/etc/if_ex_lan.conf")) {
		$if_ex_lan = explode(",",file_get_contents("/etc/if_ex_lan.conf"));
		foreach($if_ex_lan as $if_ex_lan_now) {
			$all_gtw[] = $if_ex_lan_now;
		}
	}
	
	return array_unique(array_filter($all_gtw));

}

function get_heuristc_interfaces() {

	global $config;

	$heuristic_interfaces = [];
	if (isset($config['installedpackages']['suricata']['rule'])) {
		foreach($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			if ($suricatacfg['ips_mode'] == "ips_mode_inline") {
				$heuristic_interfaces[] = get_real_interface($suricatacfg['interface']);
			}
		}
	}

	return array_unique(array_filter($heuristic_interfaces));
}


function load_settings_start($changeDefaultInterface = "", $changeDefaultIpsMode = "") {
	global $g, $config, $rebuild_rules, $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg, $suricataglob, $a_rule, $id, $ruledir, $rulefile, $rules_map, $alertsid, $dropsid, $passid, $pconfig;

	$id = 0;

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id1 = 0; $id1 <= count($a_rule)-1; $id1++) {

		$if_real = get_real_interface($a_rule[$id1]['interface']);

		$suricata_uuid = $a_rule[$id1]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    $id++;
		}

	}

	//file_put_contents("{$g['tmp_path']}/teste_id_fapp",$id);die;


	if (empty($suricataglob['rule'][$id]['uuid'])) {
		$pconfig['uuid'] = suricata_generate_id();
		$rebuild_rules = true;

		$suricata_uuid = $pconfig['uuid'];

		$natent = $a_rule[$id];

		if (isset($changeDefaultInterface) && !empty($changeDefaultInterface)) {
			$natent['interface'] = strtolower(explode("||", $changeDefaultInterface)[0]);
		} else {
			$natent['interface'] = 'lan';
		}

		if (isset($changeDefaultInterface) && !empty($changeDefaultInterface)) {
			$natent['enable'] = 'on';
		} else {
			$natent['enable'] = $_POST['enable'] ? 'on' : 'off';
		}

		$natent['uuid'] = $suricata_uuid;

		if (isset($changeDefaultInterface) && !empty($changeDefaultInterface)) {
			$natent['descr'] = strtolower(explode("||", $changeDefaultInterface)[1]);
		} else {
			$natent['descr'] = 'LAN';
		}

		$if_real_interface = get_real_interface($natent['interface']);
		
		$natent['max_pcap_log_size'] = 32;
		$natent['max_pcap_log_files'] = 1000;
		$natent['append_stats_log'] = 'off';
		$natent['stats_upd_interval'] = "10";
		$natent['enable_http_log'] = 'on';
		$natent['append_http_log'] = 'on';
		$natent['enable_tls_log'] = 'on';
		$natent['enable_tls_store'] = 'off';
		$natent['http_log_extended'] = 'on';
		$natent['tls_log_extended'] = 'on';
		$natent['enable_pcap_log'] = 'off';
		$natent['enable_json_file_log'] = 'off';
		$natent['append_json_file_log'] = 'on';
		$natent['enable_tracked_files_magic'] = 'off';
		$natent['tracked_files_hash'] = 'none';
		$natent['enable_file_store'] = 'on';
		$natent['enable_eve_log'] = 'on';
		$natent['max_pending_packets'] = 1024;
		$natent['detect_eng_profile'] = 'medium';
		$natent['mpm_algo'] = 'auto';
		$natent['sgh_mpm_context'] = 'auto';
		$natent['inspect_recursion_limit'] = '3000';
		$natent['blockoffenders'] = 'on';
		#$natent['ips_mode'] = 'ips_mode_inline';
		if (!empty($changeDefaultIpsMode)) {
			if ($changeDefaultIpsMode == "ips_mode_legacy") {
				$natent['ips_mode'] = 'ips_mode_legacy';
			} elseif ($changeDefaultIpsMode == "ips_mode_inline") {
				$natent['ips_mode'] = 'ips_mode_inline';
			} else {
				$natent['ips_mode'] = 'ips_mode_legacy';
			}
		} else {
			$natent['ips_mode'] = 'ips_mode_legacy';
		}
		#$natent['ips_mode'] = 'ips_mode_legacy';
		$natent['blockoffenderskill'] = 'on';
		$natent['block_drops_only'] = 'on';
		$natent['blockoffendersip'] = 'dst';
		$natent['passlistname'] = 'default';
		$natent['homelistname'] = 'default';
		$natent['externallistname'] = 'default';
		$natent['suppresslistname'] = 'default';
		$natent['alertsystemlog'] = 'off';
		$natent['alertsystemlog_facility'] = 'local1';
		$natent['alertsystemlog_priority'] = 'notice';
		$natent['enable_dns_log'] = 'off';
		$natent['append_dns_log'] = 'on';
		#$natent['runmode'] = 'workers';
		$natent['runmode'] = 'autofp';
		$natent['detect_eng_profile'] = 'low';
		$natent['eve_output_type'] = 'file';
		$natent['eve_log_alerts'] = 'on';
		$natent['eve_log_alerts_payload'] = 'on';
		$natent['eve_log_alerts_packet'] = 'on';
		$natent['eve_log_alerts_http'] = 'on';
		$natent['eve_log_alerts_ssh'] = 'on';
		$natent['eve_log_alerts_smtp'] = 'on';
		$natent['eve_log_alerts_dnp3'] = 'on';
		$natent['eve_log_alerts_xff'] = 'off';
		$natent['eve_log_alerts_xff_mode'] = 'extra-data';
		$natent['eve_log_alerts_xff_deployment'] = 'reverse';
		$natent['eve_log_alerts_xff_header'] = 'X-Forwarded-For';
		$natent['eve_log_http'] = 'on';
		$natent['eve_log_dns'] = 'on';
		$natent['eve_log_tls'] = 'on';
		$natent['eve_log_files'] = 'on';
		$natent['eve_log_ssh'] = 'on';
		$natent['eve_log_smtp'] = 'on';
		$natent['eve_log_stats'] = 'on';
		$natent['eve_log_flow'] = 'on';
		$natent['eve_log_netflow'] = 'on';		
		$natent['eve_log_stats_totals'] = 'on';
		$natent['eve_log_stats_deltas'] = 'off';
		$natent['eve_log_stats_threads'] = 'off';
		$natent['eve_log_http_extended'] = 'on';
		$natent['eve_log_tls_extended'] = 'on';
		$natent['eve_log_smtp_extended'] = 'on';
		$natent['eve_log_http_extended_headers'] = 'accept,accept-charset,accept-datetime,accept-encoding,accept-language,accept-range,age,allow,authorization,cache-control,connection,content-encoding,co
ntent-language,content-length,content-location,content-md5,content-range,content-type,cookie,date,dnt,etags,from,last-modified,link,location,max-forwards,origin,pragma,proxy-authenticate,proxy-authorization,rang
e,referrer,refresh,retry-after,server,set-cookie,te,trailer,transfer-encoding,upgrade,vary,via,warning,www-authenticate,x-authenticated-user,x-flash-version,x-forwarded-proto,x-requested-with';
		$natent['eve_log_smtp_extended_fields'] = 'bcc,received,reply-to,x-mailer,x-originating-ip';
		$natent['eve_log_files_magic'] = 'off';
		$natent['eve_log_files_hash'] = 'none';
		$natent['eve_log_drop'] = 'on';
		$natent['delayed_detect'] = 'off';
		$natent['intf_promisc_mode'] = 'off';
		$natent['configpassthru'] = '';

		$natent['eve_redis_server'] = '127.0.0.1';
		$natent['eve_redis_port'] = '6379';
		$natent['eve_redis_mode'] = 'list';
		#$natent['eve_redis_key'] = 'suricata';
		$natent['eve_redis_key'] = "suricata{$if_real_interface}{$suricata_uuid}";

		// Adding new interface, so set interface configuration parameter defaults
		$natent['ip_max_frags'] = "65535";
		$natent['ip_frag_timeout'] = "60";
		$natent['frag_memcap'] = '33554432';
		$natent['ip_max_trackers'] = '65535';
		$natent['frag_hash_size'] = '65536';

		$model = intval(substr(trim(file_get_contents("/etc/model")),-4));
		if ($model >= 3000) {
			$natent['flow_memcap'] = '335544320';
		} else {
			$natent['flow_memcap'] = '33554432';
		}

		$natent['flow_prealloc'] = '10000';
		$natent['flow_hash_size'] = '65536';
		$natent['flow_emerg_recovery'] = '30';
		$natent['flow_prune'] = '5';

		$natent['flow_tcp_new_timeout'] = '60';
		$natent['flow_tcp_established_timeout'] = '3600';
		$natent['flow_tcp_closed_timeout'] = '120';
		$natent['flow_tcp_emerg_new_timeout'] = '10';
		$natent['flow_tcp_emerg_established_timeout'] = '300';
		$natent['flow_tcp_emerg_closed_timeout'] = '20';

		$natent['flow_udp_new_timeout'] = '30';
		$natent['flow_udp_established_timeout'] = '300';
		$natent['flow_udp_emerg_new_timeout'] = '10';
		$natent['flow_udp_emerg_established_timeout'] = '100';

		$natent['flow_icmp_new_timeout'] = '30';
		$natent['flow_icmp_established_timeout'] = '300';
		$natent['flow_icmp_emerg_new_timeout'] = '10';
		$natent['flow_icmp_emerg_established_timeout'] = '100';

		//$natent['config']['enable_log_mgmt'] = "on";
		//$natent['config']['clearlogs'] = "on";
		//$natent['config']['suricataloglimit'] = "on";
		//$natent['config']['suricataloglimitsize'] = round(exec('/bin/df -k /var | /usr/bin/grep -v "Filesystem" | /usr/bin/awk \'{print $4}\'') * .1 / 1024);
		
		$natent['config']['alert_log_retention'] = "336";
		$natent['config']['block_log_retention'] = "336";
		$natent['config']['files_json_log_retention'] = "168";
		$natent['config']['http_log_retention'] = "168";
		$natent['config']['stats_log_retention'] = "168";
		$natent['config']['tls_log_retention'] = "336";
		$natent['config']['u2_archive_log_retention'] = "168";
		$natent['config']['file_store_retention'] = "168";
		$natent['config']['tls_certs_store_retention'] = "168";
		$natent['config']['eve_log_retention'] = "168";
		$natent['config']['sid_changes_log_retention'] = "336";
		$natent['config']['alert_log_limit_size'] = "500";
		$natent['config']['block_log_limit_size'] = "500";
		$natent['config']['files_json_log_limit_size'] = "1000";
		$natent['config']['http_log_limit_size'] = "1000";
		$natent['config']['stats_log_limit_size'] = "500";
		$natent['config']['tls_log_limit_size'] = "500";
		$natent['config']['unified2_log_limit'] = "32";
		$natent['config']['eve_log_limit_size'] = "5000";
		$natent['config']['sid_changes_log_limit_size'] = "250";

		$model = intval(substr(trim(file_get_contents("/etc/model")),-4));
		if ($model >= 3000) {
			$natent['stream_memcap'] = '1312177280';
		} else {
			$natent['stream_memcap'] = '131217728';
		}

		$natent['stream_prealloc_sessions'] = '32768';
		$natent['reassembly_memcap'] = '131217728';
		$natent['reassembly_depth'] = '1048576';
		$natent['reassembly_to_server_chunk'] = '2560';
		$natent['reassembly_to_client_chunk'] = '2560';
		$natent['max_synack_queued'] = '5';
		$natent['enable_midstream_sessions'] = 'off';
		$natent['enable_async_sessions'] = 'off';
		$natent['delayed_detect'] = 'off';
		$natent['intf_promisc_mode'] = 'off';
		$natent['intf_snaplen'] = '1518';

		$natent['asn1_max_frames'] = '256';
		$natent['dns_global_memcap'] = "16777216";
		$natent['dns_state_memcap'] = "524288";
		$natent['dns_request_flood_limit'] = "500";
		$natent['http_parser_memcap'] = "131217728";
		$natent['dns_parser_udp'] = "yes";
		$natent['dns_parser_tcp'] = "yes";
		$natent['http_parser'] = "yes";
		$natent['tls_parser'] = "yes";
		$natent['smtp_parser'] = "yes";
		$natent['imap_parser'] = "detection-only";
		$natent['ssh_parser'] = "yes";
		$natent['ftp_parser'] = "yes";
		$natent['dcerpc_parser'] = "yes";
		$natent['smb_parser'] = "yes";
		$natent['msn_parser'] = "detection-only";

		$natent['enable_iprep'] = "off";
		$natent['host_memcap'] = "33554432";
		$natent['host_hash_size'] = "4096";
		$natent['host_prealloc'] = "1000";

		$natent['homenet_text'] = '...Loading...';
		$natent['externalnet_text'] = '...Loading...';
		$natent['passlist_text'] = '...Loading...';
		$natent['suppresslistname'] = 'default';
		$natent['suppresslist_text'] = '...Loading...';

		$default = array( "name" => "default", "bind_to" => "all", "policy" => "bsd" );

		if (!is_array($natent['host_os_policy']['item']))
			$natent['host_os_policy']['item'] = array();

		$natent['host_os_policy']['item'][] = $default;

		$default = array( "name" => "default", "bind_to" => "all", "personality" => "IDS", "request-body-limit" => 0, "response-body-limit" => 0, "double-decode-path" => "no", "double-decode-query"
 => "no", "uri-include-all" => "no" );

		if (!is_array($natent['libhtp_policy']['item']))
			$natent['libhtp_policy']['item'] = array();

		$natent['libhtp_policy']['item'][] = $default;

		// Enable the basic default rules for the interface
		$dh  = opendir("{$suricata_rules_dir}");

		$rulesetsfile = "";
		while (false !== ($filename = readdir($dh))) {
			if (substr($filename, -5) != "rules")
				continue;

			$rulesetsfile .= basename($filename) . "||";
		}

		$natent['rulesets'] = rtrim($rulesetsfile, "||");

		// Adding a new interface, so set flag to build new rules
		$rebuild_rules = true;

		// Add the new interface configuration to the [rule] array in config
		$a_rule[] = $natent;

		bp_write_report_db("report_0008_acp_fapp", "FirewallApp|" . convert_friendly_interface_to_friendly_descr($natent['interface']) . "|{$if_real_interface}{$suricata_uuid}");

		// Save configuration changes
		write_config("Suricata pkg: modified interface configuration for " . convert_friendly_interface_to_friendly_descr($natent['interface']));

		// Update suricata.conf and suricata.sh files for this interface
		sync_suricata_package_config();
	}
	// INICIALIZA O SERVICO DO SURICATA
	$suricatacfg = $config['installedpackages']['suricata']['rule'][$id];

	$if_real = get_real_interface($suricatacfg['interface']);
	$if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);

	// Suricata can take several seconds to startup, so to
	// make the GUI more responsive, startup commands are
	// executed as a background process.  The commands
	// are written to a PHP file in the 'tmp_path' which
	// is executed by a PHP command line session launched
	// as a background task.

	// Create steps for the background task to start Suricata.
	// These commands will be handed off to a CLI PHP session
	// for background execution as a self-deleting PHP file.
	$start_lck_file = "{$g['varrun_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_starting.lck";
	file_put_contents("/var/run/suricata_{$if_real}{$suricatacfg['uuid']}_starting", '');

	$suricata_start_cmd1 = <<<EOD
	<?php
	require_once('/usr/local/pkg/suricata/suricata.inc');
	require_once('service-utils.inc');
	global \$g, \$rebuild_rules, \$config;
	\$suricatacfg = \$config['installedpackages']['suricata']['rule'][{$id}];
	\$rebuild_rules = true;
	touch('{$start_lck_file}');
	conf_mount_rw();
	sync_suricata_package_config();
	conf_mount_ro();
	\$rebuild_rules = false;
	suricata_start(\$suricatacfg, '{$if_real}');
	unlink_if_exists('{$start_lck_file}');
	unlink(__FILE__);
	?>
EOD;


	file_put_contents("{$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php", $suricata_start_cmd1);
}

function load_settings_start_acp($changeDefaultInterface = "", $changeDefaultIpsMode = "") {
	global $g, $config, $rebuild_rules, $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg, $suricataglob, $a_rule, $id, $ruledir, $rulefile, $rules_map, $alertsid, $dropsid, $passid, $pconfig;

	$id = 0;

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id1 = 0; $id1 <= count($a_rule)-1; $id1++) {

		$if_real = get_real_interface($a_rule[$id1]['interface']);

		$suricata_uuid = $a_rule[$id1]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    $id++;
		}

	}

	if (empty($suricataglob['rule'][$id]['uuid'])) {
		$pconfig['uuid'] = suricata_generate_id();
		$rebuild_rules = true;

		$suricata_uuid = $pconfig['uuid'];

		$natent = $a_rule[$id];

		if (!empty($changeDefaultInterface)) {
			$natent['interface'] = strtolower(explode("||", $changeDefaultInterface)[0]);
		} else {
			$natent['interface'] = 'wan';
		}

		$natent['enable'] = 'on';
		$natent['uuid'] = $suricata_uuid;

		if (!empty($changeDefaultInterface)) {
			$natent['descr'] = strtolower(explode("||", $changeDefaultInterface)[1]);
		} else {
			$natent['descr'] = 'WAN';
		}

		$if_real_interface = get_real_interface($natent['interface']);

		$natent['max_pcap_log_size'] = 32;
		$natent['max_pcap_log_files'] = 1000;
		$natent['append_stats_log'] = 'off';
		$natent['stats_upd_interval'] = "10";
		$natent['enable_http_log'] = 'on';
		$natent['append_http_log'] = 'on';
		$natent['enable_tls_log'] = 'on';
		$natent['enable_tls_store'] = 'off';
		$natent['http_log_extended'] = 'on';
		$natent['tls_log_extended'] = 'on';
		$natent['enable_pcap_log'] = 'off';
		$natent['enable_json_file_log'] = 'off';
		$natent['append_json_file_log'] = 'on';
		$natent['enable_tracked_files_magic'] = 'off';
		$natent['tracked_files_hash'] = 'none';
		$natent['enable_file_store'] = 'on';
		$natent['enable_eve_log'] = 'on';
		$natent['max_pending_packets'] = 1024;
		$natent['detect_eng_profile'] = 'medium';
		$natent['mpm_algo'] = 'auto';
		$natent['sgh_mpm_context'] = 'auto';
		$natent['inspect_recursion_limit'] = '3000';
		$natent['blockoffenders'] = 'on';
		#$natent['ips_mode'] = 'ips_mode_inline';
		if (!empty($changeDefaultIpsMode)) {
			if ($changeDefaultIpsMode == "ips_mode_legacy") {
				$natent['ips_mode'] = 'ips_mode_legacy';
			} elseif ($changeDefaultIpsMode == "ips_mode_inline") {
				$natent['ips_mode'] = 'ips_mode_inline';
			} else {
				$natent['ips_mode'] = 'ips_mode_legacy';
			}
		} else {
			$natent['ips_mode'] = 'ips_mode_legacy';
		}
		#$natent['ips_mode'] = 'ips_mode_legacy';
		$natent['blockoffenderskill'] = 'on';
		$natent['block_drops_only'] = 'on';
		$natent['blockoffendersip'] = 'both';
		$natent['passlistname'] = 'default';
		$natent['homelistname'] = 'default';
		$natent['externallistname'] = 'default';
		$natent['suppresslistname'] = 'default';
		$natent['alertsystemlog'] = 'off';
		$natent['alertsystemlog_facility'] = 'local1';
		$natent['alertsystemlog_priority'] = 'notice';
		$natent['enable_dns_log'] = 'off';
		$natent['append_dns_log'] = 'on';
		#$natent['runmode'] = 'workers';
		$natent['runmode'] = 'autofp';
		$natent['detect_eng_profile'] = 'low';
		$natent['eve_output_type'] = 'file';
		$natent['eve_log_alerts'] = 'on';
		$natent['eve_log_alerts_payload'] = 'on';
		$natent['eve_log_alerts_packet'] = 'on';
		$natent['eve_log_alerts_http'] = 'on';
		$natent['eve_log_alerts_ssh'] = 'on';
		$natent['eve_log_alerts_smtp'] = 'on';
		$natent['eve_log_alerts_dnp3'] = 'on';
		$natent['eve_log_alerts_xff'] = 'off';
		$natent['eve_log_alerts_xff_mode'] = 'extra-data';
		$natent['eve_log_alerts_xff_deployment'] = 'reverse';
		$natent['eve_log_alerts_xff_header'] = 'X-Forwarded-For';
		$natent['eve_log_http'] = 'on';
		$natent['eve_log_dns'] = 'on';
		$natent['eve_log_tls'] = 'on';
		$natent['eve_log_files'] = 'on';
		$natent['eve_log_ssh'] = 'on';
		$natent['eve_log_smtp'] = 'on';
		$natent['eve_log_stats'] = 'on';
		$natent['eve_log_flow'] = 'on';
		$natent['eve_log_netflow'] = 'on';
		$natent['eve_log_stats_totals'] = 'on';
		$natent['eve_log_stats_deltas'] = 'off';
		$natent['eve_log_stats_threads'] = 'off';
		$natent['eve_log_http_extended'] = 'on';
		$natent['eve_log_tls_extended'] = 'on';
		$natent['eve_log_smtp_extended'] = 'on';
		$natent['eve_log_http_extended_headers'] = 'accept,accept-charset,accept-datetime,accept-encoding,accept-language,accept-range,age,allow,authorization,cache-control,connection,content-encoding,co
ntent-language,content-length,content-location,content-md5,content-range,content-type,cookie,date,dnt,etags,from,last-modified,link,location,max-forwards,origin,pragma,proxy-authenticate,proxy-authorization,rang
e,referrer,refresh,retry-after,server,set-cookie,te,trailer,transfer-encoding,upgrade,vary,via,warning,www-authenticate,x-authenticated-user,x-flash-version,x-forwarded-proto,x-requested-with';
		$natent['eve_log_smtp_extended_fields'] = 'bcc,received,reply-to,x-mailer,x-originating-ip';
		$natent['eve_log_files_magic'] = 'off';
		$natent['eve_log_files_hash'] = 'none';
		$natent['eve_log_drop'] = 'on';
		$natent['delayed_detect'] = 'off';
		$natent['intf_promisc_mode'] = 'off';
		$natent['configpassthru'] = '';

		$natent['eve_redis_server'] = '127.0.0.1';
		$natent['eve_redis_port'] = '6379';
		$natent['eve_redis_mode'] = 'list';
		#$natent['eve_redis_key'] = 'suricata';
		$natent['eve_redis_key'] = "suricata{$if_real_interface}{$suricata_uuid}";

		// Adding new interface, so set interface configuration parameter defaults
		$natent['ip_max_frags'] = "65535";
		$natent['ip_frag_timeout'] = "60";
		$natent['frag_memcap'] = '33554432';
		$natent['ip_max_trackers'] = '65535';
		$natent['frag_hash_size'] = '65536';

		$model = intval(substr(trim(file_get_contents("/etc/model")),-4));
		if ($model >= 3000) {
			$natent['flow_memcap'] = '335544320';
		} else {
			$natent['flow_memcap'] = '33554432';
		}
		
		$natent['flow_prealloc'] = '10000';
		$natent['flow_hash_size'] = '65536';
		$natent['flow_emerg_recovery'] = '30';
		$natent['flow_prune'] = '5';

		$natent['flow_tcp_new_timeout'] = '60';
		$natent['flow_tcp_established_timeout'] = '3600';
		$natent['flow_tcp_closed_timeout'] = '120';
		$natent['flow_tcp_emerg_new_timeout'] = '10';
		$natent['flow_tcp_emerg_established_timeout'] = '300';
		$natent['flow_tcp_emerg_closed_timeout'] = '20';

		$natent['flow_udp_new_timeout'] = '30';
		$natent['flow_udp_established_timeout'] = '300';
		$natent['flow_udp_emerg_new_timeout'] = '10';
		$natent['flow_udp_emerg_established_timeout'] = '100';

		$natent['flow_icmp_new_timeout'] = '30';
		$natent['flow_icmp_established_timeout'] = '300';
		$natent['flow_icmp_emerg_new_timeout'] = '10';
		$natent['flow_icmp_emerg_established_timeout'] = '100';

		//$natent['config']['enable_log_mgmt'] = "on";
		//$natent['config']['clearlogs'] = "on";
		//$natent['config']['suricataloglimit'] = "on";
		//$natent['config']['suricataloglimitsize'] = round(exec('/bin/df -k /var | /usr/bin/grep -v "Filesystem" | /usr/bin/awk \'{print $4}\'') * .1 / 1024);
		
		$natent['config']['alert_log_retention'] = "336";
		$natent['config']['block_log_retention'] = "336";
		$natent['config']['files_json_log_retention'] = "168";
		$natent['config']['http_log_retention'] = "168";
		$natent['config']['stats_log_retention'] = "168";
		$natent['config']['tls_log_retention'] = "336";
		$natent['config']['u2_archive_log_retention'] = "168";
		$natent['config']['file_store_retention'] = "168";
		$natent['config']['tls_certs_store_retention'] = "168";
		$natent['config']['eve_log_retention'] = "168";
		$natent['config']['sid_changes_log_retention'] = "336";
		$natent['config']['alert_log_limit_size'] = "500";
		$natent['config']['block_log_limit_size'] = "500";
		$natent['config']['files_json_log_limit_size'] = "1000";
		$natent['config']['http_log_limit_size'] = "1000";
		$natent['config']['stats_log_limit_size'] = "500";
		$natent['config']['tls_log_limit_size'] = "500";
		$natent['config']['unified2_log_limit'] = "32";
		$natent['config']['eve_log_limit_size'] = "5000";
		$natent['config']['sid_changes_log_limit_size'] = "250";

		$model = intval(substr(trim(file_get_contents("/etc/model")),-4));
		if ($model >= 3000) {
			$natent['stream_memcap'] = '1312177280';
		} else {
			$natent['stream_memcap'] = '131217728';
		}

		$natent['stream_prealloc_sessions'] = '32768';
		$natent['reassembly_memcap'] = '131217728';
		$natent['reassembly_depth'] = '1048576';
		$natent['reassembly_to_server_chunk'] = '2560';
		$natent['reassembly_to_client_chunk'] = '2560';
		$natent['max_synack_queued'] = '5';
		$natent['enable_midstream_sessions'] = 'off';
		$natent['enable_async_sessions'] = 'off';
		$natent['delayed_detect'] = 'off';
		$natent['intf_promisc_mode'] = 'off';
		$natent['intf_snaplen'] = '1518';

		$natent['asn1_max_frames'] = '256';
		$natent['dns_global_memcap'] = "16777216";
		$natent['dns_state_memcap'] = "524288";
		$natent['dns_request_flood_limit'] = "500";
		$natent['http_parser_memcap'] = "131217728";
		$natent['dns_parser_udp'] = "yes";
		$natent['dns_parser_tcp'] = "yes";
		$natent['http_parser'] = "yes";
		$natent['tls_parser'] = "yes";
		$natent['smtp_parser'] = "yes";
		$natent['imap_parser'] = "detection-only";
		$natent['ssh_parser'] = "yes";
		$natent['ftp_parser'] = "yes";
		$natent['dcerpc_parser'] = "yes";
		$natent['smb_parser'] = "yes";
		$natent['msn_parser'] = "detection-only";

		$natent['enable_iprep'] = "off";
		$natent['host_memcap'] = "33554432";
		$natent['host_hash_size'] = "4096";
		$natent['host_prealloc'] = "1000";

		$natent['homenet_text'] = '...Loading...';
		$natent['externalnet_text'] = '...Loading...';
		$natent['passlist_text'] = '...Loading...';
		$natent['suppresslistname'] = 'default';
		$natent['suppresslist_text'] = '...Loading...';

		$default = array( "name" => "default", "bind_to" => "all", "policy" => "bsd" );

		if (!is_array($natent['host_os_policy']['item']))
			$natent['host_os_policy']['item'] = array();

		$natent['host_os_policy']['item'][] = $default;

		$default = array( "name" => "default", "bind_to" => "all", "personality" => "IDS", "request-body-limit" => 0, "response-body-limit" => 0, "double-decode-path" => "no", "double-decode-query"
 => "no", "uri-include-all" => "no" );

		if (!is_array($natent['libhtp_policy']['item']))
			$natent['libhtp_policy']['item'] = array();

		$natent['libhtp_policy']['item'][] = $default;

		// Enable the basic default rules for the interface
		$dh  = opendir("/usr/local/share/suricata/rules_acp");

		$rulesetsfile = "";
		while (false !== ($filename = readdir($dh))) {
			if (count(explode(".",$filename)) == 2) {
				if (substr($filename, -5) != "rules")
				continue;

				$rulesetsfile .= basename($filename) . "||";
			}
			
		}

		$natent['rulesets'] = rtrim($rulesetsfile, "||");

		// Adding a new interface, so set flag to build new rules
		$rebuild_rules = true;

		// Add the new interface configuration to the [rule] array in config
		$a_rule[] = $natent;

		bp_write_report_db("report_0008_acp_fapp", "Active Protection|" . convert_friendly_interface_to_friendly_descr($natent['interface']) . "|{$if_real_interface}{$suricata_uuid}");

		// Save configuration changes
		write_config("Suricata pkg: modified interface configuration for " . convert_friendly_interface_to_friendly_descr($natent['interface']));

		// Update suricata.conf and suricata.sh files for this interface
		sync_suricata_package_config();
	}

}

function create_versions_cache_file() {
	$categories = do_webservice_request("categories", "group-with-categories");
	if (!isset($categories->data->group_categories) || empty($categories->data->group_categories)) {
		log_error(gettext("OneClick warning - Could not install cache file of the categories!"));
		return false;
	}
	file_put_contents(CACHE_FILE, json_encode($categories->data->group_categories));
	if (file_exists(CACHE_FILE) && filesize(CACHE_FILE) > 0) {
		log_error(gettext("OneClick - Cache file has been installed successfully!"));
		return true;
	}
	return false;
}

function install_version_lists() {
	if (!create_versions_cache_file()) {
		log_error(gettext("OneClick warning - Could not install the lists. Cache file not exists!"));
		return false;
	}
	if (is_dir(LISTS_PATH)) {
		array_map('unlink', glob(LISTS_PATH . "/*"));
	} else {
		mkdir(LISTS_PATH, 0755);
	}
	if (!is_dir(LISTS_PATH)) {
		log_error(gettext("OneClick warning - Could not create categories lists directory!"));
		return false;
	}
	$group_categories = json_decode(file_get_contents(CACHE_FILE));
	foreach ($group_categories as $group) {
		if (empty($group->categories)) {
			continue;
		}
		foreach ($group->categories as $cat) {
			if (!empty($cat->ipaddress)) {
				file_put_contents(LISTS_PATH . "/{$cat->internal_name}.ips", $cat->ipaddress);
			}
			if (!empty($cat->urls)) {
				file_put_contents(LISTS_PATH . "/{$cat->internal_name}.urls", $cat->urls);
			}
			if (!empty($cat->regex)) {
				file_put_contents(LISTS_PATH . "/{$cat->internal_name}.regex", $cat->regex);
			}
		}
	}
	if (count(glob(LISTS_PATH . "/*")) > 0) {
		log_error(gettext("OneClick - Lists of the categories has been installed successfully!"));
		return true;
	}
}

function auto_db_install(){

	require_once('config.inc');
	require_once('util.inc');
	require_once('filter.inc');
	require_once('dataclick_report.inc');
	require_once('interfaces.inc');

	global $g, $config;

	init_config_arr(array('system', 'webfilter', 'nf_reports_settings', 'element0'));
	init_config_arr(array('system', 'webfilter', 'bluepexdataclickagent', 'config'));

	$settings_report = &$config['system']['webfilter']['nf_reports_settings']['element0'];

	// Create webfilter database
	$settings_report['remote_reports'] = "on";
	$settings_report['log_referer'] = "off";
	$settings_report['reports_ip'] = "127.0.0.1";
	$settings_report['reports_port'] = "3306";
	$settings_report['reports_user'] = "webfilter";
	$settings_report['reports_password'] = "webfilter";
	$settings_report['reports_db'] = "webfilter";

	// Create Fapp report
	$settings_report['enable'] = "on";
	$settings_report['interfaces'] = "LAN";
	$settings_report['gen_report_time'] = ":";
	$settings_report['cleanup_db_month'] = "";
	$settings_report['cleanup_db_time'] = ":";

	/*if (function_exists("createEventToAlerts")) {
		createEventToAlerts();
	}
	if (function_exists("createEventToHttp")) {
		createEventToHttp();
	}
	if (function_exists("createEventToHttps")) {
		createEventToHttps();
	}*/

	$settings_dataclick = &$config['system']['webfilter']['bluepexdataclickagent']['config'][0];

	$settings_dataclick['enable'] = "on";
	$settings_dataclick['interfaces'] = "LAN";
	$settings_dataclick['gen_report_time'] = ":";
	$settings_dataclick['cleanup_db_month'] = "";
	$settings_dataclick['cleanup_db_time'] = ":";

	filter_configure();
	write_config("Data in config.xml save success!");

	function postDataSyncronizeTest($data_session = [])
	{
	        global $g, $config;

	        if (!is_array($data_session)) {
	                log_error(dgettext("DataClick", "Could not to put session data to DataClick!"));
	                return;
	        }

	        $protocol = $config['system']['webgui']['protocol']."://";

	        $ip = empty(get_interface_ip("lan")) ? get_interface_ip("wan") : get_interface_ip("lan");
	        if (empty($ip)) {
	                log_error(dgettext("DataClick", "Could not to put session data to DataClick, LAN IP not found!"));
	                return;
	        }

	        $port = $config['system']['webgui']['port'];
	        if ($port == "") {
	                $port = ($config['system']['webgui']['protocol'] == "http") ? 80 : 443;
	        }

	        $url = $protocol . $ip;

	        $data = [
	                "user_session" => serialize($data_session)
	        ];

	        $url_api = $url . "/dataclick-web/utm/test-connection-sync/1";

	        $ch = curl_init();

	        curl_setopt($ch, CURLOPT_PORT, $port);
	        curl_setopt($ch, CURLOPT_URL, $url_api);
	        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	        curl_setopt($ch, CURLOPT_HEADER, false);
	        curl_setopt($ch, CURLOPT_POST, count($data));
	        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

	        curl_exec($ch);

	        curl_close($ch);
	}

	postDataSyncronizeTest();
}

function delete_file_rules_rules_fapp($delete_rules_usullay=false, $delete_rules_fapp=false) {
	$delete_files = [
	    "app-layer-events.rules",
	    "decoder-events.rules",
	    "dnp3-events.rules",
	    "dns-events.rules",
	    "files.rules",
	    "http-events.rules",
	    "http2-events.rules",
	    "ipsec-events.rules",
	    "kerberos-events.rules",
	    "modbus-events.rules",
	    "mqtt-events.rules",
	    "nfs-events.rules",
	    "ntp-events.rules",
	    "smb-events.rules",
	    "smtp-events.rules",
	    "stream-events.rules",
	    "tls-events.rules"
	];
	if ($delete_rules_usullay) {
		$path_files = "/usr/local/share/suricata/rules/";
		foreach($delete_files as $file_now) {
			if (file_exists("{$path_files}{$file_now}")) {
				mwexec("rm -f {$path_files}{$file_now}");
			}
		}
	}
	if ($delete_rules_fapp) {
		$path_files = "/usr/local/share/suricata/rules_fapp/";
		foreach($delete_files as $file_now) {
			if (file_exists("{$path_files}{$file_now}")) {
				mwexec("rm -f {$path_files}{$file_now}");
			}
		}
	}
}

function clean() {

	global $g, $config;

	$raiz = '/usr/local/etc/suricata/';

	//Add line about start interfaces
	//mwexec("cd /usr/local/share/suricata/rules && rm -f app-layer-events.rules && rm -f decoder-events.rules && rm -f dnp3-events.rules && rm -f dns-events.rules && rm -f files.rules && rm -f http-events.rules && rm -f ipsec-events.rules && rm -f kerberos-events.rules && rm -f modbus-events.rules && rm -f nfs-events.rules && rm -f ntp-events.rules && rm -f smb-events.rules && rm -f smtp-events.rules && rm -f stream-events.rules && rm -f tls-events.rules");
	//mwexec("cd /usr/local/share/suricata/rules_fapp && rm -f app-layer-events.rules && rm -f decoder-events.rules && rm -f dnp3-events.rules && rm -f dns-events.rules && rm -f files.rules && rm -f http-events.rules && rm -f ipsec-events.rules && rm -f kerberos-events.rules && rm -f modbus-events.rules && rm -f nfs-events.rules && rm -f ntp-events.rules && rm -f smb-events.rules && rm -f smtp-events.rules && rm -f stream-events.rules && rm -f tls-events.rules");
	delete_file_rules_rules_fapp($delete_rules_usullay=true, $delete_rules_fapp=true);

	// exclui pastas
	if (!is_array($config['installedpackages']['suricata'] ?? null)) {
		$config['installedpackages']['suricata'] = array();
	}

	if (!is_array($config['installedpackages']['suricata']['rule'] ?? null)) {
		$config['installedpackages']['suricata']['rule'] = array();
	}

	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		$config['installedpackages']['suricata']['rule'] = array();
	}

	$a_rule = $config['installedpackages']['suricata']['rule'];

	$suricata_dirs = array();

	foreach (glob("$raiz*",GLOB_ONLYDIR) as $suricata_dir) {
		$data = explode("_",str_replace($raiz, "", $suricata_dir));
		$if_real = $data[2];
		$uuid = $data[1];
		$suricata_dirs[] = "$if_real|$uuid";
	}

	$fapp_ifs = array();

	#foreach ($config['installedpackages']['suricata']['rule'] as $fapp_if_cfg) {
	foreach ($a_rule as $fapp_if_cfg) {
		$fapp_ifs[] = $fapp_if_cfg['uuid'];
	}

	foreach ($suricata_dirs as $pasta_dir) {
		$data = explode("|", $pasta_dir);
		$if_real = $data[0];
		$uuid = $data[1];

		if (!in_array($uuid, $fapp_ifs)) {
			$pasta_del = "suricata_{$if_real}{$uuid}";
			$pasta_del2 = "suricata_{$uuid}_{$if_real}";

			rmdir_recursive("/var/log/suricata/{$pasta_del}");
			rmdir_recursive("/usr/local/etc/suricata/{$pasta_del2}");
			break;
		}
	}

	$raiz = '/var/run/';
	$open = opendir($raiz);

	$arquivo = array();
	$configs = array();

	while($arquivos = readdir($open)){
		if ((substr($arquivos, -3)  === "pid" || substr($arquivos, -3)  === "lck") && substr($arquivos, 0, 9)  === "suricata_") {
			 $arquivo[] = $arquivos;
		}
	}

	// exclui pids
	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		$config['installedpackages']['suricata']['rule'] = array();
	}

	$a_rule = $config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {
		
		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		$arq_conf_pid = "suricata_{$if_real}{$suricata_uuid}.pid";
		$configs[] = $arq_conf_pid;

		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		$arq_conf_lck = "suricata_{$if_real}{$suricata_uuid}_stop.lck";
		$configs[] = $arq_conf_lck;

	}	

	for($i = 0; $i < count($arquivo); $i++){

			$arquivo_dir = $arquivo[$i];

			if(in_array($arquivo_dir, $configs)) {
				continue;
			}

			if(in_array($arquivo_dir, $configs)) {
				continue;
			}

			$arq_conf_pid = "{$g['varrun_path']}/{$arquivo_dir}";
			mwexec("rm -f {$arq_conf_pid}");		

			$arq_conf_lck = "{$g['varrun_path']}/{$arquivo_dir}";
			mwexec("rm -f {$arq_conf_lck}");

	}

	$raiz = '/tmp/';
	$open = opendir($raiz);

	$arquivo = array();
	$configs = array();

	while($arquivos = readdir($open)){
		if ((substr($arquivos, -3)  === "php") && substr($arquivos, 0, 9)  === "suricata_") {
			 $arquivo[] = $arquivos;
		}
		if (substr($arquivos, 0, 22)  === "graphic_virus_suricata") {
			 $arquivo[] = $arquivos;
		}
	}

	//print_r($arquivo);die;

	// exclui php
	if (!is_array($config['installedpackages']['suricata']['rule'])) {
		$config['installedpackages']['suricata']['rule'] = array();
	}

	$a_rule = $config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {
		

		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		$arq_conf_php = "suricata_{$if_real}{$suricata_uuid}_startcmd.php";
		$configs[] = $arq_conf_php;

		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		$graphic_virus = "graphic_virus_suricata{$if_real}{$suricata_uuid}";
		$configs[] = $graphic_virus;

	}	

	//print_r($configs);die;

	for($i = 0; $i < count($arquivo); $i++){

			$arquivo_dir = $arquivo[$i];

			if(in_array($arquivo_dir, $configs)) {
				continue;
			}

			if(in_array($arquivo_dir, $configs)) {
				continue;
			}

			$arq_conf_php= "/tmp/{$arquivo_dir}";
			mwexec("rm -f {$arq_conf_php}");

			$graphic_virus= "/tmp/{$arquivo_dir}";
			mwexec("rm -f {$graphic_virus}");

	}

	//Add line about start interfaces
	//mwexec("cd /usr/local/share/suricata/rules && rm -f app-layer-events.rules && rm -f decoder-events.rules && rm -f dnp3-events.rules && rm -f dns-events.rules && rm -f files.rules && rm -f http-events.rules && rm -f ipsec-events.rules && rm -f kerberos-events.rules && rm -f modbus-events.rules && rm -f nfs-events.rules && rm -f ntp-events.rules && rm -f smb-events.rules && rm -f smtp-events.rules && rm -f stream-events.rules && rm -f tls-events.rules");
	//mwexec("cd /usr/local/share/suricata/rules_fapp && rm -f app-layer-events.rules && rm -f decoder-events.rules && rm -f dnp3-events.rules && rm -f dns-events.rules && rm -f files.rules && rm -f http-events.rules && rm -f ipsec-events.rules && rm -f kerberos-events.rules && rm -f modbus-events.rules && rm -f nfs-events.rules && rm -f ntp-events.rules && rm -f smb-events.rules && rm -f smtp-events.rules && rm -f stream-events.rules && rm -f tls-events.rules");
	delete_file_rules_rules_fapp($delete_rules_usullay=true, $delete_rules_fapp=true);

}

clean();

function disable_and_clean() {
	
	suricata_stop_all_interfaces();

	clean();

}

function enable_all() {
	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	file_put_contents("{$g['varrun_path']}/suricata_start_all.lck", '');

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	print_r($a_rule);die;

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		$lock_del = "{$g['varrun_path']}/suricata_{$if_real}{$suricata_uuid}_stop.lck";

		mwexec("rm -f {$lock_del}");

		global $g, $config;

		/* do nothing if no Suricata interfaces active */
		if (!is_array($config['installedpackages']['suricata']['rule'])) {
			return;
		}

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];
			if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
				continue;
			}
			if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
				continue;
			}
			sleep(2);
			suricata_start($suricatacfg, get_real_interface($suricatacfg['interface']));
			sleep(28);
		}

		//file_put_contents("{$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_start", 'off');
		mwexec_bg('/usr/local/etc/rc.d/wfrotated restart');

	}

}

function control_state_interface_acp($changeDefaultInterface = "", $changeDefaultIpsMode = "") {
	global $g, $suricata_rules_dir, $config, $suricatacfg, $if_real, $if_friendly, $id, $rebuild_rules;

	clean();

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	$status_iface =  getStatusNewAcp();

	$status_iface2 =  getInterfaceNewAcp();

	if (install_rules_lists_acp()) {

	if (($status_iface == 0) && ($status_iface2 == 0)) {
			
			log_error(dgettext("ActiveProtection", "ActiveProtection - Installing the lists of the rules..."));

			load_settings_start_acp($changeDefaultInterface, $changeDefaultIpsMode);

			$rebuild_rules = true;

			/*init_config_arr(array('installedpackages', 'suricata', 'rule'));
			$fapp_ifaces = &$config['installedpackages']['suricata']['rule'];

			foreach($fapp_ifaces as $idx => $fapp_iface) {
				// Enable the basic default rules for the interface
				$dh  = opendir("/usr/local/share/suricata/rules_acp");

				$rulesetsfile = "";
				while (false !== ($filename = readdir($dh))) {
					if (count(explode(".",$filename)) == 2) {
						if (substr($filename, -5) != "rules")
						continue;

						$rulesetsfile .= basename($filename) . "||";
					}
					
				}

				$if = get_real_interface($fapp_ifaces['interface']);

				if (in_array($if, $all_gtw,true)) {

					$config['installedpackages']['suricata']['rule'][$idx]['rulesets'] = rtrim($rulesetsfile, "||");

					// Save configuration changes
					write_config("FirewallApp pkg: modified ruleset configuration");

					// Update suricata.conf and suricata.sh files for this interface
					sync_suricata_package_config();

				}
			}*/

			// Update suricata.conf and suricata.sh files for this interface
			sync_suricata_package_config();

			$lock_up = "/var/run/rules_updating.lck";
			mwexec("rm -f {$lock_up}");

			$rebuild_rules = false;
			if (!file_exists("/var/run/suricata_{$if_real}{$suricata_uuid}_stop.lck")){
				if (!suricata_is_running($suricatacfg['uuid'], $if_real)) {
					log_error("Starting Suricata on {$if_friendly}({$if_real}) per user request...");
					//mwexec_bg("/usr/local/bin/php -f {$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php");
					//install_cron_job("/usr/bin/nice -n20 /usr/local/bin/php-cgi -f /usr/local/www/firewallapp/get_rules_lists_webservice.php", true, "0", "22");
				} else {
					log_error("Restarting Suricata on {$if_friendly}({$if_real}) per user request...");
					suricata_stop($suricatacfg, $if_real);
					mwexec_bg("/usr/local/bin/php -f {$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php");

					$suri_starting[$id] = 'TRUE';

					if ($suricatacfg['barnyard_enable'] == 'on' && !isvalidpid("{$g['varrun_path']}/barnyard2_{$if_real}{$suricata_uuid}.pid")) {
						$by2_starting[$id] = 'TRUE';
					}
				}
			}
			$savemsg = gettext("Rules updated successfully!");
		} else {
			$input_errors[] = gettext("Could not update rules!");
		}

		init_config_arr(array('installedpackages', 'suricata', 'rule'));
		$a_rule = &$config['installedpackages']['suricata']['rule'];

		foreach ($config['installedpackages']['suricata']['rule'] as $id => $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    if (in_array($if, $all_gtw,true)) {
		    	// Signal Suricata to "live reload" the rules
				suricata_reload_config($a_rule[$id]);
			}
		}

		install_version_lists();

	}

	
}

#function control_state_interface($changeDefaultInterface = "") {
function control_state_interface($changeDefaultInterface = "", $changeDefaultIpsMode = "") {

	global $g, $suricata_rules_dir, $config, $suricatacfg, $if_real, $if_friendly, $id, $rebuild_rules;

	clean();

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	$status_iface =  getStatusNewFapp();

	$status_iface2 =  getInterfaceNewFapp();

	//print_r($status_iface2);die;

	if (install_rules_lists()) {

		if (($status_iface == 0) && ($status_iface2 == 0)) {
		
			log_error(dgettext("FirewallApp", "FirewallApp - Installing the lists of the rules..."));

			load_settings_start($changeDefaultInterface, $changeDefaultIpsMode);

			$rebuild_rules = true;

			/*init_config_arr(array('installedpackages', 'suricata', 'rule'));
			$fapp_ifaces = &$config['installedpackages']['suricata']['rule'];

			foreach($fapp_ifaces as $idx => $fapp_iface) {
				// Enable the basic default rules for the interface
				$dh  = opendir("{$suricata_rules_dir}");

				$rulesetsfile = "";
				while (false !== ($filename = readdir($dh))) {
					if (substr($filename, -5) != "rules")
						continue;

					$rulesetsfile .= basename($filename) . "||";
				}

				$if = get_real_interface($fapp_ifaces['interface']);

				if (!in_array($if, $all_gtw,true)) {

					$config['installedpackages']['suricata']['rule'][$idx]['rulesets'] = rtrim($rulesetsfile, "||");

					// Save configuration changes
					write_config("FirewallApp pkg: modified ruleset configuration");

					// Update suricata.conf and suricata.sh files for this interface
					sync_suricata_package_config();

				}
			}*/

			// Update suricata.conf and suricata.sh files for this interface
			sync_suricata_package_config();

			$lock_up = "/var/run/rules_updating.lck";
			mwexec("rm -f {$lock_up}");

			$rebuild_rules = false;
			if (!file_exists("/var/run/suricata_{$if_real}{$suricata_uuid}_stop.lck")){
				if (!suricata_is_running($suricatacfg['uuid'], $if_real)) {
					log_error("Starting Suricata on {$if_friendly}({$if_real}) per user request...");
					mwexec_bg("/usr/local/bin/php -f {$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php");
					//install_cron_job("/usr/bin/nice -n20 /usr/local/bin/php-cgi -f /usr/local/www/firewallapp/get_rules_lists_webservice.php", true, "0", "22");
				} else {
					log_error("Restarting Suricata on {$if_friendly}({$if_real}) per user request...");
					suricata_stop($suricatacfg, $if_real);
					mwexec_bg("/usr/local/bin/php -f {$g['tmp_path']}/suricata_{$if_real}{$suricatacfg['uuid']}_startcmd.php");

					$suri_starting[$id] = 'TRUE';

					if ($suricatacfg['barnyard_enable'] == 'on' && !isvalidpid("{$g['varrun_path']}/barnyard2_{$if_real}{$suricata_uuid}.pid")) {
						$by2_starting[$id] = 'TRUE';
					}
				}
			}
			$savemsg = gettext("Rules updated successfully!");
		} else {
			$input_errors[] = gettext("Could not update rules!");
		}

		init_config_arr(array('installedpackages', 'suricata', 'rule'));
		$a_rule = &$config['installedpackages']['suricata']['rule'];

		for ($id = 0; $id <= count($a_rule)-1; $id++) {
			// Signal Suricata to "live reload" the rules
			suricata_reload_config($a_rule[$id]);
		}

		install_version_lists();

	}
	
}

function getInterfaceNameAcp() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$ret = '';

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    if ($suricatacfg['interface'] === 'wan') {
					$ret = $if.$uuid;
			}
		}

	}

	return $ret;

}

function getInterfaceNewAcp() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

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

		    if (in_array($if, $all_gtw,true)) {
		    //if ($suricatacfg['interface'] === 'wan') {
					$ret++;
			}
		}

	}

	return $ret;

}

function getInterfaceNewFapp() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

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

		    if (!in_array($if, $all_gtw,true)) {
		    //if ($suricatacfg['interface'] != 'wan') {
					$ret++;
			}
		}

	}

	return $ret;

}

function getStatusNewFapp() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

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

		    if (!in_array($if, $all_gtw,true)) {

		    	if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
		    		$ret++;
				}
				if (file_exists("/var/suricata_{$if}{$uuid}_heuristic.pid")) {
		    		$ret++;
				}

		    }
		}

	}

	return $ret;

}

function getStatusNewAcp() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

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

		    	if (file_exists("/var/run/suricata_{$if}{$uuid}.pid")) {
		    		$ret++;
				}

		    }
		}

	}

	return $ret;

}

function memoryHardwareLimitInterfaces() {
	$memoryHardware = intval(shell_exec("sysctl hw.physmem | awk -F\" \" '{ print int($2/1024/1024/1024) }'"));
	if (($memoryHardware >= 7) && ($memoryHardware < 15)) {
		return 8;
	} elseif (($memoryHardware >= 15) && ($memoryHardware < 23)) {
		return 16;
	} elseif (($memoryHardware >= 23) && ($memoryHardware < 30)) {
		return 24;
	} elseif (($memoryHardware >= 30) && ($memoryHardware < 40)) {
		return 32;
	} elseif (($memoryHardware >= 39) && ($memoryHardware < 47)) {
		return 40;
	} elseif ($memoryHardware >= 47) {
		return 48;
	} else {
		return 1;
	}
	return 1;
}

function return_qtd_interfaces_available() {
	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$a_nat = $config['installedpackages']['suricata']['rule'];

	$ifH = 0;
	$ifS = 0;
	if (!empty($a_nat)) {
	 	foreach ($a_nat as $natent) {
			if ($natent['ips_mode'] == 'ips_mode_legacy') {
				$ifS++;
			} elseif ($natent['ips_mode'] == 'ips_mode_inline') {
				$ifH++;
			} else {
				continue;
			}
		}
	}

	/*
	//avarege
	1 Interface Heuristic = 2GB
	1 Interface FAPP = 200MB
	*/

	// set max fapp acp instances
	$maxInstancesH = 0;
	$maxInstancesS = 0;
	$model = "1000";
	if (file_exists("/etc/model")) {
		$model = explode(" ", trim(file_get_contents("/etc/model")))[1];
	}
	switch ($model) {
			case '1000':
			case '1500':
			case '2000':
				if ($ifH == 0 || empty($ifH)) {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 4; //800MB
				} else {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 1; //200MB
				}
			break;
			case '2500':
			case '3000':
				if ($ifH == 0 || empty($ifH)) {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 6; //1,2GB
				} else {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 3; //600MB
				}
			break;
			case '3500':
			case '4000':
			case '4500':
				if ($ifH == 0 || empty($ifH)) {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 10; //2GB
				} else {
					$maxInstancesH = 2; //4GB
					$maxInstancesS = 5; //1GB
				}
			break;
			case '5000':
			case '5500':
				if ($ifH == 0 || empty($ifH)) {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 14; //2,8GB
				} else {
					$maxInstancesH = 3; //6GB
					$maxInstancesS = 7; //1,4GB
				}
			break;
			case '6000':
				if ($ifH == 0 || empty($ifH)) {
					$maxInstancesH = 1; //2GB
					$maxInstancesS = 18; //3,6GB
				} else {
					$maxInstancesH = 4; //8GB
					$maxInstancesS = 9; //1,8GB
				}
			break;
			default:
				$maxInstancesH = 0;
				$maxInstancesS = 0;
			break;
	}


	//Ex: Base UTM 5000 
	$memorySystem = memoryHardwareLimitInterfaces();
	if ($memorySystem != 1 && $memorySystem != 8) {
		if ($memorySystem == 16) {
			if ($ifH == 0 || empty($ifH)) {
				$maxInstancesH = $maxInstancesH;
				$maxInstancesS = $maxInstancesS + 14; //28 Interfaces = 5,6GB  
			} else {
				$maxInstancesH = $maxInstancesH + 2; //5 Interfaces = 10GB  
				$maxInstancesS = $maxInstancesS + 7; //14 Interfaces = 2,8 GB
			}
		} elseif ($memorySystem == 24) {
			if ($ifH == 0 || empty($ifH)) {
				$maxInstancesH = $maxInstancesH;
				$maxInstancesS = $maxInstancesS + 20; //34 Interfaces = 6,8GB  
			} else {
				$maxInstancesH = $maxInstancesH + 4; //7 Interfaces = 14GB  
				$maxInstancesS = $maxInstancesS + 10; //17 Interfaces = 3,4GB
			}
		} elseif ($memorySystem == 32) {
			if ($ifH == 0 || empty($ifH)) {
				$maxInstancesH = $maxInstancesH;
				$maxInstancesS = $maxInstancesS + 26; //40 Interfaces = 8GB  
			} else {
				$maxInstancesH = $maxInstancesH + 6; //9 Interfaces = 18GB  
				$maxInstancesS = $maxInstancesS + 13; //20 Interfaces = 4GB
			}
		} elseif ($memorySystem == 40) {
			if ($ifH == 0 || empty($ifH)) {
				$maxInstancesH = $maxInstancesH;
				$maxInstancesS = $maxInstancesS + 32; //46 Interfaces = 9,2GB  
			} else {
				$maxInstancesH = $maxInstancesH + 8; //11 Interfaces = 22GB  
				$maxInstancesS = $maxInstancesS + 16; //23 Interfaces = 4,6GB
			}
		} elseif ($memorySystem == 48) {
			if ($ifH == 0 || empty($ifH)) {
				$maxInstancesH = $maxInstancesH;
				$maxInstancesS = $maxInstancesS + 38; //52 Interfaces = 10,2GB  
			} else {
				$maxInstancesH = $maxInstancesH + 10; //13 Interfaces = 26GB  
				$maxInstancesS = $maxInstancesS + 19; //27 Interfaces = 5,4GB
			}
		}
	}

	$maxInstancesH = ($maxInstancesH - $ifH);
	$maxInstancesS = ($maxInstancesS - $ifS);

	if (file_exists("/etc/mode")) {
		if (trim(file_get_contents("/etc/mode")) == "test") {
			$maxInstancesH = 100;
			$maxInstancesS = 100;
		}
	}
	
	return [$maxInstancesH, $maxInstancesS];

}


function return_option_mult_interfaces_fapp_acp() {

	$returnQtdAvaliable = return_qtd_interfaces_available();
	$maxInstancesH = $returnQtdAvaliable[0];
	$maxInstancesS = $returnQtdAvaliable[1];
	
	return $maxInstancesH . "___" . $maxInstancesS;
	
}


function limit_mult_interfaces_fapp_acp() {

	$returnQtdAvaliable = return_qtd_interfaces_available();
	$maxInstancesH = $returnQtdAvaliable[0];
	$maxInstancesS = $returnQtdAvaliable[1];
	
	if ((intval($maxInstancesH) <= -1) || (intval($maxInstancesS) <= -1)) {
		return true;
	} else {
		return false;
	}
	
}

function mult_interfaces_fapp_acp($interface, $post_ips_mode, $action) {

	$returnQtdAvaliable = return_qtd_interfaces_available();
	$maxInstancesH = $returnQtdAvaliable[0];
	$maxInstancesS = $returnQtdAvaliable[1];
	
	$instanceProxy = trim(exec("ps ax | grep -i squid | grep -v grep -c"));

	if (file_exists("/etc/model")) {
		$model = explode(" ", trim(file_get_contents("/etc/model")))[1];
		if (intval($model) >= 2500) {
			$instanceProxy = 0;
		}
	}

	if (file_exists("/etc/mode")) {
		if (trim(file_get_contents("/etc/mode")) == "test") {
			$instanceProxy = 0;
		}
	}
	
	return [$maxInstancesH, $maxInstancesS, $instanceProxy];

}


function firewallapp_users_ip_update() {
	global $g, $cpzone, $cpzoneid, $rebuild_rules, $config;

	if(!isset($config['system']['firewallapp']['profile'])) {
		exit;
	}
	$a_profile = $config['system']['firewallapp']['profile'];

	$cpdb = array();
	foreach (array_keys($config['captiveportal']) as $cpkey) {
		$cpzone = $cpkey;
		$cpdb = array_merge($cpdb, captiveportal_read_db());
	}

	$rules_map = suricata_load_rules_map(glob(SURICATA_RULES_DIR .
	    '/*.rules'));

	init_config_arr(array('installedpackages', 'suricata', 'rule'));
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();


	#for ($id = 0; $id <= count($a_rule)-1; $id++) {

	foreach ($a_rule as $id => $suricatacfg) {
		
		$if = get_real_interface($suricatacfg['interface']);

		if (!in_array($if, $all_gtw,true)) {

			$tmp_alert = "";
			$tmp_drop = "";
			$tmp_arr = array();

			/*
			* XXX Below block is insanely complex and should be simplified
			* or become a function (or even multiple functions)
			*/
			foreach ($a_profile as $profile) {
				$ips = array();

				foreach ($profile['group'] as $p_group) {
					foreach ($config['system']['group'] as $group) {
						if (empty($group['member'])) {
							continue;
						}

						if ($p_group != $group['gid'] &&
							$p_group != $group['objectguid']) {
							continue;
						}

						foreach ($group['member'] as $member) {
							if (empty($member)) {
								continue;
							}

							foreach ($config['system']['user'] as $user) {
								if ($member != $user['uid']) {
									continue;
								}

								foreach ($cpdb as $host) {
									if (in_array($host['ip'], $ips)) {
										continue;
									}

									if ($host['username'] != $user['name'] &&
										$host['username'] != explode(".", $user['name'])[0]) {
											continue;
									}

									$ips[] = $host['ip'];
								}
							}
						}
					}

					if ($p_group != "visitantes") {
						continue;
					}

					foreach($cpdb as $host) {
						if (in_array($host['ip'], $ips)) {
							continue;
						}

						$sub_username = substr(
							$host['username'], 0, 9);

						if ($sub_username == 'form_auth' ||
							$sub_username == 'facebook:') {
							$ips[] = $host['ip'];
						}
					}
				}

				if (empty($ips)) {
					continue;
				}

				$users = "[" . implode(",", $ips) . "]";

				if ($profile['rule_sid_force_drop']) {
					$rules_drop = explode("||",
						$profile['rule_sid_force_drop']);

					foreach ($rules_drop as $rule) {
						$rule_drop = explode(":", $rule);
						$gid = $rule_drop[0];
						$sid = $rule_drop[1];
						$drop_rule = str_replace(array("(",")"),
							array("", ""), $rule_drop[2]);
						$drop_item = explode("|", $drop_rule);
						$ip_src = $drop_item[0];
						$port_src = $drop_item[1];
						$direction = $drop_item[2];
						$ip_dst = $drop_item[3];
						$port_dst = $drop_item[4];

						$tmp_drop .= "{$gid}:{$sid}:" .
							"({$users}|$port_src|$direction|" .
							"$ip_dst|$port_dst)||";

						$rules_map[$gid][$sid]['action'] =
							'drop';
					}
				}

				if ($profile['rule_sid_force_alert']) {
					$rules_alert = explode("||",
						$profile['rule_sid_force_alert']);

					foreach ($rules_alert as $rule) {
						$rule_alert = explode(":", $rule);
						$gid = $rule_alert[0];
						$sid = $rule_alert[1];
						$alert_rule = str_replace(
							array("(",")"), array("", ""),
							$rule_alert[2]);
						$alert_item = explode("|", $alert_rule);
						$ip_src = $alert_item[0];
						$port_src = $alert_item[1];
						$direction = $alert_item[2];
						$ip_dst = $alert_item[3];
						$port_dst = $alert_item[4];

						$tmp_alert .= "{$gid}:{$sid}:" .
							"({$users}|$port_src|$direction|" .
							"$ip_dst|$port_dst)||";
					}
				}
			}

			$tmp_alert = rtrim($tmp_alert, "||");
			$tmp_drop = rtrim($tmp_drop, "||");

			/*
			* This loop is intended to check the ips that have been marked
			* as block for the SID rule in XML and send to update
			* suricata.rules to make the specific block. At the same time
			* it scans all unblocked ips and inserts in the same rule but
			* in alert to be able to pass the access logs of the same
			*/

			$ips_final = array();

			$tmp_alert_final = "";
			if ($tmp_alert) {
				$rules_alert = explode("||", $tmp_alert);

				foreach ($rules_alert as $rule) {
					$rule_alert = explode(":", $rule);
					$gid = $rule_alert[0];
					$sid = $rule_alert[1];
					$alert_rule = str_replace(array("(",")"),
						array("", ""), $rule_alert[2]);
					$alert_item = explode("|", $alert_rule);

					if (in_array($alert_item[0], $ips_final)) {
						continue;
					}

					$ips_final[$gid][$sid][] = $alert_item[0];
				}

				foreach ($rules_alert as $rule) {
					$rule_alert = explode(":", $rule);
					$gid = $rule_alert[0];
					$sid = $rule_alert[1];

					if (in_array("{$gid}:{$sid}", $tmp_arr)) {
						continue;
					}

					$alert_rule = str_replace(array("(",")"),
						array("", ""), $rule_alert[2]);
					$alert_item = explode("|", $alert_rule);
					$ip_src = implode(",", $ips_final[$gid][$sid]);
					$port_src = $alert_item[1];
					$direction = $alert_item[2];
					$ip_dst = $alert_item[3];
					$port_dst = $alert_item[4];

					$tmp_arr[] = "{$gid}:{$sid}";

					$tmp_alert_final .= "{$gid}:{$sid}:" .
						"({$ip_src}|$port_src|$direction|$ip_dst|" .
						"$port_dst)||";

					$rules_map[$gid][$sid]['action'] = 'alert';
				}
			}
			$tmp_alert_final = rtrim($tmp_alert_final, "||");

			if (!empty($tmp_alert_final)) {
				$a_rule[$id]['rule_sid_force_alert'] = $tmp_alert_final;
			} else {
				unset($a_rule[$id]['rule_sid_force_alert']);
			}

			$tmp_drop_final = "";
			if ($tmp_drop) {
				$rules_drop = explode("||", $tmp_drop);

				foreach ($rules_drop as $rule) {
					$rule_drop = explode(":", $rule);
					$gid = $rule_drop[0];
					$sid = $rule_drop[1];
					$drop_rule = str_replace(array("(",")"),
						array("", ""), $rule_drop[2]);
					$drop_item = explode("|", $drop_rule);

					if (in_array($drop_item[0], $ips_final)) {
						continue;
					}

					$ips_final[$gid][$sid][] = $drop_item[0];
				}

				foreach ($rules_drop as $rule) {
					$rule_drop = explode(":", $rule);
					$gid = $rule_drop[0];
					$sid = $rule_drop[1];

					if (in_array("{$gid}:{$sid}", $tmp_arr)) {
						continue;
					}

					$drop_rule = str_replace(array("(",")"),
						array("", ""), $rule_drop[2]);
					$drop_item = explode("|", $drop_rule);
					$ip_src = implode(",", $ips_final[$gid][$sid]);
					$port_src = $drop_item[1];
					$direction = $drop_item[2];
					$ip_dst = $drop_item[3];
					$port_dst = $drop_item[4];

					$tmp_arr[] = "{$gid}:{$sid}";

					$tmp_drop_final .= "{$gid}:{$sid}:" .
						"({$ip_src}|$port_src|$direction|$ip_dst|" .
						"$port_dst)||";

					$rules_map[$gid][$sid]['action'] = 'drop';
				}
			}
			$tmp_drop_final = rtrim($tmp_drop_final, "||");

			if (!empty($tmp_drop_final)) {
				$a_rule[$id]['rule_sid_force_drop'] = $tmp_drop_final;
			} else {
				unset($a_rule[$id]['rule_sid_force_drop']);
			}

			/*
			* Update our in-memory rules map with the changes just saved
			* to the Suricata configuration file.
			*/
			suricata_modify_sids_action($rules_map, $a_rule[$id]);

			// Save new configuration
			write_config("FirewallAPP pkg: save modified custom rules for " .
				$a_rule[$id]['interface'] . ".");

			exec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

			$rebuild_rules = true;
			suricata_generate_yaml($a_rule[$id]);
			$rebuild_rules = false;

			// Signal Suricata to "live reload" the rules
			suricata_reload_config($a_rule[$id]);

			// Sync to configured CARP slaves if any are enabled
			suricata_sync_on_changes();

			// Clear block table
			exec("/sbin/pfctl -t " . SURICATA_PF_TABLE . " -T flush");
		}
	}
}

?>

