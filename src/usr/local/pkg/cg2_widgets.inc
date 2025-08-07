<?php
/* cg2_widgets.inc
 * Displays CoreGUI 2 widgets. */

require_once ('config.inc');
require_once ('globals.inc');
require_once ('html.inc');
require_once ('util.inc');

define('PFSENSE_ICON_DIR', "/themes/{$g['theme']}/images/icons");
define('MULTIPLE_SELECT_MAXSIZE', 10);
define('CSS_FORMFIELD', 'formfld');
define('CSS_FORMFIELDALIAS', 'formfldalias');
define('CSS_FORMFIELD_PWD', 'formfld pwd');
define('CSS_FORMFIELD_UNKNOWN', 'formfld unknown');
define('CSS_FORMSELECT', 'formselect');
define('CSS_FORMBUTTON', 'formbtn');
define('COLOR_GRAYEDOUT', '#dddddd');

$widget_classes = array(
	'textbox' => 'TextboxWidget',
	'password' => 'PasswordWidget',
	'checkbox' => 'CheckboxWidget',
	'textarea' => 'TextareaWidget',
	'select' => 'SelectWidget',
	'radio' => 'RadioWidget',
	'ip' => 'IpWidget',
	'url' => 'UrlWidget',
	'port' => 'PortWidget',
	'subnet' => 'SubnetWidget',
    'range' => 'RangeWidget',
	'image_select' => 'ImageSelectWidget',
	'interfaces_select' => 'InterfacesSelectWidget',
	'ipsec_select' => 'IpsecSelectWidget',
	'header' => 'HeaderWidget',
	'linebreak' => 'LinebreakWidget',
	'dummy' => 'DummyWidget',
	'value_merger' => 'ValueMergerWidget',
	'hidden' => 'HiddenInputWidget',
	'button' => 'ButtonWidget',
	'submit' => 'SubmitWidget',
	'timestamp' => 'TimestampWidget',
	'report' => 'ReportWidget',
	'clearlog' => 'ClearlogWidget',
	'unknown' => 'UnknownWidget',
    'text' => 'TextWidget'
);

class Widget {
	var $form_index;
	var $config;
	var $prefix;
	var $value;
	var $source;
	var $name, $label;
	var $fieldname;
	var $customphpvariable;
	var $do_not_save, $do_not_display;
	var $default_value;
	var $input_errors = array();
	var $group;
	var $draws_self = false;
	var $required, $disabled;
	var $attributes = array();

	function Widget($widgets_data, $widget_index, $config, $form_index) {
		$this->form_index = $form_index;

		$field = $widgets_data[$widget_index];
		$this->fieldname = empty($field['name']) ? '' : $field['name'];

		$this->label = $field['label'];
		$this->description = $field['description'];
		$this->customphpvariable = $field['show_php_variable'];
		if (!isset($this->do_not_save))
			$this->do_not_save = isset($field['do_not_save']);
		if (!isset($this->do_not_display))
			$this->do_not_display = isset($field['do_not_display']);
		$this->default_value = $field['value'];
		$this->required = isset($field['required']);
		$this->disabled = isset($field['disabled']);
		if (isset($field['group_start']))
			$this->group = 'start';
		else if (isset($field['group_end']))
			$this->group = 'end';
		else
			$this->group = '';

		$this->config = $config[$this->fieldname];

		$this->prefix = "cg2_{$form_index}_{$widget_index}_";
		$this->name = $this->prefix . 'field';
	}

	function getFieldName() {
		return $this->fieldname;
	}

	function getName() {
		return $this->name;
	}

	function setValue($source) {
		if ($source == 'post') {
			$value = $_POST[$this->name];
		} else {
			if ($source == 'config')
				$value = $this->config;
			else {
				if ($source == 'defaults')
					$value = $this->default_value;
				else if ($source == 'variable') {
						$evalstr = "global " . $this->customphpvariable . "; \$value = \"$this->customphpvariable\";";
						eval($evalstr);
				}
			}
		}

		$this->value = $value;
		$this->source = $source;
	}

	function getValue() {
		return $this->value;
	}

	function getSource() {
		return $this->source;
	}

	function getDoNotSave() {
		return $this->do_not_save || $this->getFieldname() == '';
	}

	function setDoNotDisplay($value) {
		$this->do_not_display = ($value ? true : false);
	}

	function getDoNotDisplay() {
		return $this->do_not_display;
	}

	function validateInput() {
		return false;
	}

	function getInputErrors() {
		return $this->input_errors;
	}

	function isRequired() {
		return $this->required;
	}

	function getLabel() {
		return $this->label;
	}

	function getDescription() {
		if (!empty($this->description)) {
			$br = new HtmlTag('br');

			$span = new HtmlTag('span', $this->description);
			$span->addAttribute('class', 'vexpl');

			return $br->assemble() . $span->assemble();
		}
		return NULL;
	}

	function getDrawsSelf() {
		return $this->draws_self;
	}

	function setDisabled($value) {
		$this->disabled = ($value ? true : false);
	}

	function getDisabled() {
		return $this->disabled;
	}

	function isDisabled($disabled_widgets) {
		$names = $this->getHtmlNames();
		return in_array($names[0], $disabled_widgets);
	}

	function getJavascript(&$widgets) {
		return '';
	}

	function getHtmlNames() {
		return array($this->name);
	}

	function getTableValue() {
		return $this->getValue();
	}

	function display(&$html) {
		if ($this->disabled)
			$html->addAttribute('disabled', 'disabled');
	}
}

class EditableWidget extends Widget {
	var $encode;
	var $read_only;

	function EditableWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		if (isset($field['read_only'])) {
			$this->read_only = true;
			$this->do_not_save = true;
		}
		$this->encode = isset($field['encode']);
	}

	function setValue($source) {
		/* If this filed is read_only, it won't be saved to the config. */
		if ($this->read_only)
			$source = 'defaults';
		parent :: setValue($source);

		if ($this->encode && $source != 'post' && $source != 'defaults')
			$this->value = base64_decode($this->value);
	}

	function getValue() {
		$value = $this->value;
		$value = str_replace("\r\n", "\n", $value);

		if ($this->encode)
			$value = base64_encode($value);

		return $value;
	}

	function getTableValue() {
		$value = $this->getValue();
		if (empty($value)) $value = '-';
		return $value;
	}

	function display(&$html) {
		parent :: display($html);

		if ($this->read_only) {
			$html->addAttribute('readonly', 'readonly');
			$html->addAttribute('style', 'color: black; background-color: ' . COLOR_GRAYEDOUT . ';');
		}
	}

	function getReadOnly() {
		return $this->read_only;
	}

	function setReadOnly($value) {
		$this->read_only = ($value ? true : false);
	}
}

class InputWidget extends EditableWidget {
	var $size;
	var $accept_alias = false;

	function InputWidget($widgets_data, $widget_index, $config, $form_index, $type) {
		parent :: EditableWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];

		$this->type = $type;
		$this->size = $field['size'];
		$this->numeric = isset($field['numeric']);

		$fc =& $field['forbidden_chars'];
		if (empty($fc) || !is_string($fc)) $fc = '';
		$this->forbidden_chars = $fc;

		$this->validation_regex = $field['validation_regex'];
		$this->validation_message = $field['validation_message'];
		if (empty($this->validation_message))
			$this->validation_message = "The field {$this->label} contains invalid characters.";

		if (isset($field['alias']))
			$this->accept_alias = true;
	}

	function display() {
		$htmlInput = new HtmlTag('input');
		$htmlInput->addAttribute('type', $this->type);
		$class = $this->accept_alias ? CSS_FORMFIELDALIAS : CSS_FORMFIELD_UNKNOWN;
		$htmlInput->addAttribute('class', $class);
		$htmlInput->addAttribute('name', $this->name);
		$value = isset($this->value) ? $this->value : ''; /* Avoid "NOT SET" */
		$htmlInput->addAttribute('value', $value);
		if (!empty($this->size))
			$htmlInput->addAttribute('size', $this->size);

		parent :: display($htmlInput);
		$doc = $htmlInput->assemble();

		return $doc;
	}

	function validateInput() {
		if (parent :: validateInput())
			return true;

		$value = $this->getValue();
		$label = $this->getLabel();

		if (empty($value)) {
			if ($this->isRequired()) {
				$this->input_errors[] = "The field '$label' is required.";
				return true;
			}
			else
				return false;
		}

		if ($this->numeric && !is_numeric($value)) {
			$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"The field '%s' must contain a numeric value."), $label);
			return true;
		}

		for ($i = 0; $i < strlen($this->forbidden_chars); $i++) {
			$forbidden = substr($this->forbidden_chars, $i, 1);
			if (strstr($value, $forbidden) !== false) {
				$this->input_errors[] = $this->validation_message;
				return true;
			}
		}

		if (!empty($this->validation_regex)) {
			$matches = "";
			if (!preg_match($this->validation_regex, $value, $matches)) {
				$this->input_errors[] = $this->validation_message;
				return true;
			}
		}

		return false;
	}
}

class TextboxWidget extends InputWidget {
	function TextboxWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: InputWidget($widgets_data, $widget_index, $config, $form_index, 'text');
	}
}

class PasswordWidget extends InputWidget {
	function PasswordWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: InputWidget($widgets_data, $widget_index, $config, $form_index, 'password');
	}

	function display() {
		$htmlInput = new HtmlTag('input');
		$htmlInput->addAttribute('type', $this->type);
		$htmlInput->addAttribute('class', CSS_FORMFIELD_PWD);
		$htmlInput->addAttribute('name', $this->name);
		$value = isset($this->value) ? $this->value : ''; /* Avoid "NOT SET" */
		$htmlInput->addAttribute('value', $value);
		if (!empty($this->size))
			$htmlInput->addAttribute('size', $this->size);

		parent :: display($htmlInput);
		$doc = $htmlInput->assemble();

		return $doc;
	}

}

class CheckboxWidget extends Widget {
	var $caption;
	var $table_value_enabled, $table_value_disabled;
	var $enable_widgets, $disable_widgets;

	function CheckboxWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$this->caption = $field['caption'];

		if (!empty($field['table_value']))
			list($this->table_value_enabled, $this->table_value_disabled) = explode(',', $field['table_value']);
		else {
			$this->table_value_enabled = dgettext('BluePexWebFilter','Enabled');
			$this->table_value_disabled = dgettext('BluePexWebFilter','Disabled');
		}

		$this->enable_widgets = empty($field['enable_widgets']) ? array() : explode(',', $field['enable_widgets']);
		$this->disable_widgets = empty($field['disable_widgets']) ? array() : explode(',', $field['disable_widgets']);
	}

	function getValue() {
		return $this->value == 'on' ? 'on' : 'off';
	}

	function getTableValue() {
		return ($this->getValue() == 'on' ? $this->table_value_enabled : $this->table_value_disabled);
	}

	function display() {
		$htmlInput = new HtmlTag('input');
		parent :: display($htmlInput);

		if (isset($this->caption))
			$htmlInput->setValue($this->caption);
		$htmlInput->addAttribute('type', 'checkbox');
		$htmlInput->addAttribute('name', $this->name);
		$htmlInput->addAttribute('value', 'on');
		if ($this->value == 'on')
			$htmlInput->addAttribute('checked', 'checked');
		if (!empty($this->enable_widgets) || !empty($this->disable_widgets))
			$htmlInput->addAttribute('onclick', "__checked_toggle_{$this->name}(this.checked);");

		parent :: display($htmlInput);
		$doc = $htmlInput->assemble();

		return $doc;
	}

	function getJavascript(&$widgets) {
		if (empty($this->enable_widgets) && empty($this->disable_widgets))
			return '';

		$ret = <<<EOD
function __checked_toggle_{$this->name}(checked)
{

EOD;

		foreach (array('enable', 'disable') as $action) {
			$var_name = "{$action}_widgets";
			$checked = $action == 'enable' ? 'checked' : '!checked';

			$var = $this->$var_name;
			if (!empty($var)) {
				$widget_names = array();
				foreach ($var as $name) {
					$widget =& get_widget($widgets, $name);
					$names = $widget->getHtmlNames();
					foreach ($names as $name) $widget_names[] = $name;
				}

				$ret .= "	if ($checked) {\n";
				foreach ($widget_names as $name)
					$ret .= <<<EOD
		widget = document.getElementsByName('$name')[0];
		widget.disabled = false;

EOD;
				$ret .= "	} else {\n";
				foreach ($widget_names as $name)
					$ret .= <<<EOD
		widget = document.getElementsByName('$name')[0];
		widget.disabled = true;

EOD;
				$ret .= "	}\n";
			}
		}

		$ret .= <<<EOD
}
widget = document.getElementsByName('{$this->name}')[0];
__checked_toggle_{$this->name}(widget.checked);


EOD;
		return $ret;
	}
}

class TextareaWidget extends EditableWidget {
	var $rows = 5, $cols = 40;

	function TextareaWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: EditableWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];

		$this->fieldname = $field['name'];
		$this->validation_regex = $field['validation_regex'];
		$this->validation_message = $field['validation_message'];

		if (!empty($field['rows']))
			$this->rows = $field['rows'];
		if (!empty($field['cols']))
			$this->cols = $field['cols'];
	}

	function setValue($source) {
		parent :: setValue($source);

		if ($source == 'config')
			$this->value = str_replace("\\n", "\r\n", $this->value);
	}

	function getValue() {
		$value = parent :: getValue();
		$value = str_replace("\r\n", '\n', $value);
		$value = str_replace("\n", '\n', $value);
		return $value;
	}

	function getTableValue() {
		$value = $this->getValue();
		if (strlen($value) >= 75)
			$value = substr($value, 0, 75 - 3) . '...';
		return ($value);
	}

	function display() {
		/* HTML oddities... */
		if (!isset($this->value))
			$this->value = '';
		$htmlTextarea = new HtmlTag('textarea', $this->value);
		$htmlTextarea->addAttribute('name', $this->name);
		$htmlTextarea->addAttribute('class', CSS_FORMFIELD);
		$htmlTextarea->addAttribute('rows', $this->rows);
		$htmlTextarea->addAttribute('cols', $this->cols);
		foreach ($this->attributes as $key => $value)
			$htmlTextarea->addAttribute($key, $value);

		parent :: display($htmlTextarea);
		$doc = $htmlTextarea->assemble();

		return $doc;
	}

	function validate_custom_list() {
		if (empty($this->validation_regex))
			return false;

		$values = explode("\n", base64_decode($this->getValue()));
		foreach($values as $value) {
			if (!preg_match($this->validation_regex, $value)) {
				$this->input_errors[] = $this->validation_message. ": ".$value;
				return true;
			}
		}
		return false;
	}

	function validateInput() {
		if (parent :: validateInput())
			return true;

		/* Validate URL's Custom List*/
		if($this->fieldname == "urls")
			$this->validate_custom_list();

		if ($this->isRequired() && empty($this->value)) {
			$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"The field '%s' is required."), $this->label);
			return true;
		}

		return false;
	}
}

class SelectableWidget extends Widget {
	var $options = array();
	var $value_enable, $value_disable;

	function SelectableWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];

		if (is_array($field['option'])) $this->options = $field['option'];

		$range = $field['option_numerical_range'];
		if (isset($range)) {
			$step = isset($range['step']) ? $range['step'] : 1;
			for ($i = $range['start']; $i <= $range['end']; $i += $step) {
				$opt_arr = array();
				$opt_arr['name'] = $i;
				$opt_arr['label'] = $i;
				$field['option'][] = $opt_arr;
			}
		}

		$this->value_enable = empty($field['value_enable']) ? array() : explode(',', $field['value_enable']);
		$this->value_disable = empty($field['value_disable']) ? array() : explode(',', $field['value_disable']);
	}

	function getTableValue() {
		$values = $this->value;
		if (empty($values))
			return '-';

		if (!is_array($values))
			$values = array($values);
		foreach ($values as $value) {
			foreach ($this->options as $option) {
				if ($option['name'] == $value)
					$values_str .= ', ' . $option['label'];
			}
		}
		$values_str = substr($values_str, 2);
		return $values_str;
	}

	function getJavascript(&$widgets, $type) {
		if (empty($this->value_enable) && empty($this->value_disable))
			return '';

		$ret = <<<EOD
function __value_toggle_{$this->name}(value)
{
EOD;
		foreach (array('disable', 'enable') as $action) {
			$disable = $action == 'disable' ? 'true' : 'false';
			$parameters = "value_$action";
			foreach ($this->$parameters as $value_toggle) {
				list($value, $var) = explode('|', $value_toggle);

				/* Find the HTML name(s) of the widget */
				$widget = get_widget($widgets, $var);
				$names = $widget->getHtmlNames();

				$ret .= "\tif (value == '$value') {\n";
				foreach ($names as $name)
					$ret .= <<<EOD
		widget = document.getElementsByName('$name')[0];
		widget.disabled = $disable;

EOD;
				$ret .= "	}\n";
			}
		}
		$ret .= "}\n";

		$names = $this->getHtmlNames();
		$name = $names[0];
		if ($type == 'check')
			$ret .= <<<EOD
widgets = document.getElementsByName('$name');
for (i = 0; i < widgets.length; i++) {
	if (widgets[i].checked) {
		widget = widgets[i];
		break;
	}
}

EOD;
		else
			$ret .= "widget = document.getElementsByName('$name')[0];\n";
		$ret .= "__value_toggle_{$this->name}(widget.value)\n";

		return $ret;
	}
}

class SelectWidget extends SelectableWidget {
	var $multiple;
	var $size;
	var $on_change = '';

	function SelectWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: SelectableWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$this->size = $field['size'];
		if (!empty($field['on_change'])) $this->on_change .= $field['on_change'];
		$this->multiple = isset($field['multiple']);

		if (!empty($this->value_enable) || !empty($this->value_disable))
			$this->on_change = "__value_toggle_{$this->name}(this.value);";
		if (!empty($field['on_change'])) $this->on_change .= $field['on_change'];
		if (isset($field['sort']) || isset($field['sort_reverse'])) {
			$labels = array();
			foreach ($this->options as $option)
				$labels[] = $option['label'];

			if (isset($field['sort']))
				asort($labels);
			else
				arsort($labels);

			$newoptions = array();
			$options =& $this->options;
			foreach ($labels as $index => $label)
				$newoptions[] = array('name' => $options[$index]['name'], 'label' => $label);

			$options = $newoptions;
		}
	}

	function getHtmlNames() {
		$name = $this->name;
		if ($this->multiple)
			$name .= '[]';
		return array($name);
	}

	function setValue($source) { 
		parent :: setValue($source);

		if (empty($this->value))
			$this->value = array();
		else if (!is_array($this->value))
			$this->value = explode(',', $this->value);
	}

	function getValue() {
		$value = parent :: getValue();
		if (is_array($value))
			$value = implode(',', $value);
		return $value;
	}

	function display() {
		$htmlSelect = new HtmlTag('select');
		parent :: display($htmlSelect);
		$htmlSelect->addAttribute('class', CSS_FORMSELECT);
		$names = $this->getHtmlNames();
		$htmlSelect->addAttribute('name', $names[0]);
		if (!empty($this->size))
			$htmlSelect->addAttribute('size', $this->size);
		if ($this->multiple) {
			$htmlSelect->addAttribute('multiple', 'multiple');
      
			$size = count($this->options) <= MULTIPLE_SELECT_MAXSIZE ? count($this->options) : MULTIPLE_SELECT_MAXSIZE;
			$htmlSelect->addAttribute('size', $size);
		}
		if (!empty($this->on_change))
			$htmlSelect->addAttribute('onchange', $this->on_change);

		$selection = $this->value;
		$options_out = '';
		foreach ($this->options as $option) {
			$htmlOption = new HtmlTag('option', $option['label']);
			$htmlOption->addAttribute('value', $option['name']);
			if (in_array($option['name'], $selection))
				$htmlOption->addAttribute('selected', 'selected');

			$options_out .= $htmlOption->assemble();
		}

		$htmlSelect->setValue($options_out);
		return $htmlSelect->assemble();
	}

	function validateInput() {
		if ($this->isRequired() && empty($this->value)) {
			$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"The field '%s' is required."), $this->label);
			return true;
		}

		return false;
	}

	function getJavascript(&$widgets) {
		return parent :: getJavascript($widgets, 'select');
	}
}

class RadioWidget extends SelectableWidget {
	var $options;
	var $on_click = '';

	function RadioWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: SelectableWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		if (!empty($this->value_enable) || !empty($this->value_disable))
			$this->on_click = "__value_toggle_{$this->name}(this.value);";
		if (!empty($field['on_click'])) $this->onclick .= $field['on_click'];
	}

	function display() {
		$doc = '';
		$htmlBr = new HtmlTag('br');
		$num_options = count($this->options);
		foreach ($this->options as $i => $option) {
			$htmlInput = new HtmlTag('input', $option['label']);
			$htmlInput->addAttribute('type', 'radio');
			$htmlInput->addAttribute('class', CSS_FORMFIELD);
			$htmlInput->addAttribute('name', $this->name);
			$htmlInput->addAttribute('value', $option['name']);
			if ($this->value == $option['name'])
				$htmlInput->addAttribute('checked', 'checked');
			if (!empty($this->on_click))
				$htmlInput->addAttribute('onclick', "$this->on_click");

			parent :: display($htmlInput);

			$doc .= $htmlInput->assemble();
			if ($i != $num_options - 1)
				$doc .= $htmlBr->assemble();
		}

		return $doc;
	}

	function getJavascript(&$widgets) {
		return parent :: getJavascript($widgets, 'check');
	}
}

class IpWidget extends TextboxWidget {
	function validateInput() {
		if (parent :: validateInput())
			return true;

		if (empty($this->value))
			return false;

		if (!is_ipaddr($this->value)) {
			$this->input_errors[] = sprintf(dgettext('BluepexWebFilter',"The field '%s' must contain a valid IP address."), $this->label);
			return true;
		}

		return false;
	}
}

class UrlWidget extends TextboxWidget {
	var $accept_empty = false;

	function UrlWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: TextboxWidget($widgets_data, $widget_index, $config, $form_index);
		$field = $widgets_data[$widget_index];

		if (empty($this->default_value))
			$this->default_value = 'http://';

        $this->validation_regex = '/^(([\w]+:)?\/\/)?(([\d\w]|%[a-fA-f\d]{2,2})+(:([\d\w]|%[a-fA-f\d]{2,2})+)?@)?([\d\w][-\d\w]{0,253}[\d\w]\.)+[\w]{2,4}(:[\d]+)?(\/([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)*(\?(&amp;?([-+_~.\d\w]|%[a-fA-f\d]{2,2})=?)*)?(#([-+_~.\d\w]|%[a-fA-f\d]{2,2})*)?$/';

		if (empty($field['validation_message']))
			$this->validation_message = sprintf(dgettext('BluePexWebFilter',"'%s' should contain a valid URL."), $this->label);

		if (isset($field['accept_empty']))
			$this->accept_empty = true;
	}

	function validateInput() {
		if ($this->accept_empty && (empty($this->value) || $this->value == 'http://' || $this->value == 'https://'))
			return false;

		return parent :: validateInput();
	}

	function getValue() {
		$value = $this->value;
		if (substr($value, 0, 7) != 'http://')
			$value = "http://$value";
		return $value;
	}
}

class PortWidget extends TextboxWidget {
	var $minport, $maxport;

	function PortWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: TextboxWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$this->size = 5;
		$this->minport = 1;
		if (isset($field['minport']))
			$this->minport = $field['minport'];
		$this->maxport = 65535;
		if (isset($field['maxport']))
			$this->maxport = $field['maxport'];
	}

	function validateInput() {
		if (parent :: validateInput())
			return true;

		$value = $this->value;

		if (empty($value));
			return false;

		if (!is_numeric($value) || ($value < $this->minport) || ($value > $this->maxport)) {
			$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"The field '%1$s' must contain a valid port number, between %2$s and %3$s."), $this->label, $this->minport, $this->maxport);
			return true;
		}

		return false;
	}
}

class SubnetWidget extends Widget {
	var $ip, $mask;
	var $minmask, $maxmask, $overlap_action;

	function SubnetWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$this->minmask = 0;
		if (isset($field['minmask']))
			$this->minmask = $field['minmask'];
		$this->maxmask = 32;
		if (isset($field['maxmask']))
			$this->maxmask = $field['maxmask'];
		$this->overlap_action = 'ignore';
		if (isset($field['overlap_action']))
			$this->overlap_action = $field['overlap_action'];
	}

	function setValue($source) {
		if ($source == 'post') {
			$this->ip = trim($_POST[$this->prefix . 'ip']);
			$this->mask = $_POST[$this->prefix . 'mask'];
		} else {
			if ($source == 'config')
				$info = $this->config;
			else {
				if ($source == 'defaults')
					$info = $this->default_value;
			}

			list ($this->ip, $this->mask) = explode('/', $info);
		}
	}

	function getValue() {
		return $this->ip . '/' . $this->mask;
	}

	function display() {
		$doc = '';

		$htmlInput = new HtmlTag('input');
		parent :: display($htmlInput);
		$htmlInput->addAttribute('name', $this->prefix . 'ip');
		$htmlInput->addAttribute('class', 'formfld');
		$htmlInput->addAttribute('value', $this->ip);
		$doc .= $htmlInput->assemble();

		$doc .= ' / ';

		$htmlSelect = new HtmlTag('select');
		parent :: display($htmlSelect);
		$htmlSelect->addAttribute('name', $this->prefix . 'mask');
		$htmlSelect->addAttribute('class', 'formfld');
		$options_out = '';
		foreach (range($this->minmask, $this->maxmask) as $mask) {
			$htmlOption = new HtmlTag('option', $mask);
			$htmlOption->addAttribute('value', $mask);
			if ($mask == $this->mask)
				$htmlOption->addAttribute('selected', 'selected');

			$options_out .= $htmlOption->assemble();
		}
		$htmlSelect->setValue($options_out);
		$doc .= $htmlSelect->assemble();

		return $doc;
	}

	function validateInput() {
		if (empty($this->ip)) {
			if ($this->isRequired) {
				$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"The field '%s' is required."), $this->label);
				return true;
			}
			else
				return false;
		}

		if (!is_ipaddr($this->ip)) {
			$this->input_errors[] = sprintf(dgettext('BluePexWebFilter','You must enter a valid IP address in the \'' .
			'%s' . '\' field.'), $this->label);
			return true;
		}

		$subnet = gen_subnet($this->ip, $this->mask);
		if ($subnet != $this->ip) {
			switch ($this->overlap_action) {
				case 'error' :
					$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"You must enter a non-overlapping IP address and netmask combination in the '%s' field."), $this->label);
					return true;
					break;
				case 'correct' :
					$this->ip = $subnet;
					break;
				case 'ignore' :
				default :
					break;
			}
		}

		return false;
	}

	function getHtmlNames() {
		return array($this->prefix . 'ip', $this->prefix . 'mask');
	}
}

class RangeWidget extends Widget {
	var $start, $end;

	function setValue($source) {
		if ($source == 'post') {
			$this->start = trim($_POST[$this->prefix . 'start']);
			$this->end = $_POST[$this->prefix . 'end'];
		} else {
			if ($source == 'config')
				$info = $this->config;
			else {
				if ($source == 'defaults')
					$info = $this->default_value;
			}

			list ($this->start, $this->end) = explode('-', $info);
            if ($this->end === null)
                $this->end = '';
		}
	}

	function getValue() {
		return $this->start . '-' . $this->end;
	}

	function display() {
		$doc = '';

		$htmlInput = new HtmlTag('input');
		parent :: display($htmlInput);
		$htmlInput->addAttribute('name', $this->prefix . 'start');
		$htmlInput->addAttribute('class', 'formfld');
		$htmlInput->addAttribute('value', $this->start);
		$doc .= $htmlInput->assemble();

		$doc .= ' to ';

		$htmlInput = new HtmlTag('input');
		parent :: display($htmlInput);
		$htmlInput->addAttribute('name', $this->prefix . 'end');
		$htmlInput->addAttribute('class', 'formfld');
		$htmlInput->addAttribute('value', $this->end);
		$doc .= $htmlInput->assemble();

		return $doc;
	}

	function validateInput() {
        if (empty($this->start) ^ empty($this->end)) {
            $this->input_errors[] = sprintf(dgettext('BluePexWebFilter','Please fill both the start and the end '
            . 'of the range for the \'%s\' field.'), $this->label);
            return true;
        }
        else if (empty($this->start) || empty($this->end)) {
			if ($this->isRequired) {
				$this->input_errors[] = sprintf(dgettext('BluePexWebFilter',"The field '%s' is required."), $this->label);
				return true;
			}
			else
				return false;
		}

		if (!is_ipaddr($this->start) || !is_ipaddr($this->end)) {
			$this->input_errors[] = sprintf(dgettext('BluePexWebFilter','You must enter valid IP addresses in the ' .
			'%s field.'), $this->label);
			return true;
		}

        $long1 = ip2long($this->start);
        $long2 = ip2long($this->end);
        if ($long1 >= $long2) {
            $this->input_errors[] = sprintf(dgettext('BluePexWebFilter','The end IP must be greater than the start IP ' .
            'in the \'%s\' field.'), $this->label);
            return true;
        }

		return false;
	}

	function getHtmlNames() {
		return array($this->prefix . 'start', $this->prefix . 'end');
	}
}

class ImageSelectWidget extends SelectWidget {
	var $images = array();

	function ImageSelectWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: SelectWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		if (is_array($field['option'])) {
			foreach ($field['option'] as $option)
				$this->images[$option['name']] = $option['image'];
		}
	}

	function getTableValue() {
		$values = $this->value;
		if (empty($values))
			return '-';
		else if (!is_array($values))
			$values = array();

		$img_str = '';
		$labels = array();
		foreach ($values as $value) {
			$htmlImg = new HtmlTag('img');
			$htmlImg->addAttribute('src', $this->images[$value]);
			$img_str .= $htmlImg->assemble();

			foreach ($this->options as $option) {
				if ($option['name'] == $value)
					$labels[] = $option['label'];
			}
		}
		$label_str = implode(', ', $labels);
		if (!empty($labels)) $label_str = "($label_str)";

		return empty($labels) ? '' : "$img_str<br />$label_str";
	}
}

class InterfacesSelectWidget extends SelectWidget {
	function InterfacesSelectWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: SelectWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];

		if (isset($field['all_interfaces'])) {
			$ifaces = explode(' ', get_interface_str());
			$using_real = true;
		} else {
			$config_xml = $GLOBALS['config'];
			$ifaces = $config_xml['interfaces'];
			$using_real = false;
		}

		$this->options = array();
		foreach ($ifaces as $ifname => $iface) {
			if (isset($iface['descr']))
				$name = $iface['descr'];
			else
				$name = $ifname;

			$label = strtoupper($ifname);
			if (!$using_real)
				$ifname = convert_friendly_interface_to_real_interface_name($ifname);
			if ($ip = find_interface_ip($ifname))
				$label .= " ($ip)";

			$this->options[] = array (
				'label' => $label,
				'name' => $name,
			);
		}
	}
}

class IpsecSelectWidget extends SelectWidget {
	function IpsecSelectWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: SelectWidget($widgets_data, $widget_index, $config, $form_index);

		$config_xml = $GLOBALS['config'];
		$tunnels = $config_xml['ipsec']['tunnel'];

		if (is_array($tunnels)) {
			foreach ($tunnels as $i => $tunnel) {
				$descr = $tunnel['descr'];
				if (empty($descr))
					$descr = sprintf(dgettext('BluePexWebFilter',"Tunnel #%1$s (remote subnet %2$s)"), $i, $tunnel['remote-subnet']);

				$option = array();
				$option['name'] = $i;
				$option['label'] = $descr;
				$this->options[] = $option;
			}
		}
	}
}

class VoidWidget extends Widget {
	function getValue() {
		return NULL;
	}

	function getDoNotSave() {
		return true;
	}
}

class HeaderWidget extends VoidWidget {
	var $draws_self = true;
	var $first_in_form;

	function HeaderWidget($widgets_data, $widget_index, $config, $form_index) {
		$field = $widgets_data[$widget_index];
		$this->label = isset($field['label']) ? $field['label'] : '&nbsp;';
		$this->first_in_form = $widget_index == 0;
	}

	function display() {
		$ret = '';

		if (!$this->first_in_form) {
			$tr = new HtmlTag('tr');
			$td = new HtmlTag('td', '&nbsp;');
			$tr->setValue($td->assemble());
			$ret .= $tr->assemble();
		}

		$tr = new HtmlTag('tr');
		$td = new HtmlTag('td', $this->label);
		$td->addAttribute('class', 'listtopic');
		$td->addAttribute('colspan', 2);
		$tr->setValue($td->assemble());
		$ret .= $tr->assemble();

		return $ret;
	}
}

class LinebreakWidget extends VoidWidget {
	function display() {
		$br = new HtmlTag('br');
		return $br->assemble();
	}
}

/* The swiss army knife. Use this with on_table_load_event, for example. */
class DummyWidget extends VoidWidget {
	var $draws_self = true;

	function getTableValue() {
		return $this->value;
	}

	function display() {}
}

/* If you can, use the value merger instead of a dummy widget. */
class ValueMergerWidget extends VoidWidget {
	var $to_merge = array();
	var $operation;
	var $named_fields = array();
	var $do_not_display = true;

	function ValueMergerWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: VoidWidget($widgets_data, $widget_index, $config, $form_index);

		$this->index = $form_index;

		$field = $widgets_data[$widget_index];
		$operations = array(
			'or',
			'and',
		);
		foreach ($operations as $operation) {
			$attribute = $field["merge_$operation"];
			if (!empty($attribute)) {
				$this->to_merge = explode(',', $attribute);
				$this->operation = $operation;
				break;
			}
		}
		$this->rows = $config;
		$this->widgets_data = $widgets_data;

		/* When we merge with "and", this will separate the fields */
		$this->merge_separator = isset($field['merge_separator']) ? $field['merge_separator'] : ', ';

		/* Easier to search for an element */
		foreach ($widgets_data as $index => $field) {
			if (isset($field['name']))
				$this->named_fields[$field['name']] = array('field' => $field, 'index' => $index);
		}
	}

	function get_row_table_value($fieldname) {
		global $widget_classes;

		$config = $this->rows[$fieldname];
		if (!isset($config)) return false;

		$field_info = $this->named_fields[$fieldname];
		if (!isset($field_info)) return false;
		$field = $field_info['field'];
		$field_index = $field_info['index'];

		$class = $field['type'] == 'custom' ? $field['class'] : $widget_classes[$field['type']];
		if (!class_exists($class)) return false;

		$widget = new $class($this->widgets_data, $field_index, $this->rows, $this->index);
		$widget->setValue('config');
		return $widget->getTableValue();
	}

	function getTableValue() {
		switch ($this->operation) {

			case 'or': /* Will output the first field that is set */
				foreach ($this->to_merge as $fieldname) {
					$value = $this->get_row_table_value($fieldname);
					if (!empty($value)) break;
				}

				if (empty($value))
					$value = '-';

				break;

			case 'and': /* Will concatenate all the fields */
				$value = array();
				foreach ($this->to_merge as $fieldname) {
					$table_value = $this->get_row_table_value($fieldname);
					if (!empty($table_value)) $value[] = $table_value;
				}

				if (empty($value))
					$value = '-';
				else
					$value = implode($this->merge_separator, $value);

				break;
		}
		return $value;
	}

	function display() {}
}

class HiddenInputWidget extends InputWidget {
	function HiddenInputWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: InputWidget($widgets_data, $widget_index, $config, $form_index, 'hidden');
	}

	function validateInput() {
		return NULL;
	}
}

class ButtonWidget extends Widget {
	var $caption;
	var $submit;
	var $onclick;

	function ButtonWidget($widgets_data, $widget_index, $config, $form_index, $submit = false) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$this->caption = $field['caption'];
		$this->submit = $submit;
		if ($submit)
			$this->on_click .= '__set_disabled_widgets();';
		$this->on_click .= $field['on_click_javascript'];
	}

	function setValue($source) {
		if ($source == 'variable') {
			$evalstr = "global " . $this->customphpvariable . "; \$value = \"$this->customphpvariable\";";
			$value = "";
			eval($evalstr);
			$this->value = $value;
			break;
		}
		else {
			$this->value = $this->caption;
		}
	}

	function getValue() {
		$fieldname = $this->getFieldName();
		if (!empty($fieldname)) {
			$posted_fields = array_keys($_POST);
			$button_field = $this->getHtmlNames();
			$button_field = $button_field[0];
			if (in_array($button_field, $posted_fields))
				return $fieldname;
		}
		return NULL;
	}

	function validateInput() {
		return NULL;
	}

	function display() {
		$input = new HtmlTag('input');
		$input->addAttribute('class', 'formbtn');
		$type = ($this->submit ? 'submit' : 'button');
		$input->addAttribute('type', $type);
		$input->addAttribute('name', $this->name);
		$input->addAttribute('value', $this->value);
		if (!empty($this->on_click))
			$input->addAttribute('onclick', $this->on_click);

		return $input->assemble();
	}
}

class SubmitWidget extends ButtonWidget {
	function SubmitWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: ButtonWidget($widgets_data, $widget_index, $config, $form_index, true);
	}
}

class TimestampWidget extends Widget {
	var $offset;
	var $date;

	function &create_select($name, $options, $selected) {
		$select = new HtmlTag('select');
		$select->addAttribute('name', $name);
		foreach ($options as $value) {
			$option = new HtmlTag('option', str_pad($value, 2, '0', STR_PAD_LEFT));
			$option->addAttribute('name', $value);
			if ($value == $selected)
				$option->addAttribute('selected', 'selected');
			$select->addValue($option->assemble());
		}
		return $select;
	}

	function TimestampWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$this->offset = is_numeric($field['offset']) ? $field['offset'] : 0;
	}

	function setValue($source) {
		switch ($source) {
			case 'defaults':
				$this->value = time();

				$offsets = array(
					'next_year' => 365 * 24 * 60 * 60,
					'last_year' => -(365 * 24 * 60 * 60),
					'next_month' => 30 * 24 * 60 * 60,
					'last_month' => -(30 * 24 * 60 * 60),
					'tomorrow' => 24 * 60 * 60,
					'yesterday' => -(24 * 60 * 60),
					'now' => 0,
				);

				switch ($this->default_value) {
					case 'offset':
						$this->value += $this->offset;
						break;
					default:
						$this->value += $offsets[$this->default_value];
				}
				break;

			case 'post':
				$this->value = mktime($_POST[$this->prefix . 'hours'], $_POST[$this->prefix . 'mins'], $_POST[$this->prefix . 'secs'], $_POST[$this->prefix . 'month'], $_POST[$this->prefix . 'day'], $_POST[$this->prefix . 'year']);
				break;

			default:
				parent :: setValue($source);
		}

		$this->date = getdate($this->value);
	}

	function getTableValue() {
		return strftime("%c", $this->getValue());
	}

	function display() {
		$date = $this->date;
		$res = '';

		/* Year */
		$select = $this->create_select($this->prefix . 'year', range($date['year'] - 30, $date['year'] + 30), $date['year']);
		$select->addAttribute('onchange', "{$this->prefix}reload_day();");
		$res .= $select->assemble();

		$res .= '/&nbsp;';

		/* Month */
		$select = $this->create_select($this->prefix . 'month', range(1, 12),  $date['mon']);
		$select->addAttribute('onchange', "{$this->prefix}reload_day();");
		$res .= $select->assemble();

		$res .= '/&nbsp;';

		/* Day */
		$select = new HtmlTag('select', '');
		$select->addAttribute('name', $this->prefix . 'day');
		$res .= $select->assemble();

		$res .= '&nbsp;&nbsp;';

		/* Hours */
		$select = $this->create_select($this->prefix . 'hours', range(0, 23), $date['hours']);
		$res .= $select->assemble();

		$res .= ':&nbsp;';

		/* Minutes */
		$select = $this->create_select($this->prefix . 'mins', range(0, 59), $date['minutes']);
		$res .= $select->assemble();

		$res .= ':&nbsp;';

		/* Seconds */
		$select = $this->create_select($this->prefix . 'secs', range(0, 59), $date['seconds']);
		$res .= $select->assemble();

		return $res;
	}

	function getJavascript() {
		return <<<EOD
function {$this->prefix}reload_day() {
	var year_field, month_field, day_field;
	var year, month, day;
	var mdays, days;
	var selected, i;

	month_field = document.getElementsByName('{$this->prefix}month')[0];
	month = parseInt(month_field.options[month_field.selectedIndex].value, 10);

	mdays = new Array(0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31);
	days = mdays[month];
	if (month == 2) {
		/* Take in account the leap years */
		year_field =  document.getElementsByName('{$this->prefix}year')[0];
		year = parseInt(year_field.options[year_field.selectedIndex].value, 10);

		if ((year % 400 == 0) || ((year % 4 == 0) && (year % 100 != 0)))
			days++;
	}

	/* Choose the day to be selected */
	day_field = document.getElementsByName('{$this->prefix}day')[0];
	day = day_field.selectedIndex;
	if (day < 0)
		day = {$this->date['mday']};
	else
		day = parseInt(day_field.options[day_field.selectedIndex].value, 10);
	if (day > days) day = days;

	day_field.options.length = 0;
	for (i = 1; i <= days; i++) {
		selected = day == i;
		day_field.options[i] = new Option(i, i, false, selected);
	}
}
{$this->prefix}reload_day();

EOD;
	}
}

class ReportWidget extends DummyWidget {
	var $backend;

	function ReportWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: DummyWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$class = $field['backend'];
		$backend = class_exists($class) ? new $class() : NULL;
		$this->backend =& $backend;
	}

	function display() {
		$backend =& $this->backend;
		if (!is_object($backend)) {
			$tr = new HtmlTag('tr');
			$td = new HtmlTag('td');
			$td->addAttribute('colspan', 2);
			$h1 = new HtmlTag('h1', dgettext('BluePexWebFilter','ERROR: The specified class does not exist'));
			$td->addValue($h1->assemble());
			$tr->addValue($td->assemble());
			return $tr->assemble();
		}

		if (!$backend->init())
			return ''; // will not display

		$limit = $backend->getLimit();
		$count = $backend->getCount();
		$offset = $_POST[$this->prefix . 'offset'];
		if (!isset($offset))
			$offset = 0;

		$report_action = $_POST[$this->prefix . 'report_action'];
		/* Stupid PHP won't let us use a switch */
		if (isset($report_action)) {
			if ($report_action == dgettext('BluePexWebFilter','<< First'))
				$offset = 0;
			else if ($report_action == dgettext('BluePexWebFilter','< Previous'))
				$offset -= $limit;
			else if ($report_action == dgettext('BluePexWebFilter','Next >'))
				$offset += $limit;
			else if ($report_action == dgettext('BluePexWebFilter','Last >>'))
				$offset = $count - ($count % $limit);
		}
		/* This might happen if, for example, an user changes the search
		 * options or if the database shrinks */
		if ($offset > $count)
			$offset = $count - ($count % $limit);

		$master_tr = new HtmlTag('tr');
		$master_td = new HtmlTag('td');
		$master_td->addAttribute('colspan', 2);

		/* Create the table */
		$table = new HtmlTag('table');
		$table->addAttribute('width', '100%');
		$table->addAttribute('border', 0);
		$table->addAttribute('cellspacing', 0);
		$table->addAttribute('cellpadding', 6);

		$headers = $backend->getHeaders();

		if ($count == 0) {
			$str = dgettext('BluePexWebFilter','No entries found');
		} else {
			$upto = $offset + $limit;
			if ($upto > $count)
				$upto = $count;

			$str = dgettext('BluePexWebFilter','Displaying items') . ' ' . ($offset + 1) . "-$upto " . dgettext('BluePexWebFilter','of') . " $count";
		}

		$tr = new HtmlTag('tr');
		$tr = new HtmlTag('tr');
		$td = new HtmlTag('td', $str);
		$td->addAttribute('class', 'listtopic');
		$td->addAttribute('colspan', count($headers));
		$tr->setValue($td->assemble());
		$table->setValue($tr->assemble());

		if ($count != 0) {
			/* Draw the headers */
			$tr = new HtmlTag('tr');
			foreach ($headers as $header) {
				$td = new HtmlTag('td', $header);
				$td->addAttribute('class', 'listhdrr');
				$tr->addValue($td->assemble());
			}
			$table->addValue($tr->assemble());

			/* Draw the contents */
			foreach ($backend->getContent($offset) as $row) {
				$tr = new HtmlTag('tr');
				foreach ($row as $field) {
					$td = new HtmlTag('td', $field);
					$td->addAttribute('class', 'listr');
					$tr->addValue($td->assemble());
				}
				$table->addValue($tr->assemble());
			}

			/* Define what buttons to draw */
			$to_draw = array();
			if ($offset > 1) {
				$to_draw[] = dgettext('BluePexWebFilter','<< First');
				$to_draw[] = dgettext('BluePexWebFilter','< Previous');
			}
			if ($count > $upto) {
				$to_draw[] = dgettext('BluePexWebFilter','Next >');
				$to_draw[] = dgettext('BluePexWebFilter','Last >>');
			}

			/* Draw the buttons, if any */
			if (!empty($to_draw)) {
				$tr = new HtmlTag('tr');
				$tr->addAttribute('class', 'listtopic');
				$td = new HtmlTag('td');
				foreach ($to_draw as $button) {
					$td->addAttribute('colspan', count($headers));
					$base_input = new HtmlTag('input');
					$base_input->addAttribute('type', 'submit');
					$base_input->addattribute('class', 'formbtn');
					$base_input->addAttribute('onclick', '__set_disabled_widgets()');
					$input = $base_input;
					$input->addAttribute('name', $this->prefix . 'report_action');
					$input->addAttribute('value', $button);
					$td->addValue($input->assemble());
				}

				$hidden = new HtmlTag('input');
				$hidden->addAttribute('type', 'hidden');
				$hidden->addAttribute('name', $this->prefix . 'offset');
				$hidden->addAttribute('value', $offset);
				$td->addValue($hidden->assemble());

				$tr->addValue($td->assemble());
				$table->addValue($tr->assemble());
			}
		}

		$master_td->addValue($table->assemble());
		$master_tr->addValue($master_td->assemble());
		return $master_tr->assemble();
	}
}

class ClearlogWidget extends DummyWidget {
	var $backend;

	function ClearlogWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: DummyWidget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
		$class = $field['backend'];
		$backend = class_exists($class) ? new $class() : NULL;
		$this->backend =& $backend;
	}
	
	function display() {
		$backend =& $this->backend;
		if (!is_object($backend)) {
			$tr = new HtmlTag('tr');
			$td = new HtmlTag('td');
			$td->addAttribute('colspan', 2);
			$h1 = new HtmlTag('h1', dgettext('BluePexWebFilter','ERROR: The specified class does not exist'));
			$td->addValue($h1->assemble());
			$tr->addValue($td->assemble());
			return $tr->assemble();
		}

		if (!$backend->init())
			return ''; // will not display
	}

}

class TextWidget extends Widget {
    var $text = '';

	function TextWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: Widget($widgets_data, $widget_index, $config, $form_index);

		$field = $widgets_data[$widget_index];
        $this->text = $field['text'];

        $this->do_not_save = true;
        $this->not_in_table = true;
	}

    function display() {
        return $this->text;
    }
}

class UnknownWidget extends TextareaWidget {
	function UnknownWidget($widgets_data, $widget_index, $config, $form_index) {
		parent :: TextareaWidget($widgets_data, $widget_index, $config, $form_index);

		$this->field = $widgets_data[$widget_index];
	}

	function setValue($source) {
		$this->value = dgettext('BluePexWebFilter',"WARNING: This is an unknown widget!") . "\n\n";
		$this->value .= print_r($this->field, true);
	}

	function getValue() {
		return dgettext('BluePexWebFilter','UNKNOWN WIDGET TYPE');
	}
}
?>

