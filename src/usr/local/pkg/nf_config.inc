<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once('cg2_util.inc');
require_once('service-utils.inc');
require_once('nf_defines.inc');
require_once('nf_db.inc');
require_once('squid.inc');
require_once('pfsense-utils.inc');
require_once('shaper.inc');
require_once('filter.inc');
require_once('globals.inc');
require_once('auth.inc');

function redirector_reload()
{
	if (is_service_running('squid'))
		exec('killall -HUP redirector');
	else
		start_service('squid');
}

function interface_reload()
{
	if (is_service_running('interface'))
		exec('killall -HUP interface');
	else
		start_service('interface');
}

function p2p_reload()
{
	if (is_service_running('p2p'))
		exec('killall -HUP p2p');
	else
		start_service('p2p');
	filter_configure();
}

function NetfilterContentSettingsResync($instance_id, $restart = true)
{
	global $config;

	if (!is_numeric($instance_id) || !isset($config['system']['webfilter']['instance']['config'][$instance_id])) {
		log_error(dgettext("BluePexWebfilter", "Proxy Instance not found!"));
		return;
	}
	$wf_instance = $config['system']['webfilter']['instance']['config'][$instance_id];

	/* Find the LAN IP */
	$lan = $wf_instance['server']['active_interface'];
	$lan_ip = find_interface_ip($GLOBALS['config']['interfaces'][$lan]['if']);
	if (!is_ipaddr($lan_ip))
		$lan_ip = '192.168.0.1';

	$protocol = $GLOBALS['config']['system']['webgui']['protocol'];
	$port = intval($GLOBALS['config']['system']['webgui']['port']);
	if (empty($port) || ! is_port($port))
		$port = $protocol == 'https' ? 443 : 80;
	if (($protocol == 'http' && $port == 80) || ($protocol == 'https' && $port == 443))
		$port = '';
	else
		$port = ":$port";

	/* The block page */
	$settings = $wf_instance['nf_content_settings'];
	if ($settings['use_custom_urls'] != 'on')
		$blockpage = "$protocol://$lan_ip$port/webfilter/wf_blocked.php";
	else
		$blockpage = $settings['custom_content_url'];
	$licensepage = "$protocol://$lan_ip$port/webfilter/wf_license.php";
	$errorpage = "$protocol://$lan_ip$port/webfilter/wf_error.php";

	/* The skipped prefixes */
	if (isset($settings['use_custom_skipped']) && $settings['use_custom_skipped'] == 'on') {
		$skipped_prefixes = explode(',', $settings['custom_skipped']);
		$skipped_prefixes = array_map('trim', $skipped_prefixes);
		$skipped_prefixes = implode(' ', $skipped_prefixes);
	}
	else {
		$parts = explode('/', $blockpage);
		$skipped_prefixes = '';
		if (count($parts) > 2)
			$skipped_prefixes = $protocol . '://' . trim($parts[2]) . '/';
		if (empty($skipped_prefixes))
			$skipped_prefixes = $protocol . "://$lan_ip:$port/";
	}

	$config_db = get_element_config("nf_reports_settings",0);
	$syslog_redirector = ($settings['enable'] == "on") ? "AccessLogger syslog\nAccessLoggerSyslogFacility LOG_LOCAL2" : "";

        if (isset($wf_instance['nf_content_settings']['remote_interface']) && $wf_instance['nf_content_settings']['remote_interface'] == "on") {
		$conf = "ServerName updates.bluepex.com\n";
	} else if (isset($wf_instance['server']['parent_rules']) && is_numeric($wf_instance['server']['parent_rules'])) {
		$conf = "ServerName /var/run/netfilter/interface{$wf_instance['server']['parent_rules']}.socket\n";
        } else {
		$conf = "ServerName /var/run/netfilter/interface{$instance_id}.socket\n";
	}
	if ($GLOBALS['g']['platform'] == 'nanobsd') {
		$conf .= "RemoteServerName updates.bluepex.com\n";
	}
	$conf .= <<<EOD
DCAgent none
BlockPage $blockpage
LicensePage $licensepage
ErrorPage $errorpage
SkippedPrefixes $skipped_prefixes
$syslog_redirector
instance_id $instance_id

EOD;
	if (!is_dir(dirname(NETFILTER_CONF_DIR))) {
		mkdir(dirname(NETFILTER_CONF_DIR));
	}
	conf_mount_rw();
	file_put_contents(NETFILTER_CONF_DIR . "/redirector{$instance_id}.conf", $conf);
	conf_mount_ro();

	if ($restart) {
		squid_resync("", "yes");
		redirector_reload();
	} else {
		// XMLRPC Sync
		webfilter_sync_on_changes();
	}
}

function NetfilterContentRulesResync($restart = true)
{
	global $config;

	$NetfilterSpecialContentCategories = NetfilterGetSpecialContentCategories();
	$special = array_flip($NetfilterSpecialContentCategories);

	NetfilterGroupsResync(false);

	$types = array(
		'default' => false,
		'users' => 'user',
		'groups' => 'group',
		'ip' => 'ip',
		'range' => 'range',
		'subnet' => 'subnet'
	);

	/* The custom lists */
	$custom = array();
	$lists = get_table_rows('nf_content_custom', 0);
	file_put_contents("/root/lists", $lists);
	foreach ($lists as $list)
		$custom[] = $list['name'];

	init_config_arr(array('system','webfilter','nf_content_rules','element0','item'));

	if(isset($config['system']['webfilter']['instance']['config'])) {
		foreach ($config['system']['webfilter']['instance']['config'] as $instance_id => $instance_config) {
			$conf = '';
			$count = 0;

			foreach($config['system']['webfilter']['nf_content_rules']['element0']['item'] as $rule) {
				if (!isset($rule['instance_id']) || $rule['instance_id'] != $instance_id || isset($rule['disabled']) && $rule['disabled'] == 'on') {
					continue;
				}
				++$count;
				$conf .= <<<EOD
#
# Regra $count
#
rule {

EOD;

				/* Create the match directives */
				$rule_type = $rule['type'];
				$targets = $rule_type == 'default' ? array() : explode(',', $rule[$rule_type]);
				$match_what = $types[$rule_type];
				foreach ($targets as $target) {
				if($match_what == "group" || $match_what == "user") {
					foreach($config['system'][$match_what] as $grp_usr)
						if($grp_usr['uid'] == $target || $grp_usr['gid'] == $target || $grp_usr['objectguid'] == $target) {
							$target = ($match_what == "user") ? strtolower($grp_usr['name']) : $grp_usr['name'];
							break;
						}
				}

					$target = str_replace('\\', '\\\\', $target);
					$target = str_replace('"', '\\"', $target);
					$conf .= "	match { $match_what = \"$target\" }\n";
				}

				/* Content categories */
				switch ($rule['action']) {
					default:
					case 'allow':
						$conf .= "	all_allowed = true\n";
						break;
					case 'block':
						$conf .= "	all_blocked = true\n";
						break;
					case 'selected':
						$toapply = explode(',', $rule['categories']);
						if (isset($toapply[0])) {
							$cats = implode(', ', $toapply);
							$conf .= "	blocked_categories = { $cats }\n";
						}
						break;
				}

				/* Whitelist and blacklist */
				if ($rule['whitelist'] == 'on')
					$conf .= "	apply_whitelist = true\n";
				if ($rule['blacklist'] == 'on')
					$conf .= "	apply_blacklist = true\n";

				/* Activation periods */
				if ($rule['time_match'] != '' && strpos($rule['time_match'], '-') !== false) {
					$periods = implode('", "', explode(',', $rule['time_match']));
					$conf .= "	activation_periods = { \"$periods\" }\n";
				}

				/* Custom lists */
				$allowed2 = $blocked2 = array();

				// BluePex OneClick Module
				foreach (get_table_rows('nf_content_custom') as $i => $list) {
					if ($list['name'] == "oneclick_blocked") {
						$blocked2[] = $i;
					} elseif ($list['name'] == "oneclick_allowed") {
						$allowed2[] = $i;
					}
				}

				list($allowed, $blocked) = explode('|', $rule['custom_lists']);
				if (!empty($allowed)) {
					$allowed = explode(',', $allowed);
					if (isset($oneclick_allowed)) {
						$allowed2[] = $oneclick_allowed;
					}
					foreach ($allowed as $a) {
						$a = explode(':', $a);
						$allowed2[] = $a[0];
					}
				}
				if (!empty($allowed2)) {
					$allowed = implode(', ', $allowed2);
					$conf .= "      allowed_custom = { $allowed }\n";
				}
				if (!empty($blocked)) {
					$blocked = explode(',', $blocked);
					if (isset($oneclick_blocked)) {
						$blocked2[] = $oneclick_blocked;
					}
					foreach ($blocked as $b) {
						$b = explode(':', $b);
						$blocked2[] = $b[0];
					}
				}
				if (!empty($blocked2)) {
					$blocked = implode(', ', $blocked2);
					$conf .= "      blocked_custom = { $blocked }\n";
				}
				$conf .= "}\n\n";
			}

			conf_mount_rw();
			if (!is_dir(dirname(NETFILTER_REDIRECTOR_RULES_FILE))) {
				mkdir(dirname(NETFILTER_REDIRECTOR_RULES_FILE), 0755);
			}
			file_put_contents(NETFILTER_CONF_DIR . "/rules{$instance_id}.conf", $conf);
			conf_mount_ro();
		}
	}

	if ($restart)
		redirector_reload();

	// XMLRPC Sync
	webfilter_sync_on_changes();
}

function NetfilterProtocolRulesResync($restart = true)
{
	$NetfilterProtocolCategories = NetfilterGetProtocolCategories();

	$types = array(
		'default' => 'DEFAULT',
		'ip' => 'IP',
		'range' => 'RANGE',
		'subnet' => 'SUBNET'
	);

	$conf = '';
	foreach (get_table_rows('nf_protocol_rules') as $rule) {
		if (isset($rule['disabled']) && $rule['disabled'] == 'on')
			continue;

		$rule_type = $rule['type'];
		$type = $types[$rule_type];
		if ($rule_type == 'default')
			$target = 'RULE';
		else if ($rule_type == 'range')
			$target = str_replace('-', ' ', $rule['range']);
		else
			$target = $rule[$rule_type];

		switch ($rule['action']) {
			default:
			case 'allow':
				$to_apply = 'allow_all';
				break;
			case 'blocked':
				$to_apply = 'block_all';
				break;
			case 'selected':
				$selected = explode(',', $rule['protocols']);
				$filtered = array();
				foreach ($selected as $proto) {
					if (isset($NetfilterProtocolCategories[$proto]))
						$filtered[] = $proto;
				}
				if (empty($filtered))
					$to_apply = 'allow_all';
				else
					$to_apply = implode(',', $filtered);
				break;
		}

		$conf .= "$type $target $to_apply\n";
	}

	conf_mount_rw();
	if (!is_dir(dirname(NETFILTER_P2P_RULES_FILE)))
		mkdir(dirname(NETFILTER_P2P_RULES_FILE));
	file_put_contents(NETFILTER_P2P_RULES_FILE, $conf);
	conf_mount_ro();

	if ($restart)
		p2p_reload();

	// XMLRPC Sync
	webfilter_sync_on_changes();
}

function NetfilterWhitelistResync($instance_id, $restart = true)
{
	global $config;

	if (!is_numeric($instance_id) || !isset($config['system']['webfilter']['instance']['config'][$instance_id])) {
		log_error(dgettext("BluePexWebfilter", "Whitelist error: Proxy Instance not found!"));
		return;
	}
        $wf_instance = $config['system']['webfilter']['instance']['config'][$instance_id];

	if (!isset($wf_instance['nf_whitelist_blacklist']['whitelist'])) {
		return;
	}

	if (!is_dir(dirname(NETFILTER_CONF_DIR)))
		mkdir(dirname(NETFILTER_CONF_DIR));

	$urls = array();
	foreach (explode("\n", base64_decode($wf_instance['nf_whitelist_blacklist']['whitelist'])) as $item) {
		if (!empty($item)) {
			$urls[] = trim($item) . '*';
		}
	}
	conf_mount_rw();
	file_put_contents(NETFILTER_CONF_DIR . "/whitelist{$instance_id}.txt", implode("\n", $urls));
	conf_mount_ro();

	if ($restart)
		interface_reload();

	// XMLRPC Sync
	webfilter_sync_on_changes();
}

function NetfilterBlacklistResync($instance_id, $restart = true)
{
	global $config;

	if (!is_numeric($instance_id) || !isset($config['system']['webfilter']['instance']['config'][$instance_id])) {
		log_error(dgettext("BluePexWebfilter", "Blacklist error: Proxy Instance not found!"));
		return;
	}

	$wf_instance = $config['system']['webfilter']['instance']['config'][$instance_id];
	if (!isset($wf_instance['nf_whitelist_blacklist']['blacklist'])) {
		return;
	}

	if (!is_dir(dirname(NETFILTER_CONF_DIR)))
		mkdir(dirname(NETFILTER_CONF_DIR));

	$urls = array();
	foreach (explode("\n", base64_decode($wf_instance['nf_whitelist_blacklist']['blacklist'])) as $item) {
		if (!empty($item)) {
			$urls[] = trim($item) . '*';
		}
	}
	conf_mount_rw();
	file_put_contents(NETFILTER_CONF_DIR . "/blacklist{$instance_id}.txt", implode("\n", $urls));
	conf_mount_ro();

	if ($restart)
		interface_reload();

	// XMLRPC Sync
	webfilter_sync_on_changes();
}

function NetfilterCustomListsResync($restart = true)
{
	safe_mkdir(NETFILTER_CUSTOM_LISTS_DIR);
	if (!is_dir(NETFILTER_CUSTOM_LISTS_DIR)) {
		log_error(sprintf(dgettext("BluePexWebFilter", "Could not create the directory '%s'."), NETFILTER_CUSTOM_LISTS_DIR));
		return;
	}

	for ($i = 0; $i < NETFILTER_MAX_CUSTOM_LISTS; ++$i) {
		if (file_exists(NETFILTER_CUSTOM_LISTS_DIR . "/custom$i.txt"))
			@unlink(NETFILTER_CUSTOM_LISTS_DIR . "/custom$i.txt");
	}
	foreach (get_table_rows('nf_content_custom') as $i => $list) {
		$urls = explode("\n", base64_decode($list['urls']));
		$buffer = '';
		foreach ($urls as $url) {
			$url = trim($url);
			if (!empty($url))
				$buffer .= "$url\n";
		}
	conf_mount_rw();
		file_put_contents(NETFILTER_CUSTOM_LISTS_DIR . "/custom$i.txt", $buffer);
	conf_mount_ro();
	}

	if ($restart)
		interface_reload();

	// XMLRPC Sync
	webfilter_sync_on_changes();
}

function NetfilterGroupsResync($restart = true)
{
	global $config;

	if(!is_array($config['system']['group']))
		return true;
	conf_mount_rw();

	try {
		$dbcfg = get_element_config('nf_reports_settings');
		if($dbcfg['remote_reports'] == "on") {
			$db = new NetfilterDatabase();
		}
	} catch (Exception $e) {
		log_error("WebFilter DBERROR: " . $e->getMessage());
	}

	if (!is_dir(dirname(NETFILTER_REDIRECTOR_GROUPS_FILE)))
		mkdir(dirname(NETFILTER_REDIRECTOR_GROUPS_FILE));

	# Truncate the groups file
	$fp = fopen(NETFILTER_REDIRECTOR_GROUPS_FILE, "w");
	fclose($fp);

	foreach($config['system']['group'] as $group) {
		if($group['scope'] == "system" || !is_array($group['member'])) {
			continue;
		}

		# Insert the group data into the WebFilter database
		try {
			if($db) {
				$sql = "INSERT INTO groups(name,description) VALUES('{$group['name']}','{$group['description']}') ON DUPLICATE KEY UPDATE name='{$group['name']}', description='{$group['description']}'";
				$res = $db->Query($sql);
				if($res)
					$db->FreeRes($res);
				else
					log_error("WebFilter DBERROR: It was not possible to insert groups on BluePex Web Filter database");
			}
		} catch (Exception $e) {
			log_error("WebFilter DBERROR: " . $e->getMessage());
		}

		$groupline = "{$group['name']} ";

		# Get user information for each member id
		$members = array();
		foreach($group['member'] as $memberid) {
			if($memberid == 0) continue;
			$user = getUserEntryByUID($memberid);
			$members[] = strtolower($user['name']);
		}
		if(count($members) > 0) {
			$groupline.= implode(" ",$members);
		}
		# Add group members to the configuration file
		file_put_contents(NETFILTER_REDIRECTOR_GROUPS_FILE,"{$groupline}\n",FILE_APPEND);
	}
	conf_mount_ro();
	// XMLRPC Sync
	webfilter_sync_on_changes();
}

?>
