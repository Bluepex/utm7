<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Wesley F. Peres <wesley.peres@bluepex.com>, 2019
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("classes/Form.class.php");
require_once("nf_defines.inc");
require_once("nf_config.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once('functions.inc');
require_once('notices.inc');
require_once("pkg-utils.inc");
require("config.inc");
require_once("captiveportal.inc");

global $g, $rebuild_rules, $idx;

/* Captive Portal users logged */
$cpcfg = is_array($config['captiveportal']) ? $config['captiveportal'] : array();

if (in_array('firewallapp_lan', $cpcfg))
	$cpzone = 'firewallapp';
else
	$cpzone = array_column($cpcfg, 'zone')[0];

$cpdb = captiveportal_read_db();

$hosts = $cpdb;

/* The Members Group in config.xml */
init_config_arr(array('system', 'group'));
$a_group = &$config['system']['group'];
$membersGroup = array();

/* The Members(users) in config.xml */
init_config_arr(array('system', 'user'));
$a_user = &$config['system']['user'];

$suricata_rules_dir = SURICATA_RULES_DIR;
$suricatalogdir = SURICATALOGDIR;

if (!is_array($config['installedpackages']['suricata']))
	$config['installedpackages']['suricata'] = array();
$suricataglob = $config['installedpackages']['suricata'];

if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

$a_rule = &$config['installedpackages']['suricata']['rule'];

$id = 0;

$ruledir = "{$suricata_rules_dir}";
$rulefile = "{$ruledir}/bluepex-default.rules";

$tmp_alert = "";
$tmp_alert_drop = "";
$tmp_drop = "";

$suricata_rules_dir = SURICATA_RULES_DIR;

if(!isset($config['system']['firewallapp']['profile']))
	exit;

$tmp_alert_new = "";
$tmp_drop_new = "";

foreach($config['system']['firewallapp']['profile'] as $idx => $profile) {
	$pconfig = &$config['system']['firewallapp']['profile'][$idx];

	$tmp_alert_new = $pconfig['rule_sid_force_alert'];
	$tmp_drop_new = $pconfig['rule_sid_force_drop'];

	$p_groups = $pconfig['group'];

	$i = 0;
	
	$ips = array();

	foreach($p_groups as $p_group) {
		foreach($a_group as $group) {
			if ($p_group == $group['gid'] || $p_group == $group['objectguid']) {
				foreach($group['member'] as $member) {
					if (empty($member))
						continue;

					foreach($a_user as $user) {
						if ($member == $user['uid']) {
							foreach($hosts as $host) {
								if ($host['username'] == $user['name'] || $host['username'] == explode(".", $user['name'])[0]) {
									if(in_array($host['ip'], $ips))
										continue;

									$ips[] = $host['ip'];
								}
							}
						}
					}
				}
				$i++;
			}
		}
	}

	$users = implode(",", $ips);

	$users = empty($users) ? "any" : "[{$users}]"; 

	$ruledir = "{$suricata_rules_dir}";
	$rulefile = "{$ruledir}/{$currentfile}";

	$rules_map = suricata_load_rules_map($rulefile);

	$tmp_arr = array();

	if ($tmp_drop_new) {
		$rules_drop = explode("||", $tmp_drop_new);
		
		foreach($rules_drop as $rule) {
			$rule_drop = explode(":", $rule);
			$gid = $rule_drop[0];                   
			$sid = $rule_drop[1];
			$drop_rule = str_replace(array("(",")"), array("", ""), $rule_drop[2]);
			$drop_item = explode("|", $drop_rule);
			$ip_src = $drop_item[0];
			$port_src = $drop_item[1];
			$direction = $drop_item[2];
			$ip_dst = $drop_item[3];
			$port_dst = $drop_item[4];
			
			$test = "{$gid}:{$sid}";

			if (in_array($test, $tmp_arr)) {			
				continue;
			}

			#$tmp_alert .= "1:{$sid}:(!{$users}|$port_src|$direction|$ip_dst|$port_dst)||";
			$tmp_drop .= "{$gid}:{$sid}:({$users}|$port_src|$direction|$ip_dst|$port_dst)||";

			$rules_map[$gid][$sid]['action'] = 'drop';

			if (!is_array($dropsid[$gid])) {
				$dropsid[$gid] = array();
			}

			if (!is_array($dropsid[$gid][$sid])) {
				$dropsid[$gid][$sid] = array();
			}

			$dropsid[$gid][$sid] = "dropsid";		
		}
	}

	if ($tmp_alert_new) { 
		$rules_alert = explode("||", $tmp_alert_new);

		foreach($rules_alert as $rule) {
			$rule_alert = explode(":", $rule);
			$gid = $rule_alert[0];
			$sid = $rule_alert[1];
			$alert_rule = str_replace(array("(",")"), array("", ""), $rule_alert[2]);
			$alert_item = explode("|", $alert_rule);
			$ip_src = $alert_item[0];
			$port_src = $alert_item[1];
			$direction = $alert_item[2];
			$ip_dst = $alert_item[3];
			$port_dst = $alert_item[4];

			$test = "{$gid}:{$sid}";

			if (in_array($test, $tmp_arr)) {			
				continue;
			}

			if ($dropsid[2][$sid] == "dropsid")
				continue;		

			$tmp_alert .= "{$gid}:{$sid}:({$users}|$port_src|$direction|$ip_dst|$port_dst)||";
		
			$rules_map[$gid][$sid]['action'] = 'alert';

			if (!is_array($alertsid[$gid])) {
				$alertsid[$gid] = array();
			}

			if (!is_array($alertsid[$gid][$sid])) {
				$alertsid[$gid][$sid] = array();
			}
								 
			$alertsid[$gid][$sid] = "alertsid";
						 
			if (isset($dropsid[$gid][$sid])) {
				unset($dropsid[$gid][$sid]);
			}
			 
		}
		
	}

}

$tmp_alert = rtrim($tmp_alert, "||");

if (!empty($tmp_alert)) {
	$a_rule[$id]['rule_sid_force_alert'] = $tmp_alert;
	$config['system']['firewallapp']['profile'][$idx]['rule_sid_force_alert'] = $tmp_alert; 
} else {
	unset($a_rule[$id]['rule_sid_force_alert']);
	unset($tmp_alert, $tmp_alert_drop);
}

$tmp_drop = rtrim($tmp_drop, "||");
		
if (!empty($tmp_drop)) {
	$a_rule[$id]['rule_sid_force_drop'] = $tmp_drop;
	$config['system']['firewallapp']['profile'][$idx]['rule_sid_force_drop'] = $tmp_drop;
} else {
	unset($a_rule[$id]['rule_sid_force_drop']);
	unset($tmp_drop);
}

//FAPP YAML
exec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

// Update our in-memory rules map with the changes just saved
// to the Suricata configuration file.
suricata_modify_sids_action($rules_map, $a_rule[$id]);

// Save new configuration
write_config("FirewallAPP pkg: save modified custom rules for {$a_rule[$id]['interface']}.");

$rebuild_rules = true;
suricata_generate_yaml($a_rule[$id]);
$rebuild_rules = false;

// Signal Suricata to "live reload" the rules
suricata_reload_config($a_rule[$id]);

// Sync to configured CARP slaves if any are enabled
suricata_sync_on_changes();

// Clear block table
$suri_pf_table = SURICATA_PF_TABLE;
exec("/sbin/pfctl -t {$suri_pf_table} -T flush");
?>
