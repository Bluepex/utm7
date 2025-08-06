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
require_once("guiconfig.inc");
$pgtitle = array(dgettext("BluePexWebFilter", "WebFilter"), dgettext("BluePexWebFilter", "Testar Porta"));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Dashboard'), false, '/webfilter/wf_dashboard.php');
$tab_array[] = array(dgettext('BluePexWebFilter', gettext('Diagnostic')), false, '/webfilter/wf_diagnostic.php');
$tab_array[] = array(dgettext('BluePexWebFilter', gettext('Port test')), true, '/webfilter/wf_nc.php');
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

<?php

define('NC_TIMEOUT', 10);
$do_testport = false;
$retval = 1;

if ($_POST || $_REQUEST['host']) {
	unset($input_errors);

	/* input validation */
	$reqdfields = explode(" ", "host port");
	$reqdfieldsn = array(gettext("Host"), gettext("Port"));
	do_input_validation($_REQUEST, $reqdfields, $reqdfieldsn, $input_errors);

	if (!is_ipaddr($_REQUEST['host']) && !is_hostname($_REQUEST['host'])) {
		$input_errors[] = gettext("Please enter a valid IP or hostname.");
	}

	if (!is_port($_REQUEST['port'])) {
		$input_errors[] = gettext("Please enter a valid port number.");
	}

	if (($_REQUEST['srcport'] != "") && (!is_numeric($_REQUEST['srcport']) || !is_port($_REQUEST['srcport']))) {
		$input_errors[] = gettext("Please enter a valid source port number, or leave the field blank.");
	}

	if (is_ipaddrv4($_REQUEST['host']) && ($_REQUEST['ipprotocol'] == "ipv6")) {
		$input_errors[] = gettext("Cannot connect to an IPv4 address using IPv6.");
	}
	if (is_ipaddrv6($_REQUEST['host']) && ($_REQUEST['ipprotocol'] == "ipv4")) {
		$input_errors[] = gettext("Cannot connect to an IPv6 address using IPv4.");
	}

	if (!$input_errors) {
		$do_testport = true;
		$timeout = NC_TIMEOUT;
	}

	/* Save these request vars even if there were input errors. Then the fields are refilled for the user to correct. */
	$host = $_REQUEST['host'];
	$sourceip = $_REQUEST['sourceip'];
	$port = $_REQUEST['port'];
	$srcport = $_REQUEST['srcport'];
	$showtext = isset($_REQUEST['showtext']);
	$ipprotocol = $_REQUEST['ipprotocol'];

	if ($do_testport) {
?>
		<script type="text/javascript">
			//<![CDATA[
			window.onload=function() {
				document.getElementById("testportCaptured").wrap='off';
			}
			//]]>
		</script>
<?php
		$result = "";
		$ncoutput = "";
		$nc_base_cmd = '/usr/bin/nc';
		$nc_args = "-w " . escapeshellarg($timeout);
		if (!$showtext) {
			$nc_args .= ' -z ';
		}
		if (!empty($srcport)) {
			$nc_args .= ' -p ' . escapeshellarg($srcport) . ' ';
		}

		/* Attempt to determine the interface address, if possible. Else try both. */
		if (is_ipaddrv4($host)) {
			if ($sourceip == "any") {
				$ifaddr = "";
			} else {
				if (is_ipaddr($sourceip)) {
					$ifaddr = $sourceip;
				} else {
					$ifaddr = get_interface_ip($sourceip);
				}
			}
			$nc_args .= ' -4';
		} elseif (is_ipaddrv6($host)) {
			if ($sourceip == "any") {
				$ifaddr = '';
			} else if (is_linklocal($sourceip)) {
				$ifaddr = $sourceip;
			} else {
				$ifaddr = get_interface_ipv6($sourceip);
			}
			$nc_args .= ' -6';
		} else {
			switch ($ipprotocol) {
				case "ipv4":
					$ifaddr = get_interface_ip($sourceip);
					$nc_ipproto = ' -4';
					break;
				case "ipv6":
					$ifaddr = (is_linklocal($sourceip) ? $sourceip : get_interface_ipv6($sourceip));
					$nc_ipproto = ' -6';
					break;
				case "any":
					$ifaddr = get_interface_ip($sourceip);
					$nc_ipproto = (!empty($ifaddr)) ? ' -4' : '';
					if (empty($ifaddr)) {
						$ifaddr = (is_linklocal($sourceip) ? $sourceip : get_interface_ipv6($sourceip));
						$nc_ipproto = (!empty($ifaddr)) ? ' -6' : '';
					}
					break;
			}
			/* Netcat doesn't like it if we try to connect using a certain type of IP without specifying the family. */
			if (!empty($ifaddr)) {
				$nc_args .= $nc_ipproto;
			} elseif ($sourceip == "any") {
				switch ($ipprotocol) {
					case "ipv4":
						$nc_ipproto = ' -4';
						break;
					case "ipv6":
						$nc_ipproto = ' -6';
						break;
				}
				$nc_args .= $nc_ipproto;
			}
		}
		/* Only add on the interface IP if we managed to find one. */
		if (!empty($ifaddr)) {
			$nc_args .= ' -s ' . escapeshellarg($ifaddr) . ' ';
			$scope = get_ll_scope($ifaddr);
			if (!empty($scope) && !strstr($host, "%")) {
				$host .= "%{$scope}";
			}
		}

		$nc_cmd = "{$nc_base_cmd} {$nc_args} " . escapeshellarg($host) . ' ' . escapeshellarg($port) . ' 2>&1';
		exec($nc_cmd, $result, $retval);
	//	echo "NC CMD: {$nc_cmd}\n\n";

		if (!empty($result)) {
			if (is_array($result)) {
				foreach ($result as $resline) {
					$ncoutput .= htmlspecialchars($resline) . "\n";
				}
			} else {
				$ncoutput .= htmlspecialchars($result);
			}
		}
	}
}

// Handle the display of all messages here where the user can readily see them
if ($input_errors) {
	print_input_errors($input_errors);
} elseif ($do_testport) {
	// User asked for a port test
	if ($retval == 0) {
		// Good host & port
		$alert_text = sprintf(gettext('Port test to host: %1$s Port: %2$s successful.'), $host, $port);
		if ($showtext) {
			$alert_text .= ' ' . gettext('Any text received from the host will be shown below the form.');
		}
		print_info_box($alert_text, 'success', false);
	} else {
		// netcat exit value != 0
		if ($showtext) {
			$alert_text = gettext('No output received, or connection failed. Try with "Show Remote Text" unchecked first.');
		} else {
			$alert_text = gettext('Connection failed.');
		}
		print_info_box($alert_text, 'danger', false);
	}
} else {
	// First time, new page
	print_info_box(gettext('This page performs a simple TCP connection test to determine if a host is up and accepting connections on a given port.') . " " .
		gettext('This test does not function for UDP since there is no way to reliably determine if a UDP port accepts connections in this manner.'), 'warning', false);
}

$form = new Form(false);

$section = new Form_Section('Test Port');

$section->addInput(new Form_Input(
	'host',
	'*Hostname',
	'text',
	$host,
	['placeholder' => 'Hostname to look up.']
));

$section->addInput(new Form_Input(
	'port',
	'*Port',
	'text',
	$port,
	['placeholder' => 'Port to test.']
));

$section->addInput(new Form_Input(
	'srcport',
	'Source Port',
	'text',
	$srcport,
	['placeholder' => 'Typically left blank.']
), true);

$section->addInput(new Form_Checkbox(
	'showtext',
	'Remote text',
	'Show remote text',
	$showtext
), true)->setHelp("Shows the text given by the server when connecting to the port. If checked it will take 10+ seconds to display in a panel below this form.");

$section->addInput(new Form_Select(
	'sourceip',
	'*Source Address',
	$sourceip,
	['' => 'Any'] + get_possible_traffic_source_addresses(true)
), true)->setHelp('Select source address for the trace.');

$section->addInput(new Form_Select(
	'ipprotocol',
	'*IP Protocol',
	$ipprotocol,
	array('ipv4' => 'IPv4', 'ipv6' => 'IPv6')
), true)->setHelp("If IPv4 or IPv6 is forced and a hostname is used that does not contain a result using that protocol, it will result in an error." .
					" For example if IPv4 is forced and a hostname is used that only returns an AAAA IPv6 IP address, it will not work.");

$form->add($section);

$form->addGlobal(new Form_Button(
	'Submit',
	'Test',
	null,
	'fa-wrench'
))->addClass('btn-primary');

print $form;

// If the command succeeded, the user asked to see the output and there is output, then show it.
if ($retval == 0 && $showtext && !empty($ncoutput)):
?>
	<div class="panel panel-default">
		<div class="panel-heading">
			<h2 class="panel-title"><?=gettext('Received Remote Text')?></h2>
		</div>
		<div class="panel-body">
			<pre><?= $ncoutput ?></pre>
		</div>
	</div>
<?php
endif;

include("foot.inc");
