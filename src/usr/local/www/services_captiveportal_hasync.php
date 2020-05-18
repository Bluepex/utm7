<?php
/*
 * services_captiveportal_hasync.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2019 Rubicon Communications, LLC (Netgate) 
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

##|+PRIV
##|*IDENT=page-services-captiveportal-hasync
##|*NAME=Services: Captive Portal HA
##|*DESCR=Allow access to the 'Services: Captive Portal High Availability' page.
##|*MATCH=services_captiveportal_hasync.php*
##|-PRIV

if ($_POST['postafterlogin']) {
	$nocsrf = true;
}

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("captiveportal.inc");
require_once("xmlrpc_client.inc");

$cpzone = strtolower(htmlspecialchars($_REQUEST['zone']));

if (empty($cpzone)) {
	header("Location: services_captiveportal_zones.php");
	exit;
}
if (!is_array($config['captiveportal'])) {
	$config['captiveportal'] = array();
}

$a_cp =& $config['captiveportal'];

if (empty($a_cp[$cpzone])) {
	log_error(sprintf(gettext("Submission on captiveportal page with unknown zone parameter: %s"), htmlspecialchars($cpzone)));
	header("Location: services_captiveportal_zones.php");
	exit();
}

$pgtitle = array(gettext("Services"), gettext("Captive Portal"), $a_cp[$cpzone]['zone'], gettext("High Availability"));
$pglinks = array("", "services_captiveportal_zones.php", "services_captiveportal.php?zone=" . $cpzone, "@self");

$pconfig['enablebackwardsync'] = isset($config['captiveportal'][$cpzone]['enablebackwardsync']);
$pconfig['backwardsyncip'] = $config['captiveportal'][$cpzone]['backwardsyncip'];
$pconfig['backwardsyncpassword'] = $config['captiveportal'][$cpzone]['backwardsyncpassword'];
$pconfig['backwardsyncuser'] = $config['captiveportal'][$cpzone]['backwardsyncuser'];

if ($_POST['save']) {
	$pconfig = $_POST;

	/* input validation */
	if ($_POST['enablebackwardsync'] == "yes") {
		$reqdfields = explode(" ", "backwardsyncip backwardsyncpassword backwardsyncuser");
		$reqdfieldsn = array(gettext("Primary node IP"), gettext("Primary node password"), gettext("Primary node username"));
	} else {
		$reqdfields = array();
		$reqdfieldsn = array();
	}
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if ($_POST['backwardsyncip'] && (is_ipaddr_configured($_POST['backwardsyncip']))) {
		$input_errors[] = gettext("This IP is currently used by this pfSense node. Please enter an IP belonging to the primary node.");
	}
	if ($_POST['backwardsyncpassword'] != $_POST['backwardsyncpassword_confirm']) {
		$input_errors[] = gettext("Password and confirmed password must match.");
	}

	if (!$input_errors) {
		$newcp =& $a_cp[$cpzone];
		if ($pconfig['enablebackwardsync'] == "yes") {
			$newcp['enablebackwardsync'] = true;
		} else {
			unset($newcp['enablebackwardsync']);
		}
		$newcp['backwardsyncip'] = $pconfig['backwardsyncip'];
		$newcp['backwardsyncuser'] = $pconfig['backwardsyncuser'];

		$port = $config['system']['webgui']['port'];
		if (empty($port)) { // if port is empty lets rely on the protocol selection
			if ($config['system']['webgui']['protocol'] == "http") {
				$port = "80";
			} else {
				$port = "443";
			}
		}

		if ($_POST['backwardsyncpassword'] != DMYPWD ) {
			$newcp['backwardsyncpassword'] = $pconfig['backwardsyncpassword'];
		} else {
			$newcp['backwardsyncpassword'] = $config['captiveportal'][$cpzone]['backwardsyncpassword'];
		}
		if (!empty($newcp['enablebackwardsync'])) {
			$rpc_client = new pfsense_xmlrpc_client();
			$rpc_client->setConnectionData($newcp['backwardsyncip'], $port, $newcp['backwardsyncuser'], $newcp['backwardsyncpassword']);

			if (!$input_errors) {
				$savemsg = sprintf(gettext('Connected users and used vouchers are now synchronized with %1$s'), $newcp['backwardsyncip']);
			}
		}
		if (!$input_errors) {
			$config['captiveportal'][$cpzone] = $newcp;
			write_config('Updated captiveportal backward HA settings');
		}
	}
}

include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}
if ($savemsg) {
	print_info_box($savemsg, 'success');
}

$tab_array = array();
$tab_array[] = array(gettext("Configuration"), false, "services_captiveportal.php?zone={$cpzone}");
$tab_array[] = array(gettext("MACs"), false, "services_captiveportal_mac.php?zone={$cpzone}");
$tab_array[] = array(gettext("Allowed IP Addresses"), false, "services_captiveportal_ip.php?zone={$cpzone}");
$tab_array[] = array(gettext("Allowed Hostnames"), false, "services_captiveportal_hostname.php?zone={$cpzone}");
$tab_array[] = array(gettext("Vouchers"), false, "services_captiveportal_vouchers.php?zone={$cpzone}");
$tab_array[] = array(gettext("High Availability"), true, "services_captiveportal_hasync.php?zone={$cpzone}");
$tab_array[] = array(gettext("File Manager"), false, "services_captiveportal_filemanager.php?zone={$cpzone}");
display_top_tabs($tab_array, true);

$form = new Form();

$section = new Form_Section('Secondary to Primary node Synchronization');

$section->addClass('rolledit');

$section->addInput(new Form_Checkbox(
	'enablebackwardsync',
	'Enable',
	'Enable backward High Availability Sync for connected users and used vouchers',
	$pconfig['enablebackwardsync']
	))->setHelp('The XMLRPC sync provided by pfSense in <a href="/system_hasync.php">High Availability</a> settings only synchronize a secondary node to its primary node.'.
	'This checkbox enable a backward sync from the secondary to the primary, in order to have a bi-directional synchronization.%1$s'.
	'The purpose of this feature is to keep connected users synchronized between servers even if a node does down, thus providing redundancy for the captive portal zone.%1$s%1$s'.
	'<b>Important: these settings should be set on the secondary node only ! Do not update these settings if this pfSense is the primary node !</b>', '<br />');

$section->addInput(new Form_IpAddress(
	'backwardsyncip',
	'Primary node IP',
	$pconfig['backwardsyncip']
))->setHelp('Please fill here the IP address of the primary node.%1$s', '<br />');

$section->addInput(new Form_Input(
	'backwardsyncuser',
	'Primary node username',
	'text',
	$pconfig['backwardsyncuser']
))->setHelp('Please enter the username of the primary node that the secondary node will use for backward sync. This could be any pfSense user on the primary node with "System - HA node sync" privileges.');

$section->addPassword(new Form_Input(
	'backwardsyncpassword',
	'Primary node password',
	'password',
	$pconfig['backwardsyncpassword']
))->setHelp('Please enter the password associated to this user.');

$form->addGlobal(new Form_Input(
	'zone',
	null,
	'hidden',
	$cpzone
));

$form->add($section);
print($form);

print_info_box(sprintf(gettext('It is recommended to configure XMLRPC sync on the primary node before configuring backward synchronization for captive portal.'), $cpzone), 'info');
?>
</div>

<script type="text/javascript">
//<![CDATA[
events.push(function() {
	function showhideHAsync(hide) {
		hideInput('backwardsyncip', hide);
		hideInput('backwardsyncuser', hide);
		hideInput('backwardsyncpassword', hide);
	}
	// Show/hide on checkbox change
	$('#enablebackwardsync').click(function() {
		showhideHAsync(!$('#enablebackwardsync').is(":checked"));
	})
	// Set initial state
	showhideHAsync(!$('#enablebackwardsync').is(":checked"));

});
//]]>
</script>
<?php include("foot.inc");
