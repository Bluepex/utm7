<?php

//Retorna informacoes do site via whois
if (isset($_POST['targetIp'])) {
    $endereco_ip = $_POST['targetIp'];
    $ipAlvo = explode(":", $endereco_ip)[0];
    $datenow = date("dm");

    /*
    NetName:        AT-88-Z
    NetHandle:      NET-52-0-0-0-1
    Parent:         NET52 (NET-52-0-0-0-0)
    NetType:        Direct Allocation
    Organization:   Amazon Technologies Inc. (AT-88-Z)
    RegDate:        1991-12-19
    Updated:        2021-02-10
    OrgName:        Amazon Technologies Inc.
    OrgId:          AT-88-Z
    Address:        410 Terry Ave N.
    City:           Seattle
    StateProv:      WA
    PostalCode:     98109
    Country:        US
    */

    $camposAVisualizar = ["NetName","NetHandle","Parent","NetType","Organization","RegDate","Updated","OrgName","OrgId","Address","City","StateProv","PostalCode","Country"];

    shell_exec("/usr/local/bin/curl 'https://www.homehost.com.br/whois2.php?domain={$ipAlvo}&p={$datenow}' | grep -A 15 'NetName:' | grep : > /tmp/basePesquisa");

    $campo = shell_exec("grep -A 15 ':' /tmp/basePesquisa | awk -F\":\" '{ print $1 }'");
    $valor = shell_exec("grep -A 15 ':' /tmp/basePesquisa | awk -F\":\" '{ print $2 }'");

    $campos = [];
    $saida = "";
    foreach(explode("\n", $campo) as $linhas) {
        $campos[] = trim($linhas);
    }
    $valores = [];
    foreach(explode("\n", $valor) as $linhas) {
        $valores[] = trim($linhas);
    }
    for($i=0;$i<=count($campos)-1;$i++) {
        if (($campos[$i] != "RegDate") && ($campos[$i] != "Updated") && ( $campos[$i] != "PostalCode")) {
            if ($campos[$i] == "NetName") {
                $saida = $saida . "------------------------------------------------\n";
            }
            if (!empty($valores[$i]) && array_search($campos[$i], $camposAVisualizar)) {
                $saida = $saida . $campos[$i] . ": " . $valores[$i] . "\n";
            }    
        }
    }
    $saida = $saida . "------------------------------------------------";
    echo $saida;
}

if (isset($_POST['targetSID'])) {
    if (intval(trim(shell_exec("/usr/bin/grep -r 'sid:{$_POST['targetSID']}' /usr/local/share/suricata/rules_acp/_emerging.rules | /usr/bin/wc -l"))) > 0) {
        echo "emerging";
    }
    if (intval(trim(shell_exec("/usr/bin/grep -r 'sid:{$_POST['targetSID']}' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules | /usr/bin/wc -l"))) > 0) {
        echo "ameacas";
    }
}

