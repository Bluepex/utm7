<?php
/* ====================================================================
* Copyright (C) BluePex Security Solutions - All rights reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Written by Guilherme R. Brechot <guilherme.brechot@bluepex.com>, 2023
* ====================================================================
*
*/


function is_process_running_match($match) {
	$output = "";
	exec("/bin/pgrep -f " . escapeshellarg($match), $output, $retval);

	return ($retval == 0);
}


function check_is_invalide_interface() {
	return intval(trim(shell_exec("pciconf -lvc | grep -iE 'device' | grep -iE 'I211' | grep -iEc 'network|Gigabit|Ethernet'")));
}

function get_all_interfaces() {
	return explode("\n", trim(shell_exec("pciconf -lvc | grep device | grep -iE 'network|Gigabit|Ethernet' | awk -F' = ' '{print $2}' | sed \"s/\'//g\" | sort | uniq")));
}


?>
