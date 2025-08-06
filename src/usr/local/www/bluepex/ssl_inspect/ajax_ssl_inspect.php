<?php

function display_top_tabs(& $tab_array, $type_table_request, $no_drop_down = false, $type = 'pills', $usepost = "") {
    require("config.inc");
    $valuesProcess = [];
    if (isset($config['interfaces'])) {
        foreach($config['interfaces'] as $key => $values) {
            $valuesProcess[] = "{$key}_{$values['if']}";
        }
    }
	echo '<ul class="nav nav-' . $type . '">';
	foreach ($tab_array as $ta) {
		echo '<li role="presentation"';
		if ($ta[1]) {
			echo ' class="active"';
		}
        $realInterface = explode("_",  $ta[0])[1];
        $descInterface = "";
        $showInterface = "";
        foreach($valuesProcess as $valueNow) {
            $interfaceShow = explode("_",  $valueNow);
            if ($realInterface == $interfaceShow[1]) {
                $descInterface = strtoupper($interfaceShow[0]);
                $showInterface = $descInterface  . " (" . $realInterface . ")";
            }
        }
        if ($type_table_request == "real") {
    		echo '><a href=ssl_inspect.php?show=' . $ta[2] . '_' . $descInterface . '>' . $showInterface . '</a></li>';
        } else {
    		echo '><a href=ssl_inspect_registers.php?show=' . $ta[2] . '_' . $descInterface . '>' . $showInterface . '</a></li>';
        }
    }
	echo '</ul>';
}

if (isset($_POST['getAllProcessNetifyd']) && isset($_POST['typeTable'])) {
    $processNet = array_filter(explode("\n", shell_exec("ps aux | grep '/usr/local/sbin/netifyd' | grep -v grep | awk -F\" \" '{print \$2\"_\"\$13}'")));
    $showInterface = "";
    if ($_POST['getAllProcessNetifyd'] != "") {
        $tempShow = explode("_", $_POST['getAllProcessNetifyd']);
        $showInterface = $tempShow[0] . "_" . $tempShow[1];
    }
    $tab_array = [];
    if (count($processNet) == 0) {
        $tab_array[] = array("No search inspect", false, "");
        echo display_top_tabs($tab_array, $type_table_request=$_POST['typeTable']);
    } else {
        foreach($processNet as $process) {
            if ($process == $showInterface) {
                $tab_array[] = array($process, true, $process);
            } else {
                $tab_array[] = array($process, false, $process);
            }
        }
        echo display_top_tabs($tab_array, $type_table_request=$_POST['typeTable']);
    }
}

if (isset($_POST['statusProcessNetifyd'])) {
    $target = $_POST['statusProcessNetifyd'];
    $process = explode("_", $target);
    $pid = $process[0];
    $interface = $process[1];
    if (intval(trim(shell_exec("ps aux | grep netifyd | grep {$pid} | grep {$interface} | grep -v grep -c"))) == 1) {
        echo "<button type='click' onclick='stopProcessNet(\"$target\")' class='btn btn-danger' style='border-radius:10px; width:160px;padding: 2px;'>Stop Inspect Interface</button>";
    }
}

if (isset($_POST['showProcessNetifyd']) && isset($_POST['limiteTailShow'])) {
    require("util.inc");
    $ignoreText = ["null", ""];
    $protocolsTarget = ["HTTP", "HTTP/S"];
    $valuesExplode = explode("_", $_POST['showProcessNetifyd']);
    $desc_interface = $valuesExplode[2];
    $interface = $valuesExplode[1];
    $limiteTailShow = $_POST['limiteTailShow'];
    mwexec("/bin/sh /usr/local/www/ssl_inspect/ssl_get_interface.sh $interface $limiteTailShow");
    $return = "";
    if (file_exists("/tmp/filterNet")) {
        $valueFile = array_filter(explode("\n", file_get_contents("/tmp/filterNet")));
        if (!empty($valueFile)) {
            foreach ($valueFile as $line) {
                $lineClean = explode(" ", $line);
                if (!in_array($lineClean[8], $ignoreText)) {
                    $return .= "<tr>";
                    $return .= "<td>" . date("d/m/Y - H:i:s", intval($lineClean[0]/1000)) . "</td>";
                    $return .= "<td onclick='completSearchInspect(\"" . $lineClean[2] . "\")'>" . $lineClean[2] . "</td>";
                    $return .= "<td onclick='completSearchInspect(\"" . $lineClean[3] . "\")'>" . $lineClean[3] . "</td>";
                    $return .= "<td onclick='completSearchInspect(\"" . $lineClean[4] . "\")'>" . $lineClean[4] . "</td>";
                    $return .= "<td onclick='completSearchInspect(\"" . $lineClean[5] . "\")'>" . $lineClean[5] . "</td>";
                    $return .= "<td onclick='completSearchInspect(\"" . $lineClean[6] . "\")'>" . $lineClean[6] . "</td>";
                    $return .= "<td onclick='completSearchInspect(\"" . $lineClean[8] . "\")'>" . $lineClean[8] . "</td>";
                    if (in_array($lineClean[6], $protocolsTarget)) {
                        $protocolReturn = "";
                        if ($lineClean[6] == "HTTP/S") {
                            $protocolReturn = "tls";
                        } else {
                            $protocolReturn = "http";
                        }
                        $return .= "<td><i class='fa fa-plus-circle' aria-hidden='true' title='Gerar regra customizada' onclick=\"insertRuleACP('$desc_interface', '$interface', '$lineClean[2]', '$lineClean[3]', '$lineClean[4]', '$lineClean[5]', '$protocolReturn', '$lineClean[8]')\"></i></td>";
                    } else {
                        $return .= "<td>-</td>";
                    }
                    $return .= "</tr>";
                }
            }
        }
    }
    echo $return;
}