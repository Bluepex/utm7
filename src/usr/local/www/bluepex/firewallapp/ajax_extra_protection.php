<?php
require("/etc/inc/config.inc");
require_once("/etc/inc/util.inc");
require_once("/etc/inc/firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/etc/inc/firewallapp_webservice.inc");
ini_set('memory_limit', '512MB');

function deleteValueInIptabes($tableTarget, $addressTarget, $gidTarget, $sidTarget) {
    if (intval(trim(shell_exec("pfctl -sT | grep fapp2c_{$tableTarget} | grep -v grep -c"))) > 0) {
        mwexec("/sbin/pfctl -t fapp2c_{$tableTarget} -T delete {$addressTarget}");
    }
    if (intval($gidTarget) == 1) {
        if (intval(trim(shell_exec("pfctl -sT | fapp2c_ips_{$tableTarget}_{$gidTarget}{$sidTarget} | grep -v grep -c"))) > 0) {
            mwexec("/sbin/pfctl -t fapp2c_ips_{$tableTarget}_{$gidTarget}{$sidTarget} -T delete {$addressTarget}");
        }
    } else {
        if (intval(trim(shell_exec("pfctl -sT | grep fapp2c_ips_{$tableTarget}_{$gidTarget} | grep -v grep -c"))) > 0) {
            mwexec("/sbin/pfctl -t fapp2c_ips_{$tableTarget}_{$gidTarget} -T delete {$addressTarget}");
        }
    }
}

function flushTableFappInterface($interfaceTableTarget) {
    foreach(explode("\n", trim(shell_exec("pfctl -sT | grep '{$interfaceTableTarget}'"))) as $tablesSimple) {
		if (!empty($tablesSimple)) {
			shell_exec("pfctl -t {$tablesSimple} -T flush");
		}
	}
}

if ($_POST['tableTarget'] && !empty($_POST['tableTarget']) && isset($_POST['addressTarget']) && !empty($_POST['addressTarget']) && isset($_POST['gidTarget']) && !empty($_POST['gidTarget']) && isset($_POST['sidTarget']) && !empty($_POST['sidTarget'])) {
    deleteValueInIptabes(trim($_POST["tableTarget"]), trim($_POST["addressTarget"]), trim($_POST["gidTarget"]), trim($_POST["sidTarget"]));
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

if (isset($_POST['interfaceTableTarget']) && !empty($_POST['interfaceTableTarget'])) {
    flushTableFappInterface($_POST['interfaceTableTarget']);
}

if ($_POST['updateTable'] && !empty($_POST['updateTable']) && isset($_POST['interface_select']) && !empty($_POST['interface_select']) && isset($_POST['filterQtdAlerts']) && !empty($_POST['filterQtdAlerts'])) {
    
    $interface_now = $_POST['interface_select'];
    $interface_now = explode("___", $interface_now);
    $descriptionInterface = explode("_",$interface_now[0])[0];
    $real_interface = explode("_",$interface_now[0])[1];
    $interface_now = $interface_now[1]; //Interface + uuid
    foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -sT | grep -E 'fapp2c_|fapp2c_ips_|acp2c_' | grep -v grep | grep -v fapp2c_b_ | grep -v fapp2c_p_"))))) as $tableNow) {
        if (strpos($tableNow,$interface_now) !== false) {
            foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -t $tableNow -T show"))))) as $valuesIps) {
                $all_ip_table_key[] = trim($valuesIps);
            }
        }
    }

    if (file_exists('/usr/local/share/suricata/ugid_ignore')) {
        $all_ip_table_ugid_ignore_fixed = array_filter(array_unique(explode("\n", file_get_contents("/usr/local/share/suricata/ugid_ignore"))));
    }
    if (file_exists('/usr/local/share/suricata/ugid_ignore_local')) {
        $all_ip_table_ugid_ignore_local = array_filter(array_unique(explode("\n", file_get_contents("/usr/local/share/suricata/ugid_ignore_local"))));
    }

    $limitTail = $_POST['filterQtdAlerts'];
    if (empty($_POST['filterQtdAlerts']) || $_POST['filterQtdAlerts'] == 0) {
        $limitTail = 10;
    }

    $noExistsValues = true;

    if (count($all_ip_table_key) > 0) {

        $allAddressIps = "";
        if (count($all_ip_table_key) == 1) {
            $allAddressIps = implode("", $all_ip_table_key);
        } else {
            $allAddressIps = implode("|", $all_ip_table_key);
        }

        foreach(explode("\n", trim(shell_exec("tail -n{$limitTail} /var/log/suricata/suricata_{$interface_now}/block.log | grep -E \"$allAddressIps\" | awk '{\$1=\"\"; \$0 = \$0; \$1 = \$1; print \$0}' | sort | uniq"))) as $line) {
            if (!empty($line)) {
                $gidRule = end(explode("[",trim(explode(":", $line)[0])));
                $sidRule = trim(explode(":", $line)[1]);
                $descriptRule = trim(explode(" ", $line)[4]);
                $protocol = explode("} ",trim(explode(" {", $line)[1]))[0];
                $ipsLog = trim(explode("} ", $line)[1]);
                $ipsDST = explode(":", end(explode(" ",$ipsLog)))[0];
                $portDST = explode(":", end(explode(" ",$ipsLog)))[1];

                $noExistsValues = false;
                $retornoTable .= "<tr>";
                $retornoTable .= "<th>DROP</th>";
                $retornoTable .= "<th onclick=\"complementFieldSearch('$gidRule')\">{$gidRule}</th>";
                $retornoTable .= "<th onclick=\"complementFieldSearch('$sidRule')\">{$sidRule}</th>";
                $retornoTable .= "<th onclick=\"complementFieldSearch('$real_interface')\">{$descriptionInterface} ({$real_interface})</th>";
                $retornoTable .= "<th onclick=\"complementFieldSearch('$descriptRule')\">{$descriptRule}</th>";
                $retornoTable .= "<th onclick=\"complementFieldSearch('$protocol')\">{$protocol}</th>";
                $retornoTable .= "<th>";
                $retornoTable .= "<ipDeleteTag onclick=\"complementFieldSearch('$ipsDST')\">{$ipsDST}</ipDeleteTag>"; 
                $retornoTable .= " <i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"{$gidRule}\", \"{$sidRule}\", \"{$interface_now}\", \"{$ipsDST}\")' title='Deletar endereço das tabelas fapp2c da interface {$real_interface}'/>";
                if (in_array($ipsDST, $all_ip_table_ugid_ignore_fixed)) {
                    
                    $retornoTable .= " <i class='fa fa fa-lock icon-primary' style='margin-left: 10px;' title='Endereço está marcado como um exceção fixa de bloqueios no modo performance do FirewallApp'/>";
                } else {
                    if (in_array($ipsDST, $all_ip_table_ugid_ignore_local)) {
                        $retornoTable .= " <i class='fa fa fa-times icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$ipsDST}\", \"remove\")' title='Desmarcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                    } else {
                        $retornoTable .= " <i class='fa fa fa-floppy-o icon-primary' style='margin-left: 10px;' onclick='fixedIpToIgnoreUgid(\"{$ipsDST}\", \"add\")' title='Marcar endereço como exceção de bloqueio no modo performance do FirewallApp'/>";
                    }
                }
                $retornoTable .= "</th>";
                $retornoTable .= "<th onclick=\"complementFieldSearch('$portDST')\">{$portDST}</th>";
                $retornoTable .= "</tr>";
            }
        }
    }

    if ($noExistsValues) {
        $retornoTable .= "<tr>";
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