<?php
/* cg2_elements.inc
 * Defines and implements the elements that can exist in a cg2 interface. */

require_once('html.inc');
require_once('cg2_helper.inc');
require_once('cg2_widgets.inc');
require_once('cg2_buttons.inc');

class Element
{
	var $index;
	var $html_form;
	var $apply_string;

	function Element($index, $element_data, $local_config)
	{
		$this->index = $index;

		/* Create the form and initialize it */
		$form = new HtmlTag('form');
		$script_url = $GLOBALS['page_status']['script_url'];
		$form->addAttribute('action', $script_url);
		$form->addAttribute('method', 'post');
		$form->addValue(create_hidden_input('cg2_element_index', $index));

		$this->form =& $form;
	}
}

class Form extends Element
{
	var $widgets = array();
	var $javascript = '';
	var $on_validate_event, $on_sync_event;

	function Form($index, $form_data, $local_config)
	{
		global $widget_classes;

		parent :: Element($index, $form_data, $local_config);

		if (isset($_POST['cg2_action']))
			$this->form->addValue(create_hidden_input('cg2_action', $_POST['cg2_action']));
		if (isset($_POST['cg2_row']))
			$this->form->addValue(create_hidden_input('cg2_row', $_POST['cg2_row']));

		$posted = $_POST['cg2_form_posted'] == 'true'
			&& isset($_POST['cg2_element_index'])
			&& $_POST['cg2_element_index'] == $index;
		if ($posted)
			$default_source = 'post';
		else {
			if (!empty($local_config))
				$default_source = 'config';
			else
				$default_source = 'defaults';
		}

		/* Create and set the value of each widget */
		if (is_array($form_data['widget'])) {
			foreach ($form_data['widget'] as $widget_index => $widget_data) {
				$type = $widget_data['type'];
				$name = $widget_data['name'];
				if ($type == 'custom')
					$class = $widget_data['class'];
				else
					$class = $widget_classes[$type];
				if (!class_exists($class))
					$class = $widget_classes['unknown'];

				$widget_obj = new $class($form_data['widget'], $widget_index, $local_config, $index);

				$source = isset($widget_data['show_php_variable']) ? 'variable' : $default_source;
				$widget_obj->setValue($source);

				$this->widgets[] = $widget_obj;
			}
		}

		/* Now ask the widgets if they have javascript code to run */
		for ($i = 0; $i < count($this->widgets); $i++) {
			$widget =& $this->widgets[$i];
			$this->javascript .= $widget->getJavascript($this->widgets);
		}

		/* on_form_load_event
		 * The callback should modify the array of widgets as necessary (you
		 * must get the parameter by reference if you are going to change
		 * something). */
		$callback = $form_data['on_form_load_event'];
		if (function_exists($callback))
			$callback($this->widgets, $local_config);

		if (function_exists($form_data['on_validate_event']))
			$this->on_validate_event = $form_data['on_validate_event'];

		if (function_exists($form_data['on_sync_event']))
			$this->on_sync_event = $form_data['on_sync_event'];
	}

	function validateInput()
	{
		$input_errors = array();
		$custom_errors = array();
		$disabled_widgets = $_POST['cg2_disabled_widgets'];
		if (!is_array($disabled_widgets)) $disabled_widgets = array();

		foreach ($this->widgets as $widget) {
			if ($widget->isDisabled($disabled_widgets)) continue;

			$widget->validateInput();
			$input_errors = array_merge($input_errors, $widget->getInputErrors());
		}

		/* on_validate_event
		 * You should return an array of strings, one string for each array
		 * input error your code detects. */
		$callback = $this->on_validate_event;
		if (isset($callback))
			$custom_errors = $callback();

		return array_merge($input_errors, $custom_errors);
	}

	function display()
	{
		$form =& $this->form;
		$form->addValue(create_hidden_input('cg2_form_posted', 'true'));

		$table = new HtmlTag('table');
		$table->addAttribute('width', '100%');
		$table->addAttribute('border', 0);
		$table->addAttribute('cellpadding', 6);
		$table->addAttribute('cellspacing', 0);

		$in_group = false;
		$count = count($this->widgets);
		for ($i = 0; $i < $count; $i++) {
			$widget =& $this->widgets[$i];

			if ($widget->getDoNotDisplay())
				continue;

			/* Draw the row that will hold the widget */
			if (!$in_group) {
				if (!$widget->getDrawsSelf()) {
					$tr = new HtmlTag('tr');
					$td = new HtmlTag('td');
					$label = $widget->getLabel();

					/* If we are a group, proprieties are only taken from the
					 * last widget in the group (the group's entity) */
					$group_tail = $i;
					if ($widget->group == 'start') {
						for ($j = $i + 1; $j < count($this->widgets); $j++) {
							$next =& $this->widgets[$j];
							if ($next->group == 'end') {
								$group_tail = $j;
								break;
							}
						}
					}
					$this_entity =& $this->widgets[$group_tail];
					$next_widget =& $this->widgets[$group_tail + 1];

					/* Don't draw the bottom border if it's the last widget
					 * in the form and doesn't have a label or if the widget
					 * below it draws itself. */
					$label = $this_entity->getLabel();
					if (is_object($next_widget))
						$next_draws_self = $next_widget->getDrawsSelf();
					else
						$next_draws_self = false;
					if (empty($label)) {
						if ($group_tail == $count - 1 || $next_draws_self)
							$col1_class = $col2_class = NULL;
						else
							$col1_class = $col2_class = 'vtable';
					}
					else {
						$td->SetValue($label);
						$req = ($widget->isRequired() ? 'req' : '');
						$col1_class = "vncell$req";
						$col2_class = 'vtable';
					}
					if ($col1_class != NULL)
						$td->addAttribute('class', $col1_class);

					$td->addAttribute('valign', 'top');
					$td->addAttribute('width', '22%');
					$tr->addValue($td->assemble());

					$td = new HtmlTag('td');
					if ($col2_class != NULL)
						$td->addAttribute('class', $col2_class);
					$td->addAttribute('width', '78%');
				}
			}

			/* Draw the widget directly to the table if it draws itself */
			if ($widget->getDrawsSelf())
				$table->addValue($widget->display());
			else {
				$td->addValue($widget->display());
				$td->addValue($widget->getDescription());
			}

			$in_group = ($in_group ? !($widget->group == 'end') : ($widget->group == 'start'));
			if (!$in_group && !$widget->getDrawsSelf()) {
				$tr->addValue($td->assemble());
				$table->addValue($tr->assemble());
			}
		}

		$form->addValue($table->assemble());
		$to_return = $form->assemble();

		$javascript = $this->javascript;
		if (!empty($javascript))
			$to_return .= <<<EOD
<script type="text/javascript" language="javascript">
$javascript
</script>

EOD;

		return $to_return;
	}

	function getLocalConfig()
	{
		$local_config = array();
		$disabled_widgets = $_POST['cg2_disabled_widgets'];
		if (!is_array($disabled_widgets)) $disabled_widgets = array();
		for ($i = 0; $i < count($this->widgets); $i++) {
			$widget =& $this->widgets[$i];
			/* Only add to the config if the widget isn't set not to be saved
			 * or displayed and if it's not disabled. */ 
			if (!$widget->isDisabled($disabled_widgets) && !$widget->getDoNotSave() && !$widget->getDoNotDisplay())
				$local_config[$widget->getFieldName()] = $widget->getValue();
		}
		return $local_config;
	}

	function onSync()
	{
		/* on_sync_event
		 * This is the perfect opportunity to sync the configuration in
		 * config.xml to other specific configuration files, restart services,
		 * etc.. */
		$callback = $this->on_sync_event;
		if (isset($callback))
			$callback();
	}
}

class Step extends Form
{
	var $will_display;

	function Step($index, $step_data, $wizard_config)
	{
		global $page_status;

		parent :: Form($index, $step_data, $wizard_config);

		$this->step = $step_data['step'];
		$this->will_display = $page_status['wizard_step'] == $step_data['step'];
	}

	function display()
	{
		global $page_status;

		if ($this->will_display) {
			$to_display = parent :: display();

			$buttons = array('cancel');
			if ($this->step > 0)
				$buttons[] = 'previous';
			if ($this->step < $page_status['wizard_total_steps'] - 1)
				$buttons[] = 'next';
			else if ($this->step == $page_status['wizard_total_steps'] - 1)
				$buttons[] = 'finish';

			foreach ($buttons as $button) {
				$input = new HtmlTag('input');
				$input->addAttribute('type', 'button');
				$input->addAttribute('class', 'formbtn');
				$input->addAttribute('value', gettext(ucfirst($button)));
				$input->addAttribute('onclick', "__post_wizard_form('$button', {$this->step});");
				$to_display .= $input->assemble();
			}

			return $to_display;
		}
		else
			return '';
	}
}

class Table extends Element
{
	var $on_apply_event, $apply_string;

	function Table($index, $table_data, $rows)
	{
		parent :: Element($index, $table_data, $rows);

		$this->table_data = $table_data;

		/* on_table_load_event
		 * The callback is supplied an associative array containing the field
		 * names and their value in config.xml. You should return an array like
		 * that as well, optionally modified or even completaly new, but
		 * following that scheme. */
		$callback = $table_data['on_table_load_event'];
		if (function_exists($callback))
			$this->rows = $callback($table_data, $rows);
		else
			$this->rows = $rows;
		if (!is_array($this->rows))
			$this->rows = array();
	}

	function display()
	{
		global $widget_classes, $button_functions;

		$table_data = $this->table_data;
		$rows = $this->rows;
		$form_index = $this->index;

		$form =& $this->form;

		$table = new HtmlTag('table');
		$table->addAttribute('width', '100%');
		$table->addAttribute('border', '0');
		$table->addAttribute('cellspacing', '0');
		$table->addAttribute('cellpadding', '6');

		$buttons = $table_data['buttons'];
		if (!empty($buttons)) {
			$buttons = explode(',', $table_data['buttons']);
			$using_buttons = true;
		}
		else {
			$buttons = array();
			$using_buttons = false;
		}

		/* The table title */
		$title = $table_data['title'];
		if (!empty($title)) {
			$tr = new HtmlTag('tr');
			$td = new HtmlTag('td', $title);
			$td->addAttribute('class', 'listtopic');
			if (is_array($table_data['widget'])) {
				$cols = count($table_data['widget']);
				$td->addAttribute('colspan', $cols);
			}
			$tr->setValue($td->assemble());
			$table->addValue($tr->assemble());
		}

		/* The headers */
		$tr = new HtmlTag('tr');
		if (is_array($table_data['widget'])) {
			$has_items = !empty($rows);

			/* A column for the checkbox if we're going to use buttons */
			if ($has_items && $using_buttons) {
				$td = new HtmlTag('td', '&nbsp;');
				$tr->addValue($td->assemble());
			}

			$num_cells = 0;
			foreach ($table_data['widget'] as $widget) {
				if (isset($widget['not_in_table']))
					continue;
				$td = new HtmlTag('td', $widget['label']);
				$td->addAttribute('class', 'listhdrr');
				$tr->addValue($td->assemble());
				$num_cells++;
			}
			if (!$has_items) $num_cells--;

			/* A column for the buttons if we're going to use them */
			if ($using_buttons) {
				$td = new HtmlTag('td', '&nbsp;');
				$tr->addValue($td->assemble());
			}
		}
		$table->addValue($tr->assemble());

		/* Table items */
		if (is_array($rows) && !empty($rows)) {
			foreach ($rows as $row_index => $row) {
				$tr = new HtmlTag('tr');
				$tr->addAttribute('id', "cg2_row_$form_index.$row_index");
				$tr->addAttribute('valign', 'top');

				if ($using_buttons) {
					$checkbox = new HtmlTag('input');
					$checkbox->addAttribute('type', 'checkbox');
					$checkbox->addAttribute('name', 'cg2_selected_rows[]');
					$checkbox->addAttribute('value', $row_index);
					$checkbox->addAttribute('onclick', "__highlight_row($form_index, $row_index, false);");
					$td = new HtmlTag('td', $checkbox->assemble());
					$td->addAttribute('width', '1%');
					$tr->addValue($td->assemble());
				}

                $first = true;
                $disabled = isset($row['disabled']) && $row['disabled'] == 'on';
				if (is_array($table_data['widget'])) {
					foreach ($table_data['widget'] as $widget_index => $widget) {
						if (isset($widget['not_in_table']))
							continue;

						$type = $widget['type'];
						if ($type == 'custom')
							$class = $widget['class'];
						else
							$class = $widget_classes[$type];
						if (!class_exists($class))
							$class = $widget_classes['unknown'];

						$widget_obj = new $class($table_data['widget'], $widget_index, $row, $form_index);
						$widget_obj->setValue('config');
						$value = $widget_obj->getTableValue();

                        if ($disabled) {
                            $span = new HtmlTag('span', $value);
                            $span->addAttribute('class', 'gray');
                            $value = $span->assemble();
                        }

						$td = new HtmlTag('td', $value);
                        if ($first) {
                            $class = 'listlr';
                            $first = false;
                        }
                        else if (isset($widget['highlighted'])) {
                            $class = 'listbg';
                        }
                        else {
                            $class = 'listr';
                        }
						$td->addAttribute('class', $class);

						if (isset($widget['highlighted']))
							$td->addAttribute('style', 'color: white;');
						$td->addAttribute('onclick', "__highlight_row($form_index, $row_index, true);");
						$td->addAttribute('ondblclick',	"__perform_action('edit', $form_index, $row_index);");

						$tr->addValue($td->assemble());

					}
					/* The row buttons */
					if ($using_buttons) {
						$td = new HtmlTag('td', getRowButtonTable($form_index, $row_index, $buttons));
						$tr->addValue($td->assemble());
					}
				}
				$table->addValue($tr->assemble());
			}
		}
        else {
            $tr = new HtmlTag('tr');
            $tr->addAttribute('valign', 'top');

            if (isset($table_data['no_entries_text']))
                $text = $table_data['no_entries_text'];
            else
                $text = dgettext('BluePexWebFilter','No entries were found.');

            if ($using_buttons && in_array('add', $buttons)) {
                $add_button = getAddButton($form_index, 0);
                $text .= "<br /><br />" . dgettext('BluePexWebFilter',"Click the") . " $add_button " . dgettext('BluePexWebFilter',"button to add a new entry.");
            }

            $span = new HtmlTag('span', $text);
            $span->addAttribute('class', 'gray');

            $td = new HtmlTag('td', $span->assemble());
            $td->addAttribute('class', 'listlr');
            $td->addAttribute('align', 'center');
            $td->addAttribute('valign', 'center');
            $td->addAttribute('colspan', $num_cells + 1);

            $tr->addValue($td->assemble());
            $table->addValue($tr->assemble());
        }

		/* The bottom buttons */
		if ($using_buttons) {
			$tr = new HtmlTag('tr');

			$td = new HtmlTag('td', '&nbsp;');
			$td->addAttribute('colspan', $num_cells + 1);
			$tr->setValue($td->assemble());

			$td = new HtmlTag('td', getBottomButtonTable($form_index, $row_index + 1, $buttons));
			$td->addAttribute('width', '1%');
			$tr->addValue($td->assemble());

			$table->addValue($tr->assemble());
		}

		$form->addValue($table->assemble());
		return $form->assemble();
	}

	function setRows($rows)
	{
		$this->rows = $rows;
	}
}
?>

