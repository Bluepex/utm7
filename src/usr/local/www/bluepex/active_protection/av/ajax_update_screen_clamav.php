<?php
//Request libs
require_once("config.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

//Create if not exists key files
if (!file_exists("/etc/monitor_gateway_files_yara")) {
    file_put_contents("/etc/monitor_gateway_files_yara", "false");
}

if (!file_exists("/etc/monitor_gateway_files_clamd")) {
    file_put_contents("/etc/monitor_gateway_files_clamd", "false");
}

//Count amount of hashs is found in log file
if (isset($_POST['update_counter_negate_file_simple'])) {
    echo intval(trim(shell_exec("grep FOUND /var/log/yara_work.log -c")));
}
if (isset($_POST['update_counter_negate_file_advanced'])) {
    echo intval(trim(shell_exec("grep FOUND /var/log/clamav/clamd_custom.log -c")));
}

//Show hashs in tmp table
if (isset($_POST['update_table_files_simple'])) {
    shell_exec("grep FOUND /var/log/yara_work.log | tail -n100 > /tmp/status_clamav_actions_simple");
    $saida = "";
    if (intval(trim(shell_exec("wc -l /tmp/status_clamav_actions_simple"))) > 0) {
        $all_lines = [];
        foreach(explode("\n", shell_exec("cat /tmp/status_clamav_actions_simple |  awk -F\": \" '{ print $1 \"___\" $2 }'")) as $line_now) {
            $all_lines[] = $line_now;
        }
        array_filter($all_lines);
        $all_lines = array_unique($all_lines);
        $all_lines = array_reverse($all_lines, true);
        if (count($all_lines) > 0) {
            foreach($all_lines as $line_now) {
                if (!empty($line_now)) {
                    $line_now = explode("___",$line_now);
                    $targetSha256 = trim(end(explode("/", $line_now[0])));
                    $targetType = trim(reset(explode("-", $line_now[1])));
                    $return_table .= "<tr>";
                    $return_table .= "<td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFileSimple('{$targetSha256}')\">{$targetSha256}</b>";
                    $return_table .= " <i class='fa fa-search' title='{$targetSha256}' onclick='insertValueFindRule(\"{$targetSha256}\")'/></td>";
                    $return_table .= "<td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFileSimple('{$targetType}')\">{$targetType}</b></td>";
                    $return_table .= "</tr>";
                }
            }
        } else {
            $return_table .= "<tr>";
            $return_table .= "<td>---</td>";
            $return_table .= "<td>---</td>";
            $return_table .= "</tr>";
        }
    } else {
        $return_table .= "<tr>";
        $return_table .= "<td>---</td>";
        $return_table .= "<td>---</td>";
        $return_table .= "</tr>";
    }
    echo $return_table;
}

//Show hashs in tmp table advanced
if (isset($_POST['update_table_files_advanced'])) {
    shell_exec("grep FOUND /var/log/clamav/clamd_custom.log | tail -n100 > /tmp/status_clamav_actions_advanced");
    $saida = "";
    if (intval(trim(shell_exec("wc -l /tmp/status_clamav_actions_advanced"))) > 0) {
        $all_lines = [];
        foreach(explode("\n", shell_exec("cat /tmp/status_clamav_actions_advanced |  awk -F\": \" '{ print $1 \"___\" $2 }'")) as $line_now) {
            $all_lines[] = $line_now;
        }
        array_filter($all_lines);
        $all_lines = array_unique($all_lines);
        $all_lines = array_reverse($all_lines, true);
        if (count($all_lines) > 0) {
            foreach($all_lines as $line_now) {
                if (!empty($line_now)) {
                    $line_now = explode("___",$line_now);
                    $targetSha256 = trim(end(explode("/", $line_now[0])));
                    $targetType = trim(reset(explode("-", $line_now[1])));
                    $return_table .= "<tr>";
                    $return_table .= "<td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFileAdvanced('{$targetSha256}')\">{$targetSha256}</b>";
                    $return_table .= " <i class='fa fa-search' title='{$targetSha256}' onclick='insertValueFindRule(\"{$targetSha256}\")'/></td>";
                    $return_table .= "<td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFileAdvanced('{$targetType}')\">{$targetType}</b></td>";
                    $return_table .= "</tr>";
                }
            }
        } else {
            $return_table .= "<tr>";
            $return_table .= "<td>---</td>";
            $return_table .= "<td>---</td>";
            $return_table .= "</tr>";
        }
    } else {
        $return_table .= "<tr>";
        $return_table .= "<td>---</td>";
        $return_table .= "<td>---</td>";
        $return_table .= "</tr>";
    }
    echo $return_table;
}

//Table of change status hash
if (isset($_POST['find_values_files'])) {
    $source_eve_tmp = "";
    if (!empty($_POST['find_values_files'])) {

        if (!file_exists('/etc/persistFindEve')) {
            file_put_contents("/etc/persistFindEve", "");
        }

        $source_eve = "";
        if (intval(trim(shell_exec("grep -r {$_POST['find_values_files']} /etc/persistFindEve | grep fileinfo | head -n1 | wc -l"))) > 0) {
            $source_eve = shell_exec("grep -rh {$_POST['find_values_files']} /etc/persistFindEve | grep fileinfo | head -n1");
        } 

        if (intval(trim(shell_exec("grep -r {$_POST['find_values_files']} /var/log/suricata/**/eve.json | grep fileinfo | grep -v 'tx_id\":0' | head -n1 | wc -l"))) > 0 && empty($source_eve)) {
            $source_eve = shell_exec("grep -rh {$_POST['find_values_files']} /var/log/suricata/**/eve.json | grep fileinfo | egrep 'stored\":true,' | head -n1");
        }

        if (!empty($source_eve)) {
            $source_eve = json_decode($source_eve, true);
            $file_target =  end(explode("/", $source_eve['fileinfo']['filename']));
            if (empty($file_target)) {
                $file_target = "No information";
            }
            if (strlen($file_target) >= 30) {
                $file_target = substr($file_target, 0, 30) . "... <i class='fa fa-info icon-pointer icon-primary' title='{$file_target}'/>";
            }
            
            $source_eve_tmp .= "<tr>";
            $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $file_target . "</td>";
            $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $source_eve['proto'] . "</td>";
            $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $source_eve['http']['hostname'] . "</td>";
            
            $agent_http = $source_eve['http']['http_user_agent'];
            if (empty($agent_http)) {
                $agent_http = "No information";
            }
            if (strlen($agent_http) >= 30) {
                $agent_http = substr($agent_http, 0, 30) . "... <i class='fa fa-info icon-pointer icon-primary' title='{$agent_http}'/>";
            }

            $source_eve_tmp .= "<td style='vertical-align: middle;'> " . $agent_http . "</td>";
            $source_eve_tmp .= "<td style='vertical-align: middle;'>";
            $source_eve_tmp .= "SHA256 ";
            $source_eve_tmp .= " <i class='fa fa-info icon-pointer icon-primary' title='{$source_eve['fileinfo']['sha256']}'/>";
            $source_eve_tmp .= " <i class='fa fa-search icon-pointer icon-primary' title='{$source_eve['fileinfo']['sha256']}' onclick='preencherSearchDataFile(\"{$source_eve['fileinfo']['sha256']}\")'/>";
            $source_eve_tmp .= "</br>";
            $source_eve_tmp .= "MD5 <i class='fa fa-info icon-pointer icon-primary' title='{$source_eve['fileinfo']['md5']}'/></br>";
            $source_eve_tmp .= "</td>";
            $source_eve_tmp .= "<td style='vertical-align: middle;'> ";


            if (intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep -v '#drop' | wc -l"))) > 0) {

                if (intval(trim(shell_exec("grep -r {$source_eve['fileinfo']['sha256']} /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | wc -l"))) > 0) {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list btn-disabled'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list btn-disabled'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";
                } elseif (intval(trim(shell_exec("grep -r {$source_eve['fileinfo']['sha256']} /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | wc -l"))) > 0) {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list btn-disabled'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list btn-disabled'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";  
                } elseif ((file_exists('/var/db/clamav/ignore_analisy.sfp')) && (intval(trim(shell_exec("grep -r {$source_eve['fileinfo']['sha256']} /var/db/clamav/ignore_analisy.sfp | wc -l"))) > 0)) {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list btn-disabled'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list btn-disabled'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";
                } else {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list btn-disabled'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list btn-disabled'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";
                }
                 
            } else {

                if (intval(trim(shell_exec("grep -r {$source_eve['fileinfo']['sha256']} /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt | wc -l"))) > 0) {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list btn-disabled'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list btn-disabled'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";
                } elseif (intval(trim(shell_exec("grep -r {$source_eve['fileinfo']['sha256']} /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt | wc -l"))) > 0) {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list btn-disabled'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list btn-disabled'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";  
                } elseif ((file_exists('/var/db/clamav/ignore_analisy.sfp')) && (intval(trim(shell_exec("grep -r {$source_eve['fileinfo']['sha256']} /var/db/clamav/ignore_analisy.sfp | wc -l"))) > 0)) {
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"black__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-danger list btn-disabled'>Black List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"white__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-primary list btn-disabled'>White List</button>";
                    $source_eve_tmp .= "<button onclick='setValueOfListBlackWhite(\"exception__{$source_eve['fileinfo']['sha256']}\")' class='btn btn-warning list'>Exception Analisy</button>";
                    $source_eve_tmp .= "<button class='btn btn-secondary' onclick='saveListsBlackWhite()'>Save</button>";
                } else {
                    $source_eve_tmp .= "File has not yet been parsed by File Parsing Gateway";
                }
    
            }


            $source_eve_tmp .= "</td>";
            $source_eve_tmp .= "</tr>";
        } else {
            $source_eve_tmp .= "<tr>";
            $source_eve_tmp .= "<td>---</td>";
            $source_eve_tmp .= "<td>---</td>";
            $source_eve_tmp .= "<td>---</td>";
            $source_eve_tmp .= "<td>---</td>";
            $source_eve_tmp .= "<td>---</td>";
            $source_eve_tmp .= "<td>---</td>";
            $source_eve_tmp .= "</tr>";
        }
    } else {
        $source_eve_tmp .= "<tr>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "</tr>";
    }
    
    echo $source_eve_tmp;

}

//Table of type hash's
if (isset($_POST['type_list']) && isset($_POST['find_hash']) && isset($_POST['selectAmountShowTables'])) {
    $return_table = "";
    $findExpecifieValue = false;
    if (!empty($_POST['find_hash'])) {
        $findExpecifieValue = true;
    }
    if ($_POST['type_list'] == "black") {
        $blackTable = array_reverse(array_unique(array_filter(explode("\n", shell_exec("tail -n{$_POST['selectAmountShowTables']} /usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt")))));
        foreach($blackTable as $line_black) {
            if ($findExpecifieValue == true) {
                if (!empty($line_black)) {
                    if (strpos($line_black, $_POST['find_hash']) !== false) {
                        $return_table .= "<tr><td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFile('" . trim($line_black) . "')\">" . trim($line_black) . "</b> <i class='fa fa-search' title='" . trim($line_black) . "' onclick='insertValueFindRule(\"" . trim($line_black) . "\")'/><i class='fa fa-check' style='color:#dc3545;' title='Black List Hash'/></td></tr>";
                    }
                }
            } else {
                if (!empty($line_black)) {
                    $return_table .= "<tr><td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFile('" . trim($line_black) . "')\">" . trim($line_black) . "</b> <i class='fa fa-search' title='" . trim($line_black) . "' onclick='insertValueFindRule(\"" . trim($line_black) . "\")'/><i class='fa fa-check' style='color:#dc3545;' title='Black List Hash'/></td></tr>";
                }
            }
        }
    }
    if ($_POST['type_list'] == "white") {
        $whiteTable = array_reverse(array_unique(array_filter(explode("\n", shell_exec("tail -n{$_POST['selectAmountShowTables']} /usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt")))));
        foreach($whiteTable as $line_white) {
            if ($findExpecifieValue == true) {
                if (!empty($line_white)) {
                    if (strpos($line_white, $_POST['find_hash']) !== false) {
                        $return_table .= "<tr><td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFile('" . trim($line_white) . "')\">" . trim($line_white) . "</b> <i class='fa fa-search' title='" . trim($line_white) . "' onclick='insertValueFindRule(\"" . trim($line_white) . "\")'/><i class='fa fa-check' style='color:#007bff;' title='White List Hash'/></td></tr>";
                    }
                }
            } else {
                if (!empty($line_white)) {
                    $return_table .= "<tr><td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFile('" . trim($line_white) . "')\">" . trim($line_white) . "</b> <i class='fa fa-search' title='" . trim($line_white) . "' onclick='insertValueFindRule(\"" . trim($line_white) . "\")'/><i class='fa fa-check' style='color:#007bff;' title='White List Hash'/></td></tr>";
                }
            }
        }
    }
    if ($_POST['type_list'] == "exceptions") {
        $exceptionsTable = array_reverse(array_unique(array_filter(explode("\n", shell_exec("tail -n{$_POST['selectAmountShowTables']} /var/db/clamav/ignore_analisy.sfp | awk -F\":\" '{print $1}'")))));
        foreach($exceptionsTable as $line_exception) {
            if ($findExpecifieValue == true) {
                if (!empty($line_exception)) {
                    if (strpos($line_exception, $_POST['find_hash']) !== false) {
                        $return_table .= "<tr><td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFile('" . trim($line_exception) . "')\">" . trim($line_exception) . "</b> <i class='fa fa-search' title='" . trim($line_exception) . "' onclick='insertValueFindRule(\"" . trim($line_exception) . "\")'/><i class='fa fa-check' style='color:#ffc107;' title='Exception List Hash'/></td></tr>";
                    }
                }
            } else {
                if (!empty($line_exception)) {
                    $return_table .= "<tr><td><b style=\"word-wrap:break-word; white-space:normal\" onclick=\"preencherSearchDataFile('" . trim($line_exception) . "')\">" . trim($line_exception) . "</b> <i class='fa fa-search' title='" . trim($line_exception) . "' onclick='insertValueFindRule(\"" . trim($line_exception) . "\")'/><i class='fa fa-check' style='color:#ffc107;' title='Exception List Hash'/></td></tr>";
                }
            }
        }
    }
    if (empty($return_table)) {
        echo "<tr><td>---</tb></tr>";
    } else {
        echo $return_table;
    }

}

//Change status bar service
if ($_POST['barStatusFilesGateway']) {
    if ($_POST['colorBar']) {
        if (intval(getInterfaceNewAcp()) >= 1) {
            if ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") ||
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true")) {
                echo "col-12 bg-success py-3 color-white";
            } elseif ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "false") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "false")) {
                echo "col-12 bg-danger py-3 color-white";
            } else {
                echo "col-12 bg-danger py-3 color-white";
            }
        } else {
            echo "col-12 bg-danger py-3 color-white";
        }
    }
    if ($_POST['buton_status']) {
        if (intval(getInterfaceNewAcp()) >= 1) {
            if ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") ||
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true")) {
                echo "fa fa-check px-3 ml-1 fa-4x border-right";
            } elseif ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "false") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "false")) {
                echo "fa fa-ban px-3 ml-1 fa-4x border-right";
            } else {
                echo "fa fa-ban px-3 ml-1 fa-4x border-right";
            }
        } else {
            echo "fa fa-ban px-3 ml-1 fa-4x border-right";
        }
    }
    if ($_POST['id_status_click_files']) {
        if (intval(getInterfaceNewAcp()) >= 1) {
            if ((intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep http | grep -v '#drop' | wc -l"))) > 0) &&
                (file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true")) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;padding:0px;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext("Running (Modo Avançado)") . "</p> <button type='submit' class='btn btn-danger btn-sm ml-3 mx-md-5' onclick=\"disabledOptationScan()\">" . gettext("Disable file parsing service") . "</button>"; 
            } elseif ((intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep http | grep -v '#drop' | wc -l"))) == 0) &&
                (file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true")) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;padding:0px;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext("Running (Modo Scan Total)") . "</p> <button type='submit' class='btn btn-danger btn-sm ml-3 mx-md-5' onclick=\"disabledOptationScan()\">" . gettext("Disable file parsing service") . "</button>"; 
            } elseif ((intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep http | grep -v '#drop' | wc -l"))) == 0) &&
                (file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "false") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true")) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;padding:0px;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext("Running (Modo Simples)") . "</p> <button type='submit' class='btn btn-danger btn-sm ml-3 mx-md-5' onclick=\"disabledOptationScan()\">" . gettext("Disable file parsing service") . "</button>"; 
            } elseif ((intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep http | grep -v '#drop' | wc -l"))) == 0) &&
                (file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "false")) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;padding:0px;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext("Running (Modo Sime-ScanV)") . "</p> <button type='submit' class='btn btn-danger btn-sm ml-3 mx-md-5' onclick=\"disabledOptationScan()\">" . gettext("Disable file parsing service") . "</button>"; 
            } elseif ((intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep http | grep -v '#drop' | wc -l"))) > 0) &&
                ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "false") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true"))) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;padding:0px;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext("Running (Modo Sime-ScanV)") . "</p> <button type='submit' class='btn btn-danger btn-sm ml-3 mx-md-5' onclick=\"disabledOptationScan()\">" . gettext("Disable file parsing service") . "</button>"; 
            } elseif ((intval(trim(shell_exec("grep -rh 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep http | grep -v '#drop' | wc -l"))) > 0) &&
                ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "false"))) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;padding:0px;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext("Running (Modo ScanV)") . "</p> <button type='submit' class='btn btn-danger btn-sm ml-3 mx-md-5' onclick=\"disabledOptationScan()\">" . gettext("Disable file parsing service") . "</button>"; 
            } elseif ((file_exists('/etc/monitor_gateway_files_clamd') && trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "false") &&
                (file_exists('/etc/monitor_gateway_files_yara') && trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "false")) {
                    echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px !important;border:0px transparent;background:transparent; color:white;margin-right: 10px !important;'>" . gettext('Stopped') . "</p> <button type='submit' class='btn btn-success btn-sm ml-3 mx-md-5' onclick=\"selectionOperationScan()\">" . gettext("Enable file parsing service") . "</button>"; 
            }
        } else {
            echo "<p class='btn btn-success btn-sm ml-3 mx-md-5' style='margin:0px;border:0px transparent;background:transparent; color:white;'>" . gettext("Active Protection is not start") . "</p>"; 
        }
    }
    if ($_POST['showExecuteNow']) {
        if (intval(getInterfaceNewAcp()) >= 1) {
            if (file_exists('/etc/monitor_gateway_files_clamd') && 
                trim(file_get_contents('/etc/monitor_gateway_files_clamd')) == "true" && 
                (intval(trim(shell_exec("ps aux | grep fetch_different_files_clamav | grep -v grep | wc -l"))) == 0) && 
                (intval(trim(shell_exec("ps aux | grep update_interfaces_hashs | grep -v grep | wc -l"))) == 0) &&
                (intval(trim(shell_exec("ps aux | grep /usr/local/bin/freshclam | grep -v grep | wc -l"))) == 0)
            ) {
                echo "block";
            } elseif (file_exists('/etc/monitor_gateway_files_yara') && 
                trim(file_get_contents('/etc/monitor_gateway_files_yara')) == "true" && 
                (intval(trim(shell_exec("ps aux | grep fetch_different_files_yara | grep -v grep | wc -l"))) == 0) && 
                (intval(trim(shell_exec("ps aux | grep update_interfaces_hashs | grep -v grep | wc -l"))) == 0) &&
                (intval(trim(shell_exec("ps aux | grep /usr/local/bin/freshclam | grep -v grep | wc -l"))) == 0)
            ) {
                echo "block";
            } else {
                echo "none";
            }
        } else {
            echo "none";
        }
    }
    if ($_POST['showExtensionNow']) {
        //if (intval(trim(shell_exec("grep -rhn 'ACP-BLOCK-EXTENSION-HEURISTIC-' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules | grep filestore | grep -v '#drop' | wc -l"))) > 0) {
		echo "block";
        //} else {
        //    echo "none";
        //}
    } 

}

//get_list_persistent: true
//search_hash_file: 
//selectAmountShowEve: 10


//Table of change status hash
if (isset($_POST['get_list_persistent']) && isset($_POST['search_file_value']) && isset($_POST['selectAmountShowEve'])) {

    $source_eve_tmp = "";

    if (file_exists("/etc/persistFindEve")) {

        if (empty($_POST['search_file_value'])) {
            $commandFind = "tail -n{$_POST['selectAmountShowEve']} /etc/persistFindEve";
        } else {
            $commandFind = "grep -i {$_POST['search_file_value']} /etc/persistFindEve | tail -n{$_POST['selectAmountShowEve']}";
        }

        $arrayBlackList = explode("\n", file_get_contents('/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt'));
        shell_exec("cat /var/db/clamav/ignore_analisy.sfp | awk -F\":\" '{print $1}' | uniq > /tmp/exceptionsList");
        $arrayExceptionsList = explode("\n", file_get_contents('/tmp/exceptionsList'));

        foreach (array_reverse(explode("\n", shell_exec($commandFind))) as $source_eve) {
            if (!empty($source_eve)) {

                $source_eve = json_decode($source_eve, true);
                $file_target =  end(explode("/", $source_eve['fileinfo']['filename']));

                if (empty($file_target)) {
                    $file_target = "No information";
                }

                if (in_array($source_eve['fileinfo']['sha256'], $arrayBlackList)) {
                    $source_eve_tmp .= "<tr style='background-color: #e71837;color: white;'>";
                } elseif (in_array($source_eve['fileinfo']['sha256'], $arrayExceptionsList)) {
                    $source_eve_tmp .= "<tr style='background-color: #f0ad4e;color: white;'>";
                } else {
                    $source_eve_tmp .= "<tr style='background-color: #2baf2b;color: white;'>";
                }					
                
                $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $file_target . "</td>";
                $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $source_eve['proto'] . "</td>";
                $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $source_eve['http']['hostname'] . "</td>";
                
                $agent_http = $source_eve['http']['http_user_agent'];
                if (empty($source_eve['http']['http_user_agent'])) {
                    $agent_http = "No information";
                }

                $source_eve_tmp .= "<td style='vertical-align: middle;'>" . $agent_http . "</td>";
                $source_eve_tmp .= "<td style='vertical-align: middle;'>";
                $source_eve_tmp .= "SHA256";
                $source_eve_tmp .= " <i class='fa fa-info icon-pointer icon-primary' title='{$source_eve['fileinfo']['sha256']}'/>";
                $source_eve_tmp .= " <i class='fa fa-search icon-pointer icon-primary' title='{$source_eve['fileinfo']['sha256']}' onclick='preencherSearchDataFile(\"{$source_eve['fileinfo']['sha256']}\")'/>";
                $source_eve_tmp .= "<br>";
                $source_eve_tmp .= "MD5 <i class='fa fa-info icon-pointer icon-primary' title='{$source_eve['fileinfo']['md5']}'/><br>";
                $source_eve_tmp .= "</td>";
                $source_eve_tmp .= "</tr>";
                $counter++;
            }
        }
    }

    if (empty($source_eve_tmp)) {
        $source_eve_tmp .= "<tr>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "<td>---</td>";
        $source_eve_tmp .= "</tr>";
    }

    echo $source_eve_tmp;

}

//Clear log clamd if no exists ACP interface
if (intval(getInterfaceNewAcp()) == 0) {
    if (file_exists("/var/log/clamav/clamd_custom.log")) {
        if (strlen(file_get_contents("/var/log/clamav/clamd_custom.log")) > 0) {
            file_put_contents("touch /var/log/clamav/clamd_custom.log", "");
        }
    }
    if (file_exists("/var/log/yara_work.log")) {
        if (strlen(file_get_contents("/var/log/yara_work.log")) > 0) {
            file_put_contents("touch /var/log/yara_work.log", "");
        }
    }
}