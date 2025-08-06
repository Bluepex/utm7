<?php
require_once('guiconfig.inc');
require("config.inc");


/*
if (isset($_POST["cpuT10"])) {
	echo shell_exec("/bin/ps -uaxcr | /usr/bin/head -n11 | /usr/bin/awk -F\" \" '{print $2, $3, $4, $11}' | /usr/bin/tail");
}

if (isset($_POST["memT10"])) {
	echo shell_exec("/bin/ps -uaxcm | /usr/bin/head -n11 | /usr/bin/awk -F\" \" '{print $2, $3, $4, $11}' | /usr/bin/tail");
}
*/

if (isset($_POST['getDate'])) {
	echo date("d/m/Y - H:i:s");
}

function ifIsZero($value) {
	if (intval($value) == 0) {
		return "00";
	}
	if (strlen($value) != 2) {
		return "0".$value;
	}
	return $value;
}

if (isset($_POST['getUptime'])) {
	//echo trim(shell_exec("uptime | awk -F\",\" '{print $1}' | awk -F\" \" '{print $3 \" \" $4}'"));
	$dateBoot = date("Y-m-d H:i:s", strtotime(trim(shell_exec("sysctl kern.boottime | awk -F\"} \" '{print $2}'"))));
	$firstDate  = new DateTime($dateBoot);
	$secondDate = new DateTime(date("Y-m-d H:i:s",strtotime("now")));
	$returnDate = $firstDate->diff($secondDate);
	$extraDate = "";
	if (intval($returnDate->y) != 0) {
		$extraDate = $returnDate->y . " Year's, " . $returnDate->m . " Mounth's, " . $returnDate->d . " Day's, ";
	} else {
		if (intval($returnDate->m) != 0) {
			$extraDate = $returnDate->m . " Mounth's, " . $returnDate->d . "Day's, ";
		} else {
			if (intval($returnDate->d) != 0) {
				$extraDate = $returnDate->d . " Day's, ";
			}
		}
	}
	echo $extraDate . ifIsZero($returnDate->h)  . " Hour's, " . ifIsZero($returnDate->i) . " Minute's, " . ($returnDate->s) . " Second's";
}

if (isset($_POST['getTemp'])) {
	$temps = array_filter(explode("\n", trim(shell_exec("sysctl -a | grep temperature | grep cpu | awk -F\"temperature: \" '{print $2}'"))));
	if (count($temps) > 0) {
		$tempsReturn = 0;
		foreach($temps as $lineTemp) {
			$tempsReturn += $lineTemp;
		}
		echo $tempsReturn/count($temps) . " °C";
	} else {
		echo " °C";
	}
}

if (isset($_POST['gerarTop'])) {
	mwexec("/usr/bin/top -bC 10000 > /tmp/topFilterValues");
	file_put_contents('/tmp/topFilterValues', trim(file_get_contents('/tmp/topFilterValues')));
}

if (isset($_POST['getCPU'])) {
	echo json_encode(array_filter(explode("\n", trim(shell_exec("sysctl -a | egrep -i 'hw.machine:|hw.model|hw.ncpu' | /usr/bin/awk -F\": \" '{ print $2 }'")))));
}

if (isset($_POST['getMEM'])) {
	echo json_encode(array_filter(explode("\n", trim(shell_exec("/sbin/sysctl hw | /usr/bin/egrep 'hw.(real|user)' | /usr/bin/awk -F\": \" '{ print $2 }'")))));
}

if (isset($_POST['getInfoSys'])) {
	echo json_encode(array_filter(explode(", ",trim(shell_exec("/usr/bin/sed -n '2p' /tmp/topFilterValues | /usr/bin/awk -F\"processes:\" '{ print $2 }'")))));
}

if (isset($_POST['getMemoryUse'])) {
	echo json_encode(explode(", ", trim(shell_exec("/usr/bin/grep \"Mem:\" /tmp/topFilterValues  | /usr/bin/awk -F\"Mem: \" '{ print $2 }'"))));
}

if (isset($_POST['getMemorySwap'])) {
	echo json_encode(explode(", ", trim(shell_exec("/usr/bin/grep -r \"Swap:\" /tmp/topFilterValues | /usr/bin/awk -F\"Swap: \" '{ print $2}'"))));
}

if (isset($_POST['getMemorySwapInfo'])) {
	echo json_encode(explode("\n",trim(shell_exec("swapinfo -m | grep dev | awk -F\" \" '{ print $1 \"---\" $2 \"---\" $3 \"---\" $4}'"))));
}

if (isset($_POST['getCPUUse'])) {
	echo json_encode(explode(", ", trim(shell_exec("/usr/bin/grep -r \"CPU: \" /tmp/topFilterValues | /usr/bin/awk -F\"CPU: \" '{ print $2 }'"))));
}

if (isset($_POST['loadCPU'])) {
	echo json_encode(explode(",", trim(shell_exec("cat /tmp/topFilterValues | head -n1 | awk -F\" \" '{print $6 $7 $8}'"))));
}

if (isset($_POST['process']) && isset($_POST['pid'])) {
	$info = [];
	foreach (explode(" ", trim(shell_exec("/usr/bin/top -bCa | grep {$_POST['pid']} | grep {$_POST['process']} | grep -v grep"))) as $line_now) {
		if ($line_now != "") {
			$info[] = $line_now;
		}
	}
	echo json_encode($info);
}

if (isset($_POST['getAllProcess'])) {
	$tail_break = intval(explode(" ", trim(shell_exec("/usr/bin/wc /tmp/topFilterValues")))[0])-9;
	$colunas = count(array_filter(explode(" ", shell_exec("grep 'PID' /tmp/topFilterValues"))));
	$all_proccess = "";
	if ($colunas == 11) {
		$all_proccess = array_filter(explode("\n", shell_exec("/usr/bin/tail -n{$tail_break} /tmp/topFilterValues | /usr/bin/awk -F\" \" '{print $1, $7, $10, $11}'")));
	} elseif ($colunas == 12) {
		$all_proccess = array_filter(explode("\n", shell_exec("/usr/bin/tail -n{$tail_break} /tmp/topFilterValues | /usr/bin/awk -F\" \" '{print $1, $7, $10, $12}'")));
	} else {
		$all_proccess = array_filter(explode("\n", shell_exec("/usr/bin/tail -n{$tail_break} /tmp/topFilterValues | /usr/bin/awk -F\" \" '{print $1, $7, $10, $11}'")));
	}
	unset($all_proccess[count($all_proccess)-1]);
	$all_proccess_filter = [];
	foreach ($all_proccess as $line_now) {
		$all_proccess_filter[] = explode(" ", $line_now);
	}
	unset($all_proccess);
	echo json_encode($all_proccess_filter);
}

if (isset($_POST['getDisc'])) {
	$mostrar_linhas = trim(shell_exec("/bin/df -aih | /usr/bin/wc -l"))-1;
	$all_partitions = [];
	foreach (explode("\n", shell_exec("/bin/df -aih | /usr/bin/tail -n{$mostrar_linhas}")) as $line_now) {
		$tratamento = [];
		foreach (explode(" ", $line_now) as $value_now) {
			if ($value_now != "") {
				$tratamento[] = $value_now;
			}
		}
		$all_partitions[] = $tratamento;
	}
	unset($all_partitions[count($all_partitions)-1]);
	echo json_encode($all_partitions);
}

if (isset($_POST['getDiscInode'])) {
	$mostrar_linhas = trim(shell_exec("/bin/df -ai | /usr/bin/wc -l"))-1;
	$all_partitions = [];
	foreach (explode("\n", shell_exec("/bin/df -ai | /usr/bin/tail -n{$mostrar_linhas}")) as $line_now) {
		$tratamento = [];
		foreach (explode(" ", $line_now) as $value_now) {
			if ($value_now != "") {
				$tratamento[] = $value_now;
			}
		}
		$all_partitions[] = $tratamento;
	}
	unset($all_partitions[count($all_partitions)-1]);
	echo json_encode($all_partitions);
}


if (isset($_POST['getSizeXML'])) {
	echo json_encode(filesize('/cf/conf/config.xml'));
}

if (isset($_POST['getStatusUsedDevices'])) {
	echo json_encode(shell_exec("iostat -zx | grep -v device | awk -F\" \" '{print $1\"___\"$2\"___\"$3\"___\"$4\"___\"$5\"___\"$6\"___\"$7\"___\"$8\"___\"$9\"___\"$10\"___\"$11\"_break\"}'"));
}

if (isset($_POST['getStatusAllDevices'])) {
	echo json_encode(shell_exec("iostat -x | grep -v device | awk -F\" \" '{print $1\"___\"$2\"___\"$3\"___\"$4\"___\"$5\"___\"$6\"___\"$7\"___\"$8\"___\"$9\"___\"$10\"___\"$11\"_break\"}'"));
}

if (isset($_POST['getAllConnectionsUTM'])) {
	$total_connections[] = intval(trim(shell_exec("pfctl -ss | wc -l")));
	$total_connections[] = intval(trim(shell_exec("pfctl -ss | awk '{print $5}' | cut -d: -f1 | sort | uniq | wc -l")));
	$total_connections[] = intval(trim(file_get_contents("/tmp/arp_hosts")));
	$total_connections[] = intval(trim(file_get_contents("/etc/capacity-utm")));
	echo json_encode($total_connections);
}