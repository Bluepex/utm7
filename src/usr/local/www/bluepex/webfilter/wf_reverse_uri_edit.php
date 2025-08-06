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

if (!isset($config['system']['webfilter']['squidreverseuri'])) {
	$config['system']['webfilter']['squidreverseuri']['config'] = array();
}

if (isset($_REQUEST['id']) && is_numericint($_REQUEST['id'])) {
	$id = $_REQUEST['id'];
}

if (isset($_POST['id']) && is_numericint($_POST['id'])) {
	$id = $_POST['id'];
}

$wf_revUriConfID = $config['system']['webfilter']['squidreverseuri']['config'][$id];
if (isset($id) && $wf_revUriConfID) {
	$wf_revUriConf = &$wf_revUriConfID;
}

if (isset($_POST['save'])) {
	unset($input_errors);
	if (empty($input_errors)) {
		$wf_revUri = array();

		$wf_revUri['enable'] = $_POST['enable'];
		$wf_revUri['name'] = $_POST['name'];
		$wf_revUri['description'] = $_POST['description'];
		$wf_revUri['peers'] = !empty($_POST['peers']) ? implode(",", $_POST['peers']) : "";

		foreach ($_POST as $key => $value) {
			if (preg_match("/^uri(\d+)$/", $key)) {
				$wf_revUri['row'][] = array("uri" => $value);
			}
		}

		$config['system']['webfilter']['squidreverseuri']['config'][$id] = $wf_revUri;
		$savemsg = dgettext("BluePexWebFilter", "Reverse Uri Settings applied successfully!");
		write_config($savemsg);
		squid_resync();
		header("Location: wf_reverse_uri.php");
		exit;
	}
}

$pgtitle = array(
	dgettext('BluePexWebFilter', 'WebFilter'), 
	dgettext('BluePexWebFilter', 'Reverse Proxy'), 
	dgettext('BluePexWebFilter', 'Mappings'),
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
$tab_array[] = array(dgettext('BluePexWebFilter', 'Mappings'), true, '/webfilter/wf_reverse_uri.php');
$tab_array[] = array(dgettext('BluePexWebFilter', 'Redirects'), false, '/webfilter/wf_reverse_redir.php');
display_top_tabs($tab_array);

$form = new Form();
$section = new Form_Section(dgettext("BluePexWebFilter", 'WF Reverse Peer Mappings'));

$section->addInput(new Form_Checkbox(
	'enable',
	dgettext('BluePexWebFilter', 'Enable this URI'),
	dgettext('BluePexWebFilter', 'If this field is checked, then this URI(Uniform Resource Name) will be available for reverse config.'),
	(isset($wf_revUriConf['enable'])) ? $wf_revUriConf['enable'] : '',
	'on'
));

$section->addInput(new Form_Input(
	'name',
	dgettext('BluePexWebFilter', 'Group name'),
	'text',
	(isset($wf_revUriConf['name']) ? $wf_revUriConf['name'] : "")
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Name to identify this URI on webfilter reverse conf %s example: URI1"), '<br />'));

$section->addInput(new Form_Input(
	'description',
	dgettext('BluePexWebFilter', 'Group Description'),
	'text',
	(isset($wf_revUriConf['description']) ? $wf_revUriConf['description'] : "")
))->setHelp(dgettext('BluePexWebFilter', 'URI Group Description (optional)'));

$items = explode(',', 'peers');
$srcoptions = array();
$srcselected = array();
$source_txt = $config['system']['webfilter']['squidreversepeer']['config'];

if (!empty($source_txt)) {
	foreach ($source_txt as $opt) {
		$source_name = $opt['name'] ;
		$source_value = $opt['name'];
		$srcoptions[$source_value] = $source_name;

		if (in_array($source_value, $items)) {
			array_push($srcselected, $source_value);
		}
	}
}

$section->addInput(new Form_Select(
	'peers',
	dgettext('BluePexWebFilter', 'Peers'),
	(isset($wf_revUriConf['peers']) ? explode(",", $wf_revUriConf['peers']) : ''),
	$srcoptions,
	true
))->setHelp(sprintf(dgettext('BluePexWebFilter', "Apply this Group Mappings to selected Peers %s Use CTRL + click to select."), '<br />'));

$numrows = !isset($wf_revUriConf['row']) ? 0 : count($wf_revUriConf['row']) -1;
for ($row=0; $row <= $numrows; $row++) {
	$group = new Form_Group($row == 0 ? "URIs" : "");
	$group->addClass('repeatable');

	$inpt = new Form_Input(
		'uri' . $row,
		dgettext('BluePexWebFilter', 'URI to publish'),
		'text',
		(isset($wf_revUriConf['row'][$row]) ? $wf_revUriConf['row'][$row]['uri'] : "")
	);

	if ($row == $numrows) {
		$inpt->setHelp(sprintf("%s<br/><br/><strong>%s</strong><br/> %s<br/>%s<br />%s",
		dgettext('BluePexWebFilter', "Enter URL <strong>regex</strong> to match."),
		dgettext('BluePexWebFilter', "Examples:"),
		dgettext('BluePexWebFilter', ".mydomain.com .mydomain.com/test"),
		dgettext('BluePexWebFilter', "www.mydomain.com http://www.mydomain.com/"),
		dgettext('BluePexWebFilter', "^http://www.mydomain.com/.*$")
		));
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
