<?php
/*
 * acp_dnsprotection.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2006-2020 Rubicon Communications, LLC (Netgate)
 * Copyright (c) 2003-2004 Manuel Kasper
 * Copyright (c) 2005 Bill Marquette
 * Copyright (c) 2009 Robert Zelaya Sr. Developer
 * Copyright (c) 2018 Bill Meeks
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
require_once("bluepex/dnsprotection.inc");

[$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();

if ($processCounter > 0) {
	disabledServiceDNSprotection();
	$savemsg = "Serviço de DNS Protection foi desativado, por motivos de haver o processo {$serviceProcess} em execução no momento.";
	$status = "danger";
	[$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();
}

if (isset($_POST['save'])) {
	$status = "success";
	if ($processCounter == 0) {
		if ($_POST['enableDNSProtection']) {
			$savemsg = "Habilitado serviço de DNS Protection";
			enableServiceDNSProtection();
		} else {
			disabledServiceDNSprotection();
			$savemsg = "Desabilitado serviço de DNS Protection";
		}
	} else {
		disabledServiceDNSprotection();
		$savemsg = "Serviço de DNS Protection foi desativado, por motivos de haver o processo {$serviceProcess} em execução no momento.";
		$status = "danger";
	}
	[$processCounter, $serviceProcess, $pidProcess, $haveRunning] = returnStatusProcessDNSProtection();
}

$pgtitle = array(gettext("Active Protection"), gettext("DNS Protection"));
$pglinks = array("./active_protection/ap_services.php", "@self");

include_once("head.inc");

if ($savemsg) {
	print_info_box($savemsg, $status);
}

if ($processCounter == 0) {
	$form = new Form();

	$section = new Form_Section('DNS Protection Enable');

	$enableDNSProtection = '';
	if (file_exists("/etc/enableDNSProtection")) {
		if (trim(file_get_contents("/etc/enableDNSProtection")) == 'true') {
			$enableDNSProtection = 'true';
		}
	}

	$section->addInput(new Form_Checkbox(
		'enableDNSProtection',
		'Enable DNS Protection',
		'Enable DNS Protection',
		$enableDNSProtection
	))->setHelp("Habilitar proteção DNS sobre as interfaces");

	$group = new Form_Group('Status Service');
	$valueService = ($pidProcess > 0) ? '<i class="fa fa-check" aria-hidden="true"></i> Service is running...' : '<i class="fa fa-times" aria-hidden="true"></i> Service is not running...';
	$group->add(new Form_StaticText(
		'Status Service',
		'<span class="helptext">' . $valueService . '</span>'
	));
	$section->add($group);
	
	$form->add($section);

	print($form);
} else {
	print_info_box("Não é possível ativar este serviço devido a já existencia de um processo na porta 53 do Firewall, favor conferir o serviço de {$serviceProcess} e desativa-lo caso necessário.");
}

include("foot.inc"); 
?>