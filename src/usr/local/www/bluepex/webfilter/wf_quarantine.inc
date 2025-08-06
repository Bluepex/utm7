<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2015
 * Written by Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */

require_once('util.inc');
require_once('auth.inc');
require_once("nf_config.inc");

init_config_arr(array('system', 'webfilter', 'nf_content_custom', 'element0', 'item'));
init_config_arr(array('system', 'webfilter', 'nf_content_rules', 'element0', 'item'));

$customlist = &$config['system']['webfilter']['nf_content_custom']['element0']['item'];

$rules = &$config['system']['webfilter']['nf_content_rules']['element0']['item'];

$db = new NetfilterDatabase();
if (!$db->backend) {
	log_error("Quarantine: Connection database failed!");
	return;
}

function get_justifications($filter = "") {
	global $db;

	if (!$db->backend) {
		return;
	}
	$data = array();
	$sql = "SELECT * FROM justification WHERE status=1";
	if (!empty($filter)) {
		$sql .= " AND (username like '%{$filter}%' OR reason LIKE '%{$filter}%' OR url_blocked LIKE '%{$filter}%' OR ip LIKE '%{$filter}%') ORDER BY time_date desc";
	}
	$res = $db->Query($sql);
	if (!$res) {
		return $data;
	}

	while($result = $db->FetchArray($res)) {
		$data[] = array(
			"id" => $result['id'],
			"proxy_instance_id" => $result['proxy_instance_id'],
			"proxy_instance_name" => $result['proxy_instance_name'],
			"username" => $result['username'],
			"ipaddress" => $result['ip'],
			"url_blocked" => $result['url_blocked'],
			"reason" => $result['reason'],
			"time_date" => $result['time_date'],
			"status" => $result['status']
		);
	}
	$db->FreeRes($res);
	return $data;
}

function get_total_justifications() {
	global $db;

	if (!$db->backend) {
		return;
	}
	$query = $db->Query("SELECT COUNT(*) AS total FROM justification WHERE status=1 AND rejected=0");
	return $db->FetchAssoc($query);
}

function get_justification_by_id($id) {
	global $db;

	if (!$db->backend) {
		return;
	}
	$query = $db->Query("SELECT * FROM justification WHERE id=\"{$id}\"");
	return $db->FetchAssoc($query);
}

function update_status_justification($id) {
	global $db;

	if (!$db->backend) {
		return;
	}
	$update = $db->Query("UPDATE justification SET status='0' WHERE id='{$id}'");
	if ($update) {
		$db->FreeRes($update);
		return true;
	}
}

function reject_justification($id) {
	global $db;

	if (!$db->backend) {
		return;
	}
	$update = $db->Query("UPDATE justification SET status='0', rejected='1' WHERE id='{$id}'");
	if ($update) {
		$db->FreeRes($update);
		return true;
	}
}

function insert_justification($data) {
	global $db, $config;

	if (!$db->backend) {
		return;
	}
	$res = $db->Query("INSERT justification SET proxy_instance_id='{$data['proxy_instance_id']}', proxy_instance_name='{$data['proxy_instance_name']}', username='{$data['username']}', ip='{$data['ipaddress']}', reason='{$data['reason']}', url_blocked='{$data['url']}', time_date='".date('Y-m-d H:i:s')."', status='{$data['status']}'");
	if ($res) {
		$db->FreeRes($res);
		if ($config['system']['webfilter']['quarantine']['config'][0]['notices']) {
			send_smtp_notify($data);
		}
		apply_resync(dgettext("BluePexWebFilter", "Justification inserted successfully..."));
		return true;
	}
}

function add_del_custom_quarantine($instance_id, $idx_rules) {
	global $wf_instances, $customlist, $rules;

	if (empty($rules)) {
		return;
	}
	// Get index custom list quarantine
	foreach ($customlist as $idx => $custom_list) {
		if ($custom_list['instance_id'] == $instance_id && $custom_list['name'] == "quarantine") {
			$custom_list_quarantine = "{$idx}:quarantine";
			break;
		}
	}
	foreach ($rules as $rule_id => $rule) {
		if ($rule['instance_id'] != $instance_id) {
			continue;
		}
		$cl_block = array();
		$cl_allow = array();
		list($allow,$block) = explode("|", $rule['custom_lists']);
		$allow = array_filter(explode(",", $allow), 'strlen');
		$block = array_filter(explode(",", $block), 'strlen');

		// Add custom list "quarantine" in the selected rules
		if (!empty($idx_rules) && in_array($rule_id, $idx_rules)) {
			if (is_array($block)) {
				foreach ($block as $blocked) {
					if (!strstr($blocked, "quarantine")) {
						$cl_block[] = $blocked;
					}
				}
			}
			if (!in_array($custom_list_quarantine, $allow)) {
				array_push($allow, $custom_list_quarantine);
			}
			sort($cl_block);
			sort($allow);
			$custom_lists = implode(",", $allow) . "|" . implode(",", $cl_block);
		} else {
			// Remove custom list "quarantine" not selected rules
			foreach ($allow as $allowed) {
				if (!strstr($allowed, "quarantine"))
					$cl_allow[] = $allowed;
			}
			sort($cl_allow);
			$custom_lists = implode(",", $cl_allow) . "|" . implode(",", $block);
		}
		$rules[$rule_id]['custom_lists'] = $custom_lists;
	}
}

function get_rules_quarantine($instance_id) {
	global $wf_instances, $rules;

	$_rules = array();
	if (empty($rules)) {
		return $_rules;
	}
	foreach ($rules as $rule_id => $rule) {
		if ($rule['instance_id'] != $instance_id) {
			continue;
		}
		$custom = explode("|", $rule['custom_lists']);
		foreach (explode(",", $custom[0]) as $cstl) {
			if (strstr($cstl, "quarantine")) {
				$_rules[] = $rule_id;
			}
		}
	}
	return $_rules;
}

function set_url_to_customlist($instance_id, $allowed, $url) {
	global $customlist;

	if (check_url_exist($instance_id, $allowed, $url)) {
		return true;
	}
	if (empty($customlist)) {
		return;
	}
	$url = "*{$url}*";
	foreach ($customlist as $idx => $cl) {
		if ($cl['instance_id'] == $instance_id && $cl['name'] == $allowed) {
			$urls = explode("\n", base64_decode($cl['urls']));
			array_push($urls, $url);
			$customlist[$idx]['urls'] = base64_encode(implode("\n",$urls));
			return true;
		}
	}
}

function check_url_exist($instance_id, $clist, $url) {
	global $customlist;

	if (empty($customlist)) {
		return;
	}
	foreach ($customlist as $cl) {
		if ($cl['instance_id'] != $instance_id || $cl['name'] != $clist) {
			continue;
		}
		$urls = explode("\n", base64_decode($cl['urls']));
		foreach ($urls as $url_custom) {
			if (preg_match("#^{$url}$#", str_replace("*", "",$url_custom))) {
				return true;
			}
		}
	}
}

function verify_custom_quarantine($instance_id) {
	global $customlist;

	if (empty($customlist)) {
		return;
	}
	foreach ($customlist as $custom) {
		if ($custom['instance_id'] == $instance_id && $custom['name'] == "quarantine") {
			return true;
		}
	}
}

function apply_resync($savemsg) {
	write_config($savemsg);
	NetfilterCustomListsResync();
	NetfilterContentRulesResync();
}

function get_user_group_entry_by($type, $idx, $value) {
	global $config;
	if ($type == "group" && is_array($config['system']['group'])) {
		foreach ($config['system']['group'] as $group) {
			if ($group[$idx] == $value) {
				return $group;
			}
		}
	} elseif ($type == "user" && is_array($config['system']['user'])) {
		foreach ($config['system']['user'] as $user) {
				if ($user[$idx] == $value) {
					return $user;
				}
		}
	}
}

function send_smtp_notify($data_info) {
	global $config, $g;
	require_once("sasl.inc");
	require_once("smtp.inc");

	$notice = $config['notifications']['smtp'];

	if (isset($notice['disable']) && isset($notice['ipaddress']) && isset($notice['notifyemailaddress'])) {
		return;
	}
	$total_rows = get_total_justifications();

	$message.= "<h3>" . dgettext('BluePexWebFilter', 'Alert Quarantine Justification') . "</h3>";
	$message.= "<table width='800' border=1 cellspacing=5 cellpading=5 style='text-align:center'>";
	$message.= "<thead>";
	$message.= "<th>" . dgettext('BluePexWebFilter', 'Username') . "</th>";
	$message.= "<th>" . dgettext('BluePexWebFilter', 'IP Address') . "</th>";
	$message.= "<th>" . dgettext('BluePexWebFilter', 'Reason') . "</th>";
	$message.= "<th>" . dgettext('BluePexWebFilter', 'URL Blocked') . "</th>";
	$message.= "<th>" . dgettext('BluePexWebFilter', 'Time') . "</th>";
	$message.= "</thead>";
	$message.= "<tbody>";
	$message.= "<tr>";
	$message.= "<td>{$data_info['username']}</td>";
	$message.= "<td>{$data_info['ipaddress']}</td>";
	$message.= "<td>{$data_info['reason']}</td>";
	$message.= "<td>{$data_info['url']}</td>";
	$message.= "<td>" . date('d/m/Y H:i:s') . "</td>";
	$message.= "</tr>";
	$message.= "</tbody>";
	$message.= "</table>";
	$message.= "<h2>" . sprintf(dgettext("BluePexWebFilter", "Total pending justifications: %s"), $total_rows['total']) . "</h2>";

	$smtp = new smtp_class;

	$from = "pfsense@{$config['system']['hostname']}.{$config['system']['domain']}";
	$to   = $notice['notifyemailaddress'];

	$smtp->host_name = $notice['ipaddress'];
	$smtp->host_port = empty($notice['port']) ? 25 : $notice['port'];
	$smtp->direct_delivery = 0;
	$smtp->ssl = ($notice['ssl'] == "checked") ? 1 : 0;
	$smtp->localhost=$config['system']['hostname'].".".$config['system']['domain'];

	if ($notice['fromaddress']) {
		$from = $notice['fromaddress'];
	}

	// Use SMTP Auth if fields are filled out
	if ($notice['username'] && $notice['password']) {
		$smtp->authentication_mechanism = "PLAIN";
		$smtp->user = $notice['username'];
		$smtp->password = $notice['password'];
	}

	$headers = array(
		"MIME-Version: 1.0",
		"Content-type: text/html; charset=iso-8859-1",
		"From: {$from}",
		"To: {$to}",
		"Subject: {$config['system']['hostname']}.{$config['system']['domain']} - Alert Quarantine Justification BluePexUTM",
		"Date: ".strftime("%a, %d %b %Y %H:%M:%S %Z")
	);

	if ($smtp->SendMessage($from, array($to), $headers, $message)) {
		log_error(sprintf(dgettext("BluePexWebFilter", "Message sent to %s OK"), $to));
		return;
	} else {
		log_error(sprintf(dgettext('BluePexWebFilter', 'Could not send the message to %1$s -- Error: %2$s'), $to, $smtp->error));
		return(sprintf(dgettext('BluePexWebFilter', 'Could not send the message to %1$s -- Error: %2$s'), $to, $smtp->error));
	}
}
?>
