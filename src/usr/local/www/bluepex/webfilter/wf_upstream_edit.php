<?php
/*
 *====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Mariana Amorim <mariana.souza@bluepex.com>, 2016
 *
 * ====================================================================
 */

require_once("guiconfig.inc");
require_once("classes/Form.class.php");
require_once("squid.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidremote']['config'])) {
	$config['system']['webfilter']['squidremote']['config'] = array();
}

$rule = &$config['system']['webfilter']['squidremote']['config'];
$rule_edit = array();

if (isset($_GET['act']) && $_GET['act'] == "edit") {
	if (isset($_GET['id']) && is_numeric($_GET['id'])) {
		$rule_id = $_GET['id'];
		$rule_edit = $rule[$rule_id];
	} else {
		set_flash_message("error", dgettext("BluePexWebFilter", "Select the Upstream Server to edit it."));
		header("Location: /webfilter/wf_upstream.php");
		exit;
	}
}

$allowmiss = array(
	"allow-miss" => dgettext('BluePexWebFilter', 'Allow Miss'),
	"no-tproxy"  => dgettext('BluePexWebFilter', 'No-Tproxy'),
	"proxy-only" => dgettext('BluePexWebFilter', 'Proxy Only')
);

$hierarchy = array(
	"parent" => dgettext('BluePexWebFilter', 'Parent'),
	"sibling" =>  dgettext('BluePexWebFilter', 'Sibling'),
	"multicast" => dgettext('BluePexWebFilter', 'Multicast')
);

$peermethod = array(
	"round-robin" => dgettext('BluePexWebFilter', 'Round-Robin'),
	"default" => dgettext('BluePexWebFilter', 'Default'),
	"tweighted-round-robin" =>  dgettext('BluePexWebFilter', 'Tweighted-Round-Robin'),
	"carp" => dgettext('BluePexWebFilter', 'Carp'),
	"userhash" => dgettext('BluePexWebFilter', 'Userhash'),
	"sourcehash" =>  dgettext('BluePexWebFilter', 'Sourcehash'),
	"multicast-sibling" => dgettext('BluePexWebFilter', 'Multicast-Sibling')
);

if (isset($_POST['save'])) {

	$new_rule = array();
	$new_rule['enable'] = isset($_POST['enable']) ? "on" : "off";
	$new_rule['proxyaddr'] = $_POST['proxyaddr'];
	$new_rule['proxyname'] = $_POST['proxyname'];
	$new_rule['proxyport'] = $_POST['proxyport'];
	$new_rule['connecttimeout'] = $_POST['connecttimeout'];
	$new_rule['connectfailLimit'] = $_POST['connectfailLimit'];
	$new_rule['maxconn'] = $_POST['maxconn'];
	$new_rule['allowmiss'] = isset($_POST['allowmiss']) ? implode(",", $_POST['allowmiss']) : "" ;
	$new_rule['hierarchy'] = $_POST['hierarchy'];
	$new_rule['peermethod'] = $_POST['peermethod'];
	$new_rule['weight'] = $_POST['weight'];
	$new_rule['basetime'] = $_POST['basetime'];
	$new_rule['ttl'] = $_POST['ttl'];
	$new_rule['nodelay'] = isset($_POST['nodelay']) ? "on" : "off"; 
	$new_rule['icpport'] = $_POST['icpport'];
	$new_rule['icpoptions'] = $_POST['icpoptions'];
	$new_rule['username'] = $_POST['username'];
	$new_rule['password'] = $_POST['password'];
	$new_rule['authoption'] = $_POST['authoption'];

	if (isset($_POST['rule_id'])) {
		$rule[$_POST['rule_id']] = $new_rule;
	} else {
		$rule[] = $new_rule;
	}

	squid_validate_upstream($_POST,$input_errors);

	if (empty($input_errors)) {
		$savemsg = dgettext("BluePexWebFilter", "Upstream Proxy inserted successfully!");
		set_flash_message("success", $savemsg);
		write_config($savemsg);
		squid_resync_upstream();
		header("Location: /webfilter/wf_upstream.php");
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Upstream Proxy'));

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors); 
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_server.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Upstream Proxy'), true, '/webfilter/wf_upstream.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Cache Mgmt'), false, '/webfilter/wf_cache_mgmt.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), false, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), false, '/webfilter/wf_traffic_mgmt.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'General Settings'));

$section->addInput(new Form_Checkbox(
	'enable',
	'Enable',
	dgettext("BluePexWebFilter", 'This option enables the proxy server to forward requests to an upstream/neighbor server.'),
	(isset($rule_edit['enable']) && $rule_edit['enable'] == "on"),
	'on'
));

$section->addInput(new Form_Input(
	'proxyaddr',
	'Hostname',
	'text',
	(isset($rule_edit['proxyaddr']) ? $rule_edit['proxyaddr'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter here the IP address or host name of the upstream proxy.'));

$section->addInput(new Form_Input(
	'proxyname',
	dgettext('BluePexWebFilter', 'Name'),
	'text',
	(isset($rule_edit['proxyname']) ? $rule_edit['proxyname'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Unique name for the peer.Required if you have multiple peers on the same host but different ports.'));

$section->addInput(new Form_Input(
	'proxyport',
	dgettext('BluePexWebFilter', 'TCP Port'),
	'text',
	(isset($rule_edit['proxyport']) ? $rule_edit['proxyport'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter the port to use to connect to the upstream proxy.'));

$section->addInput(new Form_Input(
	'connecttimeout',
	dgettext('BluePexWebFilter', 'Timeout'),
	'text',
	(isset($rule_edit['connecttimeout']) ? $rule_edit['connecttimeout'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'A peer-specific connect timeout. Also see the peer_connect_timeout directive.'));

$section->addInput(new Form_Input(
	'connectfailLimit',
	dgettext('BluePexWebFilter', 'Fail Limit'),
	'text',
	(isset($rule_edit['connectfailLimit']) ? $rule_edit['connectfailLimit'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'How many times connecting to a peer must fail before it is marked as down. Default is 10.'));

$section->addInput(new Form_Input(
	'maxconn',
	dgettext('BluePexWebFilter', 'Max'),
	'text',
	(isset($rule_edit['maxconn']) ? $rule_edit['maxconn'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Limit the amount of connections WF may open to this peer.'));

$section->addInput(new Form_Select(
	'allowmiss',
	dgettext('BluePexWebFilter', 'Allow Miss'),
	(isset($rule_edit['allowmiss']) ? explode(",", $rule_edit['allowmiss']) : ""),
	$allowmiss,
	true
))->setHelp(dgettext('BluePexWebFilter', "<b>Allow-miss</b> - Disable Squid's use of only-if-cached when forwarding requests to siblings.<br/>").
	dgettext('BluePexWebFilter', "This is primarily useful when icp_hit_stale is used by the sibling.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>No-tproxy</b> - Do not use the client-spoof TPROXY support when forwarding requests to this peer.").
	dgettext('BluePexWebFilter', "Use normal address selection instead. <br/>").
	dgettext('BluePexWebFilter', "<br/><b>Proxy-only</b> - Objects fetched from the peer will not be stored locally."));

$form->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'Peer settings'));

$section->addInput(new Form_Select(
	'hierarchy',
	dgettext('BluePexWebFilter', 'Hierarchy'),
	(isset($rule_edit['hierarchy']) ? $rule_edit['hierarchy'] : ""),
	$hierarchy
))->setHelp(dgettext('BluePexWebFilter', 'Specify remote caches hierarchy.'));

$section->addInput(new Form_Select(
	'peermethod',
	dgettext('BluePexWebFilter', 'Select method'),
	(isset($rule_edit['peermethod']) ? $rule_edit['peermethod'] : ""),
	$peermethod
))->setHelp(dgettext('BluePexWebFilter', "The default peer selection method is ICP, with the first responding peer being used as source. These options can be used for better load balancing.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>default</b> - This is a parent cache which can be used as a 'last-resort' if a peer cannot be located by any of the peer-selection methods.<br/>").
	dgettext('BluePexWebFilter', "If specified more than once, only the first is used.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>round-robin</b> - Load-Balance parents which should be used in a round-robin fashion in the absence of any ICP queries.<br/>").
	dgettext('BluePexWebFilter', "weight=N can be used to add bias.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>weighted-round-robin</b> - Load-Balance parents which should be used in a round-robin fashion with the frequency of each parent being based on the round trip time.<br/>").
	dgettext('BluePexWebFilter', "Closer parents are used more often. Usually used for background-ping parents. weight=N can be used to add bias.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>carp</b> - Load-Balance parents which should be used as a CARP array. The requests will be distributed among the parents based on the CARP load balancing hash function based on their weight.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>userhash</b> - Load-balance parents based on the client proxy_auth or ident username.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>sourcehash</b> - Load-balance parents based on the client source IP.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>multicast-siblings</b> - To be used only for cache peers of type 'multicast'.<br/>").
	dgettext('BluePexWebFilter', "ALL members of this multicast group have 'sibling' relationship with it, not 'parent'. This is to a multicast group when the requested object would be fetched only from a 'parent' cache, anyway.<br/>").
	dgettext('BluePexWebFilter', "It's useful, e.g., when configuring a pool of redundant WF proxies, being members of the same multicast group."));

$section->addInput(new Form_Input(
	'weight',
	dgettext('BluePexWebFilter', 'Weight'),
	'text',
    	(isset($rule_edit['weight']) ? $rule_edit['weight'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Use to affect the selection of a peer during any weighted peer-selection mechanisms. The weight must be an integer; default is 1,larger weights are favored more.'));

$section->addInput(new Form_Input(
    	'basetime',
    	dgettext('BluePexWebFilter', 'Basetime'),
    	'text',
	(isset($rule_edit['basetime']) ? $rule_edit['basetime'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Specify a base amount to be subtracted from round trip times of parents.<br/>').
	dgettext('BluePexWebFilter', 'It is subtracted before division by weight in calculating which parent to fectch from. If the rtt is less than the base time the rtt is set to a minimal value.'));

$section->addInput(new Form_Input(
    	'ttl',
    	'TTL',
    	'text',
    	(isset($rule_edit['ttl']) ? $rule_edit['ttl'] : "")
))->setHelp(dgettext('BluePexWebFilter', "Specify a TTL to use when sending multicast ICP queries to this address<br/>").
	dgettext('BluePexWebFilter', "Only useful when sending to a multicast group. Because we don't accept ICP replies from random hosts, you must configure other group members as peers with the 'multicast-responder' option."));

$section->addInput(new Form_Checkbox(
    	'nodelay',
    	dgettext('BluePexWebFilter', 'No-Delay'),
    	dgettext("BluePexWebFilter", 'To prevent access to this neighbor from influencing the delay pools.'),
    	(isset($rule_edit['nodelay']) && $rule_edit['nodelay'] == "on"),
    	'on'
));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'ICP Settings'));

$section->addInput(new Form_Input(
    	'icpport',
    	dgettext('BluePexWebFilter', 'ICP Port'),
    	'text',
    	(isset($rule_edit['icpport']) ? $rule_edit['icpport'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter the port to connect to the upstream proxy for the ICP protocol. Use port number 7 to disable ICP communication between the proxies.'));

$section->addInput(new Form_Select(
	'icpoptions',
    	dgettext('BluePexWebFilter', 'ICP Options'),
    	$pconfig['icpoptions'],
    	array('no-query','multicast-responder','closest-only','background-ping')
))->setHelp(dgettext('BluePexWebFilter', "You MUST also set icp_port and icp_access explicitly when using these options.<br/>").
	dgettext('BluePexWebFilter', "The defaults will prevent peer traffic using ICP<br/>").
	dgettext('BluePexWebFilter', "<br/><b>no-query</b> -	Disable ICP queries to this neighbor.").
	dgettext('BluePexWebFilter', "<br/><b><br/>multicast-responder</b>Indicates the named peer is a member of a multicast group.<br/>").
	dgettext('BluePexWebFilter', "ICP queries will not be sent directly to the peer, but ICP replies will be accepted from it.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>closest-only</b> - Indicates that, for ICP_OP_MISS replies, we'll only forward CLOSEST_PARENT_MISSes and never FIRST_PARENT_MISSES.<br/>").
	dgettext('BluePexWebFilter', "<br/><b>background-ping</b> - To only send ICP queries to this neighbor infrequently.<br/>").
	dgettext('BluePexWebFilter', "This is used to keep the neighbor round trip time updated and is usually used in conjunction with weighted-round-robin."));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Auth Settings'));

$section->addInput(new Form_Input(
    	'username',
 		dgettext('BluePexWebFilter',	'Username'),
    	'text',
    	(isset($rule_edit['username']) ? $rule_edit['username'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'If the upstream proxy requires a username, specify it here.'));

$section->addInput(new Form_Input(
    	'password',
    	dgettext('BluePexWebFilter', 'Password'),
    	'password',
    	(isset($rule_edit['password']) ? $rule_edit['password'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'If the upstream proxy requires a password, specify it here.'));

$section->addInput(new Form_Select(
    	'authoption',
    	dgettext('BluePexWebFilter', 'Authentication Options'),
    	$pconfig['authoption'],
    	array('login=*:password','login=user:password','login=PASSTHRU','login=PASS','login=NEGOTIATE','login=NEGOTIATE:principal_name','connection-auth=on','connection-auth=off')
))->setHelp(dgettext('BluePexWebFilter', '<b>login=user:password</b> - If this is a personal/workgroup proxy and your parent requires proxy authentication.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>login=PASSTHRU</b> - Send login details received from client to this peer. Authentication is not required by WF for this to work.<br/>').
	dgettext('BluePexWebFilter', 'This will pass any form of authentication but only Basic auth will work through a proxy unless the connection-auth options are also used.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>login=PASS</b> - Send login details received from client to this peer. Authentication is not required by this option.<br/>').
	dgettext('BluePexWebFilter', 'To combine this with proxy_auth both proxies must share the same user database as HTTP only allows for a single login (one for proxy, one for origin server).<br/>').
	dgettext('BluePexWebFilter', 'Also be warned this will expose your users proxy password to the peer. USE WITH CAUTION.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>login=*:password</b> - Send the username to the upstream cache, but with a fixed password. This is meant to be used when the peer is in another administrative domain, but it is still needed to identify each user.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>login=NEGOTIATE</b> - If this is a personal/workgroup proxy and your parent requires a secure proxy authentication.<br/>').
	dgettext('BluePexWebFilter', 'The first principal from the default keytab or defined by the environment variable KRB5_KTNAME will be used.<br/>').
	dgettext('BluePexWebFilter', 'WARNING: The connection may transmit requests from multiple clients. Negotiate often assumes end-to-end authentication and a single-client. Which is not strictly true here.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>login=NEGOTIATE:principal_name</b>If this is a personal/workgroup proxy and your parent requires a secure proxy authentication.<br>').
	dgettext('BluePexWebFilter', 'The principal principal_name from the default keytab or defined by the environment variable KRB5_KTNAME will be used. </br>').
	dgettext('BluePexWebFilter', 'WARNING: The connection may transmit requests from multiple clients. Negotiate often assumes end-to-end authentication and a single-client. Which is not strictly true here.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>connection-auth=on</b> - Tell WF that this peer does support Microsoft connection oriented authentication, and any such challenges received from there should be ignored.<br/>').
	dgettext('BluePexWebFilter', 'Default is auto to automatically determine the status of the peer.<br/>').
	dgettext('BluePexWebFilter', '<br/><b>connection-auth=off</b> - Tell WF that this peer does not support Microsoft connection oriented authentication, and any such challenges received from there should be ignored.<br/>'.	'Default is auto to automatically determine the status of the peer.'));

if (isset($rule_id)) {
	$section->addInput(new Form_Input(
		"rule_id",
		"",
		"hidden",
		$rule_id
	));
}

$form ->add($section);
print $form;

include("foot.inc");

?>
