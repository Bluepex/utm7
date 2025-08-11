<?php
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/etc/inc/bluepex/firewallapp_functions.inc");

define("MODULE_PATH", $g['www_path'] . "/one_click");
define("LISTS_PATH", MODULE_PATH . "/lists");
define("CACHE_FILE", MODULE_PATH . "/group_categories.json");

$suricata_rules_dir = SURICATA_RULES_DIR;
$suricatalogdir = SURICATALOGDIR;

$if_friendly = null;
$if_real = null;
$suricatacfg = array();
$alias_check = false;
$passlist_check = false;

init_config_arr(array('aliases', 'alias'));
//init_config_arr(array('system', 'firewallapp', 'profile'));
//init_config_arr(array('system', 'firewallapp'));

$a_aliases = &$config['aliases']['alias'];

//$create_default_profile = true;
//$profiles = $config['system']['firewallapp'];
//$default_profile = ['profile' => ['name' => 'visitantes','group' => 'visitantes'], 'type' => 0];

//Search default profile
//foreach ($profiles as $profile_now) {
//	if ('visitantes' == trim($profile_now['name'])) {
//		$create_default_profile = false;
//		break;
//	}
//}

//Write default profile
//if ($create_default_profile) {
//	$profiles = &$config['system']['firewallapp'];
//	$profiles = $default_profile;
//	write_config("Profile: Profile visitantes create {$create_default_profile}");
//}

init_config_arr(array('installedpackages', 'suricata', 'rule'));

$suricataglob = $config['installedpackages']['suricata'];

$a_rule = &$config['installedpackages']['suricata']['rule'];

$id = 0;

$ruledir = "{$suricata_rules_dir}";
$rulefile = "{$ruledir}/bluepex-default.rules";

$rules_map = suricata_load_rules_map($rulefile);

/* Load up our rule action arrays with manually changed SID actions */
$alertsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_alert']);
$dropsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_drop']);
$passid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_pass']);

/*
 *
 * $rules_map => Mapeamento em forma de array das regras da pasta rules da raiz do suricata
 *
 * $a_rule => Mapeamento em forma de array do config.xml
 *
 */

suricata_modify_sids_action($rules_map, $a_rule[$id]);

$pconfig = array();

?>
