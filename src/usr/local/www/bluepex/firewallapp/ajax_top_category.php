<?php

require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $config;

if (isset($_POST['instance']) && is_numericint($_POST['instance']))
	$instanceid = $_POST['instance'];
// This is for the auto-refresh so we can stay on the same interface
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
	exec("tail -1000 -r {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log > {$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");
	if (file_exists("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}")) {

		$fd = @fopen("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}", "r");
		$buf = "";

		$virus_cat = array();
		$i = 0;
		$sum_redesocial = 0;
		$sum_portais = 0;
		$sum_stream = 0;
		$sum_music = 0;
		$sum_remote = 0;
		$sun_owner = 0;
		while (($buf = @fgets($fd)) !== FALSE) {
			$fields = array();
			$tmp = array();
			$decoder_event = FALSE;

			// The regular expression match below returns an array as follows:
			// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
			preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);

			$fields['gid'] = trim($tmp[2]);
			

			$alert_class = $fields['gid'];
			
			$social_cat_sids = array(
				10,//facebook
				146,//tiktok
				16,//twitter
				204,//instagram
				4,//whatsapp
				7,//telegram
				8,//linkedin
				9,//microsoft
				20,//google
				21,//amazon
				130,//globo
				22,//uol
				5,//yahoo
				11,//youtube
				23,//primevideo
				32,//netflix
				201,//disney
				24,//twitch
				13,//spotify
				205,//amazonmusic
				25,//deezer
				65,//teamviewer
				66,//anydesk
				70,//bittorrent
				71,//utorrent
				26,//pornhub
				27,//xvideos
				1,//outros

			);

			//echo "<pre>";
			//print_r($fields);
			//echo "</pre>";die;

			if (in_array(intval($fields['gid']), $social_cat_sids)) {
				$social_cat[] = $alert_class;
				$i++;

				//redes_sociais
				if ( ($fields['gid'] == 10) || ($fields['gid'] == 146) || ($fields['gid'] == 16) || ($fields['gid'] == 204) || ($fields['gid'] == 4) || ($fields['gid']== 7) || ($fields['gid'] == 8) ) {
					$sum_redesocial++;
				}
				//portais
				if ( ($fields['gid'] == 9) || ($fields['gid'] == 20) || ($fields['gid'] == 21) || ($fields['gid'] == 130) || ($fields['gid'] == 22) || ($fields['gid'] == 5) ) {
					$sum_portais++;
				}
				//stream
				if ( ($fields['gid'] == 11) || ($fields['gid'] == 23) || ($fields['gid'] == 32) || ($fields['gid'] == 201) || ($fields['gid'] == 24) ) {
					$sum_stream++;
				}
				//music
				if ( ($fields['gid'] == 13) || ($fields['gid'] == 205) || ($fields['gid'] == 25) ) {
					$sum_music++;
				}
				//remote
				if ( ($fields['gid'] == 65) || ($fields['gid'] == 66) ) {
					$sum_remote++;
				}
				//owner
				if ($fields['gid'] == 1) {
					$sun_owner++;
				}

			}
		}

		unset($fields, $buf, $tmp);
		@fclose($fd);
		unlink_if_exists("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");

		$data = array_count_values($social_cat);

		$response = array(
			 'redesocial' => '',
  			 'portais' => '',
  			 'stream' => '',
  			 'musica' => '',
  			 'remoto' => ''
		);

		/*echo "<pre>";
		print_r($sum_redesocial);
		print_r("***********");
		print_r($data);
		echo "</pre>";die;*/

		arsort($data);


		$response['redesocial'] = floor(($sum_redesocial * 100) / $i);
		$response['portais'] = floor(($sum_portais * 100) / $i);
		$response['stream'] = floor(($sum_stream * 100) / $i);
		$response['musica'] = floor(($sum_music * 100) / $i);
		$response['remoto'] = floor(($sum_remote * 100) / $i);

		echo json_encode($response);

	}
}
?>
