<?php

	set_time_limit(0);

	require_once("guiconfig.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");

	global $g, $rebuild_rules, $config;

	$suricata_rules_dir = SURICATA_RULES_DIR;
	$suricatalogdir = SURICATALOGDIR;
	$id = isset($_POST['interface']) ? $_POST['interface'] : 0;
	$class_default_inline = "font-size:20px;font-weight:bold;text-transform:uppercase;padding-left:10px;vertical-align:inherit;";

	if (!is_array($config['installedpackages']['suricata']))
		$config['installedpackages']['suricata'] = array();
	$suricataglob = $config['installedpackages']['suricata'];

	if (!is_array($config['installedpackages']['suricata']['rule']))
		$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	init_config_arr(array('system', 'firewallapp'));

	$typefwd = &$config['system']['firewallapp']['type'];

	$typefwd = 0;

	/* Load up our rule action arrays with manually changed SID actions */
	$alertsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_alert']);
	$dropsid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_drop']);
	$passid = suricata_load_sid_mods($a_rule[$id]['rule_sid_force_pass']);

	/*
	 * 
	 * $rules_map => Mapeamento em forma de array das regras da pasta rules da raiz do suricata
	 * 
	 * $a_rule => Mapeamento em forma de array do config.xml
	 *
	 */
	if ($typefwd == 0) {
		$file = $_POST['category'];

		$if_real = get_real_interface($a_rule[$id]['interface']);
		$suricata_uuid = $a_rule[$id]['uuid'];
		$rulefile = "{$suricata_rules_dir}{$file}";
		$rules_map = suricata_load_rules_map($rulefile);
		[$mode_change, $suricata_sig_gid_ignore] = suricata_get_group_to_sid_gid_need_msg_block($a_rule[$id]);

		asort($rules_map[1], SORT_REGULAR);

		/* Load up our rule action arrays with manually changed SID actions */
		suricata_modify_sids_action($rules_map, $a_rule[$id]);

		if (is_array($rules_map) && !empty($rules_map)) {
			foreach ($rules_map as $k1 => $rulem) {
				if (!is_array($rulem)) {
					$rulem = array();
				}

				$table_rules = "";
				$content = "";
				$has_group = false;
				$sid = 0;
				$cont = 0;
				$name_ant = "name_ant";

				foreach ($rulem as $k2 => $v) {
					$sid = $k2;
					$state = $v['action'];

					switch ($state) {
						case "alert":
							$ip_conf = explode(":", str_replace("|", ":", str_replace(array('(', ')'), "", $alertsid[$k1][$k2])));
							break;
						case "drop":
							$ip_conf = explode(":", str_replace("|", ":", str_replace(array('(', ')'), "", $dropsid[$k1][$k2])));
							break;
						case "pass":
							$ip_conf = explode(":", str_replace("|", ":", str_replace(array('(', ')'), "", $passid[$k1][$k2])));
							break;
					}

					$ip_source = empty($ip_conf[2]) ? 'any' : $ip_conf[2];
					$port_source = empty($ip_conf[3]) ? 'any' : $ip_conf[3];
					$direction = empty($ip_conf[4]) ? '->' : $ip_conf[4];
					$ip_destination = empty($ip_conf[5]) ? 'any' : $ip_conf[5];
					$port_destination = empty($ip_conf[6]) ? 'any' : $ip_conf[6];

					$state = $v['action'];

					$class_btn_advanced = (($state == "alert") || ($state == "drop")) ? "btn-disabled" : "";
					$class_btn_alert = (($state == "drop") || ($state == "pass")) ? "btn-disabled" : "";
					$class_btn_block = (($state == "alert") || ($state == "pass")) ? "btn-disabled" : "";
					preg_match('/classtype:([^;]*); gid:([^;]*)/', $v['rule'], $rule_params);
					$group = $rule_params[1];
					$has_group = empty($group) ? false : true;
					$gid = !empty($rule_params[2]) ? $rule_params[2] : 1;

					if (($cont == 0) && ($has_group == true)) {

						$table_rules = "<tr>";
						$table_rules .= "<td style='$class_default_inline'>$group</td>";
						$table_rules .= "<td style='width:400px' align='left'>";
						//$table_rules .= "<div class='btn-group d-flex' role='group'>";
						$table_rules .= "<div role='group'>";

						if (isset($config['ezshaper']['step2']) && !empty($config['ezshaper']['step2'])) {
							foreach($config['ezshaper']['step7'] as $key => $line) {
								if (($line != "D") && $key != "enable" && strtolower($key) == strtolower($group)) {
									file_put_contents("/tmp/teste11", "grep -rc 'msg:\"{$key}\"' /usr/local/share/suricata/rules/\n");
									if (intval(trim(shell_exec("grep -rch 'msg:\"{$key}\"' /usr/local/share/suricata/rules/ | sort | uniq | tail -n1"))) > 0) {
										$table_rules .= "<button type='button' class='btn btn-info btn-qos' title='Rule with service in QoS'><i class='fa fa-rss'></i> " . gettext("QoS") . "</button>";
										break;
									}
								}
							}
						}
						$table_rules .= "<button type='button' class='btn btn-default-custom btn-avancado' data-current-state='$state' data-current-file='$file' data-sid='$sid' data-gid='$gid' data-ip-source='$ip_source' data-port-source='$port_source' data-direction='$direction' data-ip-destination='$ip_destination' data-port-destination='' data-has-group='true' value='advanced'><i class='fa fa-cog'></i> " . gettext("Advanced") . "</button>";
						$table_rules .= "<button type='button' class='btn btn-warning $class_btn_advanced' onclick='toggleStateGroup($gid, this);' value='pass' data-current-file='$file'><i class='fa fa-info'></i> " . gettext("Ignore") . "</button>";
						$table_rules .= "<button type='button' class='btn btn-success $class_btn_alert' onclick='toggleStateGroup($gid, this);' value='alert' data-current-file='$file'><i class='fa fa-check'></i> " . gettext("Pass") . "</button>";						
						$disabled = "";
						$classRule = "danger";
						$styleOpacity = "";
						$btnAttention = false;
						$block_btn=false;


						if ((in_array($gid, $suricata_sig_gid_ignore) || in_array($sid, $suricata_sig_gid_ignore))&& ($a_rule[$id]['ips_mode'] == 'ips_mode_legacy')) {
							$block_btn=true;
						} elseif ((!in_array($gid, $suricata_sig_gid_ignore) && !in_array($sid, $suricata_sig_gid_ignore)) && ($a_rule[$id]['ips_mode'] == 'ips_mode_mix')) {
							$block_btn=true;
						}

						if ($block_btn) {
							$disabled = "disabled";
							$classRule = "dark";
							$styleOpacity = "style='opacity: 0.3;'";
							$btnAttention = true;
						}
						$table_rules .= "<button type='button' class='btn btn-$classRule no-confirm $class_btn_block' onclick='toggleStateGroup($gid, this);' value='drop' data-current-file='$file' {$disabled} {$styleOpacity}><i class='fa fa-times'></i> " . gettext("Block") . "</button>";
						if ($btnAttention) {
							$table_rules .= "<button type='button' class='btn btn-warning' onclick=\"showAttentionMsg('msg_{$sid}{$gid}')\"><i class='fa fa-exclamation'></i> " . gettext("Attention") . "</button>";
							$table_rules .= "<p style=\"color:red; display:none;\" id=\"msg_{$sid}{$gid}\">" . gettext("Note: This rule cannot be blocked in this operation mode, change it to '") . $mode_change . gettext("' enable editing") . "</p>";
						}
						
						$table_rules .= "<input type='hidden' name='gid' id='gid' value='' />";
						$table_rules .= "</div>";
						$table_rules .= "</td>";
						$table_rules .= "</tr>";

						$cont = 0;

						if (!empty($group)) {
							echo $table_rules;
						}
					} 

					if (($cont != 0) && ($has_group == false)) {

						$ruleset = $currentruleset;
						$style = "";
						$message = suricata_get_msg($v['rule']);
						preg_match('/(alert|drop|block|pass)/', $v['rule'], $matches_state);
						$state = $matches_state[1];

						if (substr($v['rule'], 0, 1) == "#")
							continue;

						$class_btn_advanced = (($state == "alert") || ($state == "drop")) ? "btn-disabled" : "";
						$class_btn_alert = (($state == "drop") || ($state == "pass")) ? "btn-disabled" : "";
						$class_btn_block = (($state == "alert") || ($state == "pass")) ? "btn-disabled" : "";

						$content .= "<tr>";
						$content .= "<td style='$class_default_inline'>$message</td>";
						$content .= "<td style='min-width:400px !important;' align='left'>";
						//$content .= "<div class='btn-group d-flex' role='group'>";
						$content .= "<div role='group'>";
						//$content .= "<button type='button' class='btn btn-info btn-qos' data-sid='$sid' data-gid='$gid'><i class='fa fa-rss'></i> " . gettext("QoS") . "</button>";
						$content .= "<button type='button' class='btn btn-default-custom btn-avancado' data-current-state='$state' data-current-file='$file' data-sid='$sid' data-gid='$gid' data-ip-source='$ip_source' data-port-source='$port_source' data-direction='$direction' data-ip-destination='$ip_destination' data-port-destination='$port_destination' data-has-group='false'><i class='fa fa-cog'></i> " . gettext("Advanced") . "</button>";
						$content .= "<button type='button' class='btn btn-warning $class_btn_advanced' onclick='toggleState($sid, $gid, this);' value='pass' data-current-file='$file'><i class='fa fa-info'></i> " . gettext("Ignore") . "</button>";
						$content .= "<button type='button' class='btn btn-success $class_btn_alert' onclick='toggleState($sid, $gid, this);' value='alert' data-current-file='$file'><i class='fa fa-check'></i> " . gettext("Pass") . "</button>";

						$disabled = "";
						$classRule = "danger";
						$styleOpacity = "";
						$btnAttention = false;
						$block_btn=false;

						if ((in_array($gid, $suricata_sig_gid_ignore) || in_array($sid, $suricata_sig_gid_ignore))&& ($a_rule[$id]['ips_mode'] == 'ips_mode_legacy')) {
							$block_btn=true;
						} elseif ((!in_array($gid, $suricata_sig_gid_ignore) && !in_array($sid, $suricata_sig_gid_ignore)) && ($a_rule[$id]['ips_mode'] == 'ips_mode_mix')) {
							$block_btn=true;
						}
						
						if ($block_btn) {
							$disabled = "disabled";
							$classRule = "dark";
							$styleOpacity = "style='opacity: 0.3;'";
							$btnAttention = true;
						}
						$content .= "<button type='button' class='btn btn-$classRule no-confirm $class_btn_block' onclick='toggleState($sid, $gid, this);' value='drop' data-current-file='$file' {$disabled} {$styleOpacity}><i class='fa fa-times'></i> " . gettext("Block") . "</button>";
						if ($btnAttention) {
							$content .= "<button type='button' class='btn btn-warning' onclick=\"showAttentionMsg('msg_{$sid}{$gid}')\"><i class='fa fa-exclamation'></i> " . gettext("Attention") . "</button>";
							$content .= "<p style=\"color:red; display:none;\" id=\"msg_{$sid}{$gid}\">" . gettext("Note: This rule cannot be blocked in this operation mode, change it to '") . $mode_change . gettext("' enable editing") . "</p>";
						}
						$content .= "<input type='hidden' name='gid' id='gid' value='' />";
						$content .= "</div>";
						$content .= "</td>";
						$content .= "</tr>";

						echo $content;

						$content = "";

					}

					$has_group = false;
					$cont++;

				}

				if ($has_group) {
					echo "</table></td></tr>";
				}
			}
		}
	}
	if ($typefwd == 1) {
		$file = $_POST['category'];

		$if_real = get_real_interface('wan');
		$suricata_uuid = $a_rule[$id]['uuid'];
		$rulefile = "{$suricata_rules_dir}{$file}";
		$rules_map = suricata_load_rules_map($rulefile);

		asort($rules_map, SORT_STRING);

		/* Load up our rule action arrays with manually changed SID actions */
		suricata_modify_sids_action($rules_map, $a_rule[$id]);

		if (is_array($rules_map) && !empty($rules_map)) {
			foreach ($rules_map as $k1 => $rulem) {
				if ($k1 == 2)
					continue;
				if (!is_array($rulem)) {
					$rulem = array();
				}

				$table_rules = "";
				$content = "";
				$has_group = false;
				$sid = 0;
				$cont = 0;
				$group_ant = 0;

				foreach ($rulem as $k2 => $v) {
					$sid = $k2;
					$state = $v['action'];

					$ip_conf = explode(":", str_replace("|", ":", str_replace(array('(', ')'), "", $alertsid[$k1][$k2])));
					$ip_source = empty($ip_conf[2]) ? 'any' : $ip_conf[2];
					$port_source = empty($ip_conf[3]) ? 'any' : $ip_conf[3];
					$direction = empty($ip_conf[4]) ? '->' : $ip_conf[4];
					$ip_destination = empty($ip_conf[5]) ? 'any' : $ip_conf[5];
					$port_destination = empty($ip_conf[6]) ? 'any' : $ip_conf[6];

					$state = $v['action'];

					$class_btn_advanced = (($state == "alert") || ($state == "drop")) ? "btn-disabled" : "";
					$class_btn_alert = (($state == "drop") || ($state == "pass")) ? "btn-disabled" : "";
					$class_btn_block = (($state == "alert") || ($state == "pass")) ? "btn-disabled" : "";
					preg_match('/classtype:([^;]*); gid:([^;]*)/', $v['rule'], $rule_params);
					preg_match('/classtype:([^;]*); metadata:([^;]*)/', $v['rule'], $rule_params2);
					$group = $rule_params2[1];
					$has_group = empty($group) ? false : true;
					$gid = !empty($rule_params[2]) ? $rule_params[2] : 1;

					if ($group_ant != $group) {
						$table_rules = "<tr>";
						$table_rules .= "<td colspan='2'>";
						$table_rules .= "<table border='0' width='100%' cellpadding='0' cellspacing='0'>";
						$table_rules .= "<a class='btn btn-primary' data-toggle='collapse' href='#collapseExample' role='button' aria-expanded='false' aria-controls='collapseExample'>$group 1234367</a>";
						$table_rules .= "<tr>";
						$table_rules .= "<td style='background-color:#043958'>";
						$table_rules .= "<h1 style='margin:0 0 0 10px;color:#FFF;text-transform:uppercase;'>$group</h1>";
						$table_rules .= "</td>";
						$table_rules .= "<td style='width:340px' align='center'>";
						$table_rules .= "<div class='btn-group' role='group'>";
						$table_rules .= "<button type='button' class='btn btn-default-custom btn-avancado' data-current-state='$state' data-current-file='$file' data-sid='' data-gid='$gid' data-ip-source='$ip_source' data-port-source='$port_source' data-direction='$direction' data-ip-destination='$ip_destination' data-port-destination='$port_destination' value='advanced'><i class='fa fa-cog'></i> " . gettext("Advanced") . "</button>";
						$table_rules .= "<button type='button' class='btn btn-warning $class_btn_advanced' onclick='toggleStateGroup($gid, this);' value='pass' data-current-file='$file'><i class='fa fa-info'></i> " .  gettext("Ignore") . "</button>";
						$table_rules .= "<button type='button' class='btn btn-success $class_btn_alert' onclick='toggleStateGroup($gid, this);' value='alert' data-current-file='$file'><i class='fa fa-check'></i> " . gettext("Pass") . "</button>";
						$table_rules .= "<button type='button' class='btn btn-danger no-confirm $class_btn_block' onclick='toggleStateGroup($gid, this);' value='drop' data-current-file='$file'><i class='fa fa-times'></i> " . gettext("Block") . "</button>";
						$table_rules .= "<input type='hidden' name='gid' id='gid' value='' />";
						$table_rules .= "</div>";
						$table_rules .= "</td>";
						$table_rules .= "</tr>";

						if (!empty($group)) {
							echo $table_rules;
						}
					}

					$content .= "<a class='btn btn-primary' data-toggle='collapse' href='#collapseExample' role='button' aria-expanded='false' aria-controls='collapseExample'>$group</a>";

					$ruleset = $currentruleset;
					$style = "";
					$message = suricata_get_msg($v['rule']);
					preg_match('/(alert|drop|block|pass)/', $v['rule'], $matches_state);
					$state = $matches_state[1];

					if (substr($v['rule'], 0, 1) == "#")
						continue;

					$class_btn_advanced = (($state == "alert") || ($state == "drop")) ? "btn-disabled" : "";
					$class_btn_alert = (($state == "drop") || ($state == "pass")) ? "btn-disabled" : "";
					$class_btn_block = (($state == "alert") || ($state == "pass")) ? "btn-disabled" : "";

					$content .= "<tr>";
					$content .= "<td style='$class_default_inline'>$message</td>";
					$content .= "<td style='width:340px' align='center'>";
					$content .= "<div class='btn-group' role='group'>";
					$content .= "<button type='button' class='btn btn-default-custom btn-avancado' data-current-state='$state' data-current-file='$file' data-sid='$sid' data-gid='$gid' data-ip-source='$ip_source' data-port-source='$port_source' data-direction='$direction' data-ip-destination='$ip_destination' data-port-destination='$port_destination'><i class='fa fa-cog'></i> " . gettext("Advanced") . "</button>";
					$content .= "<button type='button' class='btn btn-warning $class_btn_advanced' onclick='toggleState($sid, $gid, this);' value='pass' data-current-file='$file'><i class='fa fa-info'></i> " . gettext("Ignore") . "</button>";
					$content .= "<button type='button' class='btn btn-success $class_btn_alert' onclick='toggleState($sid, $gid, this);' value='alert' data-current-file='$file'><i class='fa fa-check'></i> " . gettext("Pass") . "</button>";
					$content .= "<button type='button' class='btn btn-danger no-confirm $class_btn_block' onclick='toggleState($sid, $gid, this);' value='drop' data-current-file='$file'><i class='fa fa-times'></i> " . gettext("Block") . "</button>";
					$content .= "<input type='hidden' name='gid' id='gid' value='' />";
					$content .= "</div>";
					$content .= "</td>";
					$content .= "</tr>";

					$group_ant = $group;

					echo $content;

					$content = "";

					$cont++;

				}

				if ($has_group) {
					echo "</table></td></tr>";
				}
			}
		}
	}
?>
