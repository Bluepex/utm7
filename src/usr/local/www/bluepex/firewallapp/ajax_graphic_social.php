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
//$suricata_uuid = $a_instance[$instanceid]['uuid'];
//$if_real = get_real_interface($a_instance[$instanceid]['interface']);

global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

if (!is_array($config['installedpackages']['suricata']['rule']))
$config['installedpackages']['suricata']['rule'] = array();

$a_rule = &$config['installedpackages']['suricata']['rule'];

//Add decision for determinate interface select
if (isset($_POST['interface_alvo'])) {
	$rules_id_now = $_POST['interface_alvo'];
	$if_real = get_real_interface($a_rule[$rules_id_now]['interface']);
	$suricata_uuid = $a_rule[$rules_id_now]['uuid'];

	if ($if_real != 'wan') {
		$suricata_uuid = $suricata_uuid;
		$if_real = $if_real;
		/* make sure alert file exists */
		if (file_exists("{$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log")) {
			$rangeStart = str_replace("/", "\/", date("m/d/Y-H:i", strtotime("now")-60));
			$rangeLimite = str_replace("/", "\/", date("m/d/Y-H:i", strtotime("now")));
			if (intval(trim(shell_exec("tail -n10000 {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log | sed -n '/$rangeStart/,/$rangeLimite/p' | wc -l"))) > 0) {
				exec("tail -n10000 {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log | sed -n '/$rangeStart/,/$rangeLimite/p' > {$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");
			} else {
				exec("tail -5000 -r {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log > {$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");
			}
			$divisor_logs = max(count(explode("\n", file_get_contents("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}"))) - 1, 1);
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
				$sum_owner = 0;
				while (($buf = @fgets($fd)) !== FALSE) {
					$fields = array();
					$tmp = array();
					$decoder_event = FALSE;
					// The regular expression match below returns an array as follows:
					// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
					preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
					$fields['gid'] = trim($tmp[2]);
					
					$alert_class = $fields['gid'];
					$social_cat[] = $alert_class;
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
					/*
					if (in_array(intval($fields['gid']), $social_cat_sids)) {
						$social_cat[] = $alert_class;
						//$i++;
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
							$sum_owner++;
						}
					}
					*/
				}
				unset($fields, $buf, $tmp);
				@fclose($fd);
				unlink_if_exists("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");
				$data = is_array($social_cat) ? array_count_values($social_cat) : [];
				$response = array(
					'redesocial' => 0,
					'portais' => 0,
					'stream' => 0,
					'musica' => 0,
					'remoto' => 0,
					'outros' => 0
				);
				/*echo "<pre>";
				print_r($sum_redesocial);
				print_r("***********");
				print_r($data);
				echo "</pre>";die;*/
				arsort($data);
				foreach ($data as $key => $value) {
					$response[$key] = round(($value / $divisor_logs)*100,2);
					$response["access_{$key}"] = $value;
					/*
					//print_r($key);die;
					if ( ($social_cat[0] == 10) || ($social_cat[0] == 146) || ($social_cat[0] == 16) || ($social_cat[0] == 204) || ($social_cat[0] == 4) || ($social_cat[0] == 7) || ($social_cat[0] == 8) ) {
						$response[$key] = floor(($value * 100) / $sum_redesocial);
					} else if ( ($social_cat[0] == 9) || ($social_cat[0] == 20) || ($social_cat[0] == 21) || ($social_cat[0] == 130) || ($social_cat[0] == 22) || ($social_cat[0] == 5) ) {
						$response[$key] = floor(($value * 100) / $sum_portais);
					} else if ( ($social_cat[0] == 11) || ($social_cat[0] == 23) || ($social_cat[0] == 32) || ($social_cat[0] == 201) || ($social_cat[0] == 24) ) {
						$response[$key] = floor(($value * 100) / $sum_stream);
					} else if ( ($social_cat[0] == 13) || ($social_cat[0] == 205) || ($social_cat[0] == 25) ) {
						$response[$key] = floor(($value * 100) / $sum_music);
					} else if ( ($social_cat[0] == 65) || ($social_cat[0] == 66) ) {
						$response[$key] = floor(($value * 100) / $sum_remote);
					} else if ($social_cat[0] == 1){
						$response[1] = floor(($value * 100) / $sum_owner);
					}
					*/
					if ( ($key == 10) || ($key == 146) || ($key == 16) || ($key == 204) || ($key == 4) || ($key == 7) || ($key == 8) ) {
						$response['redesocial'] += $value;
					} else if ( ($key == 9) || ($key == 20) || ($key == 21) || ($key == 130) || ($key == 22) || ($key == 5) ) {
						$response['portais'] += $value;
					} else if ( ($key == 11) || ($key == 23) || ($key == 32) || ($key == 201) || ($key == 24) ) {
						$response['stream'] += $value;
					} else if ( ($key == 13) || ($key == 205) || ($key == 25) ) {
						$response['musica'] += $value;
					} else if ( ($key == 65) || ($key == 66) ) {
						$response['remoto'] += $value;
					} else if ($key == 1){
						$response['outros'] += $value;
					}
				}
				$response['redesocial'] = round(($response['redesocial'] / $divisor_logs)*100,2);
				$response['portais'] = round(($response['portais'] / $divisor_logs)*100,2);
				$response['stream'] = round(($response['stream'] / $divisor_logs)*100,2);
				$response['musica'] = round(($response['musica'] / $divisor_logs)*100,2);
				$response['remoto'] = round(($response['remoto'] / $divisor_logs)*100,2);
				$response['outros'] = round(($response['outros'] / $divisor_logs)*100,2);
				echo json_encode($response);
			}
		}
	}

} else {
	#for ($id = 0; $id <= count($a_rule)-1; $id++) {
		#$if_real = get_real_interface($a_rule[$id]['interface']);
		#$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];

			if ($suricatacfg['interface'] != 'wan') {
				$suricata_uuid = $uuid;
				$if_real = $if;
				/* make sure alert file exists */
				if (file_exists("{$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log")) {
					$rangeStart = str_replace("/", "\/", date("m/d/Y-H:i", strtotime("now")-60));
					$rangeLimite = str_replace("/", "\/", date("m/d/Y-H:i", strtotime("now")));
					if (intval(trim(shell_exec("tail -n10000 {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log | sed -n '/$rangeStart/,/$rangeLimite/p' | wc -l"))) > 0) {
						exec("tail -n10000 {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log | sed -n '/$rangeStart/,/$rangeLimite/p' > {$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");		
					} else {
						exec("tail -n5000 -r {$g['varlog_path']}/suricata/suricata_{$if_real}{$suricata_uuid}/alerts.log > {$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");
					}
					$divisor_logs = count(explode("\n", file_get_contents("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}")))-1;
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
						$sum_owner = 0;
						while (($buf = @fgets($fd)) !== FALSE) {
							$fields = array();
							$tmp = array();
							$decoder_event = FALSE;
							// The regular expression match below returns an array as follows:
							// [2] => GID, [3] => SID, [4] => REV, [5] => MSG, [6] => CLASSIFICATION, [7] = PRIORITY
							preg_match('/\[\*{2}\]\s\[((\d+):(\d+):(\d+))\]\s(.*)\[\*{2}\]\s\[Classification:\s(.*)\]\s\[Priority:\s(\d+)\]\s/', $buf, $tmp);
							$fields['gid'] = trim($tmp[2]);
							
							$alert_class = $fields['gid'];
							$social_cat[] = $alert_class;
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
							/*
							if (in_array(intval($fields['gid']), $social_cat_sids)) {
								$social_cat[] = $alert_class;
								//$i++;
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
									$sum_owner++;
								}
							}
							*/
						}
						unset($fields, $buf, $tmp);
						@fclose($fd);
						unlink_if_exists("{$g['tmp_path']}/graphic_social_suricata{$suricata_uuid}");
						$data = array_count_values($social_cat);
						$response = array(
							'redesocial' => 0,
							'portais' => 0,
							'stream' => 0,
							'musica' => 0,
							'remoto' => 0,
							'outros' => 0
						);
						/*echo "<pre>";
						print_r($sum_redesocial);
						print_r("***********");
						print_r($data);
						echo "</pre>";die;*/
						arsort($data);
						foreach ($data as $key => $value) {
							$response[$key] = round(($value / $divisor_logs)*100,2);
							$response["access_{$key}"] = $value;
							/*
							//print_r($key);die;
							if ( ($social_cat[0] == 10) || ($social_cat[0] == 146) || ($social_cat[0] == 16) || ($social_cat[0] == 204) || ($social_cat[0] == 4) || ($social_cat[0] == 7) || ($social_cat[0] == 8) ) {
								$response[$key] = floor(($value * 100) / $sum_redesocial);
							} else if ( ($social_cat[0] == 9) || ($social_cat[0] == 20) || ($social_cat[0] == 21) || ($social_cat[0] == 130) || ($social_cat[0] == 22) || ($social_cat[0] == 5) ) {
								$response[$key] = floor(($value * 100) / $sum_portais);
							} else if ( ($social_cat[0] == 11) || ($social_cat[0] == 23) || ($social_cat[0] == 32) || ($social_cat[0] == 201) || ($social_cat[0] == 24) ) {
								$response[$key] = floor(($value * 100) / $sum_stream);
							} else if ( ($social_cat[0] == 13) || ($social_cat[0] == 205) || ($social_cat[0] == 25) ) {
								$response[$key] = floor(($value * 100) / $sum_music);
							} else if ( ($social_cat[0] == 65) || ($social_cat[0] == 66) ) {
								$response[$key] = floor(($value * 100) / $sum_remote);
							} else if ($social_cat[0] == 1){
								$response[1] = floor(($value * 100) / $sum_owner);
							}
							*/
							if ( ($key == 10) || ($key == 146) || ($key == 16) || ($key == 204) || ($key == 4) || ($key == 7) || ($key == 8) ) {
								$response['redesocial'] += $value;
							} else if ( ($key == 9) || ($key == 20) || ($key == 21) || ($key == 130) || ($key == 22) || ($key == 5) ) {
								$response['portais'] += $value;
							} else if ( ($key == 11) || ($key == 23) || ($key == 32) || ($key == 201) || ($key == 24) ) {
								$response['stream'] += $value;
							} else if ( ($key == 13) || ($key == 205) || ($key == 25) ) {
								$response['musica'] += $value;
							} else if ( ($key == 65) || ($key == 66) ) {
								$response['remoto'] += $value;
							} else if ($key == 1){
								$response['outros'] += $value;
							}
						}
						$response['redesocial'] = round(($response['redesocial'] / $divisor_logs)*100,2);
						$response['portais'] = round(($response['portais'] / $divisor_logs)*100,2);
						$response['stream'] = round(($response['stream'] / $divisor_logs)*100,2);
						$response['musica'] = round(($response['musica'] / $divisor_logs)*100,2);
						$response['remoto'] = round(($response['remoto'] / $divisor_logs)*100,2);
						$response['outros'] = round(($response['outros'] / $divisor_logs)*100,2);
						echo json_encode($response);
						break;
					}
				}
			}
		}
	#}
}
	
?>
