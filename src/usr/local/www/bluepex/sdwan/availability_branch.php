<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("openvpn.inc");
require_once("interfaces.inc");
require_once("util.inc");
require_once("service-utils.inc");
require_once("pkg-utils.inc");

init_config_arr(array('openvpn', 'openvpn-server'));

$a_server = &$config['openvpn']['openvpn-server'];

$input_errors = array();
if (!isset($config['installedpackages']['openbgpdgroups']['config'])) {
	$config['installedpackages']['openbgpdgroups']['config'] = array();
}
$bgpd_groups = &$config['installedpackages']['openbgpdgroups']['config'];
if (!isset($config['installedpackages']['openbgpd']['config'][0])) {
	$config['installedpackages']['openbgpd']['config'][0] = array();
}
$bgpd_config = &$config['installedpackages']['openbgpd']['config'][0];
if (!isset($config['installedpackages']['openbgpdneighbors']['config'])) {
	$config['installedpackages']['openbgpdneighbors']['config'] = array();
}
$bgpd_neighbors = &$config['installedpackages']['openbgpdneighbors']['config'];

if (is_package_installed("frr"))
	require_once("/usr/local/pkg/frr/inc/frr_bgp.inc");
if (!is_array($config['openvpn']['openvpn-server']))
	$config['openvpn']['openvpn-server'] = array();

if (isset($_GET['act'], $_GET['file'])) {
	$act = $_GET['act'];
	$filename = $_GET['file'];

	if (!file_exists($filename))
		$input_errors[] = sprintf(dgettext("SD-WAN", gettext("The file '%s' not exists!")), $filename);
	if (!check_integrity_xml_file($filename))
		$input_errors[] = sprintf(dgettext("SD-WAN", gettext("The file '%s' is corrupted!")), $filename);

	if (empty($input_errors) && $act == "edit") {
		$branch_config = parse_xml_config("{$filename}", $g["xml_rootobj"]);
		if (isset($branch_config['post_data'])) {
			$_POST = json_decode(base64_decode($branch_config['post_data']), true);
		} else {
			$input_errors[] = dgettext("SD-WAN", gettext("Could not to edit the file!"));
		}
	} elseif (empty($input_errors) && $act == "download") {
		download_xml_file($filename);
	} elseif (empty($input_errors) && $act == "del") {
		if ($filename != "template_example.xml") {
			unlink_if_exists($filename);
			$savemsg = sprintf(dgettext("SD-WAN", gettext("File '%s' removed successfully!")), $filename);
		}
	}
}

if ($_POST['load'] || $_POST['amount_vpn'])
	$total_vpn = $_POST['amount_vpn'];

if (isset($_POST['save']) && !isset($_GET['act'])) {
	$act = $_POST['save'];
	$conf = array();
	// Set the POST data to edit the conf after generate it
	$conf['post_data'] = base64_encode(json_encode($_POST));

	if ($act == "Update") {
		$branch_config = parse_xml_config("{$_POST['filename']}", $g["xml_rootobj"]);
		// Remove OpenVPN Servers
		if (isset($branch_config['openvpn-server'])) {
			foreach ($branch_config['openvpn-server'] as $server)
				del_openvpn_server($server['vpnid']);
		}
		// Remove Groups and neighbors
		if (isset($_POST['asremote']))
			del_group_neighbors_by_asnumber($_POST['asremote']);
	}
	for ($i = 1; $i <= $_POST['amount_vpn']; $i++) {
		$tunnel_network = $_POST['tunnel_network'][$i] . '/' . $_POST['tunnel_network_mask'][$i];
		$local_network  = $_POST['local_network'][$i] . '/' . $_POST['local_network_mask'][$i];
		$remote_network = $_POST['remote_network'][$i] . '/' . $_POST['remote_network_mask'][$i];

		if ($result = openvpn_validate_cidr($tunnel_network, dgettext('SD-WAN', gettext('Tunnel network')) . $i))
			$input_errors[] = $result;
		if ($result = openvpn_validate_cidr($remote_network, dgettext('SD-WAN', gettext('Remote network')) . $i))
			$input_errors[] = $result;
		if ($result = openvpn_validate_cidr($local_network, dgettext('SD-WAN', gettext('Local network')) . $i))
			$input_errors[] = $result;

		if (empty($input_errors)) {
			$server = array();
			$client = array();
			$shared_key = base64_encode(openvpn_create_key());
			$vpnid = openvpn_vpnid_next();

			// Generate OpenVPN Server
			$server['vpnid'] = $vpnid;
			$server['mode'] = "p2p_shared_key";
			$server['protocol'] = "UDP";
			$server['dev_mode'] = "tun";
			list($server['interface'], $server['ipaddr']) = explode ("|",$_POST['interface'][$i]);
			$server['local_port'] = openvpn_port_next("UDP");
			$server['description'] = $_POST['description'];
			$server['shared_key'] = $shared_key;
			$server['crypto'] = "BF-CBC";
			$server['engine'] = "none";
			$server['tunnel_network'] = $tunnel_network;
			$server['remote_network'] = $remote_network;
			$server['local_network'] = $local_network;
			$server['compression'] = "yes";
			$server['pool_enable'] = "yes";

			$conf["openvpn-server"][] = array("vpnid" => $server['vpnid']);
			openvpn_resync('server', $server);
			$a_server[] = $server;

			// Generate BGPD Neighbor of Server
			$neighbor = explode("/", $tunnel_network);
			$neighbor_ip = ip_after($neighbor[0]);
			$openbgpd_neighbor_server = array(
				"descr" => $_POST['description'].$i,
				"neighbor" => $neighbor_ip,
				"groupname" => $_POST['description'],
				"row" => array(
					array("parameters" => "announce all"), 
					array("parameters" => "local-address", "parmvalue" => $neighbor[0]), 
					array("parameters" => "set metric", "parmvalue" => $i*5)
				)
			);
			$bgpd_neighbors[] = $openbgpd_neighbor_server;

			$server_addr = get_interface_ip($server['interface']);
			$tunnel_local = explode("/", $server['tunnel_network']);
			$tunnel_network_remote = ip_after($tunnel_local[0]);

			// Generate OpenVPN Client
			$client["vpnid"] = $vpnid+1;
			$client["protocol"] = "UDP";
			$client["dev_mode"] = "tun";
			$client["interface"] = $server['interface'];
			$client["local_port"] = $server['local_port'];
			$client["server_addr"] = $server_addr;
			$client["server_port"] = $server['local_port'];
			$client["proxy_authtype"] = "none";
			$client["description"] = "Matriz_".$server['description'];
			$client["mode"] = "p2p_shared_key";
			$client["shared_key"] = $shared_key;
			$client["crypto"] = "BF-CBC";
			$client["engine"] = "none";
			$client["tunnel_network"] = $tunnel_network_remote."/30";
			$client["remote_network"] = $server['local_network'];
			$client["compression"] = "yes";

			$conf["openvpn-client"][] = $client;

			// Generate BGPD Neighbor of Client
                        $neighbor = explode("/", $tunnel_network);
			$openbgpd_neighbor_client = array(
				"descr" => "Matriz".$i,
				"neighbor" => $neighbor[0],
				"groupname" => "Matriz",
				"row" => array(
					array("parameters" => "announce all"),
					array("parameters" => "local-address", "parmvalue" => ip_after($neighbor[0])),
					array("parameters" => "set metric", "parmvalue" => $i*5)
				)
			);
			$conf['installedpackages']['openbgpdneighbors']['config'][] = $openbgpd_neighbor_client;
			unset($openbgpd_neighbor_server, $openbgpd_neighbor_client, $server, $client);
		}
	}
	if (empty($input_errors)) {
		// Generate BGPD Config Server
		if (isset($_POST['asnumber'])) {
			$bgpd_config['asnum'] = $_POST['asnumber'];
		}
		$bgpd_config['fibupdate'] = "yes";
		if (isset($_POST['network'])) {
			$bgpd_config['row'] = array(array("networks" => $_POST['network'] . '/' . $_POST['network_mask']));
		}

		// Generate BGPD Group Server
		$bgpd_groups[] = array(
			"name" => $_POST['description'],
			"remoteas" => $_POST['asremote'],
			"descr" => $_POST['description']
		);

		// Generate BGPD Config Client
		$bgpd_config_client = array();
		$bgpd_config_client['asnum'] = $_POST['asremote'];
		$bgpd_config_client['fibupdate'] = "yes";
		$bgpd_config_client['row'] = array(array("networks" => ""));

		// Generate BGPD Group Client
		$bgpd_groups_client = array();
		$bgpd_groups_client['name'] = "Matriz";
		$bgpd_groups_client['remoteas'] = !empty($_POST['asnumber']) ? $_POST['asnumber'] : $bgpd_config['asnum'];
		$bgpd_groups_client['descr'] = "Matriz";

		$conf['installedpackages']['openbgpd']['config'][0] = $bgpd_config_client;
		$conf['installedpackages']['openbgpdgroups']['config'][] = $bgpd_groups_client;

		write_config(dgettext("SD-WAN", "SD-WAN: Writing the settings..."));
		openbgpd_install_conf();
		restart_service("frr");

		// Generate Client Config XML
		$xmlconfig = dump_xml_config($conf, $g['xml_rootobj']);
		if ($xmlconfig) {
                        $filename = $_POST['description'] . ".xml";
			file_put_contents($filename, $xmlconfig);
			if ($act == "Update") {
				$savemsg = dgettext("SD-WAN", gettext("The client config XML updated successfully!"));
			} else {
				$savemsg = dgettext("SD-WAN", gettext("The client config XML generated successfully!"));
			}
			set_flash_message("success", $savemsg);
			pfSenseHeader($_SERVER['REQUEST_URI']);
			exit;
		}
	}
}

function download_xml_file($filename) {
	if (!file_exists($filename) || !check_integrity_xml_file($filename))
		return;

	$xml_content = file_get_contents($filename);
	$size = strlen($xml_content);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$filename}");
	header("Content-Length: {$size}");
	if (isset($_SERVER['HTTPS'])) {
		header('Pragma: ');
		header('Cache-Control: ');
	} else {
		header("Pragma: private");
		header("Cache-Control: private, must-revalidate");
	}
	echo $xml_content;
	exit;
}

function del_openvpn_server($vpnid) {
	global $a_server;
	if (!is_array($a_server))
		return;
	foreach ($a_server as $idx => $server) {
		if ($server['vpnid'] == $vpnid) {
			unset($a_server[$idx]);
			return;
		}
	}
}

function del_group_neighbors_by_asnumber($as_number) {
	global $bgpd_groups, $bgpd_neighbors;

	if (!is_array($bgpd_groups))
		return;

	foreach ($bgpd_groups as $idx_group => $group) {
		if ($group['remoteas'] != $as_number)
			continue;
		unset($bgpd_groups[$idx_group]);
		foreach ($bgpd_neighbors as $idx_neighbor => $neighbor) {
			if ($neighbor['groupname'] == $group['name'])
				unset($bgpd_neighbors[$idx_neighbor]);
		}
		return;
	}
}

function check_integrity_xml_file($file) {
	if (!file_exists($file))
		return;
	$gc = exec("/usr/local/bin/xmllint --noout " . $file);
	if ($err == 0)
		return true;
}

$pgtitle = array(dgettext("SD-WAN", gettext("SD-WAN")), dgettext("SD-WAN", gettext("Server")));
include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box_np($savemsg);

$tab_array = array();
$tab_array[] = array(dgettext("SD-WAN", gettext("Routes Status")), false, "availability_branch_status.php");
$tab_array[] = array(dgettext("SD-WAN", gettext("Server")), true, "availability_branch.php");
$tab_array[] = array(dgettext("SD-WAN", gettext("Client XML Import")), false, "availability_branch_import.php");
display_top_tabs($tab_array);

if (is_package_installed("frr")) {
	$form = new Form(false);
	$form->setAction("availability_branch.php");

	$section = new Form_Section(dgettext("SD-WAN", gettext("Settings")));

	$group = new Form_Group(gettext("Enter with Amount VPN"));
	$group->add(new Form_Select(
		'amount_vpn',
		'',
		$total_vpn,
		array_combine(range(2,20,2), range(2,20,2))
	))->setHelp(gettext('Enter the amount VPN.'))->setWidth(2);
	$group->add(new Form_Button(
		'load',
		dgettext('SD-WAN', gettext('Load'))
	))->removeClass('btn-primary')->addClass("btn-sm btn-success");

	$section->add($group);

	$files_generated = array();
	foreach (glob("*.xml") as $file) {
		if ($file == "." && $file == "..")
			continue;
		if ($file == "template_example.xml") {
			$files_generated[] = "<span class='badge'>{$file} <a href='availability_branch.php?act=edit&file={$file}' title=".dgettext('SD-WAN', gettext('Edit'))."><i class='fa fa-edit'></i></a> <a href='availability_branch.php?act=download&file={$file}' title='Download'><i class='fa fa-download'></i></a></span>";
		} else {
			$files_generated[] = "<span class='badge'>{$file} <a href='availability_branch.php?act=edit&file={$file}' title=".dgettext('SD-WAN', gettext('Edit'))."><i class='fa fa-edit'></i></a> <a href='availability_branch.php?act=download&file={$file}' title='Download'><i class='fa fa-download'></i></a> <a href='availability_branch.php?act=del&file={$file}' title=".dgettext('SD-WAN', gettext('Delete'))."><i class='fa fa-trash'></i></a></span>";
		}
	}
	if (!empty($files_generated)) {
		$section->addInput(new Form_StaticText( 
			gettext('Generated Files'),
			implode(" ", $files_generated)
		));
	}

	if (isset($total_vpn)) {
		$section->addInput(new Form_Input(
			'asnumber',
			dgettext('SD-WAN', gettext('Local AS Number')),
			'text',
			(isset($_POST['asnumber']) ? $_POST['asnumber'] : $bgpd_config['asnum'])
		))->setHelp(gettext('Enter with AS (Autonomous Systems) number. (eg. 65000).'))->setWidth(2);

		if (!isset($bgpd_config['row'][0]['networks']) || empty($bgpd_config['row'][0]['networks'])) {
			$ipaddress = isset($_POST['network']) ? $_POST['network'] : '';
			$mask = isset($_POST['network_mask']) ? $_POST['network_mask'] : "24";
			$section->addInput(new Form_IpAddress(
				'network',
				dgettext('SD-WAN', gettext('Local Network')),
				$ipaddress
			))->setPattern('[.a-zA-Z0-9_]+')->addMask('network_mask', $mask)->setWidth('5')->setHelp(dgettext('SD-WAN', gettext('Enter with local network.')));
		}

		$section->addInput(new Form_Input(
			'description',
			dgettext('SD-WAN', gettext('Filial name')),
			text,
			$_POST['description']
		))->setHelp(dgettext('SD-WAN', gettext('You may enter a Filial Name here for your reference (not parsed).')));

		$section->addInput(new Form_Input(
			'asremote',
			dgettext('SD-WAN', gettext('Remote AS Number')),
			'text',
			$_POST['asremote'],
			((isset($_GET['act']) && $_GET['act'] == "edit") ? ["readonly" => "readonly"] : [])
		))->setHelp(dgettext('SD-WAN', gettext('Enter with AS (Autonomous Systems) Remote Number. (eg. 65000).')))->setWidth(2);

		$form->add($section);

		$interfaces = openvpn_build_if_list();
		for ($x = 1; $x <= $total_vpn; $x++) {
			$section = new Form_Section(gettext('Tunnel Settings') . ' ' . $x);

			$iface_selected = array();
			foreach ($interfaces as $iface => $ifacename) {
				if ($iface == $_POST['interface'][$x])
					$iface_selected[$iface] = $ifacename;
			}
			$section->addInput(new Form_Select(
				'interface[' . $x . ']',
				dgettext('SD-WAN', gettext('Interface')),
				$iface_selected,
				$interfaces
			))->setHelp(dgettext('SD-WAN', gettext('Select the interface for this tunnel.')))->setWidth(2);

			$ipaddress = isset($_POST['tunnel_network'][$x]) ? $_POST['tunnel_network'][$x] : '';
			$mask = isset($_POST['tunnel_network_mask'][$x]) ? $_POST['tunnel_network_mask'][$x] : "30";
			$section->addInput(new Form_IpAddress(
				'tunnel_network[' . $x . ']',
				dgettext('SD-WAN', gettext('Tunnel Network')),
				$ipaddress
			))->setPattern('[.a-zA-Z0-9_]+')->addMask('tunnel_network_mask[' . $x . ']', $mask, 30, 30)->setWidth('5')->setHelp(dgettext('SD-WAN', gettext('This is the virtual network used for private communications between this server and client hosts expressed using CIDR (eg. 172.18.2.1/30).')));

			$ipaddress = isset($_POST['local_network'][$x]) ? $_POST['local_network'][$x] : '';
			$mask = isset($_POST['local_network_mask'][$x]) ? $_POST['local_network_mask'][$x] : "24";
			$section->addInput(new Form_IpAddress(
				'local_network[' . $x . ']',
				dgettext('SD-WAN', gettext('Local Network (Virtual)')),
				$ipaddress
			))->setPattern('[.a-zA-Z0-9_]+')->addMask('local_network_mask[' . $x . ']', $mask)->setWidth('5')->setHelp(dgettext('SD-WAN', gettext('This is the network that will be accessible from the remote endpoint. Expressed as a CIDR range.')));

			$ipaddress = isset($_POST['remote_network'][$x]) ? $_POST['remote_network'][$x] : '';
			$mask = isset($_POST['remote_network_mask'][$x]) ? $_POST['remote_network_mask'][$x] : "24";
			$section->addInput(new Form_IpAddress(
				'remote_network[' . $x . ']',
				dgettext('SD-WAN', gettext('Remote Network (Virtual)')),
				$ipaddress
			))->setPattern('[.a-zA-Z0-9_]+')->addMask('remote_network_mask[' . $x . ']', $mask)->setWidth('5')->setHelp(dgettext('SD-WAN', gettext('This is a network that will be routed through the tunnel, so that a site-to-site VPN can be established without manually changing the routing tables.')));

			$form->add($section);
		}
		$form->addGlobal(new Form_Button(
			'save',
			dgettext('SD-WAN', gettext('Generate'))
		));
		if (isset($_GET['act']) && $_GET['act'] == "edit") {
			$form->addGlobal(new Form_Input(
				'filename',
				dgettext('SD-WAN', gettext('Filename')),
				'hidden',
				$filename
			));
			$form->addGlobal(new Form_Button(
				'save',
				dgettext('SD-WAN', gettext('Update'))
			))->removeClass('btn-primary')->addClass('btn-success');
		}
	} else {
		$form->add($section);
	}
	print $form;
} else {
	echo "<h3 class='text-center'>" . sprintf(dgettext("SD-WAN", gettext("Frr package is not installed! %s")), "<a href=\"../pkg_mgr_install.php?pkg=BluePexUTM-pkg-frr\">" . dgettext('SD-WAN', gettext('Click here to install package.')) . "</a>") . "</h3>";
}
?>
<script type="text/javascript">
events.push(function(){
		$('.badge a').tooltip();
	});
</script>
<?php include("foot.inc");
