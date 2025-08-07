#!/usr/local/bin/php -f
<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2017
 *
 * ====================================================================
 *
 */
require_once("config.inc");
require_once("webfilter.inc");

define("WEBFILTER_VERSION", "1.4.98");
define("REQUIRED_BASE_VERSION", "2.3");
define("LATEST_CONFIG_VERSION", "0.4");

if (!isset($config['system']['webfilter'])) {
	$config['system']['webfilter'] = array();
}
$wf_config = &$config['system']['webfilter'];

upgrade_wf_config();

function upgrade_wf_config() {
	global $wf_config;

	$current_version = $wf_config['info']['config_version'];

	log_error(dgettext("BluePexWebFilter", "Starting upgrade WebFilter config..."));

	put_webfilter_package_info();

	if ($wf_config['info']['config_version'] < LATEST_CONFIG_VERSION) {
		log_error(dgettext("BluePexWebFilter", "Converting WebFilter config..."));
		while ($wf_config['info']['config_version'] < LATEST_CONFIG_VERSION) {
			$cur_version = $wf_config['info']['config_version'] * 10;
			$next_version = $cur_version + 1;
			$upgrade_function = sprintf('upgrade_%03d_to_%03d', $cur_version, $next_version);
			if (function_exists($upgrade_function)) {
				$upgrade_function();
			}
			$wf_config['info']['config_version'] = sprintf('%.1f', $next_version / 10);
			log_error(sprintf(dgettext("BluePexWebFilter", "Converted the WebFilter config for version '%s'..."), $wf_config['info']['config_version']));
		}
	}

	add_instances_info();
	squid_install_command();
	webfilter_install();

	log_error(dgettext("BluePexWebFilter", "Upgrade WebFilter config finished!"));
	write_config(sprintf(dgettext("BluePexWebFilter", "Migrated the WebFilter config from version '%s' to '%s'."), $current_version, $wf_config['info']['config_version']));
}

function put_webfilter_package_info() {
	global $wf_config;

	log_error(dgettext("BluePexWebFilter", "Adding the WebFilter package info..."));

	$wf_config['info']['name'] = "BluePex WebFilter";
	$wf_config['info']['internal_name'] = "webfilter";
	$wf_config['info']['version'] = WEBFILTER_VERSION;
	$wf_config['info']['required_version'] = REQUIRED_BASE_VERSION;
	$wf_config['info']['maintainer'] = "desenvolvimento@bluepex.com";

	// Start config_version tag in config.xml
	if (!isset($wf_config['info']['config_version'])) {
		$wf_config['info']['config_version'] = "";
	}
}

function upgrade_000_to_001() {
	global $wf_config;

	log_error(dgettext("BluePexWebFilter", "Adding the WebFilter config default..."));

	if (!isset($wf_config['webfilter']['config'][0])) {
		$wf_config['webfilter']['config'][0] = array();
	}
	$conf = &$wf_config['webfilter']['config'][0];

	if (!isset($conf['enable_squid']))
		$conf['enable_squid'] = "on";
	if (!isset($conf['active_interface']))
		$conf['active_interface'] = "lo0";
	if (!isset($conf['proxy_port']))
		$conf['proxy_port'] = "3128";
	if (!isset($conf['allow_interface']))
		$conf['allow_interface'] = "on";
	if (!isset($conf['patch_cp']))
		$conf['patch_cp'] = "on";
	if (!isset($conf['dns_v4_first']))
		$conf['dns_v4_first'] = "";
	if (!isset($conf['disable_pinger']))
		$conf['disable_pinger'] = "";
	if (!isset($conf['dns_nameservers']))
		$conf['dns_nameservers'] = "";
	if (!isset($conf['transparent_proxy']))
		$conf['transparent_proxy'] = "";
	if (!isset($conf['transparent_active_interface']))
		$conf['transparent_active_interface'] = "lan";
	if (!isset($conf['private_subnet_proxy_off']))
		$conf['private_subnet_proxy_off'] = "";
	if (!isset($conf['defined_ip_proxy_off']))
		$conf['defined_ip_proxy_off'] = "";
	if (!isset($conf['defined_ip_proxy_off_dest']))
		$conf['defined_ip_proxy_off_dest'] = "";
	if (!isset($conf['ssl_proxy']))
		$conf['ssl_proxy'] = "";
	if (!isset($conf['ssl_active_interface']))
		$conf['ssl_active_interface'] = "lan";
	if (!isset($conf['ssl_proxy_port']))
		$conf['ssl_proxy_port'] = "";
	if (!isset($conf['dca']))
		$conf['dca'] = "none";
	if (!isset($conf['sslcrtd_children']))
		$conf['sslcrtd_children'] = "";
	if (!isset($conf['interception_checks']))
		$conf['interception_checks'] = "";
	if (!isset($conf['interception_adapt']))
		$conf['interception_adapt'] = "";
	if (!isset($conf['log_enabled']))
		$conf['log_enabled'] = "";
	if (!isset($conf['log_dir']))
		$conf['log_dir'] = "/var/squid/logs";
	if (!isset($conf['log_rotate']))
		$conf['log_rotate'] = "";
	if (!isset($conf['visible_hostname']))
		$conf['visible_hostname'] = "localhost";
	if (!isset($conf['admin_email']))
		$conf['admin_email'] = "admin@localhost";
	if (!isset($conf['error_language']))
		$conf['error_language'] = "pt-br";
	if (!isset($conf['disable_xforward']))
		$conf['disable_xforward'] = "";
	if (!isset($conf['disable_via']))
		$conf['disable_via'] = "";
	if (!isset($conf['uri_whitespace']))
		$conf['uri_whitespace'] = "strip";
	if (!isset($conf['disable_squidversion']))
		$conf['disable_squidversion'] = "";
	if (!isset($conf['custom_options']))
		$conf['custom_options'] = "";
	if (!isset($conf['custom_options_squid3']))
		$conf['custom_options_squid3'] = "";
	if (!isset($conf['custom_options2_squid3']))
		$conf['custom_options2_squid3'] = "";
}

function upgrade_001_to_002() {
	global $config, $wf_config;

	log_error(dgettext("BluePexWebFilter", "Converting the WebFilter config for multiple instances..."));

	if (empty($wf_config)) {
		return;
	}

	$wf_instances = &$config['system']['webfilter']['instance']['config'];
	if (!empty($wf_instances)) {
		// Multiples instances already configured
		return;
	}

	if (isset($wf_config['webfilter']['config'][0])) {
		$wf_instances[0]['server'] = $wf_config['webfilter']['config'][0];
		unset($wf_config['webfilter']);
	}

	if (isset($wf_config['nf_content_settings']['element0'])) {
		$wf_instances[0]['nf_content_settings'] = $wf_config['nf_content_settings']['element0'];
		unset($wf_config['nf_content_settings']);
	}

	if (isset($wf_config['squidtraffic']['config'][0])) {
		$wf_instances[0]['squidtraffic'] = $wf_config['squidtraffic']['config'][0];
		unset($wf_config['squidtraffic']);
	}

	if (isset($wf_config['squidnac']['config'][0])) {
		$wf_instances[0]['squidnac'] = $wf_config['squidnac']['config'][0];
		if (isset($wf_config['squidnac']['config'][0]['whitelist']) && !empty($wf_config['squidnac']['config'][0]['whitelist'])) {
			$wf_instances[0]['nf_whitelist_blacklist']['whitelist'] = $wf_config['squidnac']['config'][0]['whitelist'];
			unset($wf_instances[0]['squidnac']['whitelist']);
		}
		if (isset($wf_config['squidnac']['config'][0]['blacklist']) && !empty($wf_config['squidnac']['config'][0]['blacklist'])) {
			$wf_instances[0]['nf_whitelist_blacklist']['blacklist'] = $wf_config['squidnac']['config'][0]['blacklist'];
			unset($wf_instances[0]['squidnac']['blacklist']);
		}
	}

	if (isset($wf_config['nf_block_ext']['element0'])) {
		$wf_instances[0]['wf_block_ext'] = $wf_config['nf_block_ext']['element0'];
		unset($wf_config['nf_block_ext']);
	}

	if (isset($wf_config['ads_allowed']['element0'])) {
		$wf_instances[0]['ads_allowed'] = $wf_config['ads_allowed']['element0'];
		unset($wf_config['ads_allowed']);
	}

	if (isset($wf_config['quarantine']['config'][0]['enable']) && $wf_config['quarantine']['config'][0]['enable'] == "yes") {
		$wf_config['quarantine']['config'][0]['instances'] = 0;
	}

	if (isset($wf_config['nf_content_rules']['element0']['item'])) {
		for ($i = 0; $i < count($wf_config['nf_content_rules']['element0']['item']); $i++) {
			$wf_config['nf_content_rules']['element0']['item'][$i]['instance_id'] = 0;
		}
	}

	if (isset($wf_config['nf_content_custom']['element0']['item'])) {
		for ($i = 0; $i < count($wf_config['nf_content_custom']['element0']['item']); $i++) {
			$wf_config['nf_content_custom']['element0']['item'][$i]['instance_id'] = 0;
		}
	}

	if (isset($wf_config['squidantivirus']['config'][0]['enable']) && $wf_config['squidantivirus']['config'][0]['enable'] == "on") {
		$wf_config['squidantivirus']['config'][0]['instances'] = 0;
	}

	// Remove obsolete tags
	if (isset($config['system']['coregui2'])) {
		unset($config['system']['coregui2']);
	}
}

function upgrade_002_to_003() {
	global $config, $wf_config;

	if(is_dir("/var/squid")) {
		mwexec("/usr/sbin/chown -R squid:squid /var/squid");
	}
}

function upgrade_003_to_004() {
	require_once("functions.inc");

	system_syslogd_start();
}
?>
