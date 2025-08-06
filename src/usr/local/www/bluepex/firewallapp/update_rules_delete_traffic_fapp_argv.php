<?php
require_once("config.inc");
require_once("util.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

init_config_arr(array('installedpackages', 'suricata', 'rule'));

global $config;

$a_instance = &$config['installedpackages']['suricata']['rule'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
$a_gateways = return_gateways_array(true, false, true, true);

mwexec("rm /usr/local/share/suricata/rules_fapp/_emerging.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas_ext.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas.rules");
mwexec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");			
mwexec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
    $if = get_real_interface($suricatacfg['interface']);
    $uuid = $suricatacfg['uuid'];
    if ($if == trim($argv[1])) {
        if (!in_array($if, $all_gtw)) {
            if (suricata_is_running($uuid, $if)) {
                $suricata_rules_dir = SURICATA_RULES_DIR;
                $ruledir = "{$suricata_rules_dir}";
                $rulefile = "{$ruledir}";
                $a_rule = &$config['installedpackages']['suricata']['rule'][$key];
                $rules_map = suricata_load_rules_map($rulefile);
                suricata_modify_sids_action($rules_map, $a_rule);
                $rebuild_rules = true;
                suricata_generate_yaml($a_rule);
                $rebuild_rules = false;
                suricata_reload_config($a_rule);
                suricata_sync_on_changes();
                break;
            }
        }
    }
}
sync_suricata_package_config();
mwexec("cd /usr/local/share/suricata/ && cp rules_fapp/* rules", true);