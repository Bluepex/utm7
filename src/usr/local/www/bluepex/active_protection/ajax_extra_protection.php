<?php
require("/etc/inc/config.inc");
require_once("/etc/inc/util.inc");
require_once("/etc/inc/firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
require_once("/etc/inc/firewallapp_webservice.inc");
ini_set('memory_limit', '512MB');

if ($_POST['tableTarget'] && !empty($_POST['tableTarget']) && isset($_POST['addressTarget']) && !empty($_POST['addressTarget']) && isset($_POST['gidTarget']) && !empty($_POST['gidTarget']) && isset($_POST['sidTarget']) && !empty($_POST['sidTarget'])) {
    $tableTarget = trim($_POST['tableTarget']);
    if (intval(trim(shell_exec("pfctl -sT | grep acp2c_{$tableTarget} | grep -v grep -c"))) > 0) {
        mwexec("/sbin/pfctl -t acp2c_{$tableTarget} -T delete " . trim($_POST['addressTarget']));
    }
}

if ($_POST['updateTable'] && !empty($_POST['updateTable']) && isset($_POST['interface_select']) && !empty($_POST['interface_select']) && isset($_POST['filterQtdAlerts']) && !empty($_POST['filterQtdAlerts'])) {
    
    $interface_now = $_POST['interface_select'];
    $interface_now = explode("___", $interface_now);
    $descriptionInterface = explode("_",$interface_now[0])[0];
    $real_interface = explode("_",$interface_now[0])[1];
    $interface_now = $interface_now[1]; //Interface + uuid
    foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -sT | grep -E 'acp2c_' | grep -v grep"))))) as $tableNow) {
        if (strpos($tableNow,$interface_now) !== false) {
            foreach(array_filter(array_unique(explode("\n", trim(shell_exec("pfctl -t $tableNow -T show"))))) as $valuesIps) {
                $all_ip_table_key[] = trim($valuesIps);
            }
        }
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
                $retornoTable .= "<th><ipDeleteTag onclick=\"complementFieldSearch('$ipsDST')\">{$ipsDST}</ipDeleteTag> <i class='fa fa fa-trash icon-primary' style='margin-left: 10px;' onclick='openModalDeleteAddressTotable(\"{$gidRule}\", \"{$sidRule}\", \"{$interface_now}\", \"{$ipsDST}\")' title='Deletar endereço das tabelas acp2c da interface {$real_interface}'/></th>";
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