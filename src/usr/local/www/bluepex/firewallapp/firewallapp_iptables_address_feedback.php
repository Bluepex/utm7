<?php
require_once("config.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");


function addClogBlock($sidgid, $if_real, $uuid, $gidtable) {
	if (file_exists("/usr/local/etc/suricata/suricata_{$uuid}_{$if_real}/rules/suricata.rules")) {
        if (strlen($sidgid) >= 5) {
    		$grepInfoGID=shell_exec("grep -r 'sid:{$sidgid};' /usr/local/etc/suricata/suricata_{$uuid}_{$if_real}/rules/suricata.rules | head -n1");
        } else {
        	$grepInfoGID=shell_exec("grep -r 'gid:{$sidgid};' /usr/local/etc/suricata/suricata_{$uuid}_{$if_real}/rules/suricata.rules | head -n1");    
        }
		if (!empty($grepInfoGID)) {
			$dateNow = date("m/d/Y-H:m:s.100000");
			$classType=trim(explode(";",explode("classtype:", $grepInfoGID)[1])[0]);
			$rev=trim(explode(";",explode("rev:", $grepInfoGID)[1])[0]);
			$sid=trim(explode(";",explode("sid:", $grepInfoGID)[1])[0]);
            $gid=trim(explode(";",explode("gid:", $grepInfoGID)[1])[0]);
            if (empty($gid)) {
                $gid=1;
            }
			$valuesGroup=explode(",",trim(shell_exec("grep '{$classType}' /usr/local/share/suricata/classification.config")));
			$classification=trim($valuesGroup[1]);
			$priority=trim(explode("\n", $valuesGroup[2])[0]);
            if (!empty($gid) && !empty($sid) && !empty($classification) && !empty($priority) && !empty($classType) && !empty($rev)) {
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

function configTableIptable($gidtable, $sidgid, $if_real, $uuid, $action, $ips_mode) {
    //SID
	if ($ips_mode != "ips_mode_inline") {
		if (file_exists($gidtable)) {
			// Usually
			foreach(explode("\n", trim(shell_exec("pfctl -sT | grep fapp2c_{$if_real}{$uuid}"))) as $tablesSimple) {
				if (!empty($tablesSimple)) {
					shell_exec("/sbin/pfctl -t {$tablesSimple} -T {$action} -f {$gidtable}");
					if ($action == "add") {
                        addClogBlock($sidgid, $if_real, $uuid, $gidtable);
                    }
				}
			}
			// with exceptions
			foreach(explode("\n", trim(shell_exec("/sbin/pfctl -sT | grep fapp2c_b_{$if_real}{$uuid}_1{$sidgid}"))) as $tablesSimple) {
				if (!empty($tablesSimple)) {
					if (count(intval(end(explode("_",$tablesSimple)))) >= 5) {
						shell_exec("/sbin/pfctl -t {$tablesSimple} -T {$action} -f {$gidtable}");
						if ($action == "add") {
							addClogBlock($sidgid, $if_real, $uuid, $gidtable);
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

        foreach(explode("\n", trim(shell_exec("/sbin/pfctl -sT | grep fapp2c_b_{$if_real}{$uuid}_1{$sidgid}"))) as $tablesSimple) {
			if (!empty($tablesSimple)) {
				if (count(intval(end(explode("_",$tablesSimple)))) >= 5) {
					shell_exec("/sbin/pfctl -t {$tablesSimple} -T flush");
				}	
			}
		}
	}
    if ($ips_mode != "ips_mode_inline") {
		if (file_exists($gidtable)) {
			// Usually
			foreach(explode("\n", trim(shell_exec("pfctl -sT | grep fapp2c_{$if_real}{$uuid}"))) as $tablesSimple) {
				if (!empty($tablesSimple)) {
					shell_exec("/sbin/pfctl -t {$tablesSimple} -T {$action} -f {$gidtable}");
                    if ($action == "add") {
                        addClogBlock($sidgid, $if_real, $uuid, $gidtable);
                    }
				}
			}
			// with exceptions
			foreach(explode("\n", trim(shell_exec("/sbin/pfctl -sT | grep fapp2c_b_{$if_real}{$uuid}_{$sidgid}"))) as $tablesSimple) {
				if (!empty($tablesSimple)) {
					if (count(intval(end(explode("_",$tablesSimple)))) < 5) {
						shell_exec("/sbin/pfctl -t {$tablesSimple} -T {$action} -f {$gidtable}");
						if ($action == "add") {
							addClogBlock($sidgid, $if_real, $uuid, $gidtable);
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
		foreach(explode("\n", trim(shell_exec("/sbin/pfctl -sT | grep fapp2c_b_{$if_real}{$uuid}_{$sidgid}"))) as $tablesSimple) {
			if (!empty($tablesSimple)) {
				if (count(intval(end(explode("_",$tablesSimple)))) < 5) {
					shell_exec("/sbin/pfctl -t {$tablesSimple} -T flush");
				}	
			}
		}
	}
}

if (isset($config['installedpackages']['suricata']['rule'])) {
    foreach($config['installedpackages']['suricata']['rule'] as $interface_now) {

        $ips_mode = $interface_now['ips_mode'];

		$suricata_sig_gid_ignore = [];
		$mode_change = "";

        if ($ips_mode == 'ips_mode_mix') {
            $suricata_sig_gid_ignore = suricata_get_group_auto_block_mix_values_ignore($a_rule[$id]);
        } elseif ($ips_mode == 'ips_mode_legacy') {
            $suricata_sig_gid_ignore = suricata_get_group_mix_values();
        }
		
        $if_uuid = $interface_now['uuid'];
		$if_real = get_real_interface($interface_now['interface']);
        $sid_used = [];
        $gid_used = [];

        // Clear block table
        if (isset($interface_now['rule_sid_force_alert'])) {
			foreach(explode("||", $interface_now['rule_sid_force_alert']) as $line_now) {

				$sid_now = explode(":", $line_now)[1];
                $gid_now = explode(":", $line_now)[0];

                if (!empty($gid_now) && !empty($sid_now)) {
                    if ($gid_now > 1) {
                        if (!in_array($gid_now, $gid_used)) {
                            $gid_used[] = $gid_now;
                            if ($ips_mode == 'ips_mode_mix') {
                                if (in_array($gid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid_now .'.txt', $gid_now, $if_real, $if_uuid, "delete", $ips_mode);
                                }
                            } elseif ($ips_mode == 'ips_mode_legacy') {
                                if (!in_array($gid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid_now .'.txt', $gid_now, $if_real, $if_uuid, "delete", $ips_mode);
                                }
                            }
                        }
                    } else {
                        if (!in_array($sid_now, $sid_used)) {
                            $sid_used[] = $sid_now;
                            if ($ips_mode == 'ips_mode_mix') {
                                if (in_array($sid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/sid:' . $sid_now .'.txt', $sid_now, $if_real, $if_uuid, "delete", $ips_mode);
                                }
                            } elseif ($ips_mode == 'ips_mode_legacy') {
                                if (!in_array($sid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/sid:' . $sid_now .'.txt', $sid_now, $if_real, $if_uuid, "delete", $ips_mode);
                                }
                            }
                        }
                    }
                }

			}
		}

		// Insert block table	
        if (isset($interface_now['rule_sid_force_drop'])) {
			foreach(explode("||", $interface_now['rule_sid_force_drop']) as $line_now) {

				$sid_now = explode(":", $line_now)[1];
                $gid_now = explode(":", $line_now)[0];

                if (!empty($gid_now) && !empty($sid_now)) {
                    if ($gid_now > 1) {
                        if (!in_array($gid_now, $gid_used)) {
                            $gid_used[] = $gid_now;
                            if ($ips_mode == 'ips_mode_mix') {
                                if (in_array($gid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid_now .'.txt', $gid_now, $if_real, $if_uuid, "add", $ips_mode);
                                }
                            } elseif ($ips_mode == 'ips_mode_legacy') {
                                if (!in_array($gid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/gid:' . $gid_now .'.txt', $gid_now, $if_real, $if_uuid, "add", $ips_mode);
                                }
                            }
                        }
                    } else {
                        if (!in_array($sid_now, $sid_used)) {
                            $sid_used[] = $sid_now;
                            if ($ips_mode == 'ips_mode_mix') {
                                if (in_array($sid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/sid:' . $sid_now .'.txt', $sid_now, $if_real, $if_uuid, "add", $ips_mode);
                                }
                            } elseif ($ips_mode == 'ips_mode_legacy') {
                                if (!in_array($sid_now, $suricata_sig_gid_ignore)) {
                                    configTableIptable('/usr/local/share/suricata/rules/sid:' . $sid_now .'.txt', $sid_now, $if_real, $if_uuid, "add", $ips_mode);
                                }
                            }
                        }
                    }
                }

			}
		}

    }
}