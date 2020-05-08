<?php
/*
 * interfaces_gre_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2020 Rubicon Communications, LLC (Netgate)
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
##|*IDENT=page-interfaces-gre-edit
##|*NAME=Interfaces: GRE: Edit
##|*DESCR=Allow access to the 'Interfaces: GRE: Edit' page.
##|*MATCH=interfaces_gre_edit.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");

init_config_arr(array('gres', 'gre'));
$a_gres = &$config['gres']['gre'];
$id = $_REQUEST['id'];

if (isset($id) && $a_gres[$id]) {
	$pconfig['if'] = $a_gres[$id]['if'];
	$pconfig['greif'] = $a_gres[$id]['greif'];
	$pconfig['remote-addr'] = $a_gres[$id]['remote-addr'];
	if (!isset($a_gres[$id]['tunnel-type'])) {
		if (is_ipaddrv6($a_gres[$id]['tunnel-local-addr'])) {
			// Compatibility mode
			$pconfig['tunnel-type'] = 'v6';
			$pconfig['tunnel-remote-net6'] = $a_gres[$id]['tunnel-remote-net'];
			$pconfig['tunnel-local-addr6'] = $a_gres[$id]['tunnel-local-addr'];
			$pconfig['tunnel-remote-addr6'] = $a_gres[$id]['tunnel-remote-addr'];
		} else {
	print_r($a_gres[$id]);
			$pconfig['tunnel-type'] = 'v4';
			$pconfig['tunnel-remote-net4'] = $a_gres[$id]['tunnel-remote-net'];
			$pconfig['tunnel-local-addr4'] = $a_gres[$id]['tunnel-local-addr'];
			$pconfig['tunnel-remote-addr4'] = $a_gres[$id]['tunnel-remote-addr'];
		}
	} else {
		$pconfig['tunnel-type'] = $a_gres[$id]['tunnel-type'];
		$pconfig['tunnel-remote-net4'] = $a_gres[$id]['tunnel-remote-net4'];
		$pconfig['tunnel-local-addr4'] = $a_gres[$id]['tunnel-local-addr4'];
		$pconfig['tunnel-remote-addr4'] = $a_gres[$id]['tunnel-remote-addr4'];
		$pconfig['tunnel-remote-net6'] = $a_gres[$id]['tunnel-remote-net6'];
		$pconfig['tunnel-local-addr6'] = $a_gres[$id]['tunnel-local-addr6'];
		$pconfig['tunnel-remote-addr6'] = $a_gres[$id]['tunnel-remote-addr6'];
	}
	$pconfig['link1'] = isset($a_gres[$id]['link1']);
	$pconfig['link2'] = isset($a_gres[$id]['link2']);
	$pconfig['link3'] = isset($a_gres[$id]['link3']);
	$pconfig['link0'] = isset($a_gres[$id]['link0']);
	$pconfig['descr'] = $a_gres[$id]['descr'];
}

if ($_POST['save']) {

	unset($input_errors);
	$pconfig = $_POST;

	$pconfig['tunnel-local-addr4'] = addrtolower($_POST['tunnel-local-addr4']);
	$pconfig['tunnel-remote-addr4'] = addrtolower($_POST['tunnel-remote-addr4']);
	$pconfig['tunnel-local-addr6'] = addrtolower($_POST['tunnel-local-addr6']);
	$pconfig['tunnel-remote-addr6'] = addrtolower($_POST['tunnel-remote-addr6']);
	$pconfig['remote-addr'] = addrtolower($_POST['remote-addr']);
	$pconfig['tunnel-type'] = strtolower($_POST['tunnel-type']);

	/* input validation */
	if ($pconfig['tunnel-type'] === 'v4') {
		$reqdfields = explode(" ", "if remote-addr tunnel-local-addr4 tunnel-remote-addr4 tunnel-remote-net4");
		$reqdfieldsn = array(gettext("Parent interface"), gettext("Remote tunnel endpoint IPv4 address"), gettext("Local tunnel IPv4 address"), gettext("Remote tunnel IPv4 address"), gettext("Remote IPv4 tunnel network"));
	} else if ($pconfig['tunnel-type'] === 'v6') {
		$reqdfields = explode(" ", "if remote-addr tunnel-local-addr6 tunnel-remote-addr6 tunnel-remote-net6");
		$reqdfieldsn = array(gettext("Parent interface"), gettext("Remote tunnel endpoint IPv6 address"), gettext("Local tunnel IPv6 address"), gettext("Remote tunnel IPv6 address"), gettext("Remote IPv6 tunnel network"));
	} else {
		$reqdfields = explode(" ", "if remote-addr tunnel-local-addr4 tunnel-remote-addr4 tunnel-remote-net4 tunnel-local-addr6 tunnel-remote-addr6 tunnel-remote-net6");
		$reqdfieldsn = array(gettext("Parent interface"), gettext("Remote tunnel endpoint IPv4 address"), gettext("Local tunnel IPv4 address"), gettext("Remote tunnel IPv4 address"), gettext("Remote IPv4 tunnel network"), gettext("Remote tunnel endpoint IPv6 address"), gettext("Local tunnel IPv6 address"), gettext("Remote tunnel IPv6 address"), gettext("Remote IPv6 tunnel network"));
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, $input_errors);

	if (((!is_ipaddr($_POST['tunnel-local-addr4'])) ||
	     (!is_ipaddr($_POST['tunnel-remote-addr4']))) &&
	    in_array($_POST['tunnel-type'], array('v4', 'v4v6'))) {
		$input_errors[] = gettext("The tunnel local and tunnel remote fields must have valid IPv4 addresses.");
	}

	if (((!is_ipaddrv6($_POST['tunnel-local-addr6'])) ||
	     (!is_ipaddrv6($_POST['tunnel-remote-addr6']))) &&
	     in_array($_POST['tunnel-type'], array('v6', 'v4v6'))) {
		$input_errors[] = gettext("The tunnel local and tunnel remote fields must have valid IPv6 addresses.");
	}

	if (!is_ipaddr($_POST['remote-addr'])) {
		$input_errors[] = gettext("The remote address must be a valid IP address.");
	}

	if (!in_array($_POST['tunnel-type'], array('v4', 'v6', 'v4v6'))) {
		$input_errors[] = gettext("The GRE tunnel type must be valid.");
	}

	if (!is_numericint($_POST['tunnel-remote-net4'])) {
		$input_errors[] = gettext("The GRE tunnel subnet must be an integer.");
	}

	if (!is_numericint($_POST['tunnel-remote-net6'])) {
		$input_errors[] = gettext("The v6 GRE tunnel subnet must be an integer.");
	}

	if (in_array($_POST['tunnel-type'], array('v4', 'v4v6'))) {
		if (!is_ipaddrv4($_POST['tunnel-local-addr4'])) {
			$input_errors[] = gettext("The GRE Tunnel local address must be IPv4 if the tunnel type is IPv4.");
		}
		if (!is_ipaddrv4($_POST['tunnel-remote-addr4'])) {
			$input_errors[] = gettext("The GRE Tunnel remote address must be IPv4 if the tunnel type is IPv4.");
		}
		if ($_POST['tunnel-remote-net4'] > 32 || $_POST['tunnel-remote-net4'] < 1) {
			$input_errors[] = gettext("The GRE tunnel subnet for IPv4 must be an integer between 1 and 32.");
		}
	}

	if (in_array($_POST['tunnel-type'], array('v6', 'v4v6'))) {
		if (!is_ipaddrv6($_POST['tunnel-local-addr6'])) {
			$input_errors[] = gettext("The GRE Tunnel local address must be IPv6 if the tunnel type is IPv6.");
		}
		if (!is_ipaddrv6($_POST['tunnel-remote-addr6'])) {
			$input_errors[] = gettext("The GRE Tunnel remote address must be IPv6 if the tunnel type is IPv6.");
		}
		if ($_POST['tunnel-remote-net6'] > 128 || $_POST['tunnel-remote-net6'] < 1) {
			$input_errors[] = gettext("The GRE tunnel subnet must be an integer between 1 and 128.");
		}
	}

	foreach ($a_gres as $gre) {
		if (isset($id) && ($a_gres[$id]) && ($a_gres[$id] === $gre)) {
			continue;
		}

		if (($gre['if'] == $_POST['if']) && (($gre['tunnel-remote-addr'] == $_POST['tunnel-remote-addr']) || ($gre['tunnel-remote-addr6'] == $_POST['tunnel-remote-addr6']))) {
			$input_errors[] = sprintf(gettext("A GRE tunnel with the network %s is already defined."), $gre['remote-network']);
			break;
		}
	}

	if (!$input_errors) {
		$gre = array();
		$gre['if'] = $_POST['if'];
		$gre['tunnel-local-addr4'] = $_POST['tunnel-local-addr4'];
		$gre['tunnel-remote-addr4'] = $_POST['tunnel-remote-addr4'];
		$gre['tunnel-local-addr6'] = $_POST['tunnel-local-addr6'];
		$gre['tunnel-remote-addr6'] = $_POST['tunnel-remote-addr6'];
		$gre['tunnel-remote-net4'] = $_POST['tunnel-remote-net4'];
		$gre['tunnel-remote-net6'] = $_POST['tunnel-remote-net6'];
		$gre['tunnel-type'] = $_POST['tunnel-type'];
		$gre['remote-addr'] = $_POST['remote-addr'];
		$gre['descr'] = $_POST['descr'];
		if (isset($_POST['link1']) && $_POST['link1']) {
			$gre['link1'] = '';
		}
		if (isset($_POST['link3']) && $_POST['link3']) {
			$gre['link3'] = '';
		}
		$gre['greif'] = $_POST['greif'];

		$gre['greif'] = interface_gre_configure($gre);
		if ($gre['greif'] == "" || !stristr($gre['greif'], "gre")) {
			$input_errors[] = gettext("Error occurred creating interface, please retry.");
		} else {
			if (isset($id) && $a_gres[$id]) {
				$a_gres[$id] = $gre;
			} else {
				$a_gres[] = $gre;
			}

			write_config();

			$confif = convert_real_interface_to_friendly_interface_name($gre['greif']);

			if ($confif != "") {
				interface_configure($confif);
			}

			header("Location: interfaces_gre.php");
			exit;
		}
	}
}

function build_parent_list() {
	$parentlist = array();
	$portlist = get_possible_listen_ips();
	foreach ($portlist as $ifn => $ifinfo) {
		$parentlist[$ifn] = $ifinfo;
	}

	return($parentlist);
}

$pgtitle = array(gettext("Interfaces"), gettext("GREs"), gettext("Edit"));
$pglinks = array("", "interfaces_gre.php", "@self");
$tunnelTypes = array('v4' => gettext('IPv4'), 'v6' => gettext('IPv6'), 'v4v6' => gettext('IPv4+IPv6'));
$shortcut_section = "interfaces";
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}

$form = new Form();

$section = new Form_Section('GRE Configuration');

$section->addInput(new Form_Select(
	'if',
	'*Parent Interface',
	$pconfig['if'],
	build_parent_list()
))->setHelp('This interface serves as the local address to be used for the GRE tunnel.');

$section->addInput(new Form_IpAddress(
	'remote-addr',
	'*GRE Remote Address',
	$pconfig['remote-addr']
))->setHelp('Peer address where encapsulated GRE packets will be sent.');

$section->addInput(new Form_Select(
	'tunnel-type',
	'Tunnel Address Family',
	$pconfig['tunnel-type'],
	$tunnelTypes
));

$group = new Form_Group(gettext('IPv4'));

$group->add(new Form_IpAddress(
	'tunnel-local-addr4',
	'*GRE tunnel local address',
	$pconfig['tunnel-local-addr4']
))->setHelp('Local GRE tunnel endpoint.');

$group->add(new Form_IpAddress(
	'tunnel-remote-addr4',
	'*GRE tunnel remote address',
	$pconfig['tunnel-remote-addr4']
))->setHelp('Remote GRE address endpoint.');

$group->add(new Form_Select(
	'tunnel-remote-net4',
	'*GRE tunnel subnet',
	$pconfig['tunnel-remote-net4'],
	array_combine(range(32, 1, -1), range(32, 1, -1))
))->setHelp('The subnet is used for determining the network that is tunnelled.');

$group->add(new Form_Checkbox(
	'link1',
	'Add Static Route',
	'Add an explicit static route for the remote inner tunnel address/subnet via the local tunnel address',
	$pconfig['link1']
));

$group->addClass('v4conf');
$section->add($group);

$group = new Form_Group(gettext('IPv6'));

$group->add(new Form_IpAddress(
	'tunnel-local-addr6',
	'*GRE tunnel local v6 address',
	$pconfig['tunnel-local-addr6']
))->setHelp('Local v6 GRE tunnel endpoint.');

$group->add(new Form_IpAddress(
	'tunnel-remote-addr6',
	'*GRE tunnel remote v6 address',
	$pconfig['tunnel-remote-addr6']
))->setHelp('Remote v6 GRE address endpoint.');

$group->add(new Form_Select(
	'tunnel-remote-net6',
	'*GRE tunnel v6 subnet',
	$pconfig['tunnel-remote-net6'],
	array_combine(range(128, 1, -1), range(128, 1, -1))
))->setHelp('The subnet is used for determining the v6 network that is tunnelled.');

$group->add(new Form_Checkbox(
	'link3',
	'Add Static Route',
	'Add an explicit static route for the remote inner tunnel address/subnet via the local tunnel address',
	$pconfig['link3']
));

$group->addClass('v6conf');
$section->add($group);

$section->addInput(new Form_Input(
	'descr',
	'Description',
	'text',
	$pconfig['descr']
))->setHelp('A description may be entered here for administrative reference (not parsed).');

$form->addGlobal(new Form_Input(
	'greif',
	null,
	'hidden',
	$pconfig['greif']
));

if (isset($id) && $a_gres[$id]) {
	$form->addGlobal(new Form_Input(
		'id',
		null,
		'hidden',
		$id
	));
}

$form->add($section);
print($form);
?>

<script type="text/javascript">
//<![CDATA[
events.push(function() {

	function proto_change() {
		var active_v4 = (jQuery.inArray($('#tunnel-type :selected').val(), ['v4', 'v4v6']) != -1);
		var active_v6 = (jQuery.inArray($('#tunnel-type :selected').val(), ['v6', 'v4v6']) != -1);
		hideClass('v4conf', !active_v4);
		hideClass('v6conf', !active_v6);
	}

	proto_change();

	$('#tunnel-type').on('change', function() {
		proto_change();
	});
});
//]]>
</script>

<?php
include("foot.inc");
