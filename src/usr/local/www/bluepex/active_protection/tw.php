<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2024
 * ====================================================================
 *
 */

require_once("bp_filter_tw_tacp_functions.inc");

// Variables
$return_acp_interfaces = [];
$all_gtw = getInterfacesInGatewaysWithNoExceptions();
$date_hour_pass = strtotime('-1 hour');
$date_grep = date("m/d/Y-H", $date_hour_pass);
$date_register = date("m_d_Y", $date_hour_pass);
$type_action = "TW";
$value_action = 0;

// Files
$file_filter_tw = "{$g['tmp_path']}/file_filter_tw";
$file_filter_tw_result = "{$g['tmp_path']}/file_filter_tw_result";
$file_filter_tw_execute = "{$g['tmp_path']}/file_filter_tw_execute";
unlink_if_exists($file_filter_tw);
unlink_if_exists($file_filter_tw_result);
unlink_if_exists($file_filter_tw_execute);

// Support filter file
$file_filter_tw_support_base = "{$g['tmp_path']}/file_filter_tw_support";
$file_filter_tw_support_all = "{$file_filter_tw_support_base}*";
$file_filter_tw_support_day = "{$file_filter_tw_support_base}_". date("d_m_Y");
if (!bp_support_files_to_send($file_filter_tw_support_all, $file_filter_tw_support_day)) { exit; }

// Get interfaces to work
foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
	$if = get_real_interface($suricatacfg['interface']);

	if (in_array($if, $all_gtw, true) &&
	    suricata_is_running($suricatacfg['uuid'], $if)) {
		$return_acp_interfaces[] = "/usr/bin/grep -ri \"{$date_grep}\" {$g['varlog_path']}/suricata/suricata_{$if}{$suricatacfg['uuid']}/alerts.log >> {$file_filter_tw}";
	}
}

if (empty($return_acp_interfaces)) { exit; }

// Filter action
$return_acp_interfaces = implode("\n", $return_acp_interfaces);

file_put_contents($file_filter_tw_execute, <<<EOD
{$return_acp_interfaces}

if [ ! -e {$file_filter_tw} ]
then
	exit;
fi

for values in `/usr/bin/grep -Ei 'Priority: 1|Priority: 2' {$file_filter_tw} | /usr/bin/awk -F: '{ print $4 }' | /usr/bin/sort -u`
do
	if [ `/usr/bin/grep -rc ":\${values}:" "{$file_filter_tw_support_day}"` -eq 0 ]
	then
		qtd_find=`/usr/bin/grep -rc ":\${values}:" {$file_filter_tw}`
		exp_line=`/usr/bin/grep -r ":\${values}:" {$file_filter_tw} | /usr/bin/awk '{\$1=""; sub(/^[\t]+/, ""); print}' | /usr/bin/sed -e "s/ \[wDrop\] //g" -e "s/ \[Drop\] //g" -e "s/\[\*\*\] / /g" -e "s/  / /g" -e "s/\///g" -e "s/'//g" -e "s/ /_/g" | /usr/bin/cut -c 2- | /usr/bin/tail -n1`

		if [ ! -z \${qtd_find} ] && [ ! -z \${exp_line} ]
		then
			echo "{$serial}&{$value_action}&{$date_register}_\${qtd_find}_\${exp_line}" >> {$file_filter_tw_result}
			echo ":\${values}:" >> {$file_filter_tw_support_day}
		fi
	fi
done
EOD);

exec("/bin/sh {$file_filter_tw_execute}");

if (!file_exists($file_filter_tw_result)) { exit; }

// Send values
$values_write = array_filter(array_unique(explode("\n", trim(file_get_contents($file_filter_tw_result)))));

if (empty($values_write)) { exit; }

bp_send_array_values($type_action, $values_write);
