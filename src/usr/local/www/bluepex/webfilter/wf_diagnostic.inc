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

require_once("config.inc");
require_once("nf_defines.inc");
require_once("nf_util.inc");
require_once("auth.inc");
require_once("squid.inc");

$match_network = "#^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\/\d{1,2}$#";

init_config_arr(array('system', 'webfilter', 'nf_content_custom', 'element0', 'item'));
init_config_arr(array('system', 'webfilter', 'nf_content_rules', 'element0', 'item'));

$customlist = &$config['system']['webfilter']['nf_content_custom']['element0']['item'];

$rules = &$config['system']['webfilter']['nf_content_rules']['element0']['item'];

function check_webfilter_rules($instance_id, $url, $rule_id) {
	global $wf_instances, $rules;

	$wlist = check_wf_whitelist($instance_id, $url, $rule_id);
	$blist = check_wf_blacklist($instance_id, $url, $rule_id);
	$clist = check_wf_custom_list($instance_id, $url, $rule_id);

	return array(
		"whitelist" => is_array($wlist) ? implode(",", $wlist) : "",
		"blacklist" => is_array($blist) ? implode(",", $blist) : "",
		"customlist" => $clist,
		"descrule" => $rules[$rule_id]['description']
	);
}

function check_squid_rules($instance_id, $url, $ip) {
	global $config, $wf_instances;

	$unrestrictedhosts = array();
	$bannedhosts = array();
	$squid_wlist = array();
	$squid_blist = array();
	$bypass_source = array();
	$bypass_dest = array();

	if (isset($wf_instances[$instance_id]['squidnac']) && !empty($wf_instances[$instance_id]['squidnac'])) {
		$unrestrictedhosts = check_unrestricted_hosts($instance_id, $ip);
		$bannedhosts = check_banned_hosts($instance_id, $ip);
		$squid_wlist = check_squid_whitelist($instance_id, $url);
		$squid_blist = check_squid_blacklist($instance_id, $url);
		$bypass_source = check_bypass_source($instance_id, $ip);
		$bypass_dest = check_bypass_dest($instance_id, $ip);
	}
	return array(
		"unrestricted_hosts" => implode(", ", $unrestrictedhosts),
		"banned_hosts" => implode(", ", $bannedhosts),
		"whitelist" => implode(", ", $squid_wlist),
		"blacklist" => implode(", ", $squid_blist),
		"bypass_source" => implode(", ", $bypass_source),
		"bypass_dest" => implode(", ", $bypass_dest)
	);
}

function check_wf_custom_list($instance_id, $url, $rule_id) {
	global $wf_instances, $customlist, $rules;

	$customlist_array = array();

	if (!isset($rules[$rule_id]) || empty($rules[$rule_id]['custom_lists']) || $rules[$rule_id]['custom_lists'] == "|" ||
	    $rules[$rule_id]['instance_id'] != $instance_id) {
		return $customlist_array;
	}

	$rule = $rules[$rule_id];
	$clist_array = array();

	foreach($customlist as $clist) {
		if (!isset($clist['instance_id']) || $clist['instance_id'] != $instance_id) {
			continue;
		}
		foreach (explode("|", $rule['custom_lists']) as $clist_rule_array) {
			if (!preg_match("#\b{$clist['name']}#", $clist_rule_array))
				continue;

			foreach (explode("\n", base64_decode($clist['urls'])) as $urlcustom) {
				$urlcustom = str_replace(array("*", " "), "", $urlcustom);
				if (preg_match("#\b{$urlcustom}#", $url))
					$clist_array[] = $urlcustom;
			}
			$customlist_array[] = array("name" => $clist['name'], "regex" => implode(", ", $clist_array));
		}
	}
	return $customlist_array;
}

function check_wf_blacklist($instance_id, $url, $rule_id) {
	global $wf_instances, $rules;

	$blist_array = array();
	if (!isset($rules[$rule_id]) || empty($rules[$rule_id]['blacklist']) || $rules[$rule_id]['blacklist'] != "on") {
		return $blist_array;
	}
	if ($wf_instances[$instance_id]['nf_whitelist_blacklist']['blacklist']) {
		$wf_blacklists = explode(" ", base64_decode($wf_instances[$instance_id]['nf_whitelist_blacklist']['blacklist']));
		foreach ($wf_blacklists as $blist) {
			if (trim($blist) == $url)
				$blist_array[] = $blist;
		}
	}
	return $blist_array;
}

function check_wf_whitelist($instance_id, $url, $rule_id) {
	 global $wf_instances, $rules;

	$wlist_array = array();
	if (!isset($rules[$rule_id]) || empty($rules[$rule_id]['whitelist']) || $rules[$rule_id]['whitelist'] != "on") {
		return $wlist_array;
	}
	if ($wf_instances[$instance_id]['nf_whitelist_blacklist']['whitelist']) {
		$wf_whitelists = explode(" ", base64_decode($wf_instances[$instance_id]['nf_whitelist_blacklist']['whitelist']));
		foreach ($wf_whitelists as $blist) {
			if (trim($blist) == $url)
                        	$blist_array[] = $blist;
		}
	}

	return $wlist_array;
}

function check_unrestricted_hosts($instance_id, $ip) {
	global $wf_instances;

	$unrestricted_hosts = array();
	if (!isset($wf_instances[$instance_id]['squidnac']['unrestricted_hosts']) ||
	    empty($wf_instances[$instance_id]['squidnac']['unrestricted_hosts'])) {
		return $unrestricted_hosts;
	}
	foreach (explode("\n", base64_decode($wf_instances[$instance_id]['squidnac']['unrestricted_hosts'])) as $unrestricted) {
		if (preg_match("/\b" . trim($unrestricted) . "/", $ip))
			$unrestricted_hosts[] = $unrestricted;
	}
	return $unrestricted_hosts;
}

function check_banned_hosts($instance_id, $ip) {
	global $wf_instances;

	$banned_hosts = array();
	if (!isset($wf_instances[$instance_id]['squidnac']['banned_hosts']) ||
	    empty($wf_instances[$instance_id]['squidnac']['banned_hosts'])) {
		return $banned_hosts;
	}

	foreach (explode("\n", base64_decode($wf_instances[$instance_id]['squidnac']['banned_hosts'])) as $banned) {
		if (preg_match("/\b" . trim($banned) . "/", $ip)) {
			return $banned_hosts[] = $banned;
		}
	}
	return $banned_hosts;
}

function check_squid_whitelist($instance_id, $url) {
	global $wf_instances;

	$whitelists = array();
	if (!isset($wf_instances[$instance_id]['squidnac']['whitelist']) ||
	    empty($wf_instances[$instance_id]['squidnac']['whitelist'])) {
		return $whitelists;
	}
	foreach (explode("\n", base64_decode($wf_instances[$instance_id]['squidnac']['whitelist'])) as $whitelist) {
		$w_list = trim(str_replace(array("*", " "), "", $whitelist));
		if (preg_match("/\b{$w_list}/", $url)) {
			$whitelists[] = $whitelist;
		}
	}
	return $whitelists;
}

function check_squid_blacklist($instance_id, $url) {
	global $wf_instances;

	$blacklists = array();
	if (!isset($wf_instances[$instance_id]['squidnac']['blacklist']) ||
	    empty($wf_instances[$instance_id]['squidnac']['blacklist'])) {
		return $blacklists;
	}
	foreach (explode("\n", base64_decode($wf_instances[$instance_id]['squidnac']['blacklist'])) as $blacklist) {
		$b_list = trim(str_replace(array("*", " "), "", $blacklist));
		if (preg_match("/\b{$b_list}/", $url)) {
			$blacklists[] = $blacklist;
		}
	}
	return $blacklists;
}

function check_bypass_source($instance_id, $ip) {
	global $config, $match_network, $wf_instances;

	$bypass_source = array();
	if (empty($wf_instances[$instance_id]['server']['defined_ip_proxy_off'])) {
		return $bypass_source;
	}

	$ipsoff_source = explode(";", $wf_instances[$instance_id]['server']['defined_ip_proxy_off']);
	foreach ($ipsoff_source as $source) {
		if (trim($source) == $ip) {
			$bypass_source[] = $ip;
		}
		if (!isset($config['aliases']['alias'])) {
			continue;
		}
		foreach ($config['aliases']['alias'] as $aliases) {
			if ($aliases['type'] != "host" || $aliases['type'] != "network" ||
			    $aliases['name'] != $source) {
				continue;
			}
			foreach (explode(" ", $aliases['address']) as $als) {
				if ($als == $ip) {
					$bypass_source[] = $source;
				}
				if (preg_match($match_network, $als) && checkipSubnet($ip, $als)) {
					$bypass_source[] = $source;
				}
			}
		}
	}
	return $bypass_source;
}

function check_bypass_dest($instance_id, $ip) {
	global $config, $match_network, $wf_instances;

	$bypass_dest = array();
	if (empty($wf_instances[$instance_id]['server']['defined_ip_proxy_off_dest'])) {
		return $bypass_dest;
	}

	$ipsoff_dest = explode("; ", $wf_instances[$instance_id]['server']['defined_ip_proxy_off_dest']);
	foreach($ipsoff_dest as $dest) {
		if ($dest == $ip) {
			$bypass_dest[] = $ip;
		}
		if (!isset($config['aliases']['alias'])) {
			continue;
		}
		foreach ($config['aliases']['alias'] as $aliases) {
			if ($aliases['type'] != "host" || $aliases['type'] != "network" ||
			    $aliases['name'] != $dest) {
				continue;
			}
			foreach (explode(" ", $aliases['address']) as $als) {
				if ($als == $ip) {
					$bypass_dest[] = $dest;
				}
				if (preg_match($match_network, $als) && checkipSubnet($ip, $als)) {
					$bypass_dest[] = $dest;
				}
			}
		}
	}
	return $bypass_dest;
}

function netMatch($network, $ip) {
	$network = trim($network);
	$ip = trim($ip);
	$d = strpos($network, '-');

	if (preg_match("#^\*$#", $network))
		$network = str_replace('*', '^.+', $network);

	if (preg_match("#\^\.\+|\.\*#", $network))
		return preg_match("#{$network}#", $ip);

	if ($d === false) {
		$ip_arr = explode('/', $network);
		if (!preg_match("#@\d*\.\d*\.\d*\.\d*@#", $ip_arr[0], $matches))
			$ip_arr[0] .= '.0';
		$network_long = ip2long($ip_arr[0]);
		$x = ip2long($ip_arr[1]);
		$mask = long2ip($x) == $ip_arr[1] ? $x : (0xffffffff << (32 - $ip_arr[1]));
		$ip_long = ip2long($ip);
		return ($ip_long & $mask) == ($network_long & $mask);
	} else {
		$from = ip2long(trim(substr($network, 0, $d)));
		$to = ip2long(trim(substr($network, $d+1)));
		$ip = ip2long($ip);
		return ($ip >= $from and $ip <= $to);
	}
}

function returnReason($return) {
	$string = parse_url($return);
	$query_array = explode('[;&]', $string['query']);
	foreach ($query_array as $query) {
		if (preg_match('#\breason#', $query)) {
			$match = explode("=", $query);
		}
	}
	return NetFilterGetReasonMessage($match[1]);
}

function returnCategories($return) {
	$categories = array();
	$string = parse_url($return);
	$query_array = explode('[;&]', $string['query']);

	foreach ($query_array as $query) {
		if (preg_match('#\bmatch#', $query)) {
			$match = explode("=", $query);
		}
	}
	if (isset($match)) {
		$all_cats = NetfilterGetAllContentCategories();
		$cats = explode(',', $match[1]);
		foreach ($cats as $cat) {
			$categories[] = $all_cats[$cat];
		}
	}
	sort($categories);
	return implode(',', $categories);
}

function checkipSubnet($checkip, $subnet) {
	@list($ip, $len) = explode('/', $subnet);
	if (($min = ip2long($ip)) !== false && !is_null($len)) {
		$clong = ip2long($checkip);
		$max = ($min | (1<<(32-$len))-1);
		if ($clong > $min && $clong < $max) {
			return true;
		}
	}
	return false;
}

function get_users_group($ref) {
	global $config;
	if (!is_array($config['system']['group']))
		return;

	foreach ($config['system']['group'] as $group) {
		if ($group['objectguid'] !== $ref || $group['gid'] !== $ref ||
		    empty($group['member'])) {
			return;
		}
		foreach ($group['member'] as $memberid) {
			if ($memberid == 0) {
				continue;
			}
			$user = getUserEntryByUID($memberid);
			$members[] = "{$user['name']}";
		}
		return $members;
	}
}

function test_content_rules($instance_id, $user, $url, $ip) {
	$redirector = "/usr/local/bin/redirector -c /usr/local/etc/netfilter/redirector{$instance_id}.conf -r /usr/local/etc/netfilter/rules{$instance_id}.conf";
	if (!empty($ip)) {
		$cmd = "/bin/echo '{$url} {$ip}/- - GET' | {$redirector}";
	} else {
		$cmd = "/bin/echo '{$url} 127.0.0.1/- {$user} GET' | {$redirector}";
	}
	$gc = exec($cmd, $out, $err);
	if ($err == 0) {
		if (empty($out)) {
			return true;
		}
		return $out[0];
	}
}

function test_access_control($instance_id, $user, $pass, $url, $ip, $iface) {
	global $conf, $wf_instances;

	if (!isset($wf_instances[$instance_id]['server']['proxy_port'])) {
		return;
	}
	$port = !empty($wf_instances[$instance_id]['server']['proxy_port']) ? $wf_instances[$instance_id]['server']['proxy_port'] : 3128;
	list($host, $mask) = squid_get_real_interface_address($iface);
	if (!$host) {
		return;
	}
	if (!empty($ip)) {
		$cmd = "/usr/local/sbin/squidclient -h {$host} -p {$port} '{$ip}' '{$url}' | head -1 | awk '{print $2}'";
	} else {
		$cmd = "/usr/local/sbin/squidclient -h {$host} -p {$port} -u '{$user}' -w '{$pass}' '{$url}' | head -1 | awk '{print $2}'";
	}
	$res = exec($cmd, $err, $out);
	if (!in_array($res, array("407", "403"))) {
		return true;
	}
}

?>
