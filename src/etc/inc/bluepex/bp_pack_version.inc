<?php
/*
* ====================================================================
* Copyright (C) BluePex Security Solutions - All rights reserved
* Unauthorized copying of this file, via any medium is strictly prohibited
* Proprietary and confidential
* Written by Marcos Claudiano <desenvolvimento@bluepex.com>, 2023
* Written by Guilherme Brechot <guilherme.brechot@bluepex.com>, 2023
* ====================================================================
*/

require_once("config.inc");
require_once("functions.inc");
require_once("util.inc");

function bp_list_files_pack_fix($order_array = 'sort', $show_table = false) {

	$packs_installed = [];
	$array_find_files = array(
	    '/etc/cpack_*.zip',
	    '/etc/cpack_*.sha256',
	    '/etc/pack-fix*.sha256',
	    '/usr/local/share/BluePexUTM/cpack_*.sh'
	);

	foreach ($array_find_files as $find_files) {
		foreach (glob($find_files) as $files_find) {
			if (preg_match('/(?:cpack|pack-fix)(?=.*\d)/', $files_find)) {
				$file_tmp = basename($files_find);
				$file_tmp = explode(".", $file_tmp);
				unset($file_tmp[count($file_tmp)-1]);

				if ($show_table) {
					$file_tmp[0] = str_replace("cpack_", "pack-fix-", $file_tmp[0]);
				}

				$packs_installed[] = join(".",$file_tmp);
			}
		}
	}

	$packs_installed = array_filter(array_unique($packs_installed));

	switch ($order_array) {
		case "inverse":
			arsort($packs_installed, SORT_STRING);
			break;
		case "sort":
		default:
			sort($packs_installed, SORT_STRING);
			break;
	}

	return $packs_installed;
}

function bp_link_changelog($text = '') {
	if (empty($text)) {
		return;
	}

	return "<a href='https://suite.bluepex.com.br/utm/change-log#box-informations-pack-fix' target='_blank'>{$text}</a>";
}
