<?php

require("config.inc");
require_once("/etc/inc/util.inc");
require_once("/etc/inc/firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

if ($_POST['tableTarget'] && !empty($_POST['tableTarget']) && isset($_POST['addressTarget']) && !empty($_POST['addressTarget'])) {
    mwexec("/sbin/pfctl -t " . trim($_POST['tableTarget']) . " -T delete " . trim($_POST['addressTarget']));
}

if ($_POST['updateTable'] && !empty($_POST['updateTable']) && isset($_POST['filterQtdAlerts']) && !empty($_POST['filterQtdAlerts']) && isset($_POST['search_iptables'])) {

    $all_gtw = getInterfacesInGatewaysWithNoExceptions();
	$all_heuristic_interfaces = get_heuristc_interfaces();
    $all_log_address = [];
    $retornoTable = "";
    $all_ip_table_key = [];
    $filterQtdAlerts = $_POST['filterQtdAlerts'];

    foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -sT | grep -E 'fapp2c_b_|fapp2c_ips_' | grep -v grep"))))) as $tableNow) {
        foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -t $tableNow -T show"))))) as $valuesIps) {
            $all_ip_table_key[$tableNow][] = trim($valuesIps);
        }
    }

	foreach($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		if (!empty($suricatacfg)) {
            $if_real_now = get_real_interface($suricatacfg['interface']);
			if (!in_array($if_real_now, $all_heuristic_interfaces)) {
				$suricata_uuid = $suricatacfg['uuid'];
				if (!in_array($if_real_now, $all_gtw)) {
                    if (file_exists("/var/log/suricata/suricata_{$if_real_now}{$suricata_uuid}/alerts.log")) {
                        foreach(array_unique(array_filter(explode("\n", trim(shell_exec("tail -n {$filterQtdAlerts} /var/log/suricata/suricata_{$if_real_now}{$suricata_uuid}/alerts.log | grep -E \"\[Drop\]|\[wDrop\]\" | awk '{\$1=\"\"; \$0 = \$0; \$1 = \$1; print \$0}'"))))) as $lineLog) {
                            if (!empty($_POST['search_iptables'])) {
                                if (strpos($lineLog, $_POST['search_iptables']) !== false) {
                                    $showTable = true;
                                }
                            } else {
                                $showTable = true;
                            }
                            if ($showTable && !empty($lineLog)) {
                                $gid_id = explode(":", explode("] [", $lineLog)[2]);
                                $group_service = explode("]", explode("[Classification: ", $lineLog)[1])[0];
                                $address = explode(" ", explode("} ", $lineLog)[1]);
                                $addressSTR = trim(explode(":", $address[0])[0]);
                                $addressDST = trim(explode(":", $address[2])[0]);
                                $table_complement = $gid_id[0];
                                if (intval($gid_id[0]) == 1) { 
                                    $table_complement .= $gid_id[1];
                                }
                                if (in_array($addressSTR, $all_ip_table_key["fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}"]) || 
                                    in_array($addressDST, $all_ip_table_key["fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}"])
                                ) {
                                    $retornoTable .= "<tr>";
                                    $retornoTable .= "<th>{$gid_id[0]}</th>";
                                    $retornoTable .= "<th>{$gid_id[1]}</th>";
                                    $retornoTable .= "<th>{$group_service}</th>";
                                    $retornoTable .= "<th>{$addressSTR}";
                                    if (in_array($addressSTR, $all_ip_table_key["fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                                        $retornoTable .= "<i class='fa fa-times-circle text-danger icon-primary' style='margin-left: 10px;' title='Endereço listado em fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='deleteAddressTotable(\"fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressSTR}\")' title='Deletar endereço da tabela fapp2c_b_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                                    }
                                    $retornoTable .= "</th>";
                                    $retornoTable .= "<th>{$addressDST}";
                                    if (in_array($addressDST, $all_ip_table_key["fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}"])) {
                                        $retornoTable .= "<i class='fa fa-times-circle text-danger icon-primary' style='margin-left: 10px;' title='Endereço listado em fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                                        $retornoTable .= "<i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='deleteAddressTotable(\"fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}\", \"{$addressDST}\")' title='Deletar endereço da tabela fapp2c_ips_{$if_real_now}{$suricata_uuid}_{$table_complement}'/>";
                                    }
                                    $retornoTable .= "</th>";
                                    $retornoTable .= "</tr>";
                                }
                            }
                        }
                    } 
                }
            }
        }    
    }
    if (!empty($retornoTable)) {
        echo $retornoTable;
    } else {
        $retornoTable .= "<tr>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "</tr>";
        echo $retornoTable;
    }
}
