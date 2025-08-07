<?php
/* Depending on how well PHP optimizes this, those functions might be really
 * slow. I would like some feedback from people running pfSense in embedded
 * systems, since I could not sense any slowness in the systems I tested. If
 * people start complaining about performance, optimizing this by getting rid
 * of the OO HTML code would be simple (although a tad uglier). We also could
 * choose to keep the OO code but instead generate tags only once, and then use
 * those tags as sprintf templates. This should be even simpler and cleaner,
 * but I'm not sure of how well sprintf performs. */

require_once('globals.inc');
require_once('html.inc');

define('PFSENSE_ICON_DIR', "/themes/{$g['theme']}/images/icons");
define('BUTTONS_PER_ROW', 2);

$row_button_specs = array();
$row_button_specs['add'] = array(
	'icon' => 'plus',
	'action' => 'add',
	'tooltip' => dgettext('BluePexWebFilter','Add a new entry based on this entry'),
);
$row_button_specs['edit'] = array(
	'icon' => 'e',
	'action' => 'edit',
	'tooltip' => dgettext('BluePexWebFilter','Edit this entry'),
);
$row_button_specs['delete'] = array(
	'icon' => 'x',
	'action' => 'del',
	'tooltip' => dgettext('BluePexWebFilter','Delete this entry'),
	'onclick' => "if (confirm('" . dgettext('BluePexWebFilter','Are you sure you want to delete this entry?') . "')) ",
);
$row_button_specs['move'] = array(
	'icon' => 'left',
	'action' => 'move',
	'tooltip' => dgettext('BluePexWebFilter','Move the selected entries to the top of this entry'),
	'onclick' => 'if (__rows_selected(%form%)) ',
	'attributes' => array(
		'onmouseover' => '__toggle_line(%form%, %row%, true);',
		'onmouseout' => '__toggle_line(%form%, %row%, false);',
	),
);

/* Add the disabled buttons */
foreach ($row_button_specs as $button) {
	$disabled = array(
		'icon' => $button['icon'] . '_d',
		'onclick' => 'return false;',
	);
	$row_button_specs["{$button}_dis"] = $disabled;
}

/* Ghetto inheritance */
$bottom_button_specs = $row_button_specs;
unset($bottom_button_specs['edit']);
$bottom_button_specs['add']['tooltip'] = dgettext('BluePexWebFilter','Add a new entry');
$bottom_button_specs['delete']['tooltip'] = dgettext('BluePexWebFilter','Delete the selected entries');
$bottom_button_specs['delete']['onclick'] = "if (__rows_selected(%form%) && confirm('" . dgettext('BluePexWebFilter','Are you sure you to delete the selected entries?') . "')) ";
$bottom_button_specs['move']['title'] =	dgettext('BluePexWebFilter','Move the selected entries to the bottom of the list');

function createTableButton($specs, $form_index, $row_index)
{
	$attributes = $specs['attributes'];
	if (!is_array($attributes))
		$attributes = array();
	$attributes['src'] = PFSENSE_ICON_DIR . "/icon_{$specs['icon']}.gif";
	$attributes['title'] = $specs['tooltip'];
	$attributes['style'] = 'cursor: pointer; cursor: hand;';
	$attributes['onclick'] .= $specs['onclick'] . "__perform_action('{$specs['action']}', $form_index, $row_index);";

	$img = new HtmlTag('img');
	foreach ($attributes as $key => $value) {
		if ($value == NULL)
			continue;

		$value = str_replace('%row%', $row_index, $value);
		$value = str_replace('%form%', $form_index, $value);
		$img->addAttribute($key, $value);
	}

	return $img->assemble();
}

function getButtonTable($form_index, $row_index, $buttons, $button_specs)
{
	$table = new HtmlTag('table');
	$table->addAttribute('border', 0);
	$table->addAttribute('cellspacing', 0);
	$table->addAttribute('cellpadding', 1);

	$i = 0;
	foreach ($buttons as $button) {
		/* Create the buttons. Some buttons won't be available, so skip those. */
		$specs = $button_specs[$button];
		if (!isset($specs))
			continue;

		$button = createTableButton($specs, $form_index, $row_index);
		/* Create new rows each N buttons */
		if ($i++ % BUTTONS_PER_ROW == 0) {
			if (isset($tr))
				$table->addValue($tr->assemble());
			$tr = new HtmlTag('tr');
		}

		$td = new HtmlTag('td', $button);
		$td->addAttribute('valign', 'middle');
		$tr->addValue($td->assemble());
	}
	if (isset($tr))
		$table->addValue($tr->assemble());

	return $table->assemble();
}

function getRowButtonTable($form_index, $row_index, $buttons)
{
	global $row_button_specs;
	return getButtonTable($form_index, $row_index, $buttons, $row_button_specs);
}

function getBottomButtonTable($form_index, $row_index, $buttons)
{
	global $bottom_button_specs;
	return getButtonTable($form_index, $row_index, $buttons, $bottom_button_specs);
}

function getAddButton($form_index, $row_index)
{
    global $bottom_button_specs;
    return createTableButton($bottom_button_specs['add'], $form_index, $row_index);
}
?>

