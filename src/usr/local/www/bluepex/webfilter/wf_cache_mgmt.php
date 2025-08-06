<?php
/*
 *====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Bruno B. Stein <bruno.stein@bluepex.com>, 2016
 *
 * ====================================================================
 */

require_once("guiconfig.inc");
require_once("classes/Form.class.php");
require_once("squid.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidcache']['config'])) {
	$config['system']['webfilter']['squidcache']['config'] = array();
}
$wf_cache = &$config['system']['webfilter']['squidcache']['config'][0];

if (isset($_POST['save'])) {
	squid_validate_cache($_POST, $input_errors, 0);
	if (empty($input_errors)) {
		$wf_cache = array();

		// General Config
		$wf_cache['cache_replacement_policy'] = !empty($_POST['cache_replacement_policy']) ? $_POST['cache_replacement_policy'] : "heap LFUDA";
		$wf_cache['cache_swap_low'] = !empty($_POST['cache_swap_low']) ? $_POST['cache_swap_low'] : 90;
		$wf_cache['cache_swap_high'] = !empty($_POST['cache_swap_high']) ? $_POST['cache_swap_high'] : 95;
		$wf_cache['donotcache'] = !empty($_POST['donotcache']) ? base64_encode($_POST['donotcache']) : "";
		$wf_cache['enable_offline'] = $_POST['enable_offline'];
		$wf_cache['ext_cachemanager'] = $_POST['ext_cachemanager'];

		// Hard disk Settings
		$wf_cache['harddisk_cache_size'] = !empty($_POST['harddisk_cache_size']) ? $_POST['harddisk_cache_size'] : 100;
		$wf_cache['harddisk_cache_system'] = !empty($_POST['harddisk_cache_system']) ? $_POST['harddisk_cache_system'] : "null";
		$wf_cache['clear_cache'] = $_POST['clear_cache'];
		$wf_cache['level1_subdirs'] = !empty($_POST['level1_subdirs']) ? $_POST['level1_subdirs'] : 16;
		$wf_cache['harddisk_cache_location'] = !empty($_POST['harddisk_cache_location']) ? $_POST['harddisk_cache_location'] : "/var/squid/cache";
		$wf_cache['harddisk_log_location'] = !empty($_POST['harddisk_log_location']) ? $_POST['harddisk_log_location'] : "/var/squid/logs";
		$wf_cache['minimum_object_size'] = !empty($_POST['minimum_object_size']) ? $_POST['minimum_object_size'] : 0;
		$wf_cache['maximum_object_size'] = !empty($_POST['maximum_object_size']) ? $_POST['maximum_object_size'] : 4;

		// Memory Settings
		$wf_cache['memory_cache_size'] = !empty($_POST['memory_cache_size']) ? $_POST['memory_cache_size'] : 8;
		$wf_cache['maximum_objsize_in_mem'] = !empty($_POST['maximum_objsize_in_mem']) ? $_POST['maximum_objsize_in_mem'] : 32;
		$wf_cache['memory_replacement_policy'] = !empty($_POST['memory_replacement_policy']) ? $_POST['memory_replacement_policy'] : "heap GDSF";

		// Dynamic and Update Content
		$wf_cache['cache_dynamic_content'] = $_POST['cache_dynamic_content'];
		$wf_cache['refresh_patterns'] = !empty($_POST['refresh_patterns']) ? implode(",", $_POST['refresh_patterns']) : "";
		$wf_cache['custom_refresh_patterns'] = !empty($_POST['custom_refresh_patterns']) ? base64_encode($_POST['custom_refresh_patterns']) : "";

		$config['system']['webfilter']['squidcache']['config'][0] = $wf_cache;
		$savemsg = dgettext("BluePexWebFilter", "Cache Settings applied successfully!");
		write_config($savemsg);
		squid_resync();
		squid_dash_z();
	}
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Cache Settings'));

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);

if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_server.php');
//$tab_array[] = array(dgettext('BluePexWebFilter', 'Upstream Proxy'), false, '/webfilter/wf_upstream.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Cache Mgmt'), true, '/webfilter/wf_cache_mgmt.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Access Control'), false, '/webfilter/wf_access_control.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Traffic Mgmt'), false, '/webfilter/wf_traffic_mgmt.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'Cache General Settings'));

$cache_replacement_policy = array(
	"lru" => "LRU",
	"heap LFUDA" => "Heap LFUDA",
	"heap GDSF" => "Heap GDSF",
	"heap LRU" => "Heap LRU"
);
$section->addInput(new Form_Select(
	'cache_replacement_policy',
	dgettext('BluePexWebFilter', 'Cache replacement policy'),
	(isset($wf_cache['cache_replacement_policy']) ? $wf_cache['cache_replacement_policy'] : "heap LFUDA"),
	$cache_replacement_policy
))->setHelp(dgettext('BluePexWebFilter', 'The cache replacement policy decides which objects will remain in cache and which objects are replaced to create space for the new objects. The default policy for cache replacement is LFUDA. Please see the type descriptions specified in the memory replacement policy for additional detail.'));

$section->addInput(new Form_Input(
	'cache_swap_low',
	dgettext('BluePexWebFilter', 'Low-water-mark in %'),
	'text',
	(isset($wf_cache['cache_swap_low']) ? $wf_cache['cache_swap_low'] : 90)
))->setHelp(dgettext('BluePexWebFilter', 'Cache replacement begins when the swap usage is above the low-low-water mark and attempts to maintain utilisation near the low-water-mark.'));

$section->addInput(new Form_Input(
	'cache_swap_high',
	dgettext('BluePexWebFilter', 'High-water-mark in %'),
	'text',
	(isset($wf_cache['cache_swap_high']) ? $wf_cache['cache_swap_high'] : 95)
))->setHelp(dgettext('BluePexWebFilter', "As swap utilisation gets close to the high-water-mark object eviction becomes more aggressive."));

$section->addInput(new Form_Textarea(
	'donotcache',
	dgettext('BluePexWebFilter', 'Do not cache'),
	(isset($wf_cache['donotcache']) ? base64_decode($wf_cache['donotcache']) : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter each domain or IP address on a new line that should never be cached.'));

$section->addInput(new Form_Checkbox(
	'enable_offline',
	dgettext('BluePexWebFilter', 'Enable offline mode'),
	sprintf(dgettext("BluePexWebFilter", "Enable this option and the proxy server will never try to validate cached objects. The offline mode gives access to more cached information than the proposed feature would allow %sstale cached versions, where the origin server should have been contacted%s."), "(", ")"),
	(isset($wf_cache['enable_offline']) && $wf_cache['enable_offline'] == "on"),
	'on'
));

$section->addInput(new Form_Input(
	'ext_cachemanager',
	dgettext('BluePexWebFilter', 'External Cache-Managers'),
	'text',
	(isset($wf_cache['ext_cachemanager']) ? $wf_cache['ext_cachemanager'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter the IPs for the external Cache Managers to be allowed here, separated by semi-colons (;).'));

$form->add($section);

$section = new Form_Section(dgettext("BluePexWebFilter", 'Hard disk Settings'));

$section->addInput(new Form_Input(
	'harddisk_cache_size',
	dgettext('BluePexWebFilter', 'Hard disk cache size'),
	'text',
	(isset($wf_cache['harddisk_cache_size']) ? $wf_cache['harddisk_cache_size'] : 100)
))->setHelp(dgettext('BluePexWebFilter', 'This is the amount of disk space (in megabytes) to use for cached objects.'));

$harddisk_cache_system = array(
	"null" => "off",
	"aufs" => "aufs",
	"diskd" => "diskd",
	"ufs" => "ufs"
);
$section->addInput(new Form_Select(
	'harddisk_cache_system',
	dgettext('BluePexWebFilter', 'Hard disk cache system'),
	(isset($wf_cache['harddisk_cache_system']) ? $wf_cache['harddisk_cache_system'] : ""),
	$harddisk_cache_system
))->setHelp(
	sprintf(dgettext('BluePexWebFilter', "This specifies the kind of storage system to use.%s"), "<br />").
	sprintf(dgettext('BluePexWebFilter', "%sufs%s is the old well-known WF storage format that has always been there.%s"), "<strong>", "</strong>", "<br />") .
	sprintf(dgettext('BluePexWebFilter', "%saufs%s uses POSIX-threads to avoid blocking the main WF process on disk-I/O. (Formerly known as async-io.)%s"), "<strong>", "</strong>", "<br />") .
	sprintf(dgettext('BluePexWebFilter', "%sdiskd%s uses a separate process to avoid blocking the main WF process on disk-I/O.%s"), "<strong>", "</strong>", "<br />") .
	sprintf(dgettext('BluePexWebFilter', "%snull%s Does not use any storage. Ideal for Embedded/NanoBSD."), "<strong>", "</strong>")
);

$section->addInput(new Form_Checkbox(
	'clear_cache',
	dgettext('BluePexWebFilter', 'Clear cache on log rotate'),
	dgettext("BluePexWebFilter", "If set, WF will clear cache and swap.state on every log rotate. This action will be executed automatically if the swap.state file is taking up more than 75% disk space,or the drive is 90%"),
	(isset($wf_cache['clear_cache']) && $wf_cache['clear_cache'] == "on"),
	'on'
));

$clear_cache = array();
for ($i=4; $i<=256; $i++) {
	$clear_cache[$i] = $i;
	$i = ($i*2)-1;
}
$section->addInput(new Form_Select(
	'clear_cache',
	dgettext('BluePexWebFilter', 'Level 1 subdirectories'),
	(isset($wf_cache['clear_cache']) ? $wf_cache['clear_cache'] : 16),
	$clear_cache
))->setHelp(dgettext('BluePexWebFilter', 'Each level-1 directory contains 256 subdirectories, so a value of 256 level-1 directories will use a total of 65536 directories for the hard disk cache. This will significantly slow down the startup process of the proxy service, but can speed up the caching under certain conditions.'));

$section->addInput(new Form_Input(
	'harddisk_cache_location',
	dgettext('BluePexWebFilter', 'Hard disk cache location'),
	'text',
	(isset($wf_cache['harddisk_cache_location']) ? $wf_cache['harddisk_cache_location'] : "/var/squid/cache")
))->setHelp(dgettext('BluePexWebFilter', 'This is the directory where the cache will be stored. (note: do not end with a /). If you change this location, webfilter needs to make a new cache, this could take a while'));

$section->addInput(new Form_Input(
	'harddisk_log_location',
	dgettext('BluePexWebFilter', 'Hard disk cache log location'),
	'text',
	(isset($wf_cache['harddisk_log_location']) ? $wf_cache['harddisk_log_location'] : "/var/squid/logs")
))->setHelp(dgettext('BluePexWebFilter', 'This is the directory where the file swap.state will be sored. (note: do not end with a /). If you change this location, webfilter needs to make a new cache, this could take a while'));

$section->addInput(new Form_Input(
	'minimum_object_size',
	dgettext('BluePexWebFilter', 'Minimum object size'),
	'text',
	(isset($wf_cache['minimum_object_size']) ? $wf_cache['minimum_object_size'] : 0)
))->setHelp(dgettext('BluePexWebFilter', 'Objects smaller than the size specified (in kilobytes) will not be saved on disk. The default value is 0, meaning there is no minimum.'));

$section->addInput(new Form_Input(
	'maximum_object_size',
	dgettext('BluePexWebFilter', 'Maximum object size'),
	'text',
	(isset($wf_cache['maximum_object_size']) ? $wf_cache['maximum_object_size'] : 4)
))->setHelp(dgettext('BluePexWebFilter', 'Objects larger than the size specified (in kilobytes) will not be saved on disk. If you wish to increase speed more than you want to save bandwidth, this should be set to a low value.'));

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Memory Settings'));

$section->addInput(new Form_Input(
	'memory_cache_size',
	dgettext('BluePexWebFilter', 'Memory cache size'),
	'text',
	(isset($wf_cache['memory_cache_size']) ? $wf_cache['memory_cache_size'] : 8)
))->setHelp(dgettext('BluePexWebFilter', 'This is the amount of physical RAM (in megabytes) to be used for negative cache and in-transit objects. This value should not exceed more than 50% of the installed RAM. The minimum value is 1MB.'));

$section->addInput(new Form_Input(
	'maximum_objsize_in_mem',
	dgettext('BluePexWebFilter', 'Maximum object size in RAM'),
	'text',
	(isset($wf_cache['maximum_objsize_in_mem']) ? $wf_cache['maximum_objsize_in_mem'] : 32)
))->setHelp(dgettext('BluePexWebFilter', 'Objects smaller than the size specified (in kilobytes) will be saved in RAM. Default is 32.'));

$memory_replacement_policy = array(
	"lru" => "LRU",
	"heap LFUDA" => "Heap LFUDA",
	"heap GDSF" => "Heap GDSF",
	"heap LRU" => "Heap LRU"
);
$section->addInput(new Form_Select(
	'memory_replacement_policy',
	dgettext('BluePexWebFilter', 'memory replacement policy'),
	(isset($wf_cache['memory_replacement_policy']) ? $wf_cache['memory_replacement_policy'] : "heap GDSF"),
	$memory_replacement_policy
))->setHelp(
	sprintf(dgettext('BluePexWebFilter', "The memory replacement policy determines which objects are purged from memory when space is needed. The default policy for memory replacement is GDSF.%s"), "<br /><br />") .
	sprintf(dgettext('BluePexWebFilter', "%sLRU: Last Recently Used Policy%s - The LRU policies keep recently referenced objects. i.e., it replaces the object that has not been accessed for the longest time.%s"), "<strong>", "</strong>", "<br />") .
	sprintf(dgettext('BluePexWebFilter', "%sHeap GDSF: Greedy-Dual Size Frequency%s - The Heap GDSF policy optimizes object-hit rate by keeping smaller, popular objects in cache. It achieves a lower byte hit rate than LFUDA though, since it evicts larger (possibly popular) objects.%s"), "<strong>", "</strong>", "<br />") .
	sprintf(dgettext('BluePexWebFilter', "%sHeap LFUDA: Least Frequently Used with Dynamic Aging%s - The Heap LFUDA policy keeps popular objects in cache regardless of their size and thus optimizes byte hit rate at the expense of hit rate since one large, popular object will prevent many smaller, slightly less popular objects from being cached.%s"), "<strong>", "</strong>", "<br />") .
	sprintf(dgettext('BluePexWebFilter', "%sHeap LRU: Last Recently Used%s - Works like LRU, but uses a heap instead.%s"), "<strong>", "</strong>", "<br /><br />") .
	dgettext('BluePexWebFilter', "Note: If using the LFUDA replacement policy, the value of Maximum Object Size should be increased above its default of 12KB to maximize the potential byte hit rate improvement of LFUDA.")
);

$form ->add($section);
$section = new Form_Section(dgettext("BluePexWebFilter", 'Dynamic and Update Content'));

$section->addInput(new Form_Checkbox(
	'cache_dynamic_content',
	dgettext('BluePexWebFilter', 'Cache Dynamic Content'),
	dgettext("BluePexWebFilter", "Select this option to enable caching of dynamic content."),
	(isset($wf_cache['cache_dynamic_content']) && $wf_cache['cache_dynamic_content'] == "on"),
	'on'
));

$refresh_patterns = array(
	"youtube" => "Youtube",
	"windows" => "Windows Update",
	"symantec" => "Symantec Antivirus",
	"avira" => "Avira",
	"avast" => "Avast"
);
$section->addInput(new Form_Select(
	'refresh_patterns',
	dgettext('BluePexWebFilter', 'Refresh Patterns'),
	(isset($wf_cache['refresh_patterns']) ? explode(",", $wf_cache['refresh_patterns']) : ""),
	$refresh_patterns,
	true
))->setHelp(
	sprintf(dgettext('BluePexWebFilter', "With dynamic cache enabled, you can also apply webfilter wiki refresh_patterns to sites like Youtube and windowsupdate%s"), "<br /><br />") .
	sprintf(dgettext('BluePexWebFilter', "%sNotes:%s WF wiki suggests 'Finish transfer if less than x KB remaining' on 'traffic mgmt' webfilter tab to -1 but you can apply your own values to control cache.%s"), "<strong>", "</strong>", "<br /><br />") .
	sprintf(dgettext('BluePexWebFilter', "set Maximum download size on 'traffic mgmt' webfilter tab to a value that fits patterns your are applying.%s"), "<br />") .
	dgettext('BluePexWebFilter', "Microsoft may need 200Mb and youtube 4GB.")
);

$section->addInput(new Form_Textarea(
	'custom_refresh_patterns',
	dgettext('BluePexWebFilter', 'Custom refresh_patterns'),
	(isset($wf_cache['custom_refresh_patterns']) ? base64_decode($wf_cache['custom_refresh_patterns']) : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter custom refresh_patterns for better dynamic cache. This options will be included only if dynamic cache is enabled.'));

$form ->add($section);

print $form;
include("foot.inc");
?>
