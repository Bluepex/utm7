<?php

require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $config;

if (isset($_POST['instance']) && is_numericint($_POST['instance']))
        $instanceid = $_POST['instance'];
// This is for the auto-refresh so we can  stay on the same interface
elseif (isset($_GET['instance']) && is_numericint($_GET['instance']))
        $instanceid = $_GET['instance'];

if (is_null($instanceid))
        $instanceid = 0;

if (!is_array($config['installedpackages']['suricata']['rule']))
        $config['installedpackages']['suricata']['rule'] = array();

$a_instance = &$config['installedpackages']['suricata']['rule'];
$suricata_uuid = $a_instance[$instanceid]['uuid'];
$if_real = get_real_interface($a_instance[$instanceid]['interface']);

/* make sure alert file exists */
if (file_exists("{$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log")) {
	exec("tail -100 -r {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log > {$g['tmp_path']}/graphic_virus_suricata{$suricata_uuid}");
	if (file_exists("{$g['tmp_path']}/graphic_virus_suricata{$suricata_uuid}")) {

		$fd = @fopen("{$g['tmp_path']}/graphic_virus_suricata{$suricata_uuid}", "r");
		$buf = "";

		$virus_cat = array();

		while (($buf = @fgets($fd)) !== FALSE) {			

			$fields = array();
			$tmp = array();
			$decoder_event = FALSE;

			// The regular expression match below returns an array as follows:
			// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
			preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
			$fields['sid'] = trim($tmp[3]);
			$fields['class'] = trim($tmp[6]);

			if ($fields['sid'] > 9000000)
				continue;

			/* DESCRIPTION */
			$alert_class = $fields['class'];

			//if (!in_array($alert_class, $virus_cat)) {
				//$virus_cat[$alert_class] = array();
			//}

			$virus_cat[] = $alert_class;	
		}
		
		unset($fields, $buf, $tmp);
		@fclose($fd);
		unlink_if_exists("{$g['tmp_path']}/alerts_suricata{$suricata_uuid}");
		
		$data = array_count_values($virus_cat);
		
		$response = array();

		foreach ($data as $key => $value) {
			$response['virus'][] = substr($key, 0, 20);			
			$response['percent'][] = ($value * 100) / 30;
		} 

		echo json_encode($response);

	}
}
?>
