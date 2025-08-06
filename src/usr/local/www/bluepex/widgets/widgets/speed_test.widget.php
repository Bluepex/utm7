<?php
/*
        Copyright (C) 2015 BluePex <desenvolvimento@bluepex.com>
*/

$nocsrf = true;

require_once("guiconfig.inc");
require_once("config.inc");
require_once("util.inc");

define("RADIUS", 6371);
define("URLCONFIG", "http://www.speedtest.net/speedtest-config.php");
define("URLSERVERS", "http://www.speedtest.net/speedtest-servers.php");

if (!isset($config['widgets']['speed_test'])) {
	$config['widgets']['speed_test'] = array();
}
$speedxml = &$config['widgets']['speed_test'];

function download_page($path) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_USERAGENT, 'XtraDoh xAgent');
	curl_setopt($ch, CURLOPT_URL, $path);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

	$page = curl_exec($ch);
	curl_close($ch);

	return $page;
}

function test_down_url($t_dirUrl) {
	$sizes = array(4000,3500,3000,2500,2000);
	foreach($sizes as $size) {
		@$fd = fopen("{$t_dirUrl}/random{$size}x{$size}.jpg","r");
		if ($fd) {
			@fclose($fd);
			break;
		}
	}
	return "{$t_dirUrl}/random{$size}x{$size}.jpg";
}

function return_data_speedtest() {

	require("config.inc");
	$speedxml = $config['widgets']['speed_test'];

	$result['test_datetime'] = date("Y-m-d H:i:s", $speedxml['test_datetime']);
	$result['link_provided'] = $speedxml['link_provided'];
	$result['server_sponsored'] = $speedxml['server_sponsored'];
	$result['server_located'] = $speedxml['server_located'];
	$result['distance_server'] = $speedxml['distance_server'];
	$result['download'] = $speedxml['download'];
	$result['upload'] = $speedxml['upload'];
	$result['nominal_link'] = $speedxml['nominal'];

	return json_encode($result);
}

if ($_POST['reload_servers']) {
	echo json_encode($config['widgets']['speed_test']);
	exit;
}

if ($_POST['get_servers']) {
	$client_xml = simplexml_load_string(download_page(URLCONFIG));
	$server_xml = simplexml_load_string(download_page(URLSERVERS));

	$attr_s = $server_xml->servers;
	$attr_c = $client_xml->client->attributes();
	$shortest_distance = array();

	foreach($attr_s as $server) {
		foreach($server as $attr) {
			if ($attr['country'] == "Brazil")
				$servers[] = $attr;
		}
	}

	foreach($servers as $attr) {

		$lat_s = $attr['lat'];
		$lon_s = $attr['lon'];

		$lat_c = $attr_c['lat'];
		$lon_c = $attr_c['lon'];

		$lat = deg2rad(floatval($lat_s) - floatval($lat_c));
		$lon = deg2rad(floatval($lon_s) - floatval($lon_c));

		$distance = (sin($lat / 2) * sin($lat / 2) + cos(deg2rad(floatval($lat_c)))* cos(deg2rad(floatval($lat_s)))* sin($lon / 2)* sin($lon / 2));

		$calc = 2 * atan2(sqrt($distance), sqrt(1 - $distance));
		$result = RADIUS * $calc;
		$res[] = array("distance" => $result, "server" => $attr);
	}

	usort($res, function($a, $b) {
		return $a['distance'] - $b['distance'];
	});

	foreach($res as $idx =>$distance) {
		$shortest_distance[] = $distance;
		if ($idx==4) break;
	}

	foreach($shortest_distance as $attr) {

		$attr_server = $attr['server']->attributes();
		$file_test = dirname($attr_server['url'])."/latency.txt";
		$executed = array();

		for($i=0; $i < 3; $i++) {

			$ch = curl_init($file_test);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
 
			$start = microtime(true);
			$result = trim(curl_exec($ch));
			if ($result != "test=test") {
				$executed[] = 3500;
				continue;
			}

			$response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			$end = microtime(true) - $start;
			$executed[] = ($response_code == 200 && $result == "test=test") ? $end : 3600;

			curl_close($ch);
		}

		$total_ping = round((array_sum($executed) / 2) * 1000000, 5);
		$test_server[] = array(
				"ping" => $total_ping, 
				"url" => base64_encode($attr_server['url']), 
				"location" => $attr_server['name'], 
				"sponsor" => $attr_server['sponsor'], 
				"distance" => $attr['distance']
				);
	}

	usort($test_server, function($a, $b) {
		return $a["ping"] - $b["ping"];
	});

	if (is_array($test_server)) {
		$servers = array();
		foreach($test_server as $server) {
			$servers[] = array(
				"link_provided" => htmlentities("{$attr_c->isp} ({$attr_c->ip})"),
				"location" => htmlentities($server['location']),
				"url" => $server['url'],
				"ping" => $server['ping'],
				"sponsor" => htmlentities($server['sponsor']),
				"distance" => $server['distance']
			);
		}
		$speedxml['servers'] = $servers;
		write_config(gettext("Saved Servers data."));
		echo "ok";
	} else
		echo "error";

	exit;
} elseif ($_POST['start']) {
	if (isset($_POST['server'])) {
		require("config.inc");

		if (!isset($config['widgets']['speed_test'])) {
			$config['widgets']['speed_test'] = array();
		}
		$speedxml = &$config['widgets']['speed_test'];

		$idxserver = $_POST['server'];
		$server = $config['widgets']['speed_test']['servers'][$idxserver];

		$url = base64_decode($server['url']);
		$dirUrl = dirname($url);
		$downUrl = test_down_url($dirUrl);
		
		exec("/usr/local/sbin/consumer -d {$downUrl} -u {$dirUrl}/upload.php -f /boot/kernel/kernel.gz", $out, $error);

		if ($error == 0) {
			$speedxml['test_datetime'] = time();
			$speedxml['link_provided'] = $server['link_provided'];
			$speedxml['server_sponsored'] = $server['sponsor'];
			$speedxml['server_located'] = $server['location'];
			$speedxml['distance_server'] = round($server['distance'])." KM";
			$speedxml['download'] = preg_replace("/^.*:\s/", "", $out[0]);
			$speedxml['nominal'] = preg_replace("/^.*:\s/", "", $out[1]);
			$speedxml['upload'] = preg_replace("/^.*:\s/", "", $out[2]);;

			write_config(gettext("Saving data of speed test."));
			echo return_data_speedtest();
		}
	}
	exit;
} else {
?>

<?php $title = is_array($speedxml['servers']) ? gettext('Update Servers') : gettext('Get Servers');?>

<p>
	<?php print_info_box(sprintf(gettext("The speed test uses the default gateway to the calculations. <a href=system_gateways.php>Click Here</a> to change the gateway"))); ?>
</p>

<form name="speedtest" id="FormSpeedTest">
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
	<tbody>
		<tr id="loader" style="display:none">
			<td class="listr" align="center">
			 	<div class="progress" style="width: 500px;">
					<div class="progress-bar bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:10"> 0%</div> 
				</div>	
				<div id="status"><?=gettext('Test running, please wait...')?></div>
			</td>
		</tr>
		<tr>
			<td>
				<table class="table table-striped">
				<thead>
					<tr>
						<th><?=gettext("Update Information")?></th>
						<th><button type="button" class="btn btn-success btn-xs" onclick="start_test()" id="button1"><?=gettext("Start Test");?></button></th>
					</tr>
				</thead>
				<tbody>
				<tr>
					<td class="vncellt"><b><?=gettext("Test Date")?></b></td>
					<td class="listr" valign="top"><span id="test_datetime"><?=(isset($speedxml['test_datetime']) ? date("Y-m-d H:i:s", $speedxml['test_datetime']) : "");?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Link provided by")?></b></td>
					<td class="listr" valign="top"><span id="link_provided"><?=$speedxml['link_provided']?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Server sponsored by")?></b></td>
					<td class="listr" valign="top"><span id="server_sponsored"><?=$speedxml['server_sponsored']?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Server located in")?></b></td>
					<td class="listr" valign="top"><span id="server_located"><?=$speedxml['server_located']?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Distance between your ISP and the server")?></b></td>
					<td class="listr" valign="top"><span id="distance_server"><?=$speedxml['distance_server']?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Nominal Link")?></b></td>
					<td class="listr" valign="top"><span id="nominal_link"><?=$speedxml['nominal']?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Download speed")?></b></td>
					<td class="listr" valign="top"><span id="download"><?=$speedxml['download']?></span></td>
				</tr>
				<tr>
					<td class="vncellt"><b><?=gettext("Upload Speed")?></b></td>
					<td class="listr" valign="top"><span id="upload"><?=$speedxml['upload']?></span></td>
				</tr>
				</tbody>
				</table>
			</td>
		</tr>
	</tbody>
	</table>
</form>

<?php } ?>

<!-- close the body we're wrapped in and add a configuration-panel -->
</div>
<div id="widget-<?=$widgetname?>_panel-footer"class="panel-footer collapse">
	<form name="FormServers2" id="FormServers2">
		<br />
		<button type="button" class="btn btn-primary btn-xs" onclick="get_servers()"><?=$title?></button>
		<p><b><?=gettext("Select the server to start test.")?></b></p>
		<hr />
		<table class="table table-bordered">
			<thead>
				<tr>
					<th><img id="status_servers" src="../widgets/images/indicator.gif" style="display:none" /></th>
					<th><b><?=gettext("Server")?></b></th>
					<th><b><?=gettext("Distance")?></b></th>
					<th><b><?=gettext("Sponsor")?></b></th>
				</tr>
			</thead>
			<tbody id="load-servers">
			</tbody>
		</table>
	</form>

<script type="text/javascript">
//<![CDATA[
function get_servers() {
	$("#status_servers").show();
	$.ajax({
		method: "POST",
		url: "widgets/widgets/speed_test.widget.php",
		data: { get_servers:"true" },
		success: function( response ) {
			if (response == "ok") {
				print_servers();
			}
			$("#status_servers").hide();
		},
		error: function() {
			$("#status_servers").hide();
		}
	});
}

function print_servers() {
	$.ajax({
		method: "POST",
		url: "widgets/widgets/speed_test.widget.php",
		data: { reload_servers: "true" },
	}).success(function( response ) {
		if (response == "") {
			return;
		}

		var cols = "";
		var list = JSON.parse(response);

		if(list.servers != null) {
			for(var i=0; i<list.servers.length; i++) {
				cols += "<tr>";
				cols += "<td width=\"10\">";
				if(i==0) {
					cols += "<input type=\"radio\" name=\"selected_server\" class=\"selected_server\" value=\""+i+"\" checked=\"checked\" />";
				} else {
					cols += "<input type=\"radio\" name=\"selected_server\" class=\"selected_server\" value=\""+i+"\" />";
				}
				cols += "</td>";
				cols += "<td width=\"150\">";
				cols += "<i class=\"fa fa-server\"></i> ";
				cols += "<span id=\"server_location\""+i+"\">"+list.servers[i].location+"</span>";
				cols += "</td>";
				cols += "<td>";
				cols += "<span id=\"server_distance\""+i+"\">"+Math.round(list.servers[i].distance)+" KM</span>";
				cols += "</td>";
				cols += "<td>"+list.servers[i].sponsor+"</td>";
				cols += "</tr>";
			}
			$("#load-servers").html(cols);
			$("#load-servers").show();
		}
	});
}

var progress = null;
function set_progress_consumer() {
	clearInterval(progress);
	$('.bar').width(0);
	progress = setInterval(function() {
		if ($('.bar').width() >= 490) {
			clearInterval(progress);
			$('.progress').removeClass('active');
		} else {
			$('.bar').width($('.bar').width()+5);
		}
		$('.bar').text($('.bar').width()/5 + "%");
	}, 500);
}

function start_test() {
	if (!$(".selected_server").length > 0) {
		print_servers();
		$("#widget-<?=$widgetname;?>_panel-footer").show();
	} else {
		$("#widget-<?=$widgetname;?>_panel-footer").removeClass("in");
		var server = $(".selected_server:checked").val();

		$("#loader").css("display", "");
		set_progress_consumer();
		$('button').prop('disabled', true);

		$.ajax({
			method: "POST",
			url: "widgets/widgets/speed_test.widget.php",
			data: { start:"true", server:server },
			success: function( response ) {
				$('.bar').width(500);
				$('.bar').text('100%');
				$("#loader").css("display", "none");
				$('.bar').width(0);
				
				if (response != "") {
					var elements = FormSpeedTest.getElementsByTagName('span');
					var getValues = JSON.parse(response);

						for(var i=0; i<elements.length; i++) {
							var id = elements[i].getAttribute("id");
							$('#'+id).text(getValues[id]);
						}
					$("#loader").css("display", "none");
				} else {
					alert(<?=gettext("'response empty'");?>);
				}
				$("#loader").css("display", "none");
				$('button').prop('disabled', false);
			},
			error: function() {
				$('button').prop('disabled', false);
			}
		})
	}
}

<?php if (!empty($speedxml)) : ?>
events.push(function(){
	print_servers();
});
<?php endif; ?>
//]]>
</script>
