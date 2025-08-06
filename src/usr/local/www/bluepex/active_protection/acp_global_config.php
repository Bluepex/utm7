<?php
/*
 * acp_interfaces_edit.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2019 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2019 Bill Meeks
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("guiconfig.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

global $g, $rebuild_rules;

$suricatadir = SURICATADIR;
$suricatalogdir = SURICATALOGDIR;

init_config_arr(array('installedpackages', 'suricata', 'rule'));
$suricataglob = $config['installedpackages']['suricata'];
$a_rule = &$config['installedpackages']['suricata']['rule'];
init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

$saveApplication = "";
if (isset($_POST["save"])) {
	if (isset($_POST["redis_enable"]) && $_POST["redis_enable"] == "on") {
		file_put_contents("/etc/redis_enabled", $_POST["redis_enable"]);
	} else {
		file_put_contents("/etc/redis_enabled", '');
	}
	$saveApplication = "Salva configurações globais";
}

$if_friendly = convert_friendly_interface_to_friendly_descr($pconfig['interface']);

$pgtitle = array(gettext("Active Protection"), gettext("Interfaces"), gettext("Global Settings Interfaces"));
$pglinks = array("./active_protection/ap_services.php", "./active_protection/acp_interfaces.php", "@self");
include_once("head.inc");

?>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<p>Esteja ciente que configurações aplicadas nesta página serão aplicadas em todas as interfaces do serviço de Active Protection.</p>
		</div>
	</div>
</div>

<?php

if (strlen($saveApplication) > 0) {
	print_info_box($saveApplication, 'success');
}

$form = new Form;

$redis_enable = "";
if (file_exists('/etc/redis_enabled')) {
	$redis_enable = trim(file_get_contents('/etc/redis_enabled'));
}

$section = new Form_Section(gettext("Global Settings"));
$section->addInput(new Form_Checkbox(
	'redis_enable',
	gettext('LOG Redis'),
	'',
	$redis_enable == 'on' ? true:false,
	'on'
))->addClass('redis_enable_bt_switch')->setHelp('<br>Habilita o gerenciamento do eve.log das interfaces Active Protection para o Redis-server.<p style="color:red;">OBS: Para a ação ser efetivada, é necessário reiniciar as interfaces para aplicar as alterações.</p>');

$form->add($section);

print($form);
?>

<?php include("foot.inc"); ?>
<script>
$(".redis_enable_bt_switch").bootstrapSwitch('size', 'mini');
$(".redis_enable_bt_switch").bootstrapSwitch('state', <?=$redis_enable == 'on' ? 'true' : 'false';?>);
</script>