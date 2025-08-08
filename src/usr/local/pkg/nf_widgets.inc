<?php
require_once('cg2_util.inc');
require_once('cg2_widgets.inc');
require_once('nf_defines.inc');
require_once('nf_util.inc');

class UsersSelectWidget extends SelectWidget
{
	function UsersSelectWidget($widgets_data, $widget_index,
		$config, $form_index)
	{
		parent::SelectWidget($widgets_data, $widget_index,
		$config, $form_index);

		$authmethod = &$GLOBALS['config']['system']['webgui']['authmode'];
		$a_user = &$GLOBALS['config']['system']['user'];

		foreach($a_user as $userent) {
			if($userent['scope'] == 'system')
				continue;

			$descr = empty($userent['descr']) ? $userent['name']:$userent['descr'];
			$name = !isset($userent['objectguid']) ? $userent['uid'] : $userent['objectguid'];

			if(($authmethod == "Local Database") && !isset($userent['imported'])){
				$this->options[] = array(
						'name' => $name,
						'label' => "{$userent['name']} - {$descr}",
						);
			} elseif($authmethod != "Local Database" && isset($userent['imported'])) {
				$this->options[] = array(
						'name' => $name,
						'label' => "{$userent['name']} - {$descr}",
				);
			}

		}
	}
}

class GroupsSelectWidget extends SelectWidget
{
	function GroupsSelectWidget($widgets_data,
            $widget_index, $config, $form_index)
	{
		parent::SelectWidget($widgets_data,
                $widget_index, $config, $form_index);

		$authmethod = &$GLOBALS['config']['system']['webgui']['authmode'];
		$a_group = &$GLOBALS['config']['system']['group'];

		foreach($a_group as $groupent) {
			if($groupent['scope'] == 'system')
				continue;

			$descr = empty($groupent['description']) ? $groupent['name']:$groupent['description'];
			$name = !isset($groupent['objectguid']) ? $groupent['gid'] : $groupent['objectguid'];

			if(($authmethod == "Local Database") && !isset($groupent['imported'])){
				$this->options[] = array(
						'name' => $name,
						'label' => "{$groupent['name']} - {$descr}",
						);
			} elseif($authmethod != "Local Database" && isset($groupent['imported'])) {
				$this->options[] = array(
					'name' => $name,
					'label' => "{$groupent['name']} - {$descr}",
					);
			}
		}

	}
}

class CategoriesSelectWidget extends SelectWidget
{
	function CategoriesSelectWidget($widgets_data, $widget_index,
            $config, $form_index)
	{
		parent::SelectWidget($widgets_data, $widget_index,
                $config, $form_index);

		$categories = NetfilterGetContentCategories();
		asort($categories);
		foreach ($categories as $index => $category) {
			$this->options[] = array(
				'name' => $index,
				'label' => $category,
			);
		}

		$this->default_value = '1,40,45,47';
	}
}

class ProtocolsSelectWidget extends SelectWidget
{
	function ProtocolsSelectWidget($widgets_data, $widget_index,
            $config, $form_index)
	{
		parent::SelectWidget($widgets_data, $widget_index,
                $config, $form_index);

		if (!$this->multiple) {
			$this->options[] = array(
				'name' => 'any',
				'label' => 'Any',
			);
		}

		$categories = NetfilterGetProtocolCategories();
		asort($categories);
		foreach ($categories as $index => $category) {
			$this->options[] = array(
				'name' => $index,
				'label' => $category,
			);
		}
	}
}

class CustomListWidget extends Widget
{
	var $lists = array();
	var $actions = array();

	function CustomListWidget($widgets_data, $widget_index,
            $config, $form_index)
	{
		parent::Widget($widgets_data, $widget_index, $config, $form_index);

		$lists = get_table_rows('nf_content_custom', 0);
		foreach ($lists as $list)
			$this->lists[] = $list['name'];
	}

	function setValue($source)
    {
		if ($source == 'post') {
			$form = $this->form;
			$name = $this->getName();
			foreach ($this->lists as $index => $list) {
				$action = $_POST["__customlist_{$name}_{$index}"];
				$this->actions["$index:$list"] = $action;
			}
		}
        else {
            parent::setValue($source);
			list($allow, $block) = explode('|', $this->value);

			$allow = explode(',', $allow);
			foreach ($allow as $list_entry) {
				if (empty($list_entry)) break;

				list($index, $list) = explode(':', $list_entry);
				$this->actions[$list] = 'allow';
			}

			$block = explode(',', $block);
			foreach ($block as $list_entry) {
				if (empty($list_entry)) break;

				list($index, $list) = explode(':', $list_entry);
				$this->actions[$list] = 'block';
			}
		}
	}

	function getValue()
    {
		$allow = $block = array();
		foreach ($this->actions as $list => $action) {
			if ($action == 'allow')
				$allow[] = $list;
			else if ($action == 'block')
				$block[] = $list;
		}
		return implode(',', $allow) . '|' . implode(',', $block);
	}

	function display()
	{
		if (empty($this->lists)) {
			$i = new HtmlTag('i', dgettext('BluePexWebFilter','(No custom lists defined)'));
			$p = new HtmlTag('p', $i->assemble());
			return $p->assemble();
		}

		$actions = array(
			'allow' => 'Allow',
			'block' => 'Block',
			'ignore' => 'Ignore',
		);

		$table = new HtmlTag('table');
		$table->addAttribute('class', 'customlist');

		$name = $this->getName();
		foreach ($this->lists as $index => $list) {
			$tr = new HtmlTag('tr');

			$th = new HtmlTag('th', $list);
			$th->addAttribute('class', 'customlist');
			$tr->setValue($th->assemble());

			$selected_action = $this->actions[$list];
			if (!isset($selected_action)) $selected_action = 'ignore';
			foreach ($actions as $action_name => $action_value) {
				$input = new HtmlTag('input', $action_value);
				$input->addAttribute('type', 'radio');
				$input->addAttribute('name', "__customlist_{$name}_{$index}");
				$input->addAttribute('value', $action_name);

				if ($selected_action == $action_name)
					$input->addAttribute('checked', 'checked');

				$td = new HtmlTag('td', $input->assemble());
				$td->addAttribute('class', 'customlist');
				$tr->addValue($td->assemble());
			}

			$table->addValue($tr->assemble());
		}

		return $table->assemble();
	}
}

class TimeSelectWidget extends Widget
{
	function setValue($source)
	{
		if ($source == 'post')
			$this->value = $_POST['timeselect_values'];
		else
			parent::setValue($source);

        // Lidar com o caso do formato antigo de configuracao
        if ($source == 'config' && strpos($this->value, '-') === false)
            $this->value = '';
	}

	function getTableValue()
    {
        return $this->value == '' ? 'Disabled' : 'Enabled';
	}

	function display()
	{
		$input = new HtmlTag('input', 'Enable');
		$input->addAttribute('type', 'checkbox');
		$input->addAttribute('name', 'timeselect_enable');
		$input->addAttribute('id', 'RegraTemHorarios');
		if ($this->value != '')
			$input->addAttribute('checked', 'checked');
		$res = $input->assemble();

        $hidden = new HtmlTag('input');
		$hidden->addAttribute('type', 'hidden');
        $hidden->addAttribute('name', 'timeselect_values');
        $hidden->addAttribute('value', $this->value);
        $hidden->addAttribute('id', 'RegraHorarios');
        $res .= $hidden->assemble();

        $table = '<table class="horarios"><tr><th></th>';
        for ($i = 0; $i < 24; ++$i) {
            if ($i < 10)
                $i = '0' . $i;
            $table .= '<th class="hora" colspan="2"> ' . $i . '</th>';
        }
        $table .= '</tr>';

        $dias = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        for ($i = 0; $i < 7; ++$i) {
            $table .= "<tr class=\"dia_$i\"><th class=\"dia\">{$dias[$i]}</th>";
            for ($j = 0; $j < 24; ++$j) {
                $table .= '<td class="hora_' . $j. ' min_0"></td>';
                $table .= '<td class="hora_' . $j . ' min_30"></td>';
            }
            $table .= '</tr>';
        }
        $table .= '</table>';
        $res .= $table;

		return $res;
	}

	function getHtmlNames()
    {
		return array('timeselect_enable');
	}

	function getJavascript(&$widgets)
    {
		return <<<EOD
jQuery.noConflict();

function decodificar_horario(item)
{
    var horario = 0;
    var classes = item.attr('class').split(' ');
    jQuery.each(classes, function(i, cls) {
        if (cls.indexOf('hora_') == 0)
            horario += parseInt(cls.split('_')[1], 10) * 100;
        else if (cls == 'min_30')
            horario += 30;
    });
    return horario;
}

function calcular_horarios_salvamento()
{
    if (jQuery('#RegraTemHorarios:checked').length == 0) {
        jQuery('#RegraHorarios').val('');
        return;
    }

    var res = new Array();
    var dia = 0;
    jQuery('table.horarios tr').not(':first').each(function(i, tr) {
        var base = jQuery(tr).children('td.selected:first');
        while (base.length) {
            var prefixo = dia + '-' + decodificar_horario(base) + '-';
            var first_not = base.nextAll().not('.selected').filter(':first');
            if (first_not.length) {
                res.push(prefixo + decodificar_horario(first_not));
                base = first_not.nextAll('.selected:first');
            }
            else {
                res.push(prefixo + '2400');
                break;
            }
        }
        ++dia;
    });
    jQuery('#RegraHorarios').val(res.join(','));
}

jQuery(document).ready(function() {
    function atualizar_horarios()
    {
        if (jQuery('#RegraTemHorarios:checked').length)
            jQuery('table.horarios').removeClass('disabled');
        else
            jQuery('table.horarios').addClass('disabled');
    }
    jQuery('#RegraTemHorarios').change(atualizar_horarios);
    atualizar_horarios();

    jQuery('table.horarios td').click(function() {
        if (!jQuery('table.horarios').hasClass('disabled'))
            jQuery(this).toggleClass('selected');
    });
    jQuery('table.horarios th.dia').click(function() {
        if (!jQuery('table.horarios').hasClass('disabled')) {
            if (jQuery(this).parent().children('td.selected:first').length)
                jQuery(this).parent().children('td').removeClass('selected');
            else
                jQuery(this).parent().children('td').addClass('selected');
        }
    });
    jQuery('table.horarios th.hora').click(function() {
        if (!jQuery('table.horarios').hasClass('disabled')) {
            var hora = 'hora_' + parseInt(jQuery(this).html(), 10);
            if (jQuery('table.horarios td.' + hora).filter('.selected:first').length)
                jQuery('table.horarios td.' + hora).removeClass('selected');
            else
                jQuery('table.horarios td.' + hora).addClass('selected');
        }
    });

    jQuery.each(jQuery('#RegraHorarios').val().split(','), function(i, horario) {
        var tmp = horario.split('-');
        var start = parseInt(tmp[1], 10);
        start = Math.floor(start / 100) * 2 + (start % 100 ? 1 : 0);
        var end = parseInt(tmp[2], 10);
        end = Math.floor(end / 100) * 2 + (end % 100 ? 1 : 0);
        jQuery('table.horarios tr.dia_' + tmp[0] + ' td').each(function(i, td) {
            if (i >= start && i < end)
                jQuery(td).addClass('selected');
        });
    });
});
EOD;
	}
}

class NetfilterLicenseStatusWidget extends Widget
{
	function display()
	{
        $res = explode("\n", trim(shell_exec('/opt/netfilter/bin/authmgr -a')));

        $info = $tipo = $data = 'N/A';
        if (count($res) > 0) {
            if (!empty($res[0]))
                $info = $res[0];
            if (count($res) > 1 && strlen($res[1]) > 9) {
                $data = strtotime(substr($res[1], 0, 8));
                $data = date('Y-m-d', $data);
                if ($data === false || $data === -1)
                    $data = 'N/A';
                $tipo = substr($res[1], 8);
                if ($tipo != 'OK')
                    $tipo = ucfirst(strtolower($tipo));
            }
        }

        $res = sprintf(dgettext('BluePexWebFilter',"Activation info: %s"),$info);

		$br = new HtmlTag('br');
		$res .= $br->assemble();

		$res .= sprintf(dgettext('BluePexWebFilter',"Status: %s"),$tipo);

		$br = new HtmlTag('br');
		$res .= $br->assemble();

        if ($tipo == 'Expired')
			$res .= dgettext('BluePexWebFilter','Expired: ');
		else
			$res .= dgettext('BluePexWebFilter','Expires: ');
		$res .= $data;

        if ($tipo == 'Expired' && $info != 'N/A') {
            $br = new HtmlTag('br');
            $res .= $br->assemble();
            $a = new HtmlTag('a', dgettext('BluePexWebFilter','Click here to activate this unit'));
            $a->addAttribute('href', NETFILTER_EXPIRED_URL . $info);
            $res .= $a->assemble();
        }

		return $res;
	}
}
?>

