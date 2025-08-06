<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Francisco Cavalcante <francisco.cavalcante@bluepex.com>, 2016
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");
require_once("squid.inc");
require_once('squid_reverse.inc');

$input_errors = array();
$savemsg = "";

if (!isset($config['system']['webfilter']['squidreverseredir'])) {
	$config['system']['webfilter']['squidreverseredir']['config'] = array();
}

if (isset($_REQUEST['id']) && is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (isset($_POST['id']) && is_numericint($_POST['id'])) {
	$id = $_POST['id'];
}

$wf_revRedirConfID = $config['system']['webfilter']['squidreverseredir']['config'][$id];
if (isset($id) && $wf_revRedirConfID) {
	$wf_revRedirConf = &$wf_revRedirConfID;
}

if (isset($_POST['save'])) {
	unset($input_errors);
	if (empty($input_errors)) {
		$wf_revRedir = array();

		$wf_revRedir['enable'] = $_POST['enable'];
		$wf_revRedir['name'] = $_POST['name'];
		$wf_revRedir['description'] = $_POST['description'];
		$wf_revRedir['protocol'] = !empty($_POST['protocol']) ? implode(",", $_POST['protocol']) : "";

		foreach ($_POST as $key => $value) {
			if (preg_match("/^uri(\d+)$/", $key)) {
				$wf_revRedir['row'][] = array("uri" => $value);
			}
		}

		$wf_revRedir['pathregex'] = $_POST['pathregex'];
		$wf_revRedir['redirurl'] = $_POST['redirurl'];

		$config['system']['webfilter']['squidreverseredir']['config'][$id] = $wf_revRedir;
		$savemsg = dgettext("BluePexWebFilter", "Reverse Redirect Settings applied successfully!");
		write_config($savemsg);
		squid_resync();
		header("Location: wf_reverse_redir.php");
		exit;
	}
}

$pgtitle = array(
	dgettext('BluePexWebFilter', 'WebFilter'), 
	dgettext('BluePexWebFilter', 'Reverse Proxy'),
	dgettext('BluePexWebFilter', 'Redirects'),
	dgettext('BluePexWebFilter', 'Edit')
);

include("head.inc");

if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg, 'success');

include("shortcuts_menu.php");

$tab_array = array();
$tab_array[] = array(dgettext('BluePexWebFilter', 'General'), false, '/webfilter/wf_reverse_general.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Web Servers'), false, '/webfilter/wf_reverse_peer.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Mappings'), false, '/webfilter/wf_reverse_uri.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Redirects'), true, '/webfilter/wf_reverse_redir.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse Redirect Mappings'));

$section->addInput(new Form_Checkbox(
	'enable',
	dgettext('BluePexWebFilter', 'Enable this redirect'),
	dgettext('BluePexWebFilter', 'If this field is checked, then this redirect will be available for reverse config.'),
	(isset($wf_revRedirConf['enable'])) ? $wf_revRedirConf['enable'] : '',
	'on'
));

$section->addInput(new Form_Input(
	'name',
	dgettext('BluePexWebFilter', 'Redirect name'),
	'text',
	(isset($wf_revRedirConf['name']) ? $wf_revRedirConf['name'] : "")
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Name to identify this redirect on webfilter reverse conf %s example: REDIR1"), '<br />'));

$section->addInput(new Form_Input(
	'description',
	dgettext('BluePexWebFilter', 'Redirect Description'),
	'text',
	(isset($wf_revRedirConf['description']) ? $wf_revRedirConf['description'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Redirect Description (optional)'));

$section->addInput(new Form_Select(
	'protocol',
	dgettext('BluePexWebFilter', 'Redirect Protocol'),
	(isset($wf_revRedirConf['protocol']) ? explode(",", $wf_revRedirConf['protocol']) : ""),
	['HTTP' => 'HTTP', 'HTTPS' => 'HTTPS'],
	true
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Protocol to redirect on. %s Use CTRL + click to select multiple"), '<br />'));

$numrows = !isset($wf_revRedirConf['row']) ? 0 : count($wf_revRedirConf['row']) -1;
for ($row=0; $row <= $numrows; $row++) {
	$group = new Form_Group($row == 0 ? "Blocked domains" : "");
	$group->addClass('repeatable');

	$inpt = new Form_Input(
		'uri' . $row,
		dgettext('BluePexWebFilter', 'Domains to redirect for'),
		'text',
		(isset($wf_revRedirConf['row'][$row]) ? $wf_revRedirConf['row'][$row]['uri'] : "")
	);

	if ($row == $numrows) {
		$inpt->setHelp(sprintf(dgettext('BluePexWebFilter', "%s Domains to match %s Example: mydomain.com sub.mydomain.com www.mydomain.com %s Do not enter http:&#47;&#47; or https:&#47;&#47; here! only the hostname is required."), '<strong>', '</strong><br /><br />', '<br /><br />'));
	}
	$group->add($inpt);

	$group->add(new Form_Button(
			'deleterow' . $row,
			dgettext('BluePexWebFilter', 'Delete'),
			null,
			'fa-trash'
	))->removeClass('btn-primary')->addClass('btn-warning btn-sm');

	$section->add($group);
}

$section->addInput(new Form_Button(
	'addrow',
	dgettext('BluePexWebFilter', 'Add'),
	null,
	'fa-plus'
))->addClass('btn-success');

$section->addInput(new Form_Input(
	'pathregex',
	dgettext('BluePexWebFilter', 'Path regex'),
	'text',
	(isset($wf_revRedirConf['pathregex']) ? $wf_revRedirConf['pathregex'] : "")
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Path regex to match %s Enter &#94;&#47;&#36; to match the domain only."), '<br />'));

$section->addInput(new Form_Input(
	'redirurl',
	dgettext('BluePexWebFilter', 'URL to redirect to'),
	'text',
	(isset($wf_revRedirConf['redirurl']) ? $wf_revRedirConf['redirurl'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'Enter the URL to redirect.'));

$form ->add($section);
print $form;
?>
<script type="text/javascript">
window.onload = function() {
	// Suppress "Delete row" button if there are fewer than two rows
	if ($('.repeatable').length <= 1) {
		$('#deleterow0').hide();
	} else {
		$('[id^=deleterow]').show();
	}
};
</script>
<?php include("foot.inc");
