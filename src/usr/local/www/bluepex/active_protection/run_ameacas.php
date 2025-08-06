<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Marcos Claudiano <marcos.claudiano@bluepex.com>, 2020
 * ReWritten by Guilherme Brechot <guilherme.breachot@bluepex.com>, 2023
 *
 * ====================================================================
 *
 */

require_once('functions.inc');
require_once('config.inc');

global $g, $config;

if (!isset($config['system']['bluepex_stats'])) {
	init_config_arr(array('system', 'bluepex_stats'));
}

foreach(['qtd_blocks', 'last_ip', 'qtd_threat', 'last_threat', 'active_blocks', 'country_block'] as $tag) {
	if (!isset($config['system']['bluepex_stats']["{$tag}"])) {
		init_config_arr(array('system', 'bluepex_stats', "{$tag}"));
	}
}

$config['system']['bluepex_stats']['qtd_blocks'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 3'));
$config['system']['bluepex_stats']['last_ip'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 5'));
$config['system']['bluepex_stats']['qtd_threat'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 4'));
$config['system']['bluepex_stats']['last_threat'] = "ACP";
$config['system']['bluepex_stats']['active_blocks'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 7'));
$config['system']['bluepex_stats']['country_block']['qtd_rank1'] =trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 2'));
$config['system']['bluepex_stats']['country_block']['country_rank1'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 1'));
$config['system']['bluepex_stats']['country_block']['qtd_rank2'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 1 2'));
$config['system']['bluepex_stats']['country_block']['country_rank2'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 1 1'));
$config['system']['bluepex_stats']['country_block']['qtd_rank3'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 2 2'));
$config['system']['bluepex_stats']['country_block']['country_rank3'] = trim(shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 2 1'));

write_config("BluePex Stats: modified active protection");

shell_exec('/usr/local/bin/python3.8 /usr/local/sbin/fapprotated 1');
shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 9');
shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 14');
shell_exec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 8');
