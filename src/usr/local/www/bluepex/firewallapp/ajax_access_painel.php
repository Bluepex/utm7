<?php

require_once("/usr/local/pkg/suricata/suricata.inc");

global $config;

if (isset($_POST['interface_alvo']) && $_POST['interface_alvo'] > -1) {
	$rules_id_now = intval($_POST['interface_alvo']);
	file_get_contents("/tmp/teste1", $rules_id_now);
	if (!is_array($config['installedpackages']['suricata']['rule']))
		$config['installedpackages']['suricata']['rule'] = array();

	if (isset($config['installedpackages']['suricata']['rule'][$rules_id_now])) {
		$interface_target = $config['installedpackages']['suricata']['rule'][$rules_id_now];
		$if = get_real_interface($interface_target['interface']);
		$uuid = $interface_target['uuid'];

		if (file_exists("/var/log/suricata/suricata_{$if}{$uuid}/fapp_data.json")) {
			$json_data_fapp = json_decode(file_get_contents("/var/log/suricata/suricata_{$if}{$uuid}/fapp_data.json"));
			$access[] = $json_data_fapp->{'access_all'};
			$access[] = $json_data_fapp->{'access_drop'};
			$access[] = $json_data_fapp->{'access_alerts'}; 
			echo json_encode($access);
		} else {
			echo json_encode(['0','0','0']);
		}

	} else {
		echo json_encode(['0','0','0']);
	}
}

if ($_POST['list_top5']) {
	if (file_exists('/usr/local/www/list_top5') && strlen(file_get_contents('/usr/local/www/list_top5')) > 0) {
		echo json_encode(array_filter(explode(",", trim(file_get_contents("/usr/local/www/list_top5")))));
	} else {
		echo json_encode(['','','','','']);
	}
}

if ($_POST['list_top5_img']) {
	if (file_exists('/usr/local/www/list_top5') && strlen(file_get_contents('/usr/local/www/list_top5')) > 0) {
		$returnIMG = "";
		foreach(array_filter(explode(",", trim(file_get_contents("/usr/local/www/list_top5")))) as $imgNow) {
			$imgNow = strtolower(trim($imgNow));
			if (file_exists("/usr/local/www/firewallapp/images/icon-{$imgNow}.png")) {
				$returnIMG .= "images/icon-{$imgNow}.png,";
			} elseif (file_exists("/usr/local/www/firewallapp/images/icon-www.{$imgNow}.png")) {
				$returnIMG .= "images/icon-{$imgNow}.png,";
			} else {
				$returnIMG .= "images/icon-www.png,";
			}
		}
		echo json_encode(array_filter(explode(",", $returnIMG)));
	} else {
		echo json_encode(['','','','','']);
	}
}

if ($_POST['list_top_val_top5']) {
	if (file_exists('/usr/local/www/list_top_val_top5') && strlen(file_get_contents('/usr/local/www/list_top_val_top5')) > 0) {
		echo json_encode(array_filter(explode(",", trim(file_get_contents("/usr/local/www/list_top_val_top5")))));
	} else {
		echo json_encode(['','','','','']);
	}
}

?>
