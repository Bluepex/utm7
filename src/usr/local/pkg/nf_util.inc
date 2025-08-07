<?php
/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */
require_once('nf_defines.inc');

function NetfilterGetCategoriesFromClass($class)
{
	if (empty($class) || $class == 'NULL')
		return array('N/A');

	static $cache = array();

	if (isset($cache[$class]))
		return $cache[$class];

	$NetfilterAllContentCategories = &NetfilterGetAllContentCategories();
	$cats = explode(',', $class);
	$cats_n = array();
	foreach ($cats as $cat)
		$cats_n[] = $NetfilterAllContentCategories[intval($cat)];

	$cache[$class] = $cats_n;
	return $cats_n;
}

function getSquidUsersList() {
	global $config;

	$users = $config['system']['webfilter']['squidusers']['config'];

	$userlist = array();
	if (is_array($users)) {
		foreach ($users as $user)
			$userlist[] = array(
				'username' => $user['username'],
				'description' => $user['description']
			);
	}
	return $userlist;
}
?>
