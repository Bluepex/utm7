<?php

if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_count_threads_acp") {
	$json_data_acp = json_decode('{"access_ameacas_geral":"0","access_ram":"0","access_nav":"0","access_soc":"0"}');
	if (file_exists("/usr/local/www/acp_data_{$_POST['interface']}.json")) {
		$json_data_acp = json_decode(file_get_contents("/usr/local/www/acp_data_{$_POST['interface']}.json")); 
	}
	$return_count_status = [];
	$return_count_status['access_ameacas_geral'] = $json_data_acp->{'access_ameacas_geral'} . " <img src='../images/icon-001.png' class='margin-top-img'>";
	$return_count_status['access_ram'] = $json_data_acp->{'access_ram'} . " <img src='../images/icon-002.png' class='margin-top-img'>";
	$return_count_status['access_nav'] = $json_data_acp->{'access_nav'} . " <img src='../images/icon-003.png' class='margin-top-img'>";
	$return_count_status['access_soc'] = $json_data_acp->{'access_soc'} . " <img src='../images/icon-004.png' class='margin-top-img'>";
	echo json_encode($return_count_status);
}



if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_count_table") {
	require("config.inc");
	$return_ranks = [];
	$return_ranks['qtd_rank1'] = $config['system']['bluepex_stats']['country_block']['qtd_rank1'];
	$return_ranks['country_rank1'] = $config['system']['bluepex_stats']['country_block']['country_rank1'];
	$return_ranks['rank2'] = $config['system']['bluepex_stats']['country_block']['qtd_rank2'];
	$return_ranks['country_rank2'] = $config['system']['bluepex_stats']['country_block']['country_rank2'];
	$return_ranks['rank3'] = $config['system']['bluepex_stats']['country_block']['qtd_rank3'];
	$return_ranks['country_rank3'] = $config['system']['bluepex_stats']['country_block']['country_rank3'];
	$return_ranks['last_ip'] = $config['system']['bluepex_stats']['last_ip'];
	echo json_encode($return_ranks);
}

if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_table_strutuct_geo") {
	require("config.inc");
	$nentriesinterval = isset($user_settings['widgets'][$widgetkey]['filterlogentriesinterval']) ? $user_settings['widgets'][$widgetkey]['filterlogentriesinterval'] : 2;
	$filter_logfile = "{$g['varlog_path']}/filter.log";
	$filterlog = conv_log_filter($filter_logfile, 11, 11);
	$netstat_estabelished = implode("___", explode("\n", trim(shell_exec("netstat -aan | awk -F\" \" '{print $4}' | sort | uniq"))));

	$returnTMP = [];
	foreach ($filterlog as $filterent):
		if (strpos($netstat_estabelished_now, $filterent['srcip']) === false) {
			$returnTMP[] = $filterent;
		}
	endforeach;

	$filterlog = $returnTMP;

	$table_struture = "";
	foreach ($filterlog as $filterent):
		if ($filterent['version'] == '6') {
			$srcIP = "[" . htmlspecialchars($filterent['srcip']) . "]";
			$dstIP = "[" . htmlspecialchars($filterent['dstip']) . "]";
		} else {
			$srcIP = htmlspecialchars($filterent['srcip']);
			$dstIP = htmlspecialchars($filterent['dstip']);
		}

		if ($filterent['act'] == "block") {
			$iconfn = "times text-danger";
		} else if ($filterent['act'] == "reject") {
			$iconfn = "hand-stop-o text-warning";
		} else if ($filterent['act'] == "match") {
			$iconfn = "filter";
		} else {
			$iconfn = "check text-success";
		}
		$rule = find_rule_by_number($filterent['rulenum'], $filterent['tracker'], $filterent['act']);
		// Putting <wbr> tags after each ':'  allows the string to word-wrap at that point
		$srcIP = str_replace(':', ':<wbr>', $srcIP);
		$dstIP = str_replace(':', ':<wbr>', $dstIP);
		
		$table_struture .= "<tr>";
		$table_struture .= "<td title='" . htmlspecialchars($filterent['time']) . "'>" . substr(htmlspecialchars($filterent['time']),0,-3) . "</td>";
		$table_struture .= "<td><a href='../diag_dns.php?host=" . $filterent['srcip'] . "' style='color: #ff0012; font-weight: bold; text-decoration: none;' ";
		$table_struture .= "title='" . gettext("Reverse Resolve with DNS") . "'>IP: " . $srcIP . "</a>";
		if ($filterent['srcport']) {
			$table_struture .= gettext(' | PORT: ') . htmlspecialchars($filterent['srcport']);
		}
		$table_struture .= "</td>"; 
		$table_struture .= "<td><a href='../diag_dns.php?host=" . $filterent['dstip'] . "' style='color: #ff0012; font-weight: bold; text-decoration: none;' ";
		$table_struture .= "title='" . gettext("Reverse Resolve with DNS") . "'>IP: " . $dstIP  . "</a>";
		if ($filterent['dstport']) {
			$table_struture .= gettext(' | PORT: ') . htmlspecialchars($filterent['dstport']);
		}
		$table_struture .= "</td>";
		$table_struture .= "<td><img src='icon-block.png'></td>";
		$table_struture .= "</tr>";

	endforeach;
	if (count($filterlog) == 0) {
		$table_struture .= "<tr class='text-nowrap'><td colspan=5 class='text-center'>" . gettext('No logs to display') . "</td></tr>";
	}

	echo $table_struture;
}


if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_list_cards") {

	$cards_structur = "";
	$cards_structur .= "<br>";
	$cards_structur .= "<h6>" . gettext("REAL TIME INSPECTION") . "</h6>";
	$cards_structur .= "<hr class='line-bottom-6 my-3'>";
	$cards_structur .= "<ul class='bxslider pl-0 col-12' id='stopTransformation'>";
	$cards_structur .= "</ul>";
	
	if (file_exists("{$g['varlog_path']}/suricata/suricata_{$_POST['interface']}/alerts.log")) {
		exec("tail -n1100 {$g['varlog_path']}/suricata/suricata_{$_POST['interface']}/alerts.log > {$g['tmp_path']}/graphic_virus_suricata{$_POST['interface']}_long");
		exec("tail -n100 {$g['tmp_path']}/graphic_virus_suricata{$_POST['interface']}_long > {$g['tmp_path']}/graphic_virus_suricata{$_POST['interface']}");
		if (file_exists("{$g['tmp_path']}/graphic_virus_suricata{$_POST['interface']}")) {
			$fd = @fopen("{$g['tmp_path']}/graphic_virus_suricata{$_POST['interface']}", "r");
			$fields = array();
			$tmp = array();
			$buf = "";
			while (($buf = @fgets($fd)) !== FALSE) {
				// The regular expression match below returns an array as follows:
				// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
				preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s{.*}\s(.*)\s->\s(.*)/', $buf, $tmp);
				if (!empty($tmp)) {
					// Field 0 is the event timestamp
					$date_time = substr($buf, 0, strpos($buf, '  '));
					$date_time1 = substr($date_time,0,19);
					$fields['sid'] = trim($tmp[3]);
					$fields['msg'] = trim($tmp[5]);
					$fields['ip_origin'] = trim($tmp[8]);
					$fields['ip_destiny'] = trim($tmp[9]);
					// Create a DateTime object from the event timestamp that
					// we can use to easily manipulate output formats.
					//$event_tm = date_format($date_time,"d/m/Y H:i:s");
					/* Time */
					$alert_time = substr($date_time1,-8);
					/* Date */
					$alert_date = str_replace("/","",substr($date_time1,3,2)) . "/" . str_replace("/","",substr($date_time1,0,2)) . "/" . str_replace("/","",substr($date_time1,6,4));
					$bkColor = "";
					$textColor = "white";
					$borderBox = "";
					foreach ($data_classification as $n=>$c){
						if (in_array($tmp[6], $c)) {
							$bkColor = $c['color'];
							break;
						}
					}
					if (strlen($bkColor) == 0) {
						$textColor = "black";
						$borderBox = "border:1px solid black;";
					}

					$cards_structur .= "<li class='color-white mb-2 p-1' style='background-color: " . $bkColor . "; color: " . $textColor . "; " . $borderBox . ">";
					$cards_structur .= "<h5 style='margin-top:0px;'>" . $fields['msg'] . "</h5>";
					$cards_structur .= "<span>" . $alert_date . " - " . $alert_time . "</span>";
					$cards_structur .= "<div style='margin-top: 10px;'>";
					$cards_structur .= "<b>" . gettext("Source IP:")  . "</b>" . $fields['ip_origin'] . " - " . "<b onclick=\"getInfoDestino('" . $fields['ip_destiny'] . "')\">" . gettext("Destination IP:") . "</b><b style='font-weight:normal;' onclick=\"getInfoDestino('" . $fields['ip_destiny'] . "')\">" .  $fields['ip_destiny'] . "</b></div>";
					$cards_structur .= "<div style='margin-top: 10px;' onclick=\"submitSearchRulesThreads('" . $tmp[3] . "')\">ID: " . $tmp[3] . "</div>";
					$cards_structur .= "</li>";
				}
			}
			echo $cards_structur;
		}
	} else {
		echo $cards_structur;
	}
}


if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_count_malwares") {
	$return_malwares = [];
	$return_malwares['trojan'] = shell_exec("grep -ic \"Classification: A Network Trojan was detected\" /var/log/suricata/suricata_{$_POST['interface']}/alerts.log");
	$return_malwares['ransonware'] = shell_exec("grep -ic \"Classification: Ransomware Traffic\" /var/log/suricata/suricata_{$_POST['interface']}/alerts.log");
	$return_malwares['phishing'] = shell_exec("grep -ic \"Classification: Phishing Traffic\" /var/log/suricata/suricata_{$_POST['interface']}/alerts.log");
	$return_malwares['warning'] = shell_exec("grep -ic \"Classification: A suspicious filename was detected\" /var/log/suricata/suricata_{$_POST['interface']}/alerts.log");
	$return_malwares['exploit'] = shell_exec("grep -ic \"Classification: Exploit Kit Activity Detected\" /var/log/suricata/suricata_{$_POST['interface']}/alerts.log");
	$return_malwares['malware'] = shell_exec("grep -ic \"Classification: Potentially Bad Traffic\" /var/log/suricata/suricata_{$_POST['interface']}/alerts.log");
	echo json_encode($return_malwares);
}

function generateIgnoreFiles() {
	shell_exec("find /usr/local/www/ -type f -name '*.php' -exec basename {} \; > /tmp/files_www");
	shell_exec("find /usr/local/www/ -type f -name '*.inc' -exec basename {} \; >> /tmp/files_www");
	shell_exec("find /usr/local/www/ -type f -name '*.js' -exec basename {} \; >> /tmp/files_www");
	shell_exec("find /usr/local/www/ -type f -name '*.css' -exec basename {} \; >> /tmp/files_www");
}

if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_table_strutuct_files") {
	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");
	$acp_interface = $_POST['interface'];
	$table_strutuct_files = "";

	#shell_exec("/usr/local/bin/redis-cli lrange suricata{$acp_interface} -2000 -1 > {$g['varlog_path']}/suricata/suricata_{$acp_interface}/eve.json");

	if (file_exists("{$g['varlog_path']}/suricata/suricata_{$acp_interface}/eve.json")) {

		//$interface_now = explode("__", str_replace("/", "", trim(shell_exec("ps ax | grep '{$acp_interface}' | grep -v grep | awk -F\"suricata.yaml\" '{print $1}' | awk -F\"_\" '{print $2\"__\"$3}'"))));
		//if ((intval(trim(shell_exec("echo \"keys *\" | redis-cli --raw | grep '{$acp_interface}' -c"))) > 0) && count($interface_now) > 0 && (intval(trim(shell_exec("grep 'filetype: redis' /usr/local/etc/suricata/suricata_{$interface_now[0]}_{$interface_now[1]}/suricata.yaml -c"))) > 0)) {
		//	exec("head -n5000 {$g['varlog_path']}/suricata/suricata_{$acp_interface}/eve.json > {$g['tmp_path']}/files_suricata{$acp_interface}_works");
		//} else {
		exec("tail -n10000 {$g['varlog_path']}/suricata/suricata_{$acp_interface}/eve.json |  grep filename | grep fileinfo | grep -v 'tx_id\":0' > {$g['tmp_path']}/files_suricata{$acp_interface}_works");
		//}

		$tmpFile = <<<EOD
		for flow in `grep fileinfo {$g['tmp_path']}/files_suricata{$acp_interface}_works | grep filename | tail -n100 | jq '.flow_id' | sort | uniq`
		do
			grep \$flow {$g['tmp_path']}/files_suricata{$acp_interface}_works | grep fileinfo | grep filename | tail -n1 >> {$g['tmp_path']}/files_suricata{$acp_interface}.tmp
			if [ `grep \$flow {$g['tmp_path']}/files_suricata{$acp_interface}_works | grep '\"alert\":{\"action\":\"blocked\"' -c` != 0 ]
			then
				grep \$flow {$g['tmp_path']}/files_suricata{$acp_interface}_works | grep '\"alert\":{\"action\":\"blocked\"' | tail -n1 >> {$g['tmp_path']}/files_suricata{$acp_interface}_status.tmp
			else 
				grep \$flow {$g['tmp_path']}/files_suricata{$acp_interface}_works | grep '\"alert\":{\"action\":\"' | tail -n1 >> {$g['tmp_path']}/files_suricata{$acp_interface}_status.tmp
			fi
		done
		mv {$g['tmp_path']}/files_suricata{$acp_interface}.tmp {$g['tmp_path']}/files_suricata{$acp_interface}
		mv {$g['tmp_path']}/files_suricata{$acp_interface}_status.tmp {$g['tmp_path']}/files_suricata{$acp_interface}_status
		EOD;
		file_put_contents("/tmp/filter_files_suricata{$acp_interface}", $tmpFile);
		exec("/bin/sh /tmp/filter_files_suricata{$acp_interface} && /bin/rm /tmp/filter_files_suricata{$acp_interface}");
		sleep(2);

		if (file_exists("{$g['tmp_path']}/files_suricata{$acp_interface}")) {
			
			$allowed_rules_clamd = [];
			$blocked_rules_clamd = [];
			if (file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt")) {
				$allowed_rules_clamd = array_unique(array_filter(explode("\n", trim(file_get_contents("/usr/local/share/suricata/otx/ransomd5/clamav_whitelist_256.txt")))));
			}
			if (file_exists("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt")) {
				$blocked_rules_clamd = array_unique(array_filter(explode("\n", trim(file_get_contents("/usr/local/share/suricata/otx/ransomd5/clamav_blacklist_256.txt")))));
			}

			$allowed_rules = [];
			$blocked_rules = [];
			$exception_rules = [];
			foreach (explode("\n", file_get_contents("{$g['tmp_path']}/files_suricata{$acp_interface}_status")) as $line_json) {
				$line_json = json_decode($line_json, true);
				if (isset($line_json['alert']['action'])) {
					if ($line_json['alert']['action'] == 'blocked') {
						if (!in_array($line_json['flow_id'], $blocked_rules)) {
							$blocked_rules[] = $line_json['flow_id'];
						}
					}
					if ($line_json['alert']['action'] == 'allowed') {
						if (!in_array($line_json['flow_id'], $allowed_rules)) {
							$allowed_rules[] = $line_json['flow_id'];
						}	
					}
				}
			}
			if (file_exists('/var/db/clamav/ignore_analisy.sfp')) {
				shell_exec("cat /var/db/clamav/ignore_analisy.sfp | awk -F\":\" '{print $1}' | uniq > /tmp/exceptionsList");
				$exception_rules = array_filter(array_unique(file_get_contents('/tmp/exceptionsList')));
				if (!is_array($exception_rules) || count($exception_rules) == 0) {
					$exception_rules = [];
				}
			}
			$allowed_rules = array_filter(array_unique($allowed_rules));
			$blocked_rules = array_filter(array_unique($blocked_rules));
			$exception_rules = array_filter(array_unique($exception_rules));

			$reserved_words = array(" ", "", "/", "/ip", "ip", "cpack.json", 
			"cpack_versions.json","version_server", "login", "/api", 
			"emerging_version", "fapp_version", "InRelease", "canonical.html", "success.txt",
			"list", "info", "libhtp::request_uri_not_seen", "ajax_alerts_tls.php", 
			"ajax_alerts_http.php","gateways.widget.php", "ajax_alerts_virus.php",
			".passModal", "ajax_dash_ap_services.php", "bar_ajax_index_temp_capacity.php",
			"ajax_retuns_inspect_real_time.php", "ajax_update_screen_clamav.php",
			"ajax_get_interface_setting.php", "ajax_alerts_virus_geral.php",
			"ajax_save_group_rule.php", "ajax_status_source.php", "ajax_top_category.php",
			"ajax_graphic_social.php", "ajax_save_rule.php", "ajax_categorie_rules.php",
			"ajax_alerts_geral.php", "ajax_graphic_virus.php", "ajax_latest_backup.php", 
			"ajax_links.php", "ajax_threat_barometer.php", "ajax_update_rules_fapp.php",
			"bandwidth_by_ip_dash.php", "getstats.php");

			if (file_exists('/tmp/files_www')) {
				if (filemtime('/tmp/files_www')+1200 <= strtotime("now")) {
					generateIgnoreFiles();
				}
			} else {
				generateIgnoreFiles();
			}
			$ignore_www_files = array_unique(array_filter(explode("\n", trim(file_get_contents("/tmp/files_www")))));

			$fd = fopen("{$g['tmp_path']}/files_suricata{$acp_interface}", "r");
			$buf = "";
			$counter = 0;
			while (($buf = fgets($fd)) !== FALSE) {
				$fields = array();
				$tmp = array();
			
				$fields = json_decode($buf, true);

				$event_tm = date_create_from_format("Y-m-d\TH:i:s.uP", $fields['timestamp']);

				$file_name_t = end(explode("/", $fields['fileinfo']['filename'])); #Last value in array

				if (in_array($file_name_t, $reserved_words, true)) {
					continue;
				}
				if (in_array($file_name_t, $ignore_www_files, true)) {
					continue;
				}

				/* Filename */
				$show_file_name = "";
				if (strlen($file_name_t) > 30) {
					$show_file_name = "<b style='display:none;'>{$file_name_t}</b>" . substr($file_name_t, 0, 30) . "..." . " <i class='fa fa-info icon-pointer icon-primary' title='{$file_name_t}'></i>";
				} else {
					$show_file_name = $file_name_t;
				}
			
				/* Size */
				if ($fields['fileinfo']['size'] > 1048576) {
					$file_size = round(intval($fields['fileinfo']['size'])/1048576) . ' M';
				} elseif ($fields['fileinfo']['size'] > 1024) {
					$file_size = round(intval($fields['fileinfo']['size'])/1024) . ' K';
				} else {
					$file_size = $fields['fileinfo']['size'] . ' B';
				}
			
				/* Protocol */
				$file_proto = "";
				if (isset($fields['proto']) && strlen($fields['proto']) > 0) {
					$file_proto = "<b onclick='preencherSearchDataFile(\"" . strtoupper($fields['proto']) . "\")' style='font-weight:unset!important;'>" . strtoupper($fields['proto']) . "</b>";
				}
				/* App level protocol */
				$file_app = "";
				if (isset($fields['app_proto']) && strlen($fields['app_proto']) > 0) {
					$file_app = "<b onclick='preencherSearchDataFile(\"" . strtoupper($fields['app_proto']) . "\")' style='font-weight:unset!important;'>" . strtoupper($fields['app_proto']) . "</b>";
				}
				if (!empty($file_proto) && !empty($file_app)) {
					$file_request = $file_proto . "/" . $file_app;
				} else {
					$file_request = $file_proto . $file_app;
				}
				$file_request  .= " <i class='fa fa-search' onclick='requestPathFile(\"{$fields['flow_id']}\", \"{$file_name_t}\")' title='Info of request file'></i>";
				
				/* IP SRC Port */
				$file_src_p = "";
				if (isset($fields['src_port']) && strlen($fields['src_port']) > 0) {
					$file_src_p = "<b onclick='preencherSearchDataFile(\"" . $fields['src_port'] . "\")' style='font-weight:unset!important;'>:" . $fields['src_port'] . "</b>";
				}
				
				/* IP SRC */
				$file_ip_src = $fields['src_ip'];
				/* Add zero-width space as soft-break opportunity after each colon if we have an IPv6 address */
				$file_ip_src = "<b onclick='preencherSearchDataFile(\"" . $file_ip_src . "\")' style='font-weight:unset!important;'>" . str_replace(":", ":&#8203;", $file_ip_src) . "</b>" . $file_src_p;
				/* Add Reverse DNS lookup icon */
				$file_ip_src .= '<br /><i class="fa fa-search" onclick="javascript:resolve_with_ajax(\'' . $fields['src_ip'] . '\');" title="';
				$file_ip_src .= gettext("Resolve host via reverse DNS lookup") . "\"  alt=\"Icon Reverse Resolve with DNS\" ";
				$file_ip_src .= " style=\"cursor: pointer;\"></i>";
				/* Add GeoIP check icon */
				if (!is_private_ip($fields['src_ip']) && (substr($fields['src_ip'], 0, 2) != 'fc') &&
					(substr($fields['src_ip'], 0, 2) != 'fd')) {
					$file_ip_src .= '&nbsp;&nbsp;<i class="fa fa-globe" onclick="javascript:geoip_with_ajax(\'' . $fields['src_ip'] . '\');" title="';
					$file_ip_src .= gettext("Check host GeoIP data") . "\"  alt=\"Icon Check host GeoIP\" ";
					$file_ip_src .= " style=\"cursor: pointer;\"></i>";
				}
			
				/* IP DST Port */
				$file_dst_p = "";
				if (isset($fields['dest_port']) && strlen($fields['dest_port']) > 0) {
					$file_dst_p = "<b onclick='preencherSearchDataFile(\"" . $fields['dest_port'] . "\")' style='font-weight:unset!important;'>:" . $fields['dest_port'] . "</b>";
				}

				//Filter host local ip
				$file_hostname = "-";
				$array_host = explode('\n', shell_exec("ifconfig | grep inet | awk -F\" \" '{ print $2 }'"));
				if (isset($fields['http']['hostname'])) {
					if (in_array($fields['http']['hostname'], $array_host)) {
						continue;
					}
				}

				//Filter hosts in network local
				//Ipv4
				$array_ip_local_host = explode('\n', shell_exec("ifconfig | grep inet | awk -F\" \" '{ print $2 }' | grep -r '[^0-9]**\.[0-9]**\.[0-9]**\.[0-9]**' | awk -F\".\" '{ print $1\".\"$2\".\"$3 }'"));
				if (isset($fields['http']['hostname'])) {
					foreach($array_ip_local_host as $ip_local) {
						if(preg_match("/{$ip_local}/i", $fields['http']['hostname'])) {
							continue;
						}
					}
				}
				//Hostname
				if (isset($fields['http']['hostname'])) {
					if (trim(shell_exec("hostname")) == $fields['http']['hostname']) {
						continue;
					}
				}

				if (isset($fields['http']['hostname'])) {
					$file_hostname = "<b onclick='preencherSearchDataFile(\"" . $fields['http']['hostname'] . "\")' style='font-weight:unset!important;'>" . $fields['http']['hostname'] . "</b>";
				}

				/* IP DST */
				$file_ip_dst = $fields['dest_ip'];
				/* Add zero-width space as soft-break opportunity after each colon if we have an IPv6 address */
				$file_ip_dst = "<b onclick='preencherSearchDataFile(\"" . $file_ip_dst . "\")' style='font-weight:unset!important;'>" . str_replace(":", ":&#8203;", $file_ip_dst) . "</b>" . $file_dst_p;
				/* Add Reverse DNS lookup icons */
				$file_ip_dst .= "<br /><i class=\"fa fa-search\" onclick=\"javascript:resolve_with_ajax('{$fields['dest_ip']}');\" title=\"";
				$file_ip_dst .= gettext("Resolve host via reverse DNS lookup") . "\" alt=\"Icon Reverse Resolve with DNS\" ";
				$file_ip_dst .= " style=\"cursor: pointer;\"></i>";
				/* Add GeoIP check icon */
				if (!is_private_ip($fields['dest_ip']) && (substr($fields['dest_ip'], 0, 2) != 'fc') &&
					(substr($fields['dest_ip'], 0, 2) != 'fd')) {
					$file_ip_dst .= '&nbsp;&nbsp;<i class="fa fa-globe" onclick="javascript:geoip_with_ajax(\'' . $fields['dest_ip'] . '\');" title="';
					$file_ip_dst .= gettext("Check host GeoIP data") . "\"  alt=\"Icon Check host GeoIP\" ";
					$file_ip_dst .= " style=\"cursor: pointer;\"></i>";
				}

				$hashsFile = "";
				$change_hash_list = "";
				if (isset($fields['fileinfo']['sha256']) && !empty($fields['fileinfo']['sha256'])) {
					$hashsFile = "<b style='display:none'>{$fields['fileinfo']['sha256']}</b>SHA256 <i class='fa fa-info icon-pointer icon-primary' style='margin-left:6px;' onclick='preencherSearchDataFile(\"{$fields['fileinfo']['sha256']}\")' title='{$fields['fileinfo']['sha256']}'></i><br>";
					$change_hash_list = " <i class='fa fa-book icon-pointer icon-primary' style='margin-left:6px;' onclick='setHashList(\"{$fields['fileinfo']['sha256']}\")' title='Confirm status file hash: {$fields['fileinfo']['sha256']}'></i>";
				} 
				if (isset($fields['fileinfo']['sha1']) && !empty($fields['fileinfo']['sha1'])) {
					$hashsFile = "<b style='display:none'>{$fields['fileinfo']['sha1']}</b>SHA1 <i class='fa fa-info icon-pointer icon-primary' style='margin-left:6px;' onclick='preencherSearchDataFile(\"{$fields['fileinfo']['sha1']}\")' title='{$fields['fileinfo']['sha1']}'></i><br>" . $hashsFile;				
				} 
				if (isset($fields['fileinfo']['md5']) && !empty($fields['fileinfo']['md5'])) {
					$hashsFile = "<b style='display:none'>{$fields['fileinfo']['md5']}</b>MD5 <i class='fa fa-info icon-pointer icon-primary' style='margin-left:6px;' onclick='preencherSearchDataFile(\"{$fields['fileinfo']['md5']}\")' title='{$fields['fileinfo']['md5']}'></i><br>" . $hashsFile;
				}
				if (empty($hashsFile)) {
					$hashsFile = "x";
				}
				
				$flow_id = $fields['flow_id'];
				$sha256 = $fields['fileinfo']['sha256'];

				if (in_array($sha256, $blocked_rules_clamd)) {
					$table_strutuct_files .=  "<tr style='background-color: #e71837;color: white;' name='blockColumns'>";
				} elseif (in_array($flow_id, $blocked_rules)) {
					$table_strutuct_files .=  "<tr style='background-color: #e71837;color: white;' name='blockColumns'>";
				} elseif (in_array($sha256, $allowed_rules_clamd)) {
					$table_strutuct_files .=  "<tr style='background-color: #2baf2b;color: white;' name='openColumns'>";
				} elseif (in_array($flow_id, $allowed_rules)) {
					$table_strutuct_files .=  "<tr style='background-color: #2baf2b;color: white;' name='openColumns'>";
				} elseif (in_array($flow_id, $exception_rules)) {
					$table_strutuct_files .=  "<tr style='background-color: #ffc107;color: white;' name='exceptionColumns'>";
				} else {
					$table_strutuct_files .= "<tr name='noColor'>";
				}
				
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . date_format($event_tm, "m/d/Y") . "<br/>" . date_format($event_tm, "H:i:s") . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . $file_ip_dst . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . $file_ip_src . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . $file_request . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . $file_hostname . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . $hashsFile . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'>" . $file_size . "</td>";
				$table_strutuct_files .= "<td style='word-wrap:break-word; white-space:normal;vertical-align:middle;'><b onclick=\"preencherSearchDataFile('" . $file_name_t . "')\" style='font-weight:unset!important;'>" . $show_file_name . "</b>" . $change_hash_list . "</td>";
				$table_strutuct_files .= "</tr>";

				$counter++;
				
			}
			unset($fields, $buf, $tmp);
			fclose($fd);
			#unlink_if_exists("{$g['tmp_path']}/files_suricata{$acp_interface}");
			#unlink_if_exists("{$g['tmp_path']}/files_suricata{$acp_interface}_works");
			#unlink_if_exists("{$g['tmp_path']}/files_suricata{$acp_interface}_status");
		}
	}
	echo $table_strutuct_files;
}


if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_cards_strutuct") {

	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");

	$strJsonFileContents = file_get_contents("bp-class.json");
	$data_classification = json_decode($strJsonFileContents, true);

	$acp_interface = $_POST['interface'];

	$li_cards_list = "";
	$li_cards_list .= "<div class='row'>";
	$li_cards_list .= "<div class='col-12' id='box-threats-vertical'>";
	$li_cards_list .= "<br>";
	$li_cards_list .= "<h6>" . gettext("REAL TIME INSPECTION") . "</h6>";
	$li_cards_list .= "<hr class='line-bottom-6 my-3'>";
	$li_cards_list .= "<ul class='bxslider pl-0 col-12' id='stopTransformation'>";

	if (file_exists("{$g['varlog_path']}/suricata/suricata_{$acp_interface}/alerts.log")) {
		//print_r("{$g['varlog_path']}/suricata/{$acp_if}/alerts.log");die;
		exec("tail -n1100 {$g['varlog_path']}/suricata/suricata_{$acp_interface}/alerts.log > {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long");
		exec("tail -n100 {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long > {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}");
		if (file_exists("{$g['tmp_path']}/graphic_virus_suricata{$acp_interface}")) {
			$fd = @fopen("{$g['tmp_path']}/graphic_virus_suricata{$acp_interface}", "r");
			$fields = array();
			$tmp = array();
			$buf = "";
			while (($buf = @fgets($fd)) !== FALSE) {
				// The regular expression match below returns an array as follows:
				// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
				preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s{.*}\s(.*)\s->\s(.*)/', $buf, $tmp);
				if (!empty($tmp)) {
					// Field 0 is the event timestamp
					$date_time = substr($buf, 0, strpos($buf, '  '));
					$date_time1 = substr($date_time,0,19);
					$fields['sid'] = trim($tmp[3]);
					$fields['msg'] = trim($tmp[5]);
					$fields['ip_origin'] = trim($tmp[8]);
					$fields['ip_destiny'] = trim($tmp[9]);
					// Create a DateTime object from the event timestamp that
					// we can use to easily manipulate output formats.
					//$event_tm = date_format($date_time,"d/m/Y H:i:s");
					/* Time */
					$alert_time = substr($date_time1,-8);
					/* Date */
					$alert_date = str_replace("/","",substr($date_time1,3,2)) . "/" . str_replace("/","",substr($date_time1,0,2)) . "/" . str_replace("/","",substr($date_time1,6,4));
					$bkColor = "";
					$textColor = "white";
					$borderBox = "";
					foreach ($data_classification as $n=>$c){
						if (in_array($tmp[6], $c)) {
							$bkColor = $c['color'];
							break;
						}
					}
					if (strlen($bkColor) == 0) {
						$textColor = "black";
						$borderBox = "border:1px solid black;";
					}
					$li_cards_list .= "<li class='color-white mb-2 p-1' style='background-color: " . $bkColor . "; color: " . $textColor . "; " . $borderBox . "'>";
					$li_cards_list .= "<h5 style='margin-top:0px;'>" . $fields['msg'] . "</h5>";
					$li_cards_list .= "<span>" . $alert_date . " - " . $alert_time . "</span>";
					$li_cards_list .= "<div style='margin-top: 10px;'>";
					$li_cards_list .= "<b>" . gettext("Source IP:")  . "</b>" . $fields['ip_origin'] .  " - " . "<b onclick=\"getInfoDestino('" . $fields['ip_destiny'] . "')\">" . gettext("Destination IP:") . "</b><b style='font-weight:normal;' onclick=\"getInfoDestino('" . $fields['ip_destiny'] . "')\">" .  $fields['ip_destiny'] . "</b></div>";
					$li_cards_list .= "<div style='margin-top: 10px;' onclick=\"submitSearchRulesThreads('" . $tmp[3] . "')\">ID: " . $tmp[3] . "</div>";
					$li_cards_list .= "</li>";
				}
			}
		}
	}
	echo $li_cards_list;
}


if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_select_table_strutuct_alertlog") {
	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");
	$acp_interface = $_POST['interface'];
	exec("tail -n1100 {$g['varlog_path']}/suricata/suricata_{$acp_interface}/alerts.log | awk -F\"Classification: \" '{ print $2}' | awk -F\"]\" '{print $1}' | uniq > {$g['tmp_path']}/values_classification_{$acp_interface}_long");
	$arrayWork = [];
	if (file_exists('/tmp/valuesColumnsAlerts')) {
		$arrayWork = array_unique(array_filter(explode("\n", file_get_contents("/tmp/valuesColumnsAlerts"))));
	}
	foreach (array_unique(array_filter(explode("\n", file_get_contents("{$g['tmp_path']}/values_classification_{$acp_interface}_long")))) as $lineArray) {
		if (!in_array($lineArray, $arrayWork)) {
			file_put_contents("/tmp/valuesColumnsAlerts", trim($lineArray) . "\n", FILE_APPEND);
		}
	}
	$returnArray = "";
	$returnArray .= "<option value=''>All classifications</option>";
	foreach(array_unique(array_filter(explode("\n", file_get_contents("/tmp/valuesColumnsAlerts")))) as $lineNow) {
		if (!empty($lineNow)) {
			$returnArray .= "<option value='" . implode("_", explode(" ", strtolower($lineNow))) . "'>{$lineNow}</option>";
		}
	}
	echo $returnArray;
}


if (isset($_POST['interface']) && isset($_POST['service']) && $_POST['service'] == "update_table_strutuct_alertlog") {

	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");

	$strJsonFileContents = file_get_contents("bp-class.json");
	$data_classification = json_decode($strJsonFileContents, true);

	$filterlogentries = FALSE;
	$filterfieldsarray = array();
	$acp_interface = $_POST['interface'];
	
	$table_strutuct_alerts = "";
	exec("tail -n1100 {$g['varlog_path']}/suricata/suricata_{$acp_interface}/alerts.log > {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long");
	exec("tail -n100 {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long > {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}");
	if (file_exists("{$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long")) {
		exec("head -n1000 {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long > {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long.tmp");
		exec("mv {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long.tmp {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long");
		exec("rm {$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long.tmp");
		$counter = 0;

		/*************** FORMAT without CSV patch -- ALERT -- ***********************************************************************************/
		/* Line format: timestamp  action[**] [gid:sid:rev] msg [**] [Classification: class] [Priority: pri] {proto} src:srcport -> dst:dstport */
		/*             0          1           2   3   4    5                         6                 7     8      9   10         11  12       */
		/****************************************************************************************************************************************/
		/**************** FORMAT without CSV patch -- DECODER EVENT -- **************************************************************************/
		/* Line format: timestamp  action[**] [gid:sid:rev] msg [**] [Classification: class] [Priority: pri] [**] [Raw pkt: ...]                */
		/*              0          1           2   3   4    5                         6                 7                                       */
		/************** *************************************************************************************************************************/
		$fd = fopen("{$g['tmp_path']}/graphic_virus_suricata{$acp_interface}_long", "r");
		$buf = "";
		while (($buf = fgets($fd)) !== FALSE) {
			$fields = array();
			$tmp = array();
			$decoder_event = FALSE;
			/**************************************************************/
			/* Parse alert log entry to find the parts we want to display */
			/**************************************************************/
			// Field 0 is the event timestamp
			$fields['time'] = substr($buf, 0, strpos($buf, '  '));
			// Field 1 is the rule action (value is '**' when mode is not inline IPS or 'block-drops-only')
			if (($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline'  || $a_instance[$instanceid]['block_drops_only'] == 'on') && preg_match('/\[([A-Z]+)\]\s/i', $buf, $tmp)) {
				$fields['action'] = trim($tmp[1]);
			}
			else {
				$fields['action'] = null;
			}
			// The regular expression match below returns an array as follows:
			// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
			preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
			if (!empty($tmp)) {
				$fields['gid'] = trim($tmp[2]);
				$fields['sid'] = trim($tmp[3]);
				$fields['rev'] = trim($tmp[4]);
				$fields['msg'] = trim($tmp[5]);
				$fields['class'] = trim($tmp[6]);
				$fields['priority'] = trim($tmp[7]);
				// The regular expression match below looks for the PROTO, SRC and DST fields
				// and returns an array as follows:
				// [1] = PROTO, [2] => SRC:SPORT [3] => DST:DPORT
				if (preg_match('/\{(.*)\}\s(.*)\s->\s(.*)/', $buf, $tmp)) {
					// Get PROTO
					$fields['proto'] = trim($tmp[1]);
					// Get SRC
					$fields['src'] = trim(substr($tmp[2], 0, strrpos($tmp[2], ':')));
					if (is_ipaddrv6($fields['src']))
						$fields['src'] = inet_ntop(inet_pton($fields['src']));
					// Get SPORT
					$fields['sport'] = trim(substr($tmp[2], strrpos($tmp[2], ':') + 1));
					// Get DST
					$fields['dst'] = trim(substr($tmp[3], 0, strrpos($tmp[3], ':')));
					if (is_ipaddrv6($fields['dst']))
						$fields['dst'] = inet_ntop(inet_pton($fields['dst']));
					// Get DPORT
					$fields['dport'] = trim(substr($tmp[3], strrpos($tmp[3], ':') + 1));
				}
				else {
					// If no PROTO nor IP ADDR, then this is a DECODER EVENT
					$decoder_event = TRUE;
					$fields['proto'] = gettext("n/a");
					$fields['sport'] = gettext("n/a");
					$fields['dport'] = gettext("n/a");
				}
				// Create a DateTime object from the event timestamp that
				// we can use to easily manipulate output formats.
				$event_tm = date_create_from_format("m/d/Y-H:i:s.u", $fields['time']);
				// Check the 'CATEGORY' field for the text "(null)" and
				// substitute "Not Assigned".
				if ($fields['class'] == "(null)")
					$fields['class'] = gettext("Not Assigned");
				// PHP date_format issues a bogus warning even though $event_tm really is an object
				// Suppress it with @
				@$fields['time'] = date_format($event_tm, "m/d/Y") . " " . date_format($event_tm, "H:i:s");
				if ($filterlogentries && !suricata_match_filter_field($fields, $filterfieldsarray, $filterlogentries_exact_match)) {
					continue;
				}
				/* Description */
				$alert_descr = $fields['msg'];
				$alert_descr_url = urlencode($fields['msg']);
				/* Priority */
				//$alert_priority = $fields['priority'];
				/* Protocol */
				$alert_proto = $fields['proto'];
				/* Action */
				if (isset($fields['action']) && $a_instance[$instanceid]['blockoffenders'] == 'on' && ($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline' || $a_instance[$instanceid]['block_drops_only'] == 'on')) {
					switch ($fields['action']) {
						case "Drop":
						case "wDrop":
							if (isset($dropsid[$fields['gid']][$fields['sid']])) {
								$alert_action = '<i class="fa fa-thumbs-down icon-pointer text-danger text-center" title="';
								$alert_action .= gettext("Rule action is User-Forced to DROP. Click to force a different action for this rule.");
							}
							elseif ($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline' && isset($rejectsid[$fields['gid']][$fields['sid']])) {
								$alert_action = '<i class="fa fa-hand-stop-o icon-pointer text-warning text-center" title="';
								$alert_action .= gettext("Rule action is User-Forced to REJECT. Click to force a different action for this rule.");
							}
							else {
								$alert_action = '<i class="fa fa-thumbs-down icon-pointer text-danger text-center" title="';
								$alert_action .=  gettext("Rule action is DROP. Click to force a different action for this rule.");
							}
							break;
						default:
							$alert_action = '<i class="fa fa-question-circle icon-pointer text-danger text-center" title="' . gettext("Rule action is unrecognized!. Click to force a different action for this rule.");
					}
					$alert_action .= '" onClick="toggleAction(\'' . $fields['gid'] . '\', \'' . $fields['sid'] . '\');"</i>';
				}
				else {
					if ($a_instance[$instanceid]['blockoffenders'] == 'on' && ($a_instance[$instanceid]['ips_mode'] == 'ips_mode_inline' || $a_instance[$instanceid]['block_drops_only'] == 'on')) {
						$alert_action = '<i class="fa fa-exclamation-triangle icon-pointer text-center" title="' . gettext("Rule action is ALERT.");
						$alert_action .= '" onClick="toggleAction(\'' . $fields['gid'] . '\', \'' . $fields['sid'] . '\');"</i>';
					}
					else {
						$alert_action = '<i class="fa fa-exclamation-triangle text-center" title="' . gettext("Rule action is ALERT.") . '"</i>';
					}
				}

				/* IP SRC Port */
				$alert_src_p = "";
				if (isset($fields['sport']) && strlen($fields['sport']) > 0) {
					$alert_src_p = "<b onclick='preencherSearchBlock(\"" . $fields['sport'] . "\")' style='font-weight:unset!important;'>:" . $fields['sport'] . "</b>";
				}
				/* IP SRC */
				if ($decoder_event == FALSE) {
					$alert_ip_src = $fields['src'];
					/* Add zero-width space as soft-break opportunity after each colon if we have an IPv6 address */
					$alert_ip_src = str_replace(":", ":&#8203;", $alert_ip_src);
					$alert_ip_src = "<b onclick='preencherSearchBlock(\"" . $alert_ip_src . "\")' style='font-weight:unset!important;'>{$alert_ip_src}</b>{$alert_src_p}<br>";
					/* Add Reverse DNS lookup icon */
					$alert_ip_src .= '<i class="fa fa-search" onclick="javascript:resolve_with_ajax(\'' . $fields['src'] . '\');" title="';
					$alert_ip_src .= gettext("Resolve host via reverse DNS lookup") . "\"  alt=\"Icon Reverse Resolve with DNS\" ";
					$alert_ip_src .= " style=\"cursor: pointer;\"></i>";
					/* Add GeoIP check icon */
					if (!is_private_ip($fields['src']) && (substr($fields['src'], 0, 2) != 'fc') &&
						(substr($fields['src'], 0, 2) != 'fd')) {
						$alert_ip_src .= '&nbsp;&nbsp;<i class="fa fa-globe" onclick="javascript:geoip_with_ajax(\'' . $fields['src'] . '\');" title="';
						$alert_ip_src .= gettext("Check host GeoIP data") . "\"  alt=\"Icon Check host GeoIP\" ";
						$alert_ip_src .= " style=\"cursor: pointer;\"></i>";
					}
					elseif (isset($supplist[$fields['gid']][$fields['sid']]['by_src'][$fields['src']])) {
						$alert_ip_src .= '&nbsp;&nbsp;<i class="fa fa-info-circle" ';
						$alert_ip_src .= 'title="' . gettext("This alert track by_src IP is already in the Suppress List") . '"></i>';
					}
				}
				else {
					if (preg_match('/\s\[Raw pkt:(.*)\]/', $buf, $tmp))
						$alert_ip_src = "<div title='[Raw pkt: {$tmp[1]}]'>" . gettext("Decoder Event") . "</div>";
					else
						$alert_ip_src = gettext("Decoder Event");
				}
				/* IP DST Port */
				$alert_dst_p = "";
				if (isset($fields['dport']) && strlen($fields['dport']) > 0) {
					$alert_dst_p = "<b onclick='preencherSearchBlock(\"" . $fields['dport'] . "\")' style='font-weight:unset!important;'>:" . $fields['dport'] . "</b>";
				}
										
				if ($decoder_event == FALSE) {
					$alert_ip_dst = $fields['dst'];
					/* Add zero-width space as soft-break opportunity after each colon if we have an IPv6 address */
					$alert_ip_dst = str_replace(":", ":&#8203;", $alert_ip_dst);
					$alert_ip_dst = "<b onclick='preencherSearchBlock(\"" . $alert_ip_dst . "\")' style='font-weight:unset!important;'>{$alert_ip_dst}</b>{$alert_dst_p}<br>";
					$alert_ip_dst .= "<i class=\"fa fa-search\" onclick=\"javascript:resolve_with_ajax('{$fields['dst']}');\" title=\"";
					$alert_ip_dst .= gettext("Resolve host via reverse DNS lookup") . "\" alt=\"Icon Reverse Resolve with DNS\" ";
					$alert_ip_dst .= " style=\"cursor: pointer;\"></i>";
					/* Add GeoIP check icon */
					if (!is_private_ip($fields['dst']) && (substr($fields['dst'], 0, 2) != 'fc') &&
						(substr($fields['dst'], 0, 2) != 'fd')) {
						$alert_ip_dst .= '&nbsp;&nbsp;<i class="fa fa-globe" onclick="javascript:geoip_with_ajax(\'' . $fields['dst'] . '\');" title="';
						$alert_ip_dst .= gettext("Check host GeoIP data") . "\"  alt=\"Icon Check host GeoIP\" ";
						$alert_ip_dst .= " style=\"cursor: pointer;\"></i>";
					}
				}
				else {
					$alert_ip_dst = gettext("n/a");
				}
				/* SID */
				$alert_sid_str = '<a onclick="javascript:preencherSearchBlock(\'' .
						$fields['gid'] . ':' . $fields['sid'] . '\');" title="' .
						gettext("Show the rule") . '" style="cursor: pointer;" >' .
						$fields['gid'] . ':' . $fields['sid'] . '</a> ' . 
						'<i class="fa fa-info icon-pointer icon-primary" onclick="submitSearchRulesThreads(\'' . $fields['sid'] . '\')" title="Find SID:' . $fields['sid'] . '"></i>';

				/* DESCRIPTION */
				$alert_class = $fields['class'];
				$alert_class_name = implode("_", explode(" ", strtolower($fields['class'])));
				
				$bkColor = "";
				$textColor = "white";
				foreach ($data_classification as $n=>$c){
					if (in_array($fields['class'], $c)) {
						$bkColor = $c['color'];
						break;
					}
				}
				if (strlen($bkColor) == 0) {
					$textColor = "black";
				}
				
				if ($fields['action']) {
					$table_strutuct_alerts .= "<tr class='text-danger' name='{$alert_class_name}'>";
				} else {
					$table_strutuct_alerts .= "<tr style='background-color: " . $bkColor . "; color: " . $textColor . ";' name='{$alert_class_name}'>";
				}
				$table_strutuct_alerts .= "<td>" . date_format($event_tm, "m/d/Y") . "<br/>" . date_format($event_tm, "H:i:s") . "</td>";
				$table_strutuct_alerts .= "<td style='word-wrap:break-word; white-space:normal'>" . $alert_ip_src . "</td>";
				$table_strutuct_alerts .= "<td style='word-wrap:break-word; white-space:normal'>" . $alert_ip_dst . "</td>";
				$table_strutuct_alerts .= "<td style='word-wrap:break-word; white-space:normal' onclick=\"preencherSearchBlock('" . $alert_proto . "')\">" . $alert_proto . "</td>";
				$table_strutuct_alerts .= "<td>" . $alert_sid_str . "</td>";
				$table_strutuct_alerts .= "<td style='word-wrap:break-word; white-space:normal' onclick=\"preencherSearchBlock('" . $alert_class . "')\">" . $alert_class . "</td>";
				$table_strutuct_alerts .= "<td>" . $alert_action . "</td>";
				$table_strutuct_alerts .= "<td style='word-wrap:break-word; white-space:normal' onclick=\"preencherSearchBlock('" . $alert_descr . "')\">" . $alert_descr . "</td>";
				$table_strutuct_alerts .= "</tr>";
				$counter++;
			}
		}
		unset($fields, $buf, $tmp);
		fclose($fd);
		unlink_if_exists("{$g['tmp_path']}/alerts_suricata{$acp_interface}");
	}
	$li_cards_list .= "</ul>";
	$li_cards_list .= "</div>";
	$li_cards_list .= "</div>";

	echo $table_strutuct_alerts;
}

if (isset($_POST['interface']) && isset($_POST['service']) && ($_POST['service'] == "flow_id") && isset($_POST['flow_id_target']) && !empty($_POST['flow_id_target'])) {
	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");

	$render .= "<table class='table table-striped table-hover table-condensed sortable-theme-bootstrap' style='background: transparent !important;' data-sortable>";
	$render .= "<thead>";
	$render .= "<tr class='sortableHeaderRowIdentifier text-nowrap'>";
	$render .= "<th>" . gettext("Source") . "</th>";
	$render .= "<th>" . gettext("Destination") . "</th>";
	$render .= "<th>" . gettext("Host") . "</th>";
	$render .= "<th>" . gettext("Request") . "</th>";
	$render .= "<th>" . gettext("ID") . "</th>";
	$render .= "</tr>";
	$render .= "</thead>";
	$render .= "<tbody>";

	#shell_exec("/usr/local/bin/redis-cli lrange suricata{$acp_interface} -2000 -1 > {$g['varlog_path']}/suricata/suricata_{$acp_interface}/eve.json");
	
	foreach (explode("\n",shell_exec("grep '\"flow_id\":{$_POST['flow_id_target']}' /var/log/suricata/suricata_{$_POST['interface']}/eve.json")) as $line_json) {
		$line_json = json_decode($line_json, true);
		if (isset($line_json['alert']['signature_id'])) {
			if ($line_json['alert']['action'] == "blocked") {
				$colorBk = "#e71837";
				$colorFt = "white";
			}
			if ($line_json['alert']['action'] == "allowed") {
				$colorBk = "#2baf2b";
				$colorFt = "white";
			}
			$render .= "<tr style='background-color: {$colorBk}; color: {$colorFt};'>";
			$render .= "<td>" . $line_json['src_ip'] . ":" . $line_json['src_port'] . "</td>";
			$render .= "<td>" . $line_json['dest_ip'] . ":" . $line_json['dest_port'] . "</td>";
			if (empty($line_json['http']['hostname'])) {
				$render .= "<td> - </td>";
			} else {
				$render .= "<td>" . $line_json['http']['hostname'] . "</td>";
			}
			$render .= "<td style='text-transform: uppercase;'>";
			if (isset($line_json['proto']) && isset($line_json['app_proto'])) {
				$render .= $line_json['proto'] . "/" . $line_json['app_proto'];
			} elseif (isset($line_json['proto'])) {
				$render .= $line_json['proto'];
			} elseif (isset($line_json['app_proto'])) {
				$render .= $line_json['app_proto'];
			} else {
				$render .= "-";
			}
			$render .= "</td>";
			$render .= "<td>" . $line_json['alert']['signature_id'] . "</td>";
			$render .= "</tr>";
		}
		$file_ip_dst = $fields['dest_ip'];
	}
	$render .= "</tbody>";
	$render .= "</table>";
	$render .= "<br>";
	$render .= "<p>Requests made when getting the file</p>";

	echo $render;
}