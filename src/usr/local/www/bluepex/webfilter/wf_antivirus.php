<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by  Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");
require_once("squid.inc");

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['instance']['config'])) {
	$config['system']['webfilter']['instance']['config'] = array();
}
$wf_instance = &$config['system']['webfilter']['instance']['config'];

if (!isset($config['system']['webfilter']['squidantivirus']['config'][0])) {
	$config['system']['webfilter']['squidantivirus']['config'][0] = array();
}
$av_config = &$config['system']['webfilter']['squidantivirus']['config'][0];

if (isset($_POST['save'])) {
	squid_validate_antivirus($_POST, $input_errors);

	if (empty($input_errors)) {
		$av_config = array();

		$av_config['enable'] = $_POST['enable'];
		$av_config['instances'] = !empty($_POST['instances']) ? implode(",", $_POST['instances']) : false;
		$av_config['client_info'] = 'both';
		$av_config['enable_advanced'] = 'disabled';
		$av_config['clamav_url'] = $_POST['clamav_url'];
		$av_config['clamav_safebrowsing'] = '';
		$av_config['clamav_disable_stream_scanning'] = $_POST['clamav_disable_stream_scanning'];
		$av_config['clamav_update'] = $_POST['clamav_upd'];
		$av_config['clamav_dbregion'] = $_POST['clamav_dbregion'];
		$av_config['clamav_dbservers'] = $_POST['clamav_dbservers'];

		$config['system']['webfilter']['squidantivirus']['config'][0] = $av_config;
		$savemsg = dgettext("BluePexWebFilter", "Antivirus settings applied successfully!");
		set_flash_message('success', $savemsg);
		write_config($savemsg);
		squid_resync();
		header("Location: /webfilter/wf_antivirus.php");
		exit;
	}
}

/* Manual ClamAV database update */
if (isset($_POST['update_av'])) {
	$savemsg = dgettext("BluePexWebFilter", "Antivirus update is started!");
	squid_update_clamav();
}

$pgtitle = array(dgettext('BluePexWebFilter', 'WebFilter'), dgettext('BluePexWebFilter', 'Proxy Server: Antivirus Settings'));
include("head.inc");

if ($input_errors) {
	print_input_errors($input_errors);
}
if ($savemsg) {
	print_info_box($savemsg, 'success');
}

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'Antivirus'), true, '/webfilter/wf_antivirus.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Antivirus Status'), false, '/webfilter/wf_antivirus_status.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'Update Antivirus'));

$section->addInput(new Form_Checkbox(
	'enable',
	dgettext("BluePexWebFilter", 'Active Antivirus'),
	dgettext("BluePexWebFilter", 'Check to enable navigation by antivirus.'),
	(isset($av_config['enable'])) ? $av_config['enable'] : '',
	'on'
));

$instances_list = array();
if (!empty($wf_instance)) {
	foreach ($wf_instance as $instance_config) {
		$instances_list[] = $instance_config['server']['name'];
	}
}
$section->addInput(new Form_Select(
	'instances',
	dgettext('BluePexWebFilter', 'Proxy Instances'),
	(isset($av_config['instances']) ? explode(",", $av_config['instances']) : ""),
	$instances_list,
	true
))->setHelp(dgettext('BluePexWebFilter', "Select the proxy instances to enable antivirus."));

$section->addInput(new Form_Checkbox(
	'clamav_disable_stream_scanning',
	dgettext('BluePexWebFilter', 'Filter Audio/Video Streams'),
	dgettext("BluePexWebFilter", 'Check to enable antivirus scanning of streamed video and audio.'),
	(isset($av_config['clamav_disable_stream_scanning'])) ? $av_config['clamav_disable_stream_scanning'] : '',
	'on'
));

$section->addInput(new Form_StaticText(
	'',
	"<div class='alert alert-danger' id='alert-enable-stream'><strong>" . dgettext('BluePexWebFilter', "Note: This option enabled consumes significant amount of RAM Memory.") . '</strong></div>'
));

$regions = get_regions_clamav_db_update();
$section->addInput(new Form_Select(
	'clamav_dbregion',
	dgettext('BluePexWebFilter', 'Region'),
	(isset($av_config['clamav_dbregion']) ? $av_config['clamav_dbregion'] : "us"),
	$regions
))->setHelp(dgettext('BluePexWebFilter', "Select the region to update the database."));

$update_times = array(
	'1' => dgettext("BluePexWebFilter", "every 1  hours"),
	'2' => dgettext("BluePexWebFilter", "every 2  hours"),
	'3' => dgettext("BluePexWebFilter", "every 3  hours"),
	'4' => dgettext("BluePexWebFilter", "every 4  hours"),
	'6' => dgettext("BluePexWebFilter", "every 6  hours"),
	'8' => dgettext("BluePexWebFilter", "every 8  hours"),
	'12' => dgettext("BluePexWebFilter", "every 12  hours"),
	'24' => dgettext("BluePexWebFilter", "every 24  hours")
);

$section->addInput(new Form_Select(
	'clamav_upd',
	dgettext('BluePexWebFilter', 'Antivirus Database Update'),
	(isset($av_config['clamav_update']) ? $av_config['clamav_update'] : "6"),
	$update_times
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Optionally, you can schedule Antivirus definitions updates via cron. %sSelect the desired frequency here."), '<br />'));

$section->addInput(new Form_Button(
	'update_av',
	dgettext('BluePexWebFilter', 'Update Antivirus'),
	null,
	'fa-undo'
))->removeClass('btn-primary')->addClass('btn-success')->setHelp(dgettext('BluePexWebFilter', '<b>Note: This will take a while.'.'</b>'." Check freshclam log on the <a href='wf_antivirus_status.php'>'Antivirus Status' tab</a> for progress information."));

$form ->add($section);

print $form;
?>
<script type="text/javascript">
window.onload = function(){

	$('#clamav_disable_stream_scanning').click(function() {
		hideInput('alert-enable-stream', !$('#clamav_disable_stream_scanning').prop('checked'));
	});

	// ---------- Set initial page display state ----------------------------------------------------------------------
	hideInput('alert-enable-stream', true);
};
</script>
<?php include("foot.inc");
