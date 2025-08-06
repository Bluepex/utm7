<?php

require_once("guiconfig.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $rebuild_rules;

$suricata_rules_dir = SURICATA_RULES_DIR;
$suricatalogdir = SURICATALOGDIR;
$interface = isset($_POST['interface']) ? $_POST['interface'] : 0;

init_config_arr(array('installedpackages', 'suricata', 'rule'));
$suricataglob = $config['installedpackages']['suricata'];
$ruledir = "{$suricata_rules_dir}";

/*
 * 
 * $rules_map => Mapeamento em forma de array das regras da pasta rules da raiz do suricata
 * 
 * $a_rule => Mapeamento em forma de array do config.xml
 *
 */

$a_rule = &$config['installedpackages']['suricata']['rule'];

//$id = 0;
$id = $interface;

$exist_legacy_fapp = [];
$reload_interfaces = false;

mwexec("rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");

function addClogBlock($gid, $if_real, $uuid, $gidtable) {

	if (file_exists("/usr/local/etc/suricata/suricata_{$uuid}_{$if_real}/rules/suricata.rules")) {
		$grepInfoGID=shell_exec("grep -r 'gid:{$gid};' /usr/local/etc/suricata/suricata_{$uuid}_{$if_real}/rules/suricata.rules | head -n1");
		if (!empty($grepInfoGID)) {
			$dateNow = date("m/d/Y-H:m:s.100000");
			$classType=trim(explode(";",explode("classtype:", $grepInfoGID)[1])[0]);
			$rev=trim(explode(";",explode("rev:", $grepInfoGID)[1])[0]);
			$sid=trim(explode(";",explode("sid:", $grepInfoGID)[1])[0]);
			$valuesGroup=explode(",",trim(shell_exec("grep '{$classType}' /usr/local/share/suricata/classification.config")));
			$classification=trim($valuesGroup[1]);
			$priority=trim(explode("\n", $valuesGroup[2])[0]);
			$gid=trim(explode(";",explode("gid:", $grepInfoGID)[1])[0]);
            if (empty($gid)) {
                $gid=1;
            }
			if (!empty($sid) && !empty($classification) && !empty($priority) && !empty($classType) && !empty($rev)) {
				foreach(array_unique(array_filter(explode("\n", file_get_contents($gidtable)))) as $lineIP) {
					$lineIP = trim($lineIP);
					if (!empty($lineIP)) {
						$line="{$dateNow}  [Block Src] [**] [{$gid}:{$sid}:{$rev}] {$classType} [**] [Classification: {$classification}] [Priority: {$priority}] {TCP} {$lineIP}:80\n";
						file_put_contents("/var/log/suricata/suricata_{$if_real}{$uuid}/block.log", $line, FILE_APPEND);
					}
				}
			}
		}
	}
}

function configTableIptable($gidtable, $gid, $if_real, $uuid, $action, $ips_mode) {
	if ($ips_mode != "ips_mode_inline") {
		if (file_exists($gidtable)) {
			// Usually
			foreach(explode("\n", trim(shell_exec("pfctl -sT | grep fapp2c_{$if_real}{$uuid}"))) as $tablesSimple) {
				if (!empty($tablesSimple)) {
					shell_exec("/sbin/pfctl -t {$tablesSimple} -T {$action} -f {$gidtable}");
					if ($action == "add") {
						addClogBlock($gid, $if_real, $uuid, $gidtable);
					}
				}
			}
			// with exceptions
			foreach(explode("\n", trim(shell_exec("/sbin/pfctl -sT | grep fapp2c_b_{$if_real}{$uuid}_{$gid}"))) as $tablesSimple) {
				if (!empty($tablesSimple)) {
					if (count(intval(end(explode("_",$tablesSimple)))) < 5) {
						shell_exec("/sbin/pfctl -t {$tablesSimple} -T {$action} -f {$gidtable}");
						if ($action == "add") {
							addClogBlock($gid, $if_real, $uuid, $gidtable);
						}
					}	
				}
			}
		}
	} else {
		// Usually
		foreach(explode("\n", trim(shell_exec("pfctl -sT | grep fapp2c_{$if_real}{$uuid}"))) as $tablesSimple) {
			if (!empty($tablesSimple)) {
				shell_exec("/sbin/pfctl -t {$tablesSimple} -T flush");
			}
		}
		// with exceptions
		foreach(explode("\n", trim(shell_exec("/sbin/pfctl -sT | grep fapp2c_b_{$if_real}{$uuid}_{$gid}"))) as $tablesSimple) {
			if (!empty($tablesSimple)) {
				if (count(intval(end(explode("_",$tablesSimple)))) < 5) {
					shell_exec("/sbin/pfctl -t {$tablesSimple} -T flush");
				}	
			}
		}
	}
}

//file_put_contents("/tmp/teste1", "");
//file_put_contents("/tmp/teste2", "");
//file_put_contents("/tmp/teste3", "");

if (isset($_POST['state']) && !empty($_POST['state'])) {
	$gid_used = [];
	for($counter=0;$counter<=(count($_POST['gid'])-1);$counter++) {

		$id = isset($_POST['interface'][$counter]) ? $_POST['interface'][$counter] : 0;
		$currentfile = $_POST['currentfile'][$counter];
		$rulefile = "{$ruledir}{$currentfile}";
		$rules_map = suricata_load_rules_map($rulefile);
		suricata_modify_sids_action($rules_map, $a_rule[$id]);

		/* Load up our rule action arrays with manually changed SID actions */
		$alertsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_alert']);
		$dropsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_drop']);
		$passid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_pass']);
		$exist_legacy_fapp[] = $a_rule[$id]['ips_mode'];

		$suricata_uuid = $a_rule[$id]['uuid'];
		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_sig_gid_ignore = [];
		$mode_change = "";
		if ($a_rule[$id]['ips_mode'] == 'ips_mode_legacy') {
			$suricata_sig_gid_ignore = suricata_get_group_mix_values();
		}
		if ($a_rule[$id]['ips_mode'] == 'ips_mode_mix') {
			$suricata_sig_gid_ignore = suricata_get_group_auto_block_mix_values_ignore();
		}

		$gid = $_POST['gid'][$counter];
		$action = $_POST['state'][$counter];
		$currentfile = $_POST['currentfile'][$counter];
		$ip_source = $_POST['ip_source'][$counter];                          
    	$port_source = $_POST['port_source'][$counter];                                
    	$direction = $_POST['direction'][$counter];                         
    	$ip_destination = $_POST['ip_destination'][$counter];                             
    	$port_destination = $_POST['port_destination'][$counter];

		#echo $sid . "\n";
		
		#echo $gid . "\n";

		#echo var_dump($a_rule["0"]);

		#echo "GID: $gid, ACTION: $action, CURRENTEFILE: $currentfile, IPSOURCE: $ip_source, PORTSOURCE: $port_source, DIRECTION: $direction, IPDESTINATION: $ip_destination, PORTDESTINATION: $port_destination \n";

		#echo var_dump($rules_map[$gid]);

		switch ($action) {
			case "alert":
				foreach ($rules_map[$gid] as $sid => $rule) {
					$rules_map[$gid][$sid]['action'] = 'alert';
					if (!is_array($alertsid[$gid])) {                       
						$alertsid[$gid] = array();                      
					}                                                       
					$alertsid[$gid][$sid] = "alertsid";                     
					if (isset($dropsid[$gid][$sid])) {                      
						unset($dropsid[$gid][$sid]);                    
					}                                                       
					if (isset($passid[$gid][$sid])) {                       
						unset($passid[$gid][$sid]);                     
					}
				}
				break;

			case "drop":
				foreach ($rules_map[$gid] as $sid => $rule) {
					$rules_map[$gid][$sid]['action'] = 'drop';
					if (!is_array($dropsid[$gid])) {
						$dropsid[$gid] = array();
					}
					$dropsid[$gid][$sid] = "dropsid";
					if (isset($alertsid[$gid][$sid])) {
						unset($alertsid[$gid][$sid]);
					}
					if (isset($passid[$gid][$sid])) {
						unset($passid[$gid][$sid]);
					}
				}
				break;

			case "pass":
				foreach ($rules_map[$gid] as $sid => $rule) {
					$rules_map[$gid][$sid]['action'] = 'pass';
					if (!is_array($passid[$gid])) {
						$passid[$gid] = array();
					}
					$passid[$gid][$sid] = "passid";
					if (isset($alertsid[$gid][$sid])) {
						unset($alertsid[$gid][$sid]);
					}
					if (isset($dropsid[$gid][$sid])) {
						unset($dropsid[$gid][$sid]);
					}
				}
				break;
		}

		$uuid = $a_rule[$id]['uuid'];
		$interface_real_id = get_real_interface($a_rule[$id]['interface']);

		$tmp = [];
		$ignore_array = [];
		$new_legacy_array_simple = [];

		foreach (array_keys($alertsid) as $k1) {
			foreach ($alertsid[$k1] as $k2 => $val) {
				if ($k1 == $gid) {
					$tmp[] = "{$k1}:{$k2}:({$ip_source}|{$port_source}|{$direction}|{$ip_destination}|{$port_destination})";
					$new_legacy_array_simple[] = "{$k1}{$k2}";
					if ($ip_source != "any") {
						$reload_interfaces = true;
					}
				} else {
					$ip_info = explode("|", str_replace(array("(", ")"), "", explode(":", $alertsid[$k1][$k2])[2]));
					$ip_source_cf = $ip_info[0];
					$port_source_cf = $ip_info[1];
					$direction_cf = $ip_info[2];
					$ip_destination_cf = $ip_info[3];
					$port_destination_cf = $ip_info[4];
					$tmp[] = "{$k1}:{$k2}:({$ip_source_cf}|{$port_source_cf}|{$direction_cf}|{$ip_destination_cf}|{$port_destination_cf})";
					$new_legacy_array_simple[] = "{$k1}{$k2}";
					if ($ip_source != "any") {
						$reload_interfaces = true;
					}
				}
			}
		}

		$tmp = array_unique(array_filter($tmp));

		if (!empty($tmp)) {
			$return_tmp = [];
			foreach(explode("||", $a_rule[$id]['rule_sid_force_alert']) as $line_now) {
				$gid_sid = explode(":", $line_now)[0] . explode(":", $line_now)[1];
				if (!in_array($gid_sid, $new_legacy_array_simple)) {
					continue;
				}
			}
			$tmp = implode("||", $tmp);
			$tmp = rtrim($tmp, "||");
			$tmp = ltrim($tmp, "||");
			$a_rule[$id]['rule_sid_force_alert'] = $tmp;
		} else {
			unset($a_rule[$id]['rule_sid_force_alert']);
		}

		// implements passlist for drop rules
		/*$home_net = array();
		$passlist = '';
		global $config;
		$suricataglob = $config['installedpackages']['suricata'];

		foreach ($suricataglob['passlist']['item'] as $value) {
			$home_net[] = explode(" ", trim(filter_expand_alias($value['address'])));
		} 

		if (!empty($home_net)) {

			foreach($home_net as $host){
				if(is_array($host)){
					foreach($host as $host_int){
						if ($host_int == "") {
							continue;
						}
						$passlist = $passlist . '!'.$host_int.', ';
					}
				} else {
					if ($host == "") {
						continue;
					}
					$passlist = $passlist . '!'.$host.', ';
				}
			}
			$resultado = substr($passlist,0,-2);
			$rep1 = ',' . '[' . $resultado . ']';
			$check_pass_add = 	str_replace($rep1,"",$ip_source);	
			file_put_contents("/tmp/text_ip_source.txt", $check_pass_add);
			if (empty($passlist)){
				if (!empty($ip_source)) {
					$ip_source = $ip_source;
				} else {
					$ip_source = "any";
				}
			} else {
				if (!empty($ip_source)) {
					$ip_source = $check_pass_add . ',' . '[' . $resultado . ']';// . $ip_source;
				} else {
					$ip_source = "any";
				}
			}

		}*/
		
		$tmp = [];
		$ignore_array = [];
		$new_legacy_array_simple = [];
		foreach (array_keys($dropsid) as $k1) {
			foreach ($dropsid[$k1] as $k2 => $val) {
    	        if ($k1 == $gid) {
    	            $tmp[] = "{$k1}:{$k2}:({$ip_source}|{$port_source}|{$direction}|{$ip_destination}|{$port_destination})";
					$new_legacy_array_simple[] = "{$k1}{$k2}";
					if ($ip_source != "any") {
						$reload_interfaces = true;
					}
    	        } else {
    	            $ip_info = explode("|", str_replace(array("(", ")"), "", explode(":", $dropsid[$k1][$k2])[2]));
    	            $ip_source_cf = $ip_info[0];
    	            $port_source_cf = $ip_info[1];
    	            $direction_cf = $ip_info[2];
    	            $ip_destination_cf = $ip_info[3];
    	            $port_destination_cf = $ip_info[4];
    	            $tmp[] = "{$k1}:{$k2}:({$ip_source_cf}|{$port_source_cf}|{$direction_cf}|{$ip_destination_cf}|{$port_destination_cf})";
					$new_legacy_array_simple[] = "{$k1}{$k2}";
					if ($ip_source != "any") {
						$reload_interfaces = true;
					}
    	        }
    	    }
		}

		$tmp = array_unique(array_filter($tmp));

		if (!empty($tmp)) {
			$return_tmp = [];
			foreach(explode("||", $a_rule[$id]['rule_sid_force_drop']) as $line_now) {
				$gid_sid = explode(":", $line_now)[0] . explode(":", $line_now)[1];
				if (!in_array($gid_sid, $new_legacy_array_simple)) {
					continue;
				}
			}
			$tmp = implode("||", $tmp);
			$tmp = rtrim($tmp, "||");
			$tmp = ltrim($tmp, "||");
			$a_rule[$id]['rule_sid_force_drop'] = $tmp;
		} else {
			unset($a_rule[$id]['rule_sid_force_drop']);
		}


		#echo $a_rule[$id]['rule_sid_force_drop'];

		#echo "Suricata pkg: modified action for group {$gid} on {$a_rule[$id]['interface']}.\n";


		$tmp = [];
		$ignore_array = [];
		$new_legacy_array_simple = [];

		foreach (array_keys($passid) as $k1) {
			foreach ($passid[$k1] as $k2 => $val) {
				if ($k1 == $gid) {
					$tmp[] = "{$k1}:{$k2}:({$ip_source}|{$port_source}|{$direction}|{$ip_destination}|{$port_destination})";
					$new_legacy_array_simple[] = "{$k1}{$k2}";
				} else {
					$ip_info = explode("|", str_replace(array("(", ")"), "", explode(":", $alertsid[$k1][$k2])[2]));
					$ip_source_cf = $ip_info[0];
					$port_source_cf = $ip_info[1];
					$direction_cf = $ip_info[2];
					$ip_destination_cf = $ip_info[3];
					$port_destination_cf = $ip_info[4];
					$tmp[] = "{$k1}:{$k2}:({$ip_source_cf}|{$port_source_cf}|{$direction_cf}|{$ip_destination_cf}|{$port_destination_cf})";
					$new_legacy_array_simple[] = "{$k1}{$k2}";
				}
			}
		}

		$tmp = array_unique(array_filter($tmp));

		if (!empty($tmp)) {
			$return_tmp = [];
			foreach(explode("||", $a_rule[$id]['rule_sid_force_pass']) as $line_now) {
				$gid_sid = explode(":", $line_now)[0] . explode(":", $line_now)[1];
				if (!in_array($gid_sid, $new_legacy_array_simple)) {
					continue;
				}
			}
			$tmp = implode("||", $tmp);
			$tmp = rtrim($tmp, "||");
			$tmp = ltrim($tmp, "||");
			$a_rule[$id]['rule_sid_force_pass'] = $tmp;
		} else {
			unset($a_rule[$id]['rule_sid_force_pass']);
		}

		$if_real = get_real_interface($a_rule[$id]['interface']);
		$if_uuid = $a_rule[$id]['uuid'];
		$ips_mode = $a_rule[$id]['ips_mode'];

		// Clear block table
		if ($action == "alert") {
			foreach(explode("||", $a_rule[$id]['rule_sid_force_alert']) as $line_now) {
				$gid_sid = explode(":", $line_now)[0];
				if ((intval($gid_sid) == intval($gid)) && (!in_array($gid, $gid_used))) {
					$gid_used[] = $gid;
					if (!in_array($gid, $suricata_sig_gid_ignore)) {
						$gidtable = '/usr/local/share/suricata/rules/gid:' . $gid .'.txt';
						if (!in_array($gid, $suricata_sig_gid_ignore) && ($a_rule[$id]['ips_mode'] == 'ips_mode_legacy')) {
							configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid .'.txt', $gid, $if_real, $if_uuid, "delete", $ips_mode);
						} elseif (in_array($gid, $suricata_sig_gid_ignore) && ($a_rule[$id]['ips_mode'] == 'ips_mode_mix')) {
							configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid .'.txt', $gid, $if_real, $if_uuid, "delete", $ips_mode);
						}
					}
				}
			}
		}
			
		// Insert block table	
		if ($action == "drop") {			
			foreach(explode("||", $a_rule[$id]['rule_sid_force_drop']) as $line_now) {
				$gid_sid = explode(":", $line_now)[0];
				if ((intval($gid_sid) == intval($gid)) && (!in_array($gid, $gid_used))) {
					$gid_used[] = $gid;
					if (!in_array($gid, $suricata_sig_gid_ignore)) {
						$gidtable = '/usr/local/share/suricata/rules/gid:' . $gid .'.txt';
						if (!in_array($gid, $suricata_sig_gid_ignore) && ($a_rule[$id]['ips_mode'] == 'ips_mode_legacy')) {
							configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid .'.txt', $gid, $if_real, $if_uuid, "add", $ips_mode);
						} elseif (in_array($gid, $suricata_sig_gid_ignore) && ($a_rule[$id]['ips_mode'] == 'ips_mode_mix')) {
							configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid .'.txt', $gid, $if_real, $if_uuid, "add", $ips_mode);
						}
					}
				}
			}
		}		
	}
	// Update our in-memory rules map with the changes just saved
	// to the Suricata configuration file.
	suricata_modify_sids_action($rules_map, $a_rule[$id]);
	/* Save new configuration */
	//write_config("Suricata pkg: save modified custom rules for {$a_rule[$id]['interface']}.");
	$rebuild_rules = true;
	suricata_generate_yaml($a_rule[$id]);
	$rebuild_rules = false;
	//Mixed operation
	sleep(3);
	suricata_merge_mixed_and_commented_fapp($a_rule[$id]);
	sleep(3);
	suricata_start_reload_mixed_interface_fapp($a_rule[$id], $stop=true, $start=false, $reload=true);
	/* Signal Suricata to "live reload" the rules */
	suricata_reload_config($a_rule[$id]);
	// Sync to configured CARP slaves if any are enabled
	suricata_sync_on_changes();
}

if (in_array('ips_mode_legacy', $exist_legacy_fapp) && $reload_interfaces) {
	suricata_reload_interfaces_and_start_check_exceptions_simple();
}

write_config("Aplicando regras do Suricata");


if (isset($_POST['Error_Find']) && isset($_POST['interface'])) {
	if ($_POST['interface'] == '100') {
		for($counter=0;$counter<=(count($a_rule)-1);$counter++) {
			$interface_grep = get_real_interface($a_rule[$counter]['interface']);
			$uuid = $a_rule[$counter]['uuid'];
			$diretorio_logs_suricata = "/var/log/suricata/";
			if (file_exists($diretorio_logs_suricata)) {
				$interface_grep = trim(shell_exec("/bin/ls {$diretorio_logs_suricata} | /usr/bin/grep suricata_{$interface_grep}{$uuid}"));
				echo intval(trim(shell_exec("/usr/bin/tail -n10 {$diretorio_logs_suricata}{$interface_grep}/suricata.log | /usr/bin/grep '<Error>' | /usr/bin/wc -l")));
			}
		}
	} else {
		$interface_grep = get_real_interface($a_rule[$_POST['interface']]['interface']);
		$uuid = $a_rule[$_POST['interface']]['uuid'];
		$diretorio_logs_suricata = "/var/log/suricata/";
		if (file_exists($diretorio_logs_suricata)) {
			$interface_grep = trim(shell_exec("/bin/ls {$diretorio_logs_suricata} | /usr/bin/grep suricata_{$interface_grep}{$uuid}"));
			echo intval(trim(shell_exec("/usr/bin/tail -n10 {$diretorio_logs_suricata}{$interface_grep}/suricata.log | /usr/bin/grep '<Error>' | /usr/bin/wc -l")));
		}
	}
	echo '0';
}

?>
