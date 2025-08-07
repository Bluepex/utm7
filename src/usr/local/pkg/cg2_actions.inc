<?php

/*  
 * ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * <desenvolvimento@bluepex.com>, 2015
 * ====================================================================
 */

require_once('cg2_helper.inc');
require_once('cg2_util.inc');
require_once('cg2_elements.inc');

$action_classes = array(
	'default' => DefaultAction,
	'edit' => EditAction,
	'add' => AddAction,
	'del' => DeleteAction,
	'move' => MoveAction,
	'apply' => ApplyAction,
	'dummy' => DummyAction,
);

class Action
{
	var $config_key;

	/* Config key is the ID of the interface in the configuration XML. */
	function Action($config_key)
	{
		$this->config_key = $config_key;
		$this->index = $_POST['cg2_element_index'];
	}

	/* Called every iteration through the elements in the XML. */
	function parseElement($form_index, $element) {}

	/* Called when $_POST is not empty. An action can fill $input_errors to
	 * display possible input errors to the user. */
	function onPost(&$input_errors) {}

	/* Return what you want to display. */
	function display() {}
}

class DefaultAction extends Action
{
	var $elements = array();
	var $do_not_save = false;
	var $step = 0;

	function parseElement($element_index, $element)
	{
		global $page_status;

		switch ($element['type']) {
			case 'form':
				$class = 'Form';
				$config = get_element_config($this->config_key, $element_index);
				break;
			case 'table':
				$class = 'Table';
				$config = get_table_rows($this->config_key, $element_index);
				break;
			case 'step':
				$class = 'Step';
				$config = $_SESSION['cg2_wizard_steps'][$this->step];
				if (!isset($config))
					$config = get_element_config($this->config_key, $element_index);
				$element['step'] = $this->step++;
				break;
			default:
				return;
		}
		if (isset($element['do_not_save']) || $page_status['wizard_mode'])
			$this->do_not_save = true;
		$this->elements[] =& new $class($element_index, $element, $config);
	}

	function onPost(&$input_errors)
	{
		global $pgtitle;
		global $page_status;

		if ($_POST['cg2_form_posted'] != 'true')
			return;

		$form_index = $_POST['cg2_element_index'];
		if (!isset($form_index))
			return;

		$form =& $this->elements[$form_index];
		if (!isset($form))
			return;

		$config = $form->getLocalConfig();
		$page_status['config'] = $config;

		$input_errors = $form->validateInput();
		$callback = $form->on_validate_event;
		if ($callback != NULL)
			$input_errors = array_merge($input_errors, $callback());

		if (empty($input_errors)) {
			if ($page_status['wizard_mode']) {
				/* Save to cookie */
				$_SESSION['cg2_wizard_steps'][$form_index] = $config;

				/* If we are done with it, tell the wizard */
				if ($page_status['wizard_finish']) {
					$callback = $page_status['on_wizard_finish'];
					if (function_exists($callback))
						$callback($_SESSION['cg2_wizard_steps']);

					unset($_SESSION['cg2_wizard_steps']);
					pfSenseHeader($page_status['script_url']);
				}
			}
			else {
				if (!$this->do_not_save) {
					/* Save to config file */
					set_element_config($this->config_key, $form_index, $config);
					write_config("$pgtitle: " . dgettext('BluePexWebFilter',"Saved a form."));
				}
				add_apply_info($this->config_key, $this->index);
			}
		}
	}

	function display()
	{
		$to_display = '';
		foreach ($this->elements as $element) {
			if (is_object($element))
				$to_display .= $element->display();
		}
		return $to_display;
	}

	function resetStep()
	{
		$this->step = 0;
		unset($this->elements);
	}
}

class EditAction extends Action
{
	var $form, $index, $row;

	function EditAction($config_key)
	{
		parent :: Action($config_key);

		$this->row = $_POST['cg2_row'];
		$GLOBALS['page_status']['row'] = $this->row;
	}

	function parseElement($form_index, $element)
	{
		if ($form_index == $this->index) {
			/* Add save button */
			$button = array(
				'type' => 'submit',
				'name' => 'cg2_save_button',
				'caption' => dgettext('BluePexWebFilter','Save'),
				'group_start' => 'yes'
			);
			if (!empty($element['on_save_javascript']))
				$button['on_click_javascript'] = $element['on_save_javascript'];
			$element['widget'][] = $button;

			/* Add cancel button */
			$script_url = $GLOBALS['page_status']['script_url'];
			$button = array(
				'type' => 'button',
				'name' => 'cg2_cancel_button',
				'caption' => dgettext('BluePexWebFilter','Cancel'),
				'on_click_javascript' => "document.location='$script_url';",
				'group_end' => 'yes'
			);
			$element['widget'][] = $button;

			/* Create our form */
			$config = get_row_config($this->config_key, $form_index, $this->row);
			$this->form = new Form($form_index, $element, $config);
		}
	}

	function display()
	{
		return $this->form->display();
	}

	function onPost(&$input_errors)
	{
		global $pgtitle;
		global $page_status;

		if ($_POST['cg2_form_posted'] != 'true')
			return;

		$form =& $this->form;
		$form_index = $this->index;
		$row_index = $this->row;

		$config = $form->getLocalConfig();
		$page_status['config'] = $config;

		$input_errors = $form->validateInput();

		if (empty($input_errors)) {
			/* Save to config file */
			set_row_config($this->config_key, $form_index, $row_index, $config);
			write_config("$pgtitle: " . dgettext('BluePexWebFilter',"Saved a form"));

			add_apply_info($this->config_key, $this->index);

			pfSenseHeader($GLOBALS['page_status']['script_url']);
		}
	}
}

class AddAction extends EditAction
{
	function AddAction($config_key)
	{
		parent :: EditAction($config_key);
		$this->config_row = $this->row;

		$rows = get_table_rows($config_key, $this->index);
		$this->save_row = count($rows);
	}

	function parseElement($form_index, $element)
	{
		$this->row = $this->config_row;
		parent :: parseElement($form_index, $element);
	}

	function onPost(&$input_errors)
	{
		$this->row = $this->save_row;
		parent :: onPost($input_errors);
	}
}

class DeleteAction extends DefaultAction
{
	function onPost(&$input_errors)
	{
		global $pgtitle;

		/* Unset the action, since the next step before deleting is going back to the
		 * default action, unlike add and edit. */
		unset($_POST['cg2_action']);

		$row_index = $_POST['cg2_row'];
		$element_index = $_POST['cg2_element_index'];

		$row_config = get_row_config($this->config_key, $element_index, $row_index);
		if (isset($row_config))
			$to_delete = array($row_index);
		else {
			/* If the row doesn't exist, the bottom button was clicked, so
			 * delete the selected rows. */
			$to_delete = $_POST['cg2_selected_rows'];
		}

		if (is_array($to_delete)) {
			unset_row_entries($this->config_key, $element_index, $to_delete);

			if (count($to_delete) > 0) {
				$table =& $this->elements[$element_index];

				/* Write to the XML */
				write_config("$pgtitle: " . dgettext('BluePexWebFilter',"Deleted an entry"));

				/* Synchronize the table config */
				$table->setRows(get_table_rows($this->config_key, $element_index));

				add_apply_info($this->config_key, $element_index);
			}
		}
	}
}

class MoveAction extends DefaultAction
{
	function onPost(&$input_errors)
	{
		global $pgtitle;

		/* Unset the action, since the next step before moving is going back to the
		 * default action. */
		unset($_POST['cg2_action']);

		$table_index = $_POST['cg2_element_index'];
		$to_move = $_POST['cg2_selected_rows'];
		$move_under = $_POST['cg2_row'];

		$rows = get_table_rows($this->config_key, $table_index);
		$new_rows = array();

		for ($i = 0; $i < $move_under; $i++) {
			if (!in_array($i, $to_move))
				$new_rows[] = $rows[$i];
		}
		for ($i = 0; $i < count($rows); $i++) {
			if ($i == $move_under) continue;
			if (in_array($i, $to_move))
				$new_rows[] = $rows[$i];
		}
		if ($move_under < count($rows))
			$new_rows[] = $rows[$move_under];
		for ($i = $move_under + 1; $i < count($rows); $i++) {
			if (!in_array($i, $to_move))
				$new_rows[] = $rows[$i];
		}

		set_table_rows($this->config_key, $table_index, $new_rows);
		write_config("$pgtitle: " . dgettext('BluePexWebFilter',"Swapped entries in table") . " $table_index.");

		$table =& $this->elements[$table_index];
		$table->setRows($new_rows);

		add_apply_info($this->config_key, $table_index);
	}
}

class ApplyAction extends DefaultAction
{
	var $apply_elements;

	function ApplyAction($config_key)
	{
		$this->apply_elements = explode(',', $_GET['elements']);
		sort($this->apply_elements);

		delete_apply_file($config_key);

		parent :: DefaultAction($config_key);
	}

	function parseElement($element_index, $element)
	{
		$elements =& $this->apply_elements;
		if ($elements[0] == $element_index) {
			array_shift($elements);

			$callback = $element['on_apply_event'];
			if (function_exists($callback)) $callback();
		}

		parent :: parseElement($element_index, $element);
	}
}

class DummyAction extends DefaultAction
{
	var $post_event_callback;

	/* On post, do not save the config, just check for input errors. */
	function onPost(&$input_errors) {
		global $page_status;

		if ($_POST['cg2_form_posted'] != 'true')
			return;

		$form_index = $_POST['cg2_element_index'];
		if (!isset($form_index))
			return;

		$form =& $this->elements[$form_index];
		if (!isset($form))
			return;

		$input_errors = $form->validateInput();
		$callback = $form->on_validate_event;
		if ($callback != NULL)
			$input_errors = array_merge($input_errors, $callback());

		if (empty($input_errors)) {
			$config = $form->getLocalConfig();
			$page_status['config'] = $config;

			if (function_exists($this->post_event_callback))
				$this->post_event_callback($config);
		}
	}

	function parseElement($form_index, $element) {
		parent :: parseElement($form_index, $element);

		if ($element['on_post_event'])
			$this->post_event_callback = $element['on_post_event'];
	}
}
?>

