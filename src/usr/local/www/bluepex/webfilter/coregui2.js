/* coregui2.js
 * Helper JavaScript code. */
 
/* Returns the handle to the row defined by the form and row index. */
function __get_row(form_index, row_index)
{
	return document.getElementById('cg2_row_' + form_index + '.' + row_index);
}
/* Creates a hidden input with name and value as specified in the given
 * form. */
function __add_hidden_input(form, name, value)
{
	var hidden;
	hidden = document.createElement('input');
	hidden.type = 'hidden';
	hidden.name = name;
	hidden.value = value;
	form.appendChild(hidden);
}
/* Adds hidden inputs to all forms showing the disabled widgets. */
function __set_disabled_widgets()
{
	var form, widget, hidden;
	var i, j;
	for (i = 0; i < document.forms.length; i++) {
		form = document.forms[i];
		for (j = 0; j < form.elements.length; j++) {
			widget = form.elements[j];
			if (widget.disabled)
				__add_hidden_input(form, 'cg2_disabled_widgets[]', widget.name);
		}
	}
}
/* Submits the form, setting the selected row and the action to edit. */
function __perform_action(action, form_index, row_index)
{
	var form;
	form = document.forms[form_index];
	__add_hidden_input(form, 'cg2_action', action);
	__add_hidden_input(form, 'cg2_row', row_index);
	form.submit();
}
/* Posts a form, creating a hidden input */
function __post_wizard_form(action, step)
{
	var form;
	form = document.forms[0];
	__add_hidden_input(form, 'cg2_wizard_action', action);
	__add_hidden_input(form, 'cg2_wizard_step', step);
	form.submit();
}
/* Highlights a given row in a given form. */
function __highlight_row(form_index, row_index, toggle_cb)
{
	var row, checkbox, cells, cell;
	var color;
	var i;
	row = __get_row(form_index, row_index);
	cell = row.getElementsByTagName('td')[0];
	checkbox = cell.getElementsByTagName('input')[0];
	cells = row.getElementsByTagName('td');
	// XXX: This should go into the CSS.
	if (toggle_cb)
		checkbox.checked = !checkbox.checked;
	color = checkbox.checked ? '#ffffbb' : '#ffffff';
	for (i = 0; i < cells.length; i++) {
		cell = cells[i];
		if (cell.className != 'listr')
			continue;
		cell.style.backgroundColor = color;
	}
}
/* Toggle the line above an arbitrary row, either top or bottom. */
function __toggle_row_line(row, top, enable)
{
	var cells, cell, style;
	var border;
	/* XXX: This should also go into the CSS. */
	if (enable)
		border = '2px solid #990000';
	else {
		if (top)
			border = '';
		else
			border = '1px solid #999999';
	}
	cells = row.getElementsByTagName('td');
	for (i = 0; i < cells.length; i++) {
		cell = cells[i];
		if (cell.className != 'listr')
			continue;
		style = cells[i].style;
		if (top)
			style.borderTop = border;
		else
			style.borderBottom = border;
	}
}
/* Inserts or delets a line between the given row and the row before it. */
function __toggle_line(form_index, row_index, enable)
{
	var row;
	row = __get_row(form_index, row_index - 1);
	if (row != undefined)
		__toggle_row_line(row, false, enable);
	row = __get_row(form_index, row_index);
	if (row != undefined)
		__toggle_row_line(row, true, enable);
}
function __rows_selected(form_index)
{
	var row, cell, checkbox;
	var found_selected;
	var i;
	found_selected = false;
	i = 0;
	while (!found_selected) {
		row = __get_row(form_index, i++);
		if (row == undefined) break;
		cell = row.getElementsByTagName('td')[0];
		if (cell == undefined) continue;
		checkbox = cell.getElementsByTagName('input')[0];
		if (checkbox == undefined) continue;
		if (checkbox.checked)
			found_selected = true;
	}
	return found_selected;
}
