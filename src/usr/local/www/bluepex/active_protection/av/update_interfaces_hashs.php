<?php

require_once("config.inc");
require_once("util.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

//Don't continue process if exists is states
if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt.tmp')) {
    die;
}

if (file_exists('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt.tmp')) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep fetch_different_files_clamav | grep -v grep | wc -l"))) > 0) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep fetch_different_files_yara | grep -v grep | wc -l"))) > 1) {
    die;
}

if (intval(trim(shell_exec("ps aux | grep update_interfaces_hashs | grep -v grep | wc -l"))) > 1) {
    die;
}


init_config_arr(array('installedpackages', 'suricata', 'rule'));

$a_instance = &$config['installedpackages']['suricata']['rule'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

$hashBlackList = trim(shell_exec("sha256sum /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | awk -F\" \" '{ print $1}'"));
$hashWhiteList = trim(shell_exec("sha256sum /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | awk -F\" \" '{ print $1}'"));
$changeAmeacas = trim(shell_exec("sha256sum /usr/local/share/suricata/rules_acp/_ameacas_ext.rules | awk -F\" \" '{ print $1}'"));

if (file_exists('/etc/monitor_gateway_files') && trim(file_get_contents('/etc/monitor_gateway_files')) == "true") {

    $tw_includes1 = file_get_contents("/etc/tw_includes");

    if (($tw_includes1 == "with traffic") || (empty($tw_includes1))) {
            exec("cd /usr/local/share/suricata/rules/ && rm * && cp /usr/local/share/suricata/rules_acp/_ameacas.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules_fapp/rede_sociais.rules /usr/local/share/suricata/rules/ && cp rules_fapp/portais.rules /usr/local/share/suricata/rules/ && cp rules_fapp/outros.rules /usr/local/share/suricata/rules/ && cp rules_fapp/streaming.rules /usr/local/share/suricata/rules/");
    } else if ($tw_includes1 == "only") {
            exec("cd /usr/local/share/suricata/rules/ && rm * && cp /usr/local/share/suricata/rules_acp/_ameacas.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/reputation.list /usr/local/share/suricata/rules/");
    }

    exec("cp -f /usr/local/pkg/suricata/yalm/acp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

    for($i=0;$i<=count($config['installedpackages']['suricata']['rule'])-1;$i++) {
        $interface_now = $config['installedpackages']['suricata']['rule'][$i];
        $if_real = get_real_interface($interface_now['interface']);
        if (in_array(get_real_interface($interface_now['interface']), $all_gtw, true)) {
            if (suricata_is_running($interface_now['uuid'], $if_real)) {
                $restartInterface = false;
                $hashNowBlack = "/usr/local/etc/suricata/suricata_{$interface_now['uuid']}_{$if_real}/rules/clamav_blacklist_256.txt";
                $hashNowWhite = "/usr/local/etc/suricata/suricata_{$interface_now['uuid']}_{$if_real}/rules/clamav_whitelist_256.txt";
                $interfaceAmeacasNow = "/usr/local/etc/suricata/suricata_{$interface_now['uuid']}_{$if_real}/rules/_ameacas_ext.rules";

                if (!file_exists($hashNowBlack)) {
                    $restartInterface = true;    
                } elseif (trim(shell_exec("sha256sum {$hashNowBlack} | awk -F\" \" '{ print $1}'")) != $hashBlackList) {
                    $restartInterface = true;
                } elseif (!file_exists($hashNowWhite)) {
                    $restartInterface = true;
                } elseif (trim(shell_exec("sha256sum {$hashNowWhite} | awk -F\" \" '{ print $1}'")) != $hashWhiteList) {
                    $restartInterface = true;
                } elseif (trim(shell_exec("sha256sum {$interfaceAmeacasNow} | awk -F\" \" '{ print $1}'")) != $changeAmeacas) {
                    $restartInterface = true;
                }

                if ($restartInterface) {

                    $id = $i;
                    $suricatacfg = $interface_now;
                    $if_real = get_real_interface(strtolower($suricatacfg['interface']));
                    $if_friendly = convert_friendly_interface_to_friendly_descr($suricatacfg['interface']);
                    $suricata_uuid = $suricatacfg['uuid'];
                
                    if (suricata_is_running($suricata_uuid, $if_real)) {
                
                        foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
                            $if = get_real_interface($suricatacfg['interface']);
                            $uuid = $suricatacfg['uuid'];
                            
                            if ($suricatacfg['interface'] != $if_real) {
                                if ($if_real == $if) {
                                    if (in_array($if, $all_gtw,true)) {
                                        if ($suricatacfg['enable'] != 'on' || $if == "") {
                                            continue;
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
        
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }
}

mwexec("cd /usr/local/share/suricata/ && cp rules_fapp/* rules", true);
mwexec("/bin/sh /usr/local/www/active_protection/av/generatePersistentEve.sh");