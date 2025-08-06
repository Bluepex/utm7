<?php
require("config.inc");
if (isset($argv[1]) && $argv[1]=="true") {
	if (!isset($config['system']['active_protection_sid_show'])) {
		$config['system']['active_protection_sid_show'] = true;
		echo "Sid Habilitado";
		write_config('Sid habilitado');
	}
} elseif (isset($config['system']['active_protection_sid_show'])) {
	unset($config['system']['active_protection_sid_show']);
	echo "Sid Desabilitado";
	write_config('Sid Desabilitado');
}
?>
