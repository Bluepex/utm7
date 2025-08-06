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
$date_hour_pass = strtotime('-1 hour');
$date_grep = bp_return_date_filter_log($date_hour_pass);
$date_complete = date("d_m_Y_H_i", $date_hour_pass);
$type_action = "TACP";
$value_action = 1;

// Files
$file_filter_tacp = "{$g['tmp_path']}/file_filter_tacp";
$file_filter_tacp_result = "{$g['tmp_path']}/file_filter_tacp_result";
$file_filter_tacp_execute = "{$g['tmp_path']}/file_filter_tacp_execute";
$filter_test_external_address = "{$g['tmp_path']}/filter_test_external_address";
unlink_if_exists($file_filter_tacp);
unlink_if_exists($file_filter_tacp_result);
unlink_if_exists($file_filter_tacp_execute);
unlink_if_exists($filter_test_external_address);

// Support filter file
$file_filter_tacp_support_base = "{$g['tmp_path']}/file_filter_tacp_support";
$file_filter_tacp_support_all = "{$file_filter_tacp_support_base}*";
$file_filter_tacp_support_day = "{$file_filter_tacp_support_base}_". date("d_m_Y");
if (!bp_support_files_to_send($file_filter_tacp_support_all, $file_filter_tacp_support_day)) { exit; }

// Generate file to confirm external address
bp_generate_filter_external_address($filter_test_external_address);

// Get values to work send
file_put_contents($file_filter_tacp_execute, <<<EOD
if [ ! -e /var/log/filter.log ]
then
	exit;
fi

/bin/cat /var/log/filter.log* | /usr/bin/grep -ri '{$date_grep}' | /usr/bin/grep -v 'size>500K' > {$file_filter_tacp}

for values in `/usr/bin/awk -F',' '{print ","$5",|,"$19","}' {$file_filter_tacp} | /usr/bin/sort -u`
do
	if [ `/usr/bin/grep -rc "\${values}" "{$file_filter_tacp_support_day}"` -eq 0 ]
	then
		if [ `/usr/local/bin/php {$filter_test_external_address} \${values}` = "true" ]
		then
			interface_target=`echo "\${values}" | cut -d '|' -f1`
			address_target=`echo "\${values}" | cut -d '|' -f2`

			qtd_find=`/usr/bin/grep -r "\${interface_target}" {$file_filter_tacp} | grep -rc "\${address_target}"`
			exp_line=`/usr/bin/grep -r "\${interface_target}" {$file_filter_tacp} | grep -r "\${address_target}" | /usr/bin/awk -F': ' '{print $2}' | /usr/bin/sed -e "s/  / /g" -e "s/\///g" -e "s/'//g" -e "s/ /_/g" | /usr/bin/tail -n1`

			if [ ! -z \${qtd_find} ] && [ ! -z \${exp_line} ]
			then
				echo "{$serial}&{$value_action}&\${qtd_find}_{$date_complete}~_\${exp_line}" >> {$file_filter_tacp_result}
				echo "\${values}" >> {$file_filter_tacp_support_day}
			fi
		fi
	fi
done
EOD);

exec("/bin/sh {$file_filter_tacp_execute}");

if (!file_exists($file_filter_tacp_result)) { exit; }

// Send values
$values_write = array_filter(array_unique(explode("\n", trim(file_get_contents($file_filter_tacp_result)))));

if (empty($values_write)) { exit; }

bp_send_array_values($type_action, $values_write);
