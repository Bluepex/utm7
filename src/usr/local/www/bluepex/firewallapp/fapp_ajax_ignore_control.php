<?php

require("/etc/inc/config.inc");
require_once("/usr/local/www/guiconfig.inc");
ini_set('memory_limit', '512MB');


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


if ($_POST['updateTable'] && !empty($_POST['updateTable']) && isset($_POST['filterQtdAlerts']) && !empty($_POST['filterQtdAlerts']) && isset($_POST['search_iptables'])) {

    $limiteLine = intval($_POST['filterQtdAlerts']);
    $all_ip_table_ugid_ignore_fixed = [];
    $all_ip_table_ugid_ignore_local = [];
    $retornoTable = "";
    if (file_exists('/usr/local/share/suricata/ugid_ignore')) {
        $all_ip_table_ugid_ignore_fixed = array_filter(array_unique(explode("\n", file_get_contents("/usr/local/share/suricata/ugid_ignore"))));
    }
    if (file_exists('/usr/local/share/suricata/ugid_ignore_local')) {
        $all_ip_table_ugid_ignore_local = array_filter(array_unique(explode("\n", file_get_contents("/usr/local/share/suricata/ugid_ignore_local"))));
    }
    $array_ips = array_filter(array_unique(array_merge($all_ip_table_ugid_ignore_local, $all_ip_table_ugid_ignore_fixed)));
    unset($all_ip_table_ugid_ignore_local);

    if (count($array_ips) > 0) {
        $array_ips = array_slice($array_ips, 0, $limiteLine);
        foreach($array_ips as $ipnow) {
            if (!empty($_POST['search_iptables'])) {
                if (strpos($ipnow, $_POST['search_iptables']) !== false) {
                    $retornoTable .= "<tr>";
                    $retornoTable .= "<th>{$ipnow}</th>";
                    if (in_array($ipnow, $all_ip_table_ugid_ignore_fixed)) {
                        $retornoTable .= "<th><i class='fa fa-lock' title='Este endereço não pode ser revido da lista de \"ignorar bloqueio\"'></i></th>";
                    } else {
                        $retornoTable .= "<th><i class='fa fa-times' onclick='fixedIpToIgnoreUgid(\"{$ipnow}\",\"remove\")' title='Remover endereço da lista de \"Ignorar bloqueio\"'></i></th>";
                    }
                    $retornoTable .= "</tr>";
                }
            } else {
                $retornoTable .= "<tr>";
                $retornoTable .= "<th>{$ipnow}</th>";
                if (in_array($ipnow, $all_ip_table_ugid_ignore_fixed)) {
                    $retornoTable .= "<th><i class='fa fa-lock' title='Este endereço não pode ser revido da lista de \"ignorar bloqueio\"'></i></th>";
                } else {
                    $retornoTable .= "<th><i class='fa fa-times' onclick='fixedIpToIgnoreUgid(\"{$ipnow}\", \"remove\")' title='Remover endereço da lista de \"Ignorar bloqueio\"'></i></th>";
                }
                $retornoTable .= "</tr>";
            }
        }
    }

    if (empty(trim($retornoTable))) {
        $retornoTable .= "<tr>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "<th>-</th>";
        $retornoTable .= "</tr>";
    }

    echo $retornoTable;

}