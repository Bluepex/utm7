<?php
/* cg2_helper.inc
 * CoreGUI2 helper functions (not meant to be included by user interfaces). */

require_once('globals.inc');
require_once('xmlparse.inc');

define('MASTER_XML_BASE', '/usr/local/pkg');
define('INTERFACE_XML_BASE', '/usr/local/pkg');

if (!function_exists('gettext')) {
	function gettext($text) { return $text; }
}
if (!function_exists('pfSenseHeader')) {
	function pfSenseHeader($location) { header("Location: $location"); }
}
if (!function_exists('get_interface_str')) {
	function get_interface_str() { return trim(shell_exec('ifconfig  -l')); }
}

function javascript_file($file)
{
	return "<script type=\"text/javascript\" language=\"javascript\" src=\"/$file\"></script>\n";
}

function css_file($file)
{
	return "<link rel=\"stylesheet\" type=\"text/css\" href=\"$file\" media=\"all\" />\n";
}

function parse_xml_config_wf($cffile, $rootobj, $isstring = "false") {

	global $depth, $curpath, $parsedcfg, $havedata, $listtags;
	$parsedcfg = array();
	$curpath = array();
	$depth = 0;
	$havedata = 0;

	$xml_parser = xml_parser_create();

	xml_set_element_handler($xml_parser, "wf_startElement", "wf_endElement");
	xml_set_character_data_handler($xml_parser, "cdata");
	xml_parser_set_option($xml_parser,XML_OPTION_SKIP_WHITE, 1); 

	if (!($fp = fopen($cffile, "r"))) {
		log_error(gettext("Error: could not open XML input") . "\n");
		return -1; 
	}

	while ($data = fread($fp, 4096)) {
		if (!xml_parse($xml_parser, $data, feof($fp))) {
			log_error(sprintf(gettext('XML error: %1$s at line %2$d in %3$s') . "\n",
						xml_error_string(xml_get_error_code($xml_parser)),
						xml_get_current_line_number($xml_parser),
						$cffile));
			return -1; 
		}
	}
	xml_parser_free($xml_parser);

	if ($rootobj) {
		if (!is_array($rootobj))
			$rootobj = array($rootobj);
		foreach ($rootobj as $rootobj_name)
			if ($parsedcfg[$rootobj_name])
				break;

		if (!$parsedcfg[$rootobj_name]) {
			log_error(sprintf(gettext("XML error: no %s object found!") . "\n", implode(" or ", $rootobj)));
			return -1; 
		}
		return $parsedcfg[$rootobj_name];
	} else {
		return $parsedcfg;
	}
}

function wf_startElement($parser, $name, $attrs) {
	global $parsedcfg, $depth, $curpath, $havedata, $listtags;

	array_push($curpath, strtolower($name));

	$ptr =& $parsedcfg;
	foreach ($curpath as $path) {
		$ptr =& $ptr[$path];
	}

	/* is it an element that belongs to a list? */
	if (in_array(strtolower($name), $listtags)) {

		/* is there an array already? */
		if (!is_array($ptr)) {
			/* make an array */
			$ptr = array();
		}

		array_push($curpath, count($ptr));

	} else if (isset($ptr)) {
		/* multiple entries not allowed for this element, bail out */
		die(sprintf(gettext('XML error: %1$s at line %2$d cannot occur more than once') . "\n",
				$name,
				xml_get_current_line_number($parser)));
	}

	$depth++;
	$havedata = $depth;
}

function wf_endElement($parser, $name) {
	global $depth, $curpath, $parsedcfg, $havedata, $listtags;

	if ($havedata == $depth) {
		$ptr =& $parsedcfg;
		foreach ($curpath as $path) {
			$ptr =& $ptr[$path];
		}
		$ptr = "";
	}

	array_pop($curpath);

	if (in_array(strtolower($name), $listtags))
		array_pop($curpath);

	$depth--;
}

function open_interface_xml($interface)
{
	global $listtags;
	$listtags = array(
		'include',
		'include_javascript',
		'include_css',
		'element',
		'widget',
		'option',
	);

	$filename = INTERFACE_XML_BASE . "/po/" . $_SESSION['language'] . "/$interface";
	if (!file_exists($filename)) {
		$filename = INTERFACE_XML_BASE . "/$interface";
		if (!file_exists($filename))
			die(dgettext('BluePexWebFilter','ERROR: Unable to find the interface file!'));
	}
	return parse_xml_config_wf($filename, 'coregui_interface');
}

function open_master_xml($master)
{
	global $listtags;
	$listtags = array(
		'include',
		'include_css',
		'include_javascript',
		'tab',
	);

	$filename = MASTER_XML_BASE . "/po/" . $_SESSION['language'] . "/$master";
	if (!file_exists($filename)) {
		$filename = MASTER_XML_BASE . "/$master";
		if (!file_exists($filename))
			die(dgettext('BluePexWebFilter','ERROR: The specified master file does not exist.'));
	}
	return parse_xml_config_wf($filename, 'coregui_master');
}

function get_apply_file($config_key)
{
	global $g;
	return "{$g['tmp_path']}/coregui2_{$config_key}.apply";
}

function add_apply_info($config_key, $element_index)
{
	$apply_file = get_apply_file($config_key);
	if (file_exists($apply_file))
		$info = explode(' ', trim(file_get_contents($apply_file)));
	else
		$info = array();

	$info[] = $element_index;
	$info = trim(implode(' ', array_unique($info)));
	file_put_contents($apply_file, $info);
}

function get_apply_info($config_key)
{
	$apply_file = get_apply_file($config_key);
	if (!file_exists($apply_file))
		return array();

	$info = explode(' ', trim(file_get_contents($apply_file)));
	if (trim($info[0]) == '')
		return array();

	return $info;
}

function delete_apply_file($config_key)
{
	$apply_file = get_apply_file($config_key);
	if (file_exists($apply_file)) unlink($apply_file);
}

/* Our own version of print_info_box. */
function cg2_print_info_box($elements)
{
	$theme = $GLOBALS['g']['theme'];
	$script_url = $GLOBALS['page_status']['script_url'];
	$elements = implode(',', $elements);

	$msg = dgettext('BluePexWebFilter',"The configuration has been changed. You must apply the changes in order for them to take effect.");
	$value = dgettext('BluePexWebFilter',"Apply changes");
	print <<<EOD
<div id="redbox" style="color: white; background-color: #990000; font-weight: bold; margin-bottom: 3ex;">

<table width="100%" style="padding: 0 10px">
<tr>
	<td width="1%">
		<img src="/themes/$theme/images/icons/icon_exclam.gif" alt="" />
	</td>
	<td style="padding: 0 10px">
		$msg
	</td>
	<td width="1%">
		<input type="button" class="formbtn" value="$value" onClick="document.location='$script_url&amp;action=apply&amp;elements=$elements'" />
	</td>
</tr>
</table>

</div>

<script type="text/javascript" language="javascript">
<!--
NiftyCheck();
Rounded('div#redbox', 'all', '#fff', '#990000', 'smooth');
-->
</script>

EOD;
}

/* A funny name for a function, eh? */
function get_get_string($valid_vars)
{
	$var_array = array();
	foreach ($valid_vars as $var) {
		if (isset($_GET[$var]))
			$var_array[] = "$var={$_GET[$var]}";
		else if (isset($_POST["cg2_$var"]))
			$var_array[] = "$var=" . $_POST["cg2_$var"];
	}
	return getenv('SCRIPT_NAME') . '?' . implode('&', $var_array);
}
?>

