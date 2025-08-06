<?php
/* cg2.php
 * Displays CoreGUI 2 XML files. */
ob_start();
session_start();
$nocsrf = true;
require_once('guiconfig.inc');
require_once('cg2_helper.inc');
require_once('cg2_util.inc');
/* This will be used to keep track of the page status */
$page_status = array(
	'action' => NULL,
	'config_key' => NULL,
	'posted' => !empty($_POST),
	'form_posted' => $_POST['cg2_form_posted'] == 'true',
	'input_errors' => NULL,
	'script_url' => get_get_string(array('xml', 'tab')),
);
/* Open the master XML */
$master = open_master_xml($_GET['xml']);
/* Open the interface XML */
$active_tab = (isset($_GET['tab']) ? $_GET['tab'] : 0);
$page_status['active_tab'] = $active_tab;
$interface_file = $master['tab'][$active_tab]['interface'];
if (!isset($interface_file))
	die(dgettext('BluePexWebFilter','ERROR: Interface file not specified or invalid tab number.'));
$interface = open_interface_xml($interface_file);
/* The config key */
$config_key = $interface['config'];
if (isset($config_key)) {
	$page_status['config_key'] == $config_key;
	if (!is_config_set($config_key)) {
		/* Check whether we have a wizard for this screen */
		$interface_file = $master['tab'][$active_tab]['wizard'];
		if (isset($interface_file)) {
			$wizard_action = $_POST['cg2_wizard_action'];
			$step = $_POST['cg2_wizard_step'];
			if (!isset($step))
				unset($_SESSION['cg2_wizard_steps']);
			$step = intval($step);
			$page_status['wizard_mode'] = true;
			switch ($wizard_action) {
				default:
					$interface = open_interface_xml($interface_file);
					break;
				case 'cancel':
					unset($_POST);
					unset($_SESSION['cg2_wizard_steps']);
					unset($page_status['wizard_mode']);
					break;
				case 'finish':
					$interface = open_interface_xml($interface_file);
					$page_status['wizard_finish'] = true;
					$page_status['on_wizard_finish'] = $interface['on_wizard_finish'];
					break;
			}
			if ($wizard_action != 'cancel') {
				$page_status['wizard_step'] = $step;
				$page_status['wizard_total_steps'] = count($interface['element']);
			}
		}
	}
}
/* Load the custom PHP includes */
if (!is_array($master['include']))
	$master['include'] = array();
if (!is_array($interface['include']))
	$interface['include'] = array();
$includes = array_merge($master['include'], $interface['include']);
foreach ($includes as $include)
	require_once($include);
// Create $pgtitle (must be done before the right action is invoked)
$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', $master['title']));
if ($page_status['wizard_mode'])
	$pgtitle[] = gettext('Wizard');
/* Order matters here. We must first require the custom PHP includes, or else
 * the callbacks won't be seen by the form. */
require_once('cg2_actions.inc');
/* Define the action */
if (!isset($config_key)) {
	$action = 'dummy';
} else {
	$action = $_GET['action'];
	if (!isset($action))
		$action = $_POST['cg2_action'];
}
$page_status['action'] = $action;
$class = $action_classes[$action];
if (!isset($class))
	$class = $action_classes['default'];
$action_obj = new $class($config_key);
/* Parse the elements */
if (is_array($interface['element'])) {
	foreach ($interface['element'] as $index => $element) {
		$action_obj->parseElement($index, $element);
	}
}
/* If we have $_POST, inform the action about it */
unset($input_errors);
if (!empty($_POST))
	$action_obj->onPost($input_errors);
$page_status['input_errors'] = $input_errors;
/* Now we've got to advance the wizard */
if ($page_status['wizard_mode']) {
	$wizard_action = $_POST['cg2_wizard_action'];
	if (empty($input_errors)) {
		if ($wizard_action == 'next')
			$step++;
		else if ($wizard_action == 'previous')
			$step--;
		$page_status['wizard_step'] = $step;
		/* Parse the elements */
		$action_obj->resetStep();
		if (is_array($interface['element'])) {
			foreach ($interface['element'] as $index => $element)
				$action_obj->parseElement($index, $element);
		}
	}
}
/* Start drawing the HTML */
$closehead = false;
include('head.inc');
/* Custom stylesheets */
if (!is_array($master['include_css']))
	$master['include_css'] = array();
if (!is_array($interface['include_css']))
	$interface['include_css'] = array();
$includes = array_merge($master['include_css'], $interface['include_css']);
foreach ($includes as $include)
    echo "<link rel=\"stylesheet\" href=\"$include\" type=\"text/css\" />\n";
echo "</head>\n";
?>
<script type="text/javascript">
function urlreport(domain) {
	window.open("wf_reports.php?domain=" + domain,"WFReport","menubar=0,scrollbars=1,resizable=1,toolbar=0,width=800,height=600");
}
</script>
<?php
print "<body onload='{$jsevents['body']['onload']}'>\n";
include('fbegin.inc');
if (!empty($input_errors)) print_input_errors($input_errors);
else if ($action != 'edit' && $action != 'add') {
	$info = get_apply_info($config_key);
	if (!empty($info))
		cg2_print_info_box($info);
}
/* Our JavaScript helpers */
print(javascript_file('webfilter/coregui2.js'));
/* Custom JavaScript helpers */
if (!is_array($master['include_javascript']))
	$master['include_javascript'] = array();
if (!is_array($interface['include_javascript']))
	$interface['include_javascript'] = array();
$includes = array_merge($master['include_javascript'], $interface['include_javascript']);
foreach ($includes as $include)
	print(javascript_file($include));
/* Display the tabs */
/* XXX Maybe we should use a topbar if there's only one element. The problem is
 * that the topbar is ugly (not only appearance-wise, but there are hardcoded
 * styles and the such). */
$tab_array = array();
foreach ($master['tab'] as $i => $tab) {
	$active = ($active_tab == $i);
	if (!empty($master['tab'][$i]['url']))
		$url = $master['tab'][$i]['url'];
	else
		$url = getenv('SCRIPT_NAME') . "?xml={$_GET['xml']}&amp;tab=$i";
	$tab_array[] = array($tab['label'], $active, $url);
}
?>
<table style="background: #FFFFFF" width="100%" border="0" cellpadding="0" cellspacing="0">
    <tr>
        <td>
		<?php include("shortcuts_menu.php"); ?>
        <td>
    </tr>
    <tr><td>&nbsp;</td></tr>
    <tr><td>
<?php
display_top_tabs($tab_array);
print <<<EOD
    </td></tr>
    <tr>
        <td>
<div id="mainarea">
<table style='background: #FFFFFF' width="100%" border="0" cellspacing="0" cellpadding="0">
<tr>
<td class="tabcont">
EOD;
/* Some buffering magic, first display whatever the widgets might display
 * (headers and something to be downloaded, for example), then display the
 * actual widgets. */
$page = ob_get_contents();
ob_clean();
$action_out = $action_obj->display();
ob_flush();
print $page;
print $action_out;
print <<<EOD
</td>
</tr>
</table>
</div>
</td></tr></table>
EOD;
/* And that's it */
include('fend.inc');
ob_flush();
?>
