<?php
 /* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Desenvolvimento <desenvolvimento@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */
require_once('wf_quarantine.inc');
require_once('nf_defines.inc');
require_once('nf_util.inc');
require_once('auth.inc');
require_once("cg2_util.inc");

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instances = &$config['system']['webfilter']['instance']['config'];

if (!isset($config['system']['webfilter']['quarantine']['config'][0])) {
	$config['system']['webfilter']['quarantine']['config'][0] = array();
}
$conf_q = &$config['system']['webfilter']['quarantine']['config'][0];
$instances_enabled = isset($conf_q['instances']) ? explode(',', $conf_q['instances']) : array();

if (isset($_GET)) {
	$instance_id = isset($_GET['instance_id']) ? $_GET['instance_id'] : "";
	$url = isset($_GET['blocked_url']) ? $_GET['blocked_url'] : "";
	$match = isset($_GET['match']) ? $_GET['match'] : "";
	$user = isset($_GET['user']) ? $_GET['user'] : "";
	$ipaddress = isset($_GET['ip']) ? $_GET['ip'] : "";
}

$g['theme'] = "BluePex-4.0";
$authmode = $config['system']['webgui']['authmode'];
$msg = "";

if (isset($_POST['justify']) && !empty($_POST['reason'])) {
	$data = array();
	$data['username'] = $user;
	$data['ipaddress'] = $ipaddress;
	$data['reason'] = $_POST['reason'];
	$data['url'] = $_POST['url'];
	$data['status'] = 1;
	$data['proxy_instance_id'] = $instance_id;
	$data['proxy_instance_name'] = isset($wf_instances[$instance_id]['server']['name']) ? $wf_instances[$instance_id]['server']['name'] : "";
	if (!empty($data['url']) && !empty($data['reason'])) {
		$icon = "error";
		$allow_url_auto = false;
		if ($conf_q['type'] == "enabled_auto") {
			$webfilter_rules = get_element_config("nf_content_rules");
			foreach ($webfilter_rules['item'] as $rule) {
				if (($rule['instance_id'] != $instance_id) || !preg_match('/[0-9]:quarantine[,\|]/', $rule['custom_lists'])) {
					continue;
				}
				if ($rule['type'] == "default") {
					$allow_url_auto = true;
					break;
				}
				if ($rule['type'] == "groups" && !empty($rule['groups'])) {
					$idx = ($authmode == "Local Database") ? "gid" : "objectguid";
					$user_entry = getUserEntry($user);
					foreach (explode(',', $rule['groups']) as $group_idx) {
						$group_entry = get_user_group_entry_by("group", $idx, $group_idx);
						foreach ($group_entry['member'] as $member) {
							if ($member == $user_entry['uid'] || $member == $user_entry['objectguid']) {
								$allow_url_auto = true;
								break;
							}
						}
					}
				}
				if ($rule['type'] == "users" && !empty($rule['users'])) {
					$idx = ($authmode == "Local Database") ? "uid" : "objectguid";
					$n_users = explode(",", $rule['users']);
					foreach ($n_users as $n_user) {
						$user_entry = get_user_group_entry_by("user", $idx, $n_user);
						if (!empty($user_entry)) {
							$allow_url_auto = true;
							break;
						}
					}
				}
				if ($rule['type'] == "ip" && !empty($rule['ip'])) {
					foreach (explode(',', $rule['ip']) as $ip) {
						if (trim($ip) == $ipaddress) {
							$allow_url_auto = true;
							break;
						}
					}
				}
				if ($rule['type'] == "range" && !empty($rule['range'])) {
					list($range_start, $range_end) = explode("-", $rule['range']);
					if (ip2long($ipaddress) <= ip2long($range_end) && ip2long($ipaddress) >= ip2long($range_start)) {
						$allow_url_auto = true;
						break;
					}
				}
				if ($rule['type'] == "subnet" && $rule['subnet'] != "" && ip_in_subnet($ipaddress, $rule['subnet'])) {
					$allow_url_auto = true;
					break;
				}
			}
		}
		if ($allow_url_auto) {
			$url = preg_match("/^(http:\/\/|https:\/\/|ftp:\/\/).*/", $data['url']) ? parse_url($data['url'], PHP_URL_HOST) : $data['url'];
			if (set_url_to_customlist($instance_id, "quarantine", $url)) {
				$data['status'] = 0;
				if (insert_justification($data)) {
					apply_resync(dgettext('BluePexWebFilter','WebFilter Quarantine: Justification inserted.'));
					header("Location: {$data['url']}");
					exit;
				} else {
					$msg = dgettext('BluePexWebFilter','Could not to send the justification, please contact the Administrator!');
				}
			} else {
				$msg = sprintf(dgettext('BluePexWebFilter','Could not to allow the access to %s.'), $data['url']);
				syslog(LOG_NOTICE, sprintf(dgettext("BluePexWebFilter", "Could not to allow the access to %s! Verify if exists rules and custom list for the user '%s'."), $data['url'], $data['username']));
			}
		} else {
			if (insert_justification($data)) {
				$msg = dgettext('BluePexWebFilter','Justification sent successfully! Please wait the permission of the Administrator to access this content.');
				$icon = "warning";
			} else {
				$msg = dgettext('BluePexWebFilter','Could not to send the justification! Please contact the Administrator!');
			}
		}
	}
}

$categories = array();
if (!empty($match)) {
	$all_cats = NetfilterGetAllContentCategories();
	$cats = explode(',', $match);
	foreach ($cats as $cat)
		$categories[] = $all_cats[$cat];
	sort($categories);
}
$categories = implode(',', $categories);
?>
<!DOCTYPE html>
<html>
<head>
	<title><?=dgettext('BluePexWebFilter','Blocked Content!')?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="/vendor/bootstrap/css/bootstrap.min.css" rel="stylesheet">
	<script src="/vendor/jquery/jquery-1.12.0.min.js"></script>
	<script src="/vendor/bootstrap/js/bootstrap.min.js"></script>
	<style>
		html { height: 100%; box-sizing: border-box; }
		body { position: relative; margin: 0; min-height: 100%; }
		#wrap { min-height:100%; position:relative; padding-bottom:100px; }
		#content { margin-top:-300px; margin-left:50px; width: 450px; font-size:16px; }
		#content h1 { color:#007dc5; }
		#content p { text-align: justify; text-justify: inter-word; }
		#content textarea { border:2px solid #333; border-radius:0 }
		#content button { border-radius:0; width:100% }
		#img-cloud { background: url("/webfilter/themes/<?=$g['theme']?>/img/cloud-blocked.png"); width:100%; height:515px; background-size: 100%; background-repeat: no-repeat; }
		#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
		@media only screen and (max-width : 768px) {
			body { background: #fff; }
			#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
			#img-cloud { height:240px }
		}
		@media only screen and (max-width : 480px) {
			#img-cloud { height:150px }
		}
		@media only screen and (max-width : 320px) {
			#img-cloud { height:100px }   
		}
	</style>
</head>
<body>
<div id="wrap" class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="row">
				<div id="img-cloud"></div>
			</div>
			<div id="content">
				<h1><?=dgettext('BluePexWebFilter','Blocked Content!')?></h1>
				<p><?=dgettext('BluePexWebFilter','Sorry, I have been warned that the website is not in agreement with your access policy of company security,for this reason I need block it.')?></p>
				<p><?=dgettext('BluePexWebFilter','In case I have precipitated me, I can release it as soon as you inform it to my manager.')?></p>
				<table>
				<tbody>
					<tr>
						<td><b><?=dgettext('BluePexWebFilter','BLOCKED URL')?>: </b></td>
						<td><?=$url?></td>
					</tr>
					<tr>
						<td><b><?=dgettext('BluePexWebFilter','CATEGORY')?>: </b></td>
						<td><?=$categories?></td>
					</tr>
					<tr>
						<td><b><?=dgettext('BluePexWebFilter','REASON')?>: </b></td>
						<td><?=NetFilterGetReasonMessage($_GET['reason'])?></td>
					</tr>
				</tbody>
				</table>
				<?php if ($conf_q['enable'] == "yes" && in_array($instance_id, $instances_enabled)) : ?>
				<br />
				<form name="FormJustification" action="" method="POST">
					<div class="form-group">
						<textarea class="form-control" name="reason" placeholder="<?=dgettext('BluePexWebFilter','Enter the justification to allow access to the site: ')?><?=$url?>."></textarea>
					</div>
					<div class="form-group">
						<input type="hidden" name="url" value="<?=$url?>" />
						<button type="submit" name="justify" class="btn btn-primary"><?=dgettext('BluePexWebFilter', 'justify');?></button>
					</div>
				</form>
				<?php endif; ?>
				<?php if (!empty($msg)) : ?>
				<div class="alert alert-<?=$icon;?> text-center">
					<img src="/webfilter/themes/<?=$g['theme']?>/img/icon_<?=$icon?>.png" />
					<h3><?=$msg?></h3>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</div>
<div id="footer">
	<img src="/webfilter/themes/<?=$g['theme']?>/img/logotipo-tecnology.png" />
</div>
<script type="text/javascript">
$(window).load(function() {
	if ($('.alert').length > 0) {
		$("html, body").animate({ scrollTop: $('.alert').offset().top }, 1000);
	}
});
</script>
</body>
</html> 
