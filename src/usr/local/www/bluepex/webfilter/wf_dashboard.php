<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */
$allowautocomplete = true;
$pgtitle = array(gettext("Diagnostics"), gettext("Ping"));
require_once("guiconfig.inc");
require_once("service-utils.inc");

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 5);
$do_ping = false;
$host = '';
$count = DEFAULT_COUNT;

define("WEBFILTER_LOG_FILE", "/var/log/webfilter.log");
define("NETFILTER_FILE", "/var/tmp/wflogs");

$input_errors = array();
$savemsg = "";
$services_status = get_status_services_by_wf_monitor();
$webfilter_logs = get_log_webfilter();

$input_errors = array();
$savemsg = "";
$categories = array(
	"1" => dgettext('BluePexWebFilter','Pornography'),
	"2" => dgettext('BluePexWebFilter','Music'),
	"3" => dgettext('BluePexWebFilter','Video'),
	"4" => dgettext('BluePexWebFilter','Books'),
	"5" => dgettext('BluePexWebFilter','Job Search'),
	"6" => dgettext('BluePexWebFilter','Sports'),
	"7" => dgettext('BluePexWebFilter','Games'),
	"8" => dgettext('BluePexWebFilter','Entertainment'),
	"9" => dgettext('BluePexWebFilter','E-learning'),
	"10" => dgettext('BluePexWebFilter','Chat'),
	"11" => dgettext('BluePexWebFilter','Newspapers'),
	"12" => dgettext('BluePexWebFilter','Magazines'),
	"13" => dgettext('BluePexWebFilter','Animations'),
	"14" => dgettext('BluePexWebFilter','Tutorials'),
	"15" => dgettext('BluePexWebFilter','Classfields'),
	"16" => dgettext('BluePexWebFilter','Dating'),
	"17" => dgettext('BluePexWebFilter','Curiosities'),
	"18" => dgettext('BluePexWebFilter','Shopping'),
	"19" => dgettext('BluePexWebFilter','News'),
	"20" => dgettext('BluePexWebFilter','Virtual cards'),
	"21" => dgettext('BluePexWebFilter','Esoterism'),
	"22" => dgettext('BluePexWebFilter','Webmail'),
	"25" => dgettext('BluePexWebFilter','Comics'),
	"26" => dgettext('BluePexWebFilter','TV'),
	"27" => dgettext('BluePexWebFilter','Recipes'),
	"28" => dgettext('BluePexWebFilter','Weapons'),
	"29" => dgettext('BluePexWebFilter','Auctions'),
	"30" => dgettext('BluePexWebFilter','Travelling'),
	"31" => dgettext('BluePexWebFilter','Pets'),
	"32" => dgettext('BluePexWebFilter','Hacking'),
	"33" => dgettext('BluePexWebFilter','Movies'),
	"34" => dgettext('BluePexWebFilter','Photography'),
	"35" => dgettext('BluePexWebFilter','Airlines'),
	"36" => dgettext('BluePexWebFilter','Arts'),
	"37" => dgettext('BluePexWebFilter','Cars'),
	"38" => dgettext('BluePexWebFilter','Banking'),
	"39" => dgettext('BluePexWebFilter','Blogs'),
	"40" => dgettext('BluePexWebFilter','Drugs'),
	"41" => dgettext('BluePexWebFilter','Social networks'),
	"42" => dgettext('BluePexWebFilter','Health'),
	"43" => dgettext('BluePexWebFilter','Sects and cults'),
	"44" => dgettext('BluePexWebFilter','dvertisement banners'),
	"45" => dgettext('BluePexWebFilter','Proxy tunnels'),
	"46" => dgettext('BluePexWebFilter','Search engines'),
	"47" => dgettext('BluePexWebFilter','Violence'),
	"48" => dgettext('BluePexWebFilter','Web portals'),
	"49" => dgettext('BluePexWebFilter','Nazism and hate crimes'),
	"50" => dgettext('BluePexWebFilter','Download sites'),
	"99" => dgettext('BluePexWebFilter','Not Categorized')
);
$status = 1;
if ($_REQUEST['status'] == 'stop') {
	$status = 0;
}

// AJAX Request
if (isset($_GET['webtail'])) {
	if (!is_pid_running("{$g['varrun_path']}/wfrotated.pid")) {
		echo "<h3 class='text-center'>" . dgettext("BluePexWebFilter","WF Realtime: Service WF Rotate not is running!") . "</h3>";
		exit;
	}

	$maxlines = 200;
	$filter   = isset($_GET['filter']) ? $_GET['filter'] : "";
	$enable_color = true;

    if ($status == 1) {

    	exec("/usr/bin/pgrep -f realtime_logs", $out, $err);
		if (!$out)
		    mwexec_bg("/usr/local/bin/php /usr/local/bin/realtime_logs.php");
    	
		//exec("cat /var/log/squid.log | tail -$maxlines > /var/tmp/wflogs");
		exec("tail -n$maxlines /var/log/wflogs.log > /var/tmp/wflogs");
		exec("tail -r /var/tmp/wflogs > /var/tmp/wflogs.tmp && mv /var/tmp/wflogs.tmp /var/tmp/wflogs");
	}


	if (!empty($filter)) {
		$cmd = "tail -n {$maxlines} " . NETFILTER_FILE . " | /usr/bin/grep -i '" . $filter . "' | sed 's/[[:space:]]/ / g'";
	} else {
		$cmd = "tail -n {$maxlines} " . NETFILTER_FILE . " | sed 's/[[:space:]]/ / g'";
	}

	$gc = exec($cmd, $outnet, $err);
	if ($err != 0) {
		echo "<h3 class='text-center'>" . dgettext('BluePexWebFilter','Error to process netfilter logs!') . "</h3>";
		exit;
	} elseif (empty($outnet)) {
		echo "<h3 class='text-center'>" . dgettext('BluePexWebFilter', 'No access data to display!') . "</h3>";
		exit;
	}

	if ($status == 1) {

		$html = "<table class='table'>";
		$html .= "<thead>";
		$html .= "<tr>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','DateTime')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','URL')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Status')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Categories')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','IP Address')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Username')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Groupname')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','Diagnostic')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','PING')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','PORTA 80')) . "</th>";
		$html .= "<th>" . htmlentities(dgettext('BluePexWebFilter','PORTA 443')) . "</th>";
		$html .= "</tr>";
		$html .= "</thead>";
		$html .= "<tbody id='table_webfilter'>";
		foreach ($outnet as $out) {
			$lines = explode("\n", $out);
			foreach($lines as $line) {
				if(!empty($line)) {
					if (preg_match("/(http|tls|alerts).log|(last message repeated)/", $line, $matches))
						continue;

					if (!is_null($line[4])) {
						$line_raw = explode(" ", str_replace("  ", " ", $line));
						$line = implode(" ", array_splice($line_raw, 5));
						$lines_array = explode(' ', $line);
						$color = "";
						$url_new = str_replace('https://','',$lines_array[1]);
						$url_new = str_replace('http://','',$url_new);
						$url_new = str_replace('/','',$url_new);
						if ($enable_color)
							$color = ($lines_array[2] >= 1000) ? "bgcolor='#FF6347'" : "bgcolor='#7FFFD4'";
						if ($lines_array[2] >= 1000) {
							$status = htmlentities(dgettext('BluePexWebFilter', 'blocked'));
						} else {
							$status = htmlentities(dgettext('BluePexWebFilter', 'allowed'));
						}
						$lines_array[5] = ($lines_array[5] == "-") ? htmlentities(dgettext('BluePexWebFilter','not referenced')) : $lines_array[5];
						$lines_array[6] = ($lines_array[6] == "-") ? htmlentities(dgettext('BluePexWebFilter','not referenced')) : $lines_array[6];
						$html .= "<tr>";
						$html .= "<td {$color}>" . (is_numeric($lines_array[0]) ? date('d/m/Y H:i:s', (int)$lines_array[0]) : htmlentities(dgettext('BluePexWebFilter','Invalid timestamp'))) . "</td>";
						$html .= "<td {$color}><a href='{$lines_array[1]}'>" . substr($lines_array[1], 0, 60) . "...</a></td>";
						$html .= "<td {$color}><span class='badge'>{$status}</span></td>";
						$html .= "<td {$color}>" . htmlentities(getCategories($lines_array[3])) . "</td>";
						$html .= "<td {$color}>{$lines_array[4]}</td>";
						$html .= "<td {$color}>{$lines_array[5]}</td>";
						$html .= "<td {$color}>{$lines_array[6]}</td>";
						$html .= "<td {$color} align='center'><a class='btn btn-xs' href='/webfilter/wf_diagnostic.php?url={$lines_array[1]}&ip=$lines_array[4]&user={$lines_array[5]}'><i class='fa fa-search'></i></a></td>";
						$html .= "<td {$color} align='center'><a class='btn btn-xs' href='/webfilter/wf_ping.php?host={$url_new}&count=5'><i class='fa fa-search'></i></a></td>";
						$html .= "<td {$color} align='center'><a class='btn btn-xs' href='/webfilter/wf_nc.php?host={$url_new}&port=80'><i class='fa fa-search'></i></a></td>";
						$html .= "<td {$color} align='center'><a class='btn btn-xs' href='/webfilter/wf_nc.php?host={$url_new}&port=443'><i class='fa fa-search'></i></a></td>";
						$html .= "</tr>";
					}
				}
			}
		}
		$html .= "</tbody>";
		$html .= "</table>";
		echo $html;

	}
	exit;
}

function getCategories($refcat) {
	global $categories;
	foreach (explode(",", $refcat) as $cat) {
		if ($cat == "-" || $cat == 0)
			$categories_array[] = $categories['99'];
		else
			$categories_array[] = $categories[$cat];
	}
	return implode(", ", $categories_array);
}
if (!is_pid_running("{$g['varrun_path']}/wfrotated.pid")) {
	$input_errors[] = dgettext("BluePexWebFilter","WF Realtime: Service WF Rotate not is running!");
}


function get_status_services_by_wf_monitor() {
	global $g, $config;

	//@shell_exec("/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py");
	$services_status_file = "/usr/local/etc/webfilter/wf_monitor_services";
	if (!file_exists($services_status_file) || filesize($services_status_file) == 0) {
		return;
	}
	$services = array();
	$status_services = explode("\n", trim(file_get_contents($services_status_file)));

	$wf_extra_services = array("wfrotate");
	if ($g['platform'] == "BluePexUTM") {
		$wf_extra_services[] = "mysql";
	}

	// Organize services with status
	foreach ($status_services as $status_svc) {
		$get_services_status = explode(",", $status_svc);
		if (count($get_services_status) >= 6) {
			list($service_name, $service_status, $ntlm_status, $ads_status) = array_map("trim", explode(",", str_replace("\"", "", $status_svc)));
		} else {
			list($service_name, $service_status) = array_map("trim", explode(",", $status_svc));
		}
		if (preg_match("/^(squid|interface).*/", $service_name, $matches)) {
			$instance_name = str_replace("{$matches[1]}_", "", $service_name);
			if ($service_status != "ok") {
				$service_status = "error";
			}
			$services['webfilter'][$instance_name][] = array($matches[1] => $service_status);
			continue;
		}
		$services[$service_name] = $service_status;
	}

	if (!isset($services['webfilter'])) {
		return $services;
	}

	// Set status for WebFilter instances
	foreach ($services['webfilter'] as $instance => $wf_services) {
		$wf_status_services = array();
		foreach ($wf_services as $service_name => $service_status_array) {
			foreach ($service_status_array as $service_status) {
				$wf_status_services[] = $service_status;
			}
		}
		foreach ($wf_extra_services as $wf_extra_service) {
			if (isset($services[$wf_extra_service])) {
				$wf_status_services[] = $services[$wf_extra_service];
			}
		}
		if (in_array("error", $wf_status_services)) {
			$services['webfilter'][$instance] = "error";
		} elseif (in_array("alert", $wf_status_services)) {
			$services['webfilter'][$instance] = "alert";
		} else {
			$services['webfilter'][$instance] = "ok";
		}
	}
	return $services;
}

function get_log_webfilter($last_lines = "100") {
	if (!file_exists(WEBFILTER_LOG_FILE)) {
		return;
	}
	$out = @shell_exec("tail -n{$last_lines} " . WEBFILTER_LOG_FILE);
	return trim($out);
}

function get_button_status($service, $status) {
	switch (trim($status)) {
		case "ok":
			$btn = "<div class=\"btn btn-success\"><i class=\"fa fa-check-circle\"></i><br />{$service}</div>";
			break;
		case "error":
		case "off":
			$btn = "<div class=\"btn btn-danger no-confirm\"><i class=\"fa fa-times-circle\"></i><br />{$service}</div>";
			break;
		case "alert":
			$btn = "<div class=\"btn btn-warning\"><i class=\"fa fa-warning\"></i><br />{$service}</div>";
			break;
		case "disabled":
			$btn = "<div class=\"btn btn-default disabled\"><i class=\"fa fa-times-circle\"></i><br />{$service}</div>";
			break;
		default:
			$btn = "";
			break;
	}
	return $btn;
}

$pgtitle = array(dgettext("BluePexWebFilter", "WebFilter"), dgettext("BluePexWebFilter", "Dashboard"));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Dashboard'), true, '/webfilter/wf_dashboard.php');
$tab_array[] = array(dgettext('BluePexWebFilter', gettext('Diagnostic')), false, '/webfilter/wf_diagnostic.php');
$tab_array[] = array(dgettext('BluePexWebFilter', gettext('Port test')), false, '/webfilter/wf_nc.php');
display_top_tabs($tab_array);
?>
<style>
#wrap-iframe { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; }
#wrap-iframe iframe { position: absolute; top: 0px; bottom:5px; left: 0; width: 100%; height: 100%; border: 3px solid #dedede; padding: 3px; }
#status-services i { font-size:50px; }
#status-services .btn { width:100%; margin-bottom:5px; border-color:#eee; }
#log { overflow:auto; height:100px }
tbody#log > tr > th { vertical-align: inherit; }
input[name='url_test'] { height: 34px; }
.service .subservice { padding:0 }
.panel-no-padding-left-right { padding-left:0px;padding-right:0px; }
</style>
<?php if (!empty($services_status)) : ?>
<div id="status-services">
	<!-- WebFilter Instances -->
	<div class="col-sm-4 service">
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title text-center"><?=dgettext("BluePexWebFilter", "Instances");?></h3></div>
			<div class="panel-body">
			<?php
			if (isset($services_status['webfilter'])) :
				$total_instances = count($services_status['webfilter']);
				if ($total_instances == 1) {
					$cols = "col-md-12";
				} elseif ($total_instances == 2) {
					$cols = "col-xs-6 col-sm-6 col-md-6";
				} elseif ($total_instances >= 3) {
					$cols = "col-xs-12 col-sm-4 col-md-4";
				}
				foreach ($services_status['webfilter'] as $instance => $status) :
					foreach($config['system']['webfilter']['instance']['config'] as $ins) :
						if($ins['server']['name'] == $instance && $ins['server']['enable_squid'] != "on") :
							$s_status = "disabled";
						else:
							$s_status = $status;
						endif;
					endforeach;
			?>
				<div class="<?=$cols?> subservice">
					<?=get_button_status($instance, $s_status);?>
				</div>
			<?php endforeach; endif; ?>
			</div>
		</div>
	</div>
	<!-- General Services -->
	<div class="col-sm-8 service">
		<div class="panel panel-default">
			<div class="panel-heading"><h3 class="panel-title text-center"><?=dgettext("BluePexWebFilter", "STATUS");?></h3></div>
			<div class="panel-body">
				<!-- MySQL Database -->
				<?php if (isset($services_status['mysql'])) : ?>
				<div class="col-sm-6 subservice">
					<?=get_button_status(dgettext("BluePexWebFilter", "Database"), $services_status['mysql']);?>
				</div>
				<?php endif; ?>
				<!-- Disk Usage -->
				<?php
				if (isset($services_status['diskusage'])) :
					$service = sprintf(dgettext("BluePexWebFilter", "Disk Usage %s"), $services_status['diskusage'] . "%");
					$status = ($services_status['diskusage'] > 80) ? "alert" : "ok";
				?>
				<div class="col-sm-6 subservice">
					<?=get_button_status($service, $status);?>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<div class="clearfix"></div>
</div>
<?php endif;?>
<script>
//<![CDATA[
events.push(function(){
	//var int=self.setInterval(function(){webtail()},1000);
	$("#realtime-logs").removeClass("hide");
	var int;
	var status = 0;
	<?php if (($_REQUEST['status'] == "start") || ($_REQUEST['status'] == "")) { ?>
		var status = 1;
	<?php } ?>
	function startInterval() {
		int = self.setInterval(function(){webtail()},1000);
		$('#loading').fadeOut('fast');
	}
	function stopInterval() {
		//int=window.clearInterval(int);
		//$('#loading').fadeOut('fast');
	}
	function webtail() {
		var maxlines = $("#maxlines").val();
		var filter = $("#filter").val();
		var color = $("#enable_color:checked").val();
		var killcache = Math.random()*9999;
		var request_data = { "webtail": true, "filter": filter, "maxlines": maxlines, "color": color, "cachekill": killcache };
		$.get("wf_dashboard.php", request_data, function(data) {
			$('#loading').fadeIn();
			$('#log').html(data);
		});
	}
	function webstop() {
		var maxlines = $("#maxlines").val();
		var filter = $("#filter").val();
		var color = $("#enable_color:checked").val();
		var killcache = 0;
		var request_data = { "webtail": true, "filter": filter, "maxlines": maxlines, "color": color };
		$.get("wf_dashboard.php", request_data, function(data) {
			$('#loading').fadeIn();
			$('#log').html(data);
		});
	}
	if (status == 1) {
		$("#realtime-logs").removeClass("hide");
		int = self.setInterval(function(){webtail()},1000);
	} else {
		var request_data = { "webtail": true };
		$.get("wf_dashboard.php", request_data, function(data) {
			$('#loading').fadeIn();
			$('#log').html(data);
		});
	}
});
//]]>
</script>

<div class="col-sm-12">
	<div class="panel panel-default">
		<div class="panel-heading"><h2 class="panel-title"><?=gettext("Real Time Access")?> </h2></div>
			<div>
				<a type="button" href='/webfilter/wf_dashboard.php?status=start' name="status" id="status" value="start" class="btn btn-sm btn-success"><?=gettext("START")?></a>
				<a type="button" href='/webfilter/wf_dashboard.php?status=stop' name="status" id="status" value="stop" class="btn btn-sm btn-danger"><?=gettext("STOP")?></a>
				<?php if ($_REQUEST['status'] == "start") { ?>
					<a class="btn btn-sm btn-success"><span id="status" style="color:white"><?=gettext("STATUS: RUNNING")?></span></a>
				<?php } else if ($_REQUEST['status'] == "stop") { ?>
					<a class="btn btn-sm btn-danger"><span id="status" style="color:white"><?=gettext("STATUS: STOPPED")?></span></a>
				<?php } else { ?>
					<a class="btn btn-sm btn-success"><span id="status" style="color:white"><?=gettext("STATUS: RUNNING")?></span></a>
				<?php } ?>
				<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:350px;" id="filter" placeholder="<?=gettext("Search for...")?>">
			</div>
			<div class="panel-body panel-no-padding-left-right">
				<div class="Access-table" id="realtime-logs">
					<div class="table-responsive" style="height:600px;">
						<table id="table-access" class="table table-striped table-bordered">
							<thead></thead>
							<tbody id="log"></tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
</div>

<?php include('foot.inc'); ?>
