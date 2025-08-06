<?php

require("/etc/inc/config.inc");
require_once("/etc/inc/util.inc");
require_once("/etc/inc/firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/etc/inc/firewallapp_webservice.inc");
ini_set('memory_limit', '512MB');

//Get token
function get_token() {
	$url = "http://wsutm.bluepex.com:33777/api/login";

	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_POST => true,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POSTFIELDS => "user=devutm&pwd=bddda08abb3cfbc5f04ad561d880cead",
		CURLOPT_HTTPHEADER => array("Content-Type: application/x-www-form-urlencoded"),
	));

	$resp = json_decode(curl_exec($curl), true);
	curl_close($curl);

	$token = "";
	if (isset($resp["token"])) {
		$token = $resp["token"];
	}

	return $token;
}

//Enviar valores
function set_values($token, $valores) {
	$url = "http://wsutm.bluepex.com:33777/api/fpositive_new/$valores";
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $url,
		CURLOPT_CUSTOMREQUEST => "POST",
		CURLOPT_HTTPHEADER => array("x-access-token:$token"),
	));
	curl_exec($curl);
	curl_close($curl);
	return;
}

function deleteValueInIptabes($tableTarget, $addressTarget) {
    mwexec("/sbin/pfctl -t {$tableTarget} -T delete {$addressTarget}");
}

if ($_POST['tableTarget'] && !empty($_POST['tableTarget']) && isset($_POST['addressTarget']) && !empty($_POST['addressTarget'])) {
    deleteValueInIptabes(trim($_POST["tableTarget"]), trim($_POST["addressTarget"]));
}

if ($_POST['tableTargetDeleteRule'] && !empty($_POST['tableTargetDeleteRule']) && isset($_POST['addressTargetDeleteRule']) && !empty($_POST['addressTargetDeleteRule']) && isset($_POST['interfaceRealDeleteRule']) && !empty($_POST['interfaceRealDeleteRule']) && isset($_POST['sidTargetDeleteRule']) && !empty($_POST['sidTargetDeleteRule']) && isset($_POST['gidTargetDeleteRule']) && !empty($_POST['gidTargetDeleteRule'])) {

    //tableTargetDeleteRule
    //addressTargetDeleteRule
    //interfaceRealDeleteRule
    //sidTargetDeleteRule
    //gidTargetDeleteRule

    global $config;

    deleteValueInIptabes(trim($_POST["tableTargetDeleteRule"]), trim($_POST["addressTargetDeleteRule"]));
    $sidTargetDeleteRule = trim($_POST['sidTargetDeleteRule']);
    $gidTargetDeleteRule = trim($_POST['gidTargetDeleteRule']);

    $all_gtw = getInterfacesInGatewaysWithNoExceptions();

    mwexec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");
    mwexec("rm /usr/local/share/suricata/rules_fapp/_emerging.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas_ext.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas.rules");
    mwexec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");			

    foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
        $if = get_real_interface($suricatacfg['interface']);
        $uuid = $suricatacfg['uuid'];
        if ($if == trim($_POST["interfaceRealDeleteRule"])) {
            if (!in_array($if, $all_gtw, true)) {
                if (isset($config['installedpackages']['suricata']['rule'][$key]['rule_sid_force_drop']) && !empty($config['installedpackages']['suricata']['rule'][$key]['rule_sid_force_drop'])) {
                    if ($gidTargetDeleteRule == 1) {
                        $rule_sid_force_drop = [];
                        foreach(array_filter(array_unique(explode('||', $config['installedpackages']['suricata']['rule'][$key]['rule_sid_force_drop']))) as $rule_now) {
                            $valuesNow = explode(":", $rule_now);
                            if ("{$gidTargetDeleteRule}:{$sidTargetDeleteRule}" != "{$valuesNow[0]}{$valuesNow[1]}") {
                                $rule_sid_force_drop[] = $rule_now;
                            }
                        }
                        $config['installedpackages']['suricata']['rule'][$key]['rule_sid_force_drop'] = implode('||',$rule_sid_force_drop);
                    } else {
                        $rule_sid_force_drop = [];
                        foreach(array_filter(array_unique(explode('||', $config['installedpackages']['suricata']['rule'][$key]['rule_sid_force_drop']))) as $rule_now) {
                            $valuesNow = explode(":", $rule_now);
                            if ("{$gidTargetDeleteRule}" != "{$valuesNow[0]}") {
                                $rule_sid_force_drop[] = $rule_now;
                            }
                        }
                        $config['installedpackages']['suricata']['rule'][$key]['rule_sid_force_drop'] = implode('||',$rule_sid_force_drop);
                    }
                }
                break;
            }
        }
    }
    write_config("Remove rule {$gidTargetDeleteRule}:{$sidTargetDeleteRule} from Fapp interface");
    mwexec("/usr/local/bin/php /usr/local/www/firewallapp/update_rules_delete_traffic_fapp_argv.php {$_POST['interfaceRealDeleteRule']}");
}

if ($_POST['remove_add_false_rule'] && isset($_POST['remove_add_false_rule']) && !empty($_POST['remove_add_false_rule'])) {

    $remove_add_false_rule = trim($_POST['remove_add_false_rule']);

    if (file_exists('/etc/rules_false_select')) {
        if (intval(trim(shell_exec("grep '{$remove_add_false_rule}' /etc/rules_false_select | grep -v grep | wc -l"))) > 0) {
            shell_exec("grep -v '{$remove_add_false_rule}' /etc/rules_false_select > /etc/rules_false_select.tmp");
            shell_exec("cp /etc/rules_false_select.tmp /etc/rules_false_select");
            shell_exec("rm /etc/rules_false_select.tmp");
        } else {
            if (intval(trim(shell_exec("grep '{$remove_add_false_rule}' /etc/rules_false_select | grep -v grep | wc -l"))) == 0) {
                file_put_contents('/etc/rules_false_select', trim($remove_add_false_rule) . "\n", FILE_APPEND);
            }
        }
    } else {
        file_put_contents('/etc/rules_false_select', trim($remove_add_false_rule) . "\n", FILE_APPEND);
    }

    $post_false_positive = false;
    if (file_exists('/etc/rules_false_select_send')) {
        if (intval(trim(shell_exec("grep '{$remove_add_false_rule}' /etc/rules_false_select_send | grep -v grep | wc -l"))) == 0) {
            file_put_contents('/etc/rules_false_select_send', trim($remove_add_false_rule) . "\n", FILE_APPEND);
            $post_false_positive = true;
        }
    } else {
        file_put_contents('/etc/rules_false_select_send', trim($remove_add_false_rule) . "\n", FILE_APPEND);
        $post_false_positive = true;
    }

    if ($post_false_positive) {
        $serial = "";
        if (file_exists("/etc/serial")) {
            $serial = trim(file_get_contents("/etc/serial"));
            $token = get_token();
            if (!empty($token)) {
                ob_start();
                set_values($token, $serial .  "&1&" . $remove_add_false_rule);
                ob_end_clean();
            }
        }
    }

}

if (isset($_POST['getConnection']) &&  !empty($_POST['getConnection']) && isset($_POST['addressSRC']) && !empty($_POST['addressSRC']) && isset($_POST['addressDST']) && !empty($_POST['addressDST']) && isset($_POST['portDST']) && !empty($_POST['portDST'])) {
    shell_exec("timeout 10 telnet -S {$_POST['addressSRC']} {$_POST['addressDST']} {$_POST['portDST']} > /tmp/telnet_return");
    shell_exec("grep 'Connected to' /tmp/telnet_return > /tmp/telnet_return.tmp && cp /tmp/telnet_return.tmp /tmp/telnet_return && rm /tmp/telnet_return.tmp");
    $return_value = strlen(trim(file_get_contents('/tmp/telnet_return')));
    unlink('/tmp/telnet_return');
    echo $return_value;
}

if (isset($_POST['getConnection']) &&  !empty($_POST['getConnection']) && isset($_POST['getInterface']) && !empty($_POST['getInterface']) && isset($_POST['getURL']) && !empty($_POST['getURL'])) {
    $getPort = 443;
    if (isset($_POST['getPort']) && !empty($_POST['getPort'])) {
        $getPort = trim($_POST['getPort']);
    }
    shell_exec("timeout 10 telnet {$_POST['getURL']} {$getPort} > /tmp/telnet_return_connection");
    shell_exec("grep 'Trying ' /tmp/telnet_return_connection > /tmp/telnet_return_connection.tmp");
    shell_exec("cp /tmp/telnet_return_connection.tmp /tmp/telnet_return_connection && rm /tmp/telnet_return_connection.tmp");
    //    Trying 186.192.81.31...
    $return_value = trim(explode("...",explode("Trying ", trim(file_get_contents('/tmp/telnet_return_connection')))[1])[0]);
    unlink('/tmp/telnet_return_connection');
    echo $return_value;
}

if ($_POST['updateTable'] && !empty($_POST['updateTable']) && isset($_POST['filterMarkShow']) && !empty($_POST['filterMarkShow']) && isset($_POST['filterInterface']) && !empty($_POST['filterInterface']) && isset($_POST['filterQtdAlerts']) && !empty($_POST['filterQtdAlerts']) && isset($_POST['search_iptables'])) {

    $all_log_address = [];
    $retornoTable = "";
    $all_ip_table_key = [];
    $filterInterface = $_POST['filterInterface'];
    $filterQtdAlerts = $_POST['filterQtdAlerts'];
    $filterMarkShow = $_POST['filterMarkShow'];
    $rules_acp = [];
    $all_ip_table_ugid_ignore_fixed = [];
    $all_ip_table_ugid_ignore_local = [];
    if (file_exists('/usr/local/share/suricata/ugid_ignore')) {
        $all_ip_table_ugid_ignore_fixed = array_filter(array_unique(explode("\n", file_get_contents("/usr/local/share/suricata/ugid_ignore"))));
    }
    if (file_exists('/usr/local/share/suricata/ugid_ignore_local')) {
        $all_ip_table_ugid_ignore_local = array_filter(array_unique(explode("\n", file_get_contents("/usr/local/share/suricata/ugid_ignore_local"))));
    }

    foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -sT | grep -E 'fapp2c_|fapp2c_ips_|acp2c_' | grep -v grep | grep -v fapp2c_b_ | grep -v fapp2c_p_"))))) as $tableNow) {
        foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -t $tableNow -T show"))))) as $valuesIps) {
            $all_ip_table_key[$tableNow][] = trim($valuesIps);
        }
    }

	foreach($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
        if (!empty($suricatacfg)) {
            if ($filterInterface == get_real_interface($suricatacfg['interface'])) {
                $retornoTable .= get_log_values_of_interface($suricatacfg, $filterQtdAlerts, $all_ip_table_key, $rules_acp, $_POST['search_iptables'], $filterMarkShow, $all_ip_table_ugid_ignore_fixed, $all_ip_table_ugid_ignore_local);
            }
        }
    } 

    if (empty(trim($retornoTable))) {
        $retornoTable .= "<tr>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "</tr>";
    }

    echo $retornoTable;

}

function get_log_values_of_interface($suricatacfg, $filterQtdAlerts, $all_ip_table_key, $rules_acp, $search_iptables, $filterMarkShow, $all_ip_table_ugid_ignore_fixed, $all_ip_table_ugid_ignore_local) {
    $if_real_now = get_real_interface($suricatacfg['interface']);
    $suricata_uuid = $suricatacfg['uuid'];
    $retornoTable = "";

    if (file_exists('/etc/rules_false_select')) {
        $rules_target = array_unique(array_filter(explode("\n",trim(file_get_contents('/etc/rules_false_select')))));
    }
    if (file_exists('/etc/rules_false_select_send')) {
        $rules_target_send = array_unique(array_filter(explode("\n",trim(file_get_contents('/etc/rules_false_select_send')))));
    }

    if ($filterMarkShow == "all") {
                    
        if (file_exists("/var/log/suricata/suricata_{$if_real_now}{$suricata_uuid}/alerts.log")) {

            foreach(array_unique(array_filter(explode("\n", trim(shell_exec("tail -n {$filterQtdAlerts} /var/log/suricata/suricata_{$if_real_now}{$suricata_uuid}/alerts.log | sort -r | awk '{\$1=\"\"; \$0 = \$0; \$1 = \$1; print \$0}' | uniq"))))) as $lineLog) {
                $showTable = false;
                if (!empty($search_iptables)) {
                    if (strpos($lineLog, $search_iptables) !== false) {
                        $showTable = true;
                    }
                } else {
                    if (empty($search_iptables)) {
                        $showTable = true;
                    }   
                }

                if ($showTable && !empty($lineLog)) {

                    $group_service = strtolower(explode("]", explode("[Classification: ", $lineLog)[1])[0]);
                    $address = explode(" ", explode("} ", $lineLog)[1]);

                    $valueSTR = explode(":", $address[0]);
                    $addressSTR = trim($valueSTR[0]);
                    $portSTR = trim($valueSTR[1]);

                    $valueDST = explode(":", $address[2]);
                    $addressDST = trim($valueDST[0]);
                    $portDST = trim($valueDST[1]);

                    $drop_rule = false;
                    if (strpos($lineLog, 'Drop]') !== false) {
                        $gid_id = explode(":", explode("] [", $lineLog)[2]);
                        $drop_rule = true;
                    } else {
                        $gid_id = explode(":", explode("] [", $lineLog)[1]);
                    }
                    $gid_rule = $gid_id[0];
                    $id_rule = $gid_id[1];
                    $table_complement = $gid_id[0];
                    if (intval($gid_id[0]) == 1) { 
                        $table_complement .= $gid_id[1];
                    }

                    $line_color = "";
                    $line_color .= "{$if_real_now}___";
                    $line_color .= "{$suricata_uuid}___";
                    $line_color .= "{$gid_rule}___";
                    $line_color .= "{$id_rule}___";
                    if ($drop_rule) {
                        $line_color .= "droptrue___";
                    } else {
                        $line_color .= "dropfalse___";
                    }
                    $line_color .= "{$group_service}___";
                    $line_color .= "{$addressSTR}___";
                    $line_color .= "{$portSTR}___";
                    $line_color .= "{$addressDST}___";
                    $line_color .= "{$portDST}";

                    $change_color_line = '';
                    
                    if (file_exists('/etc/rules_false_select')) {
                        if (!empty($rules_target)) {
                            if (in_array($line_color, $rules_target)) {
                                $change_color_line = "style='background-color:#FFA500 !important;'";
                                $add_change_color_line = "background-color:#FFA500 !important;";
                            } 
                        }
                    }

                    $retornoTable .= "<tr>";
                    $retornoTable .= "<th {$change_color_line}>{$suricatacfg['descr']} ({$if_real_now})</th>";
                    $retornoTable .= "<th {$change_color_line}>{$gid_rule}</th>";
                    if (!empty($rules_acp) && in_array($id_rule, $rules_acp)) {
                        $retornoTable .= "<th onclick=\"submitSearchRulesThreads('{$id_rule}')\" style='{$add_change_color_line} color: #177bb4 !important; text-decoration: underline !important' title='Fetch SID rule record: {$id_rule} in Active Protection'>{$id_rule}</th>";
                    } else {
                        $retornoTable .= "<th {$change_color_line}>{$id_rule}</th>";    
                    }
                    if ($drop_rule) {
                        $retornoTable .= "<th {$change_color_line}>DROP</th>";
                    } else {
                        $retornoTable .= "<th {$change_color_line}>-</th>";
                    }
                    $retornoTable .= "<th {$change_color_line}>{$group_service}</th>";
                    $retornoTable .= "<th {$change_color_line}>{$addressSTR}";
                    $first_show = false;
                    if (array_key_exists("fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}", $all_ip_table_key)) {
                        if (in_array($addressSTR, $all_ip_table_key["fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                            $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                            $first_show = true;
                        }
                    }
                    if (array_key_exists("fapp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                        if (in_array($addressSTR, $all_ip_table_key["fapp2c_{$if_real_now}{$suricata_uuid}"])) {
                            $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_{$if_real_now}{$suricata_uuid}'/>";
                            $first_show = true;
                        }
                    }
                    if (array_key_exists("acp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                        if (in_array($addressSTR, $all_ip_table_key["acp2c_{$if_real_now}{$suricata_uuid}"])) {
                            if ($first_show) {
                                $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                            }
                            $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"acp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela acp2c_{$if_real_now}{$suricata_uuid}'/>";
                        }
                    }
                    $retornoTable .= "</th>";
                    $retornoTable .= "<th {$change_color_line}>{$portSTR}</th>";
                    $retornoTable .= "<th {$change_color_line}>{$addressDST}";
                    $first_show = false;
                    if (array_key_exists("fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}", $all_ip_table_key)) {
                        if (in_array($addressDST, $all_ip_table_key["fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                            $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                            $first_show = true;
                        }
                    }
                    if (array_key_exists("fapp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                        if (in_array($addressDST, $all_ip_table_key["fapp2c_{$if_real_now}{$suricata_uuid}"])) {
                            $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_{$if_real_now}{$suricata_uuid}'/>";
                            $first_show = true;
                        }
                    }
                    if (array_key_exists("acp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                        if (in_array($addressDST, $all_ip_table_key["acp2c_{$if_real_now}{$suricata_uuid}"])) {
                            if ($first_show) {
                                $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                            }
                            $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"acp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela acp2c_{$if_real_now}{$suricata_uuid}'/>";
                        }
                    }
                    if (in_array($addressDST, $all_ip_table_ugid_ignore_fixed)) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-lock icon-primary' style='margin-left: 10px;' title='Endereço está marcado como um exceção fixa de bloqueios no modo performance do FirewallApp'/>";
                    } else {
                        if (in_array($addressDST, $all_ip_table_ugid_ignore_local)) {
                            if ($first_show) {
                                $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                            }
                            $retornoTable .= "<i class='fa fa fa-times icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$addressDST}\", \"remove\")' title='Desmarcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                        } else {
                            if ($first_show) {
                                $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                            }
                            $retornoTable .= "<i class='fa fa fa-floppy-o icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$addressDST}\", \"add\")' title='Marcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                        }
                    }

                    $retornoTable .= "</th>";
                    $retornoTable .= "<th {$change_color_line}>{$portDST}</th>";
                    $retornoTable .= "<th {$change_color_line}>";
                    $select_icon_send = true;
                    if (file_exists('/etc/rules_false_select_send')) {
                        if (!empty($rules_target_send)) {
                            if (in_array($line_color, $rules_target_send)) {
                                $retornoTable .= "<i class='fa fa-send icon-primary' style='margin-right: 10px !important;' aria-hidden='true' title='Traffic sent as \"false/positive\"'></i>" . $i_change_state;
                                $select_icon_send = false;
                            }
                        }
                    }
                    if ($select_icon_send) {
                        $retornoTable .= "<i class='fa fa-send-o icon-primary' style='margin-right: 10px !important;' aria-hidden='true' title='Traffic not sent as \"false/positive\"'></i>" . $i_change_state;
                    }
                    $select_icon_mark = true;
                    if (file_exists('/etc/rules_false_select')) {
                        if (!empty($rules_target)) {
                            if (in_array($line_color, $rules_target)) {
                                $retornoTable .= "<i class='fa fa-bookmark' aria-hidden='true' title='Deselecting \"false/positive\" will only remove the highlight color from the row, the request will remain.' onclick=\"remove_add_false_rule('{$line_color}')\"></i>";
                                $select_icon_mark = false;
                            } 
                        }
                    }
                    if ($select_icon_mark) {
                        $retornoTable .= "<i class='fa fa-bookmark-o' aria-hidden='true' title='Marking as \"false/positive\" will trigger a verification request on the signature with a 24 hour turnaround time via rule updates.' onclick=\"remove_add_false_rule('{$line_color}')\"></i>";
                    }
                    $retornoTable .= "<i class='fa fa-rss' style='margin-left: 10px;' aria-hidden='true' onclick='telnet_test_connection(\"{$addressSTR}\", \"{$addressDST}\", \"{$portDST}\")'></i>";
                    $retornoTable .= "</th>";
                    $retornoTable .= "</tr>";
                }
            }
        }
    } elseif ($filterMarkShow == "onlymark") {
        
        mwexec("grep '{$search_iptables}' /etc/rules_false_select | tail -n{$filterQtdAlerts} > /tmp/rules_false_select_tmp");
        if (file_exists('/tmp/rules_false_select_tmp')) {
            $rules_false_select_tmp = array_unique(array_filter(explode("\n",trim(file_get_contents('/tmp/rules_false_select_tmp')))));
        }

        foreach($rules_false_select_tmp as $rules_now) {
            $values_now = explode("___", $rules_now);
            if ("{$values_now[0]}{$values_now[1]}" == "{$if_real_now}{$suricata_uuid}") {
                $change_color_line = "style='background-color:#FFA500 !important;'";
                $add_change_color_line = "background-color:#FFA500 !important;";
                $line_color = $rules_now;

                $retornoTable .= "<tr>";
                $retornoTable .= "<th {$change_color_line}>{$suricatacfg['descr']} ({$if_real_now})</th>";
                $retornoTable .= "<th {$change_color_line}>{$values_now[2]}</th>";
                $id_rule = $values_now[3];
                if (!empty($rules_acp) && in_array($id_rule, $rules_acp)) {
                    $retornoTable .= "<th onclick=\"submitSearchRulesThreads('{$id_rule}')\" style='{$add_change_color_line} color: #177bb4 !important; text-decoration: underline !important' title='Fetch SID rule record: {$id_rule} in Active Protection'>{$id_rule}</th>";
                } else {
                    $retornoTable .= "<th {$change_color_line}>{$id_rule}</th>";    
                }

                if ($values_now[4] == "droptrue") {
                    $retornoTable .= "<th {$change_color_line}>DROP</th>";
                } else {
                    $retornoTable .= "<th {$change_color_line}>-</th>";
                }
                $group_service = strtolower(trim($values_now[5]));
                $retornoTable .= "<th {$change_color_line}>{$group_service}</th>";

                $addressSTR = $values_now[6];
                $portSTR = $values_now[7];
                $addressDST = $values_now[8];
                $portDST = $values_now[9];

                $retornoTable .= "<th {$change_color_line}>{$addressSTR}";
                $first_show = false;
                if (array_key_exists("fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}", $all_ip_table_key)) {
                    if (in_array($addressSTR, $all_ip_table_key["fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("fapp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressSTR, $all_ip_table_key["fapp2c_{$if_real_now}{$suricata_uuid}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_{$if_real_now}{$suricata_uuid}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("acp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressSTR, $all_ip_table_key["acp2c_{$if_real_now}{$suricata_uuid}"])) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"acp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela acp2c_{$if_real_now}{$suricata_uuid}'/>";
                    }
                }
                $retornoTable .= "</th>";
                $retornoTable .= "<th {$change_color_line}>{$portSTR}</th>";
                $retornoTable .= "<th {$change_color_line}>{$addressDST}";
                $first_show = false;
                if (array_key_exists("fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}", $all_ip_table_key)) {
                    if (in_array($addressDST, $all_ip_table_key["fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("fapp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressDST, $all_ip_table_key["fapp2c_{$if_real_now}{$suricata_uuid}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_{$if_real_now}{$suricata_uuid}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("acp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressDST, $all_ip_table_key["acp2c_{$if_real_now}{$suricata_uuid}"])) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"acp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela acp2c_{$if_real_now}{$suricata_uuid}'/>";
                    }
                }
                if (in_array($addressDST, $all_ip_table_ugid_ignore_fixed)) {
                    if ($first_show) {
                        $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                    }
                    $retornoTable .= "<i class='fa fa fa-lock icon-primary' style='margin-left: 10px;' title='Endereço está marcado como um exceção fixa de bloqueios no modo performance do FirewallApp'/>";
                } else {
                    if (in_array($addressDST, $all_ip_table_ugid_ignore_local)) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-times icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$addressDST}\", \"remove\")' title='Desmarcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                    } else {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-floppy-o icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$addressDST}\", \"add\")' title='Marcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                    }
                }
                $retornoTable .= "<th {$change_color_line}>{$portDST}</th>";
                $retornoTable .= "<th {$change_color_line}>";
                $select_icon_send = true;
                if (file_exists('/etc/rules_false_select_send')) {
                    if (!empty($rules_target_send)) {
                        if (in_array($line_color, $rules_target_send)) {
                            $retornoTable .= "<i class='fa fa-send icon-primary' style='margin-right: 10px !important;' aria-hidden='true' title='Traffic sent as \"false/positive\"'></i>" . $i_change_state;
                            $select_icon_send = false;
                        }
                    }
                }
                if ($select_icon_send) {
                    $retornoTable .= "<i class='fa fa-send-o icon-primary' style='margin-right: 10px !important;' aria-hidden='true' title='Traffic not sent as \"false/positive\"'></i>" . $i_change_state;
                }
                $select_icon_mark = true;
                if (file_exists('/etc/rules_false_select')) {
                    if (!empty($rules_target)) {
                        if (in_array($line_color, $rules_target)) {
                            $retornoTable .= "<i class='fa fa-bookmark' aria-hidden='true' title='Deselecting \"false/positive\" will only remove the highlight color from the row, the request will remain.' onclick=\"remove_add_false_rule('{$line_color}')\"></i>";
                            $select_icon_mark = false;
                        } 
                    }
                }
                if ($select_icon_mark) {
                    $retornoTable .= "<i class='fa fa-bookmark-o' aria-hidden='true' title='Marking as \"false/positive\" will trigger a verification request on the signature with a 24 hour turnaround time via rule updates.' onclick=\"remove_add_false_rule('{$line_color}')\"></i>";
                }
                $retornoTable .= "<i class='fa fa-rss' style='margin-left: 10px;' aria-hidden='true' onclick='telnet_test_connection(\"{$addressSTR}\", \"{$addressDST}\", \"{$portDST}\")'></i>";
                $retornoTable .= "</th>";
                $retornoTable .= "</tr>";
            }
        }
    } elseif ($filterMarkShow == "onlysend") {
        mwexec("grep '{$search_iptables}' /etc/rules_false_select_send | tail -n{$filterQtdAlerts} > /tmp/rules_false_select_send_tmp");
        if (file_exists('/tmp/rules_false_select_tmp')) {
            $rules_false_select_send_tmp = array_unique(array_filter(explode("\n",trim(file_get_contents('/tmp/rules_false_select_send_tmp')))));
        }
        
        foreach($rules_false_select_send_tmp as $rules_now) {
            $values_now = explode("___", $rules_now);
            if ("{$values_now[0]}{$values_now[1]}" == "{$if_real_now}{$suricata_uuid}") {
                $line_color = $rules_now;

                $change_color_line = '';
                $add_change_color_line = '';
                        
                if (file_exists('/etc/rules_false_select')) {
                    if (!empty($rules_now)) {
                        if (in_array($rules_now, $rules_target)) {
                            $change_color_line = "style='background-color:#FFA500 !important;'";
                            $add_change_color_line = "background-color:#FFA500 !important;";
                        } 
                    }
                }

                $retornoTable .= "<tr>";
                $retornoTable .= "<th {$change_color_line}>{$suricatacfg['descr']} ({$if_real_now})</th>";
                $retornoTable .= "<th {$change_color_line}>{$values_now[2]}</th>";
                $id_rule = $values_now[3];
                if (!empty($rules_acp) && in_array($id_rule, $rules_acp)) {
                    $retornoTable .= "<th onclick=\"submitSearchRulesThreads('{$id_rule}')\" style='{$add_change_color_line} color: #177bb4 !important; text-decoration: underline !important' title='Fetch SID rule record: {$id_rule} in Active Protection'>{$id_rule}</th>";
                } else {
                    $retornoTable .= "<th {$change_color_line}>{$id_rule}</th>";    
                }

                if ($values_now[4] == "droptrue") {
                    $retornoTable .= "<th {$change_color_line}>DROP</th>";
                } else {
                    $retornoTable .= "<th {$change_color_line}>-</th>";
                }
                $group_service = strtolower(trim($values_now[5]));
                $retornoTable .= "<th {$change_color_line}>{$group_service}</th>";

                $addressSTR = $values_now[6];
                $portSTR = $values_now[7];
                $addressDST = $values_now[8];
                $portDST = $values_now[9];

                $retornoTable .= "<th {$change_color_line}>{$addressSTR}";
                $first_show = false;
                if (array_key_exists("fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}", $all_ip_table_key)) {
                    if (in_array($addressSTR, $all_ip_table_key["fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("fapp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressSTR, $all_ip_table_key["fapp2c_{$if_real_now}{$suricata_uuid}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_{$if_real_now}{$suricata_uuid}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("acp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressSTR, $all_ip_table_key["acp2c_{$if_real_now}{$suricata_uuid}"])) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"acp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressSTR}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela acp2c_{$if_real_now}{$suricata_uuid}'/>";
                    }
                }
                $retornoTable .= "</th>";
                $retornoTable .= "<th {$change_color_line}>{$portSTR}</th>";
                $retornoTable .= "<th {$change_color_line}>{$addressDST}";
                $first_show = false;
                if (array_key_exists("fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}", $all_ip_table_key)) {
                    if (in_array($addressDST, $all_ip_table_key["fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("fapp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressDST, $all_ip_table_key["fapp2c_{$if_real_now}{$suricata_uuid}"])) {
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"fapp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela fapp2c_{$if_real_now}{$suricata_uuid}'/>";
                        $first_show = true;
                    }
                }
                if (array_key_exists("acp2c_{$if_real_now}{$suricata_uuid}", $all_ip_table_key)) {
                    if (in_array($addressDST, $all_ip_table_key["acp2c_{$if_real_now}{$suricata_uuid}"])) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"acp2c_{$if_real_now}{$suricata_uuid}\", \"{$addressDST}\", \"{$group_service}\",  \"{$if_real_now}\", \"{$gid_rule}\", \"{$id_rule}\")' title='Deletar endereço da tabela acp2c_{$if_real_now}{$suricata_uuid}'/>";
                    }
                }
                if (in_array($addressDST, $all_ip_table_ugid_ignore_fixed)) {
                    if ($first_show) {
                        $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                    }
                    $retornoTable .= "<i class='fa fa fa-lock icon-primary' style='margin-left: 10px;' title='Endereço está marcado como um exceção fixa de bloqueios no modo performance do FirewallApp'/>";
                } else {
                    if (in_array($addressDST, $all_ip_table_ugid_ignore_local)) {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-times icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$addressDST}\", \"remove\")' title='Desmarcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                    } else {
                        if ($first_show) {
                            $retornoTable .= "<separetor style='margin-left: 10px;'>|</separetor>";
                        }
                        $retornoTable .= "<i class='fa fa fa-floppy-o icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$addressDST}\", \"add\")' title='Marcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                    }
                }
                $retornoTable .= "<th {$change_color_line}>{$portDST}</th>";
                $retornoTable .= "<th {$change_color_line}>";
                $select_icon_send = true;
                if (file_exists('/etc/rules_false_select_send')) {
                    if (!empty($rules_target_send)) {
                        if (in_array($line_color, $rules_target_send)) {
                            $retornoTable .= "<i class='fa fa-send icon-primary' style='margin-right: 10px !important;' aria-hidden='true' title='Traffic sent as \"false/positive\"'></i>" . $i_change_state;
                            $select_icon_send = false;
                        }
                    }
                }
                if ($select_icon_send) {
                    $retornoTable .= "<i class='fa fa-send-o icon-primary' style='margin-right: 10px !important;' aria-hidden='true' title='Traffic not sent as \"false/positive\"'></i>" . $i_change_state;
                }
                $select_icon_mark = true;
                if (file_exists('/etc/rules_false_select')) {
                    if (!empty($rules_target)) {
                        if (in_array($line_color, $rules_target)) {
                            $retornoTable .= "<i class='fa fa-bookmark' aria-hidden='true' title='Deselecting \"false/positive\" will only remove the highlight color from the row, the request will remain.' onclick=\"remove_add_false_rule('{$line_color}')\"></i>";
                            $select_icon_mark = false;
                        } 
                    }
                }
                if ($select_icon_mark) {
                    $retornoTable .= "<i class='fa fa-bookmark-o' aria-hidden='true' title='Marking as \"false/positive\" will trigger a verification request on the signature with a 24 hour turnaround time via rule updates.' onclick=\"remove_add_false_rule('{$line_color}')\"></i>";
                }
                $retornoTable .= "<i class='fa fa-rss' style='margin-left: 10px;' aria-hidden='true' onclick='telnet_test_connection(\"{$addressSTR}\", \"{$addressDST}\", \"{$portDST}\")'></i>";
                $retornoTable .= "</th>";
                $retornoTable .= "</tr>";
            }
        }
    }

    return $retornoTable;
}

if (isset($_POST['ipAddressValue']) &&  !empty($_POST['ipAddressValue']) && isset($_POST['actionAddress']) && !empty($_POST['actionAddress'])) {
    $ipAddressValue = trim($_POST['ipAddressValue']);
    $actionAddress = trim($_POST['actionAddress']);
    if ($actionAddress == "add") {
        file_put_contents("/usr/local/share/suricata/ugid_ignore_local", "{$ipAddressValue}\n", FILE_APPEND);
        shell_exec("cat /usr/local/share/suricata/ugid_ignore_local | sort | uniq > /usr/local/share/suricata/ugid_ignore_local.tmp && mv /usr/local/share/suricata/ugid_ignore_local.tmp /usr/local/share/suricata/ugid_ignore_local");
    } elseif ($actionAddress == "remove") {
        shell_exec("grep -v '$ipAddressValue' /usr/local/share/suricata/ugid_ignore_local > /usr/local/share/suricata/ugid_ignore_local.tmp");
        shell_exec("cp /usr/local/share/suricata/ugid_ignore_local.tmp /usr/local/share/suricata/ugid_ignore_local");
        shell_exec("rm /usr/local/share/suricata/ugid_ignore_local.tmp");
    }
}

