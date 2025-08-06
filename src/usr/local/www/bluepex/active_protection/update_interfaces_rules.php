<?php

require_once("config.inc");
require_once("util.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

init_config_arr(array('installedpackages', 'suricata', 'rule'));

$a_instance = &$config['installedpackages']['suricata']['rule'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

$tw_includes1 = file_get_contents("/etc/tw_includes");

if (($tw_includes1 == "with traffic") || (empty($tw_includes1))) {
        exec("cd /usr/local/share/suricata/rules/ && rm * && cp /usr/local/share/suricata/rules_acp/_ameacas.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules_fapp/rede_sociais.rules /usr/local/share/suricata/rules/ && cp rules_fapp/portais.rules /usr/local/share/suricata/rules/ && cp rules_fapp/outros.rules /usr/local/share/suricata/rules/ && cp rules_fapp/streaming.rules /usr/local/share/suricata/rules/");
} else if ($tw_includes1 == "only") {
        exec("cd /usr/local/share/suricata/rules/ && rm * && cp /usr/local/share/suricata/rules_acp/_ameacas.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/reputation.list /usr/local/share/suricata/rules/");
}

exec("cp -f /usr/local/pkg/suricata/yalm/acp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {

    $if = get_real_interface(strtolower($suricatacfg['interface']));
    $uuid = $suricatacfg['uuid'];
            
    if (suricata_is_running($uuid, $if)) {
                        
        if (in_array($if, $all_gtw,true)) {
            if ($suricatacfg['enable'] != 'on' || $if == "") {
                continue;
            }

            if ($suricatacfg['mixed_mode'] == 'on') {

                exec("cd /usr/local/share/suricata/rules/ && rm *");
                exec("cp /usr/local/share/suricata/acp_mix/suricata2.rules /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/");
                exec("cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/");

            }

            $dh  = opendir("/usr/local/share/suricata/rules_acp");

            $rulesetsfile = "";
            while (false !== ($filename = readdir($dh))) {
                if (count(explode(".",$filename)) == 2) {
                    if (substr($filename, -5) != "rules")
                    continue;

                    $rulesetsfile .= basename($filename) . "||";
                }
                
            }

            $config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");

            file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');

            // Save configuration changes
            write_config("Active Protection pkg: modified ruleset configuration");

            exec("cp /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/");
            exec("cp /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/");
        
            $ruledir = "{$suricata_rules_dir}";
            $currentfile = $_POST['currentfile'];
            $rulefile = "{$ruledir}{$currentfile}";
            $a_rule = &$config['installedpackages']['suricata']['rule'][$key];
            $rules_map = suricata_load_rules_map($rulefile);
            suricata_modify_sids_action($rules_map, $a_rule);
        
            $rebuild_rules = true;
            suricata_generate_yaml($a_rule);
            $rebuild_rules = false;

            suricata_reload_config($a_rule);

            suricata_sync_on_changes();

            if ($suricatacfg['mixed_mode'] == 'on') {
                suricata_start_mixed_mode($suricatacfg, $if, $uuid);
            }

        }
    }
}

mwexec("cd /usr/local/share/suricata/ && cp rules_fapp/* rules", true);
mwexec("/bin/sh /usr/local/www/active_protection/av/generatePersistentEve.sh");
