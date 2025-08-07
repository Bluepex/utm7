<?php
/* This file contains utility functions for the CoreGUI 2 system. Those are
 * mostly functions that provide a higher abstraction level for the interfaces.
 * That way, the XML backend can be moved without having to worry about
 * interface compatibility. */

require_once('config.inc');

function &get_cg2_interfaces_config()
{
    global $config;

	$coregui2 = &$config['system']['webfilter'];
    if (!isset($coregui2))
        $coregui2 = array();
    if (!isset($coregui2) || is_string($coregui2))
        $coregui2 = array();
    return $coregui2;
}

function is_config_set($key)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	return isset($cg2_interfaces_config[$key]);
}

/* Returns the configuration of an element based on its configuration key and
 * form index. */
function get_element_config($key, $index = 0)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$config = $cg2_interfaces_config[$key]["element$index"];
	if (empty($config))
		return array();
	return $config;
}

/* Sets the configuration of the element based on its configuration key and
 * form index to the value specified. */
function set_element_config($key, $index, $value)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$cg2_interfaces_config[$key]["element$index"] = $value;
}

/* Gets the configuration of a row in a table. */
function get_row_config($key, $index, $row_index)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	if (!is_array($cg2_interfaces_config[$key]))
		$cg2_interfaces_config[$key] = array();
	if (!is_array($cg2_interfaces_config[$key]["element$index"]))
		$cg2_interfaces_config[$key]["element$index"] = array();
	if (!is_array($cg2_interfaces_config[$key]["element$index"]['item']))
		$cg2_interfaces_config[$key]["element$index"]['item'] = array();
	return $cg2_interfaces_config[$key]["element$index"]['item'][$row_index];
}

/* Sets the configuration of a row in a table. */
function set_row_config($key, $index, $row_index, $value)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$cg2_interfaces_config[$key]["element$index"]['item'][$row_index] = $value;
}

/* Removes the configuration of a row from the given table. */
function unset_row_config($key, $index, $row_index)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$items =& $cg2_interfaces_config[$key]["element$index"]['item'];
	unset($items[$row_index]);
	$items = array_values($items);
}

/* This is an optimized function, calls ksort only once. You should use this
 * instead of unset_row_config whenever possible. Should be given an array of
 * indices. */
function unset_row_entries($key, $index, $entries)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$items =& $cg2_interfaces_config[$key]["element$index"]['item'];
	foreach ($entries as $entry)
		unset($items[$entry]);
	$items = array_values($items);
}

/* Returns an array with the rows of a table. */
function get_table_rows($key, $index = 0)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$res = $cg2_interfaces_config[$key]["element$index"]['item'];
	if (empty($res))
		return array();
	return $res;
}

/* Sets the rows of a table to the given array. */
function set_table_rows($key, $index, $value)
{
	$cg2_interfaces_config =& get_cg2_interfaces_config();
	$cg2_interfaces_config[$key]["element$index"]['item'] = $value;
}

/* Performs a search on a given array to return the reference of a widget whose
 * name matches the specified name or NULL if there's no widget with that name
 * in this array. */
function &get_widget(&$widgets, $name)
{
	if (!is_array($widgets))
		return NULL;

	for ($i = 0; $i < count($widgets); $i++) {
		$widget = $widgets[$i];
		if ($widget->getFieldName() == $name)
			break;
		else
			$widget = NULL;
	}
	return $widgets[$i];
}

function get_page_status() {
	global $page_status;
	return $page_status;
}
?>
