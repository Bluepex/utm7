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

define('MAX_COUNT', 10);
define('DEFAULT_COUNT', 5);
$do_ping = false;
$host = '';
$count = DEFAULT_COUNT;

define("WEBFILTER_LOG_FILE", "/var/log/webfilter.log");

$input_errors = array();
$savemsg = "";

if (!isset($_GET['host']) || ($_GET['host'] == "")) {
	header("Location: ./wf_dashboard.php");
	die;
}

if (!isset($_GET['count']) || ($_GET['count'] == 0) || ($_GET['count'] > 10) || ($_GET['count'] == "")) {
	header("Location: ./wf_dashboard.php");
	die;
}

if ($_GET || $_REQUEST['host']) {
	unset($input_errors);
	unset($do_ping);

	/* input validation */
	$reqdfields = explode(" ", "host count");
	$reqdfieldsn = array(gettext("Host"), gettext("Count"));
	do_input_validation($_REQUEST, $reqdfields, $reqdfieldsn, $input_errors);

	if (($_REQUEST['count'] < 1) || ($_REQUEST['count'] > MAX_COUNT)) {
		$input_errors[] = sprintf(gettext("Count must be between 1 and %s"), MAX_COUNT);
	}

	$host = trim($_REQUEST['host']);
	$ipproto = $_REQUEST['ipproto'];
	if (($ipproto == "ipv4") && is_ipaddrv6($host)) {
		$input_errors[] = gettext("When using IPv4, the target host must be an IPv4 address or hostname.");
	}
	if (($ipproto == "ipv6") && is_ipaddrv4($host)) {
		$input_errors[] = gettext("When using IPv6, the target host must be an IPv6 address or hostname.");
	}

	if (!$input_errors) {
		if ($_POST) {
			$do_ping = true;
		}
		if (isset($_REQUEST['sourceip'])) {
			$sourceip = $_REQUEST['sourceip'];
		}
		$count = $_REQUEST['count'];
		if (preg_match('/[^0-9]/', $count)) {
			$count = DEFAULT_COUNT;
		}
	}
}

if ($do_ping) {
?>
	<script type="text/javascript">
	//<![CDATA[
	window.onload=function() {
		document.getElementById("pingCaptured").wrap='off';
	}
	//]]>
	</script>
<?php
	$ifscope = '';
	$command = "/sbin/ping";
	if ($ipproto == "ipv6") {
		$command .= "6";
		$ifaddr = is_ipaddr($sourceip) ? $sourceip : get_interface_ipv6($sourceip);
		if (is_linklocal($ifaddr)) {
			$ifscope = get_ll_scope($ifaddr);
		}
	} else {
		$ifaddr = is_ipaddr($sourceip) ? $sourceip : get_interface_ip($sourceip);
	}

	if ($ifaddr && (is_ipaddr($host) || is_hostname($host))) {
		$srcip = "-S" . escapeshellarg($ifaddr);
		if (is_linklocal($host) && !strstr($host, "%") && !empty($ifscope)) {
			$host .= "%{$ifscope}";
		}
	}

	$cmd = "{$command} {$srcip} -c" . escapeshellarg($count) . " " . escapeshellarg($host);
	//echo "Ping command: {$cmd}\n";
	$result = shell_exec($cmd);

	if (empty($result)) {
		$input_errors[] = sprintf(gettext('Host "%s" did not respond or could not be resolved.'), $host);
	}

}

function check_connection_with_host($url) {

	$result = mwexec("/usr/bin/nc -z -w 3 google.com 80", false);

	file_put_contents("/tmp/nc_test.text", $result);	

}

function check_ping($host, $count = 10) {
	$url = parse_url($host);
	$output = shell_exec("/sbin/ping -c {$count} {$url['host']}");
	if (empty($output)) {
		return;
	}
	if (preg_match('/[0-9]+\.[0-9]+% packet loss/', $output, $match) === 1) {
		$packet_loss = $match[0];
		return array(
			"log" => $output,
			"packet_loss" => $packet_loss
		);
	}
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

$pgtitle = array(dgettext("BluePexWebFilter", "WebFilter"), dgettext("BluePexWebFilter", "PING"));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Dashboard'), false, '/webfilter/wf_dashboard.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'PING'), true, $_SERVER['REQUEST_URI']);

display_top_tabs($tab_array);
?>
<style>
#wrap-iframe { position: relative; padding-bottom: 56.25%; padding-top: 30px; height: 0; overflow: hidden; }
#wrap-iframe iframe { position: absolute; top: 0px; bottom:5px; left: 0; width: 100%; height: 100%; border: 3px solid #dedede; padding: 3px; }
#status-services i { font-size:50px; }
#status-services .btn { width:100%; margin-bottom:5px; border-color:#eee; }
#log { overflow:auto; height:300px }
input[name='url_test'] { height: 34px; }
.service .subservice { padding:0 }
</style>

<br>
<div class="p-0">
	<div class="col-12 cards-info">
				<div class="">
					<?php
						if ($do_ping && !empty($result) && !$input_errors) {
						?>
							<div class="panel panel-default">
								<div class="panel-heading">
									<h2 class="panel-title"><?=gettext('Results')?></h2>
								</div>

								<div class="panel-body">
									<pre><?= $result ?></pre>
								</div>
							</div>
						<?php
						}
						?>
				</div>
				<?php
					$form = new Form(false);

					$section = new Form_Section('');

					$section->addInput(new Form_Input(
						'host',
						'*Hostname',
						'text',
						$host,
						['placeholder' => 'Hostname to ping']
					),true);

					$section->addInput(new Form_Select(
						'ipproto',
						'*IP Protocol',
						$ipproto,
						['ipv4' => 'IPv4', 'ipv6' => 'IPv6']
					),true);

					$section->addInput(new Form_Select(
						'sourceip',
						'*Source address',
						$sourceip,
						array('' => gettext('Automatically selected (default)')) + get_possible_traffic_source_addresses(true)
					),true)->setHelp('Select source address for the ping.');

					$section->addInput(new Form_Select(
						'count',
						'Maximum number of pings',
						$count,
						array_combine(range(1, MAX_COUNT), range(1, MAX_COUNT))
					),true)->setHelp('Select the maximum number of pings.');

					$form->addGlobal(new Form_Button(
						'Submit',
						'TESTAR NOVAMENTE',
						null,
						'fa-rss'
					))->addClass('btn-primary');
					
					print $form;
				?>
			</div>	
	</div>
</div>
<script type="text/javascript">
window.onload = function() {
	document.getElementById("Submit").click();
	$(".btn-test").click(function() {
		var url = $(this).val();
		open_url_iframe(url);
		check_packets_loss(url);
	});
	$("button[name='exec_test']").click(function() {
		var url = $("input[name='url_test']").val();
		if (!url.match("^(http|https):\/\/")) {
			//url = "http://"+url;
			url = url;
		}
		open_url_iframe(url);
		//check_packets_loss(url);
	});
	function open_url_iframe(url) {
		console.log(url);
		$.ajax({
			type: 'GET',
			url: '/webfilter/wf_dashboard.php',
			data: { "test": true, "url": url, "type": "connection" },
			success: function(data) {
				var res = JSON.parse(data);
				if (res.status == "ok") {
					$("#wrap-iframe").show();
					var iframe = document.getElementById("iframe1");
					iframe.src = url;
				} else {
					$("#wrap-iframe").hide();
				}
				$('#result-conn').html(res.msg);
				$('#result-conn2').html(res.msg2);
			},
			beforeSend: function(){
				$('#result-conn').html("<?=dgettext("BluePexWebFilter", "testing...");?>");
				$('#result-conn2').html("<?=dgettext("BluePexWebFilter", "testing...");?>");
			},
			complete: function(){
				$('#result-conn').html();
				$('#result-conn2').html();
			}
		});
	}
	$("#log").animate({ scrollTop: $("#log")[0].scrollHeight}, 5000);
};
</script>
 
<br>
<br>
<?php include('foot.inc'); ?>
