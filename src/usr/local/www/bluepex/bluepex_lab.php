<?php
/*
 * system.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2019 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally based on m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

##|+PRIV
##|*IDENT=page-system-generalsetup
##|*NAME=System: General Setup
##|*DESCR=Allow access to the 'System: General Setup' page.
##|*MATCH=system.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("system.inc");
require_once("bluepex/bp_cron_control.inc");
require_once("bluepex/bp_auditing.inc");
require_once("bluepex/util.inc");

$passModal = "!@#Bluepex!@#";
$tmpPass = "";
if (!file_exists("/var/db/.passModal")) {
	if (file_exists("/var/db/.passModalTemp")) {
		$tmpPass = trim(file_get_contents("/var/db/.passModalTemp"));
	}
} else {
	$tmpPass = trim(file_get_contents("/var/db/.passModal"));
}
if (strlen($tmpPass) > 0) {
	$passModal = $tmpPass;
}

$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['domain'] = $config['system']['domain'];

$pconfig['dnsserver'] = $config['system']['dnsserver'];

$arr_gateways = return_gateways_array();

$senha = $_POST['senha'];

// set default columns to two if unset
if (!isset($config['system']['webgui']['dashboardcolumns'])) {
	$config['system']['webgui']['dashboardcolumns'] = 2;
}

// set default language if unset
if (!isset($config['system']['language'])) {
	$config['system']['language'] = $g['language'];
}

$dnshost_counter = 1;

while (isset($config["system"]["dns{$dnshost_counter}host"])) {
	$pconfig_dnshost_counter = $dnshost_counter - 1;
	$pconfig["dnshost{$pconfig_dnshost_counter}"] = $config["system"]["dns{$dnshost_counter}host"];
	$dnshost_counter++;
}

$dnsgw_counter = 1;

while (isset($config["system"]["dns{$dnsgw_counter}gw"])) {
	$pconfig_dnsgw_counter = $dnsgw_counter - 1;
	$pconfig["dnsgw{$pconfig_dnsgw_counter}"] = $config["system"]["dns{$dnsgw_counter}gw"];
	$dnsgw_counter++;
}

$pconfig['dnsallowoverride'] = isset($config['system']['dnsallowoverride']);
$pconfig['timezone'] = $config['system']['timezone'];
$pconfig['timeservers'] = $config['system']['timeservers'];
$pconfig['language'] = $config['system']['language'];
$pconfig['webguicss'] = $config['system']['webgui']['webguicss'];
//$pconfig['logincss'] = $config['system']['webgui']['logincss'];
$pconfig['webguifixedmenu'] = $config['system']['webgui']['webguifixedmenu'];
$pconfig['dashboardcolumns'] = $config['system']['webgui']['dashboardcolumns'];
$pconfig['interfacessort'] = isset($config['system']['webgui']['interfacessort']);
$pconfig['webguileftcolumnhyper'] = isset($config['system']['webgui']['webguileftcolumnhyper']);
$pconfig['disablealiaspopupdetail'] = isset($config['system']['webgui']['disablealiaspopupdetail']);
$pconfig['dashboardavailablewidgetspanel'] = isset($config['system']['webgui']['dashboardavailablewidgetspanel']);
$pconfig['systemlogsfilterpanel'] = isset($config['system']['webgui']['systemlogsfilterpanel']);
$pconfig['systemlogsmanagelogpanel'] = isset($config['system']['webgui']['systemlogsmanagelogpanel']);
$pconfig['statusmonitoringsettingspanel'] = isset($config['system']['webgui']['statusmonitoringsettingspanel']);
$pconfig['webguihostnamemenu'] = $config['system']['webgui']['webguihostnamemenu'];
$pconfig['dnslocalhost'] = isset($config['system']['dnslocalhost']);
//$pconfig['dashboardperiod'] = isset($config['widgets']['period']) ? $config['widgets']['period']:"10";
$pconfig['roworderdragging'] = isset($config['system']['webgui']['roworderdragging']);
$pconfig['loginshowhost'] = isset($config['system']['webgui']['loginshowhost']);
$pconfig['requirestatefilter'] = isset($config['system']['webgui']['requirestatefilter']);
$pconfig['encryption_password'] = $config['modules']['schedules_bpx']['encryption_password'];

if (!$pconfig['timezone']) {
	if (isset($g['default_timezone']) && !empty($g['default_timezone'])) {
		$pconfig['timezone'] = $g['default_timezone'];
	} else {
		$pconfig['timezone'] = "Etc/UTC";
	}
}

if (!$pconfig['timeservers']) {
	$pconfig['timeservers'] = "pool.ntp.org";
}

$changedesc = gettext("System") . ": ";
$changecount = 0;

function is_timezone($elt) {
	return !preg_match("/\/$/", $elt);
}

if ($pconfig['timezone'] <> $_POST['timezone']) {
	filter_pflog_start(true);
}

$timezonelist = system_get_timezone_list();
$timezonedesc = $timezonelist;

/*
 * Etc/GMT entries work the opposite way to what people expect.
 * Ref: https://github.com/eggert/tz/blob/master/etcetera and Redmine issue 7089
 * Add explanatory text to entries like:
 * Etc/GMT+1 and Etc/GMT-1
 * but not:
 * Etc/GMT or Etc/GMT+0
 */
foreach ($timezonedesc as $idx => $desc) {
	if (substr($desc, 0, 7) != "Etc/GMT" || substr($desc, 8, 1) == "0") {
		continue;
	}

	$direction = substr($desc, 7, 1);

	switch ($direction) {
	case '-':
		$direction_str = gettext('AHEAD of');
		break;
	case '+':
		$direction_str = gettext('BEHIND');
		break;
	default:
		continue;
	}

	$hr_offset = substr($desc, 8);
	$timezonedesc[$idx] = $desc . " " .
	    sprintf(ngettext('(%1$s hour %2$s GMT)', '(%1$s hours %2$s GMT)', intval($hr_offset)), $hr_offset, $direction_str);
}

$multiwan = false;
$interfaces = get_configured_interface_list();
foreach ($interfaces as $interface) {
	if (interface_has_gateway($interface)) {
		$multiwan = true;
	}
}

if ($_GET['act'] == "dellogo" && $config['logo']['content']) {
	unlink_if_exists("/usr/local/www/images/{$config['logo']['name']}");
	unlink_if_exists("{$g['captiveportal_path']}/captiveportal_public/images/{$config['logo']['name']}");
	unset($config['logo']);
	write_config(gettext("System/General Setup: Restored default logo!"));
	if (is_array($config['captiveportal'])) {
		require_once("captiveportal.inc");
		captiveportal_configure();
	}
	header("Location: system.php");
	exit;
}

$save_true=false;
if (isset($_POST['save']) && strlen($_POST['save']) > 0) {
	
	file_put_contents("/etc/serial", $_POST['serial']);
	file_put_contents("/etc/capacity-utm", $_POST['capacity']);
	file_put_contents("/etc/model", $_POST['model']);
	file_put_contents("/etc/hw", $_POST['HW']);
	file_put_contents("/etc/mode", $_POST['mode']);

	if ($_POST['disabled_report_accesses_acp'] == "yes") {
		unlink_if_exists("/etc/disabled_report_accesses_acp");
	} else {
		file_put_contents("/etc/disabled_report_accesses_acp", "");
	}

	if ($_POST['lock_fapp_acp_limit'] == "yes") {
		file_put_contents('/etc/lock_fapp_acp_limit', 'true');
	} else {
		file_put_contents('/etc/lock_fapp_acp_limit', 'false');
	}

	if ($_POST['disabled_check_data'] == "yes") {
		file_put_contents('/etc/disabled_check_data', 'true');
	} else {
		file_put_contents('/etc/disabled_check_data', 'false');
	}

	if ($_POST['showInstableACP'] == "yes") {
		file_put_contents('/etc/showInstableACP', '');
	} else {
		if (file_exists('/etc/showInstableACP')) {
			unlink('/etc/showInstableACP');
		}
		foreach (glob("/etc/hardwarelimitACP*") as $fileacp) {
			if (file_exists($fileacp)) {
				unlink($fileacp);
			}
		}
	}

	if (isset($_POST['implemention_mode'])) {
		if (trim(file_get_contents("/etc/implemention_mode")) == 'false') {
			bp_cron_disabled_all();
		}
		file_put_contents("/etc/implemention_mode", 'true');
	} else {
		if (trim(file_get_contents("/etc/implemention_mode")) == 'true') {
			bp_cron_active_all();
		}
		file_put_contents("/etc/implemention_mode", 'false');
	}

	if ($_POST['blockShell'] == "yes") {
		file_put_contents('/etc/block_shell', 'true');
	} else {
		file_put_contents('/etc/block_shell', 'false');
	}

	if (isset($_POST["generatyNewKey"]) && $_POST["generatyNewKey"] == "yes") {
		file_put_contents("/etc/generatyNewKey", "");
	} else {
		if (file_exists("/etc/generatyNewKey")) {
			unlink("/etc/generatyNewKey");
		}
	}

	if($_POST["enableTunning"] == "yes") {
		file_put_contents('/etc/enableTunning', '');
	} else {
		if (file_exists('/etc/enableTunning')) {
			unlink('/etc/enableTunning');
		}
	}

	if (file_exists('/etc/enableTunning')) {
		$model_ = intval(substr(trim(file_get_contents("/etc/model")),-4));
		if ($model_ <= 2000) {
			#Old
			#exec("/usr/local/bin/php /usr/local/www/clean_tunning.php && /usr/local/bin/php /usr/local/www/tunning.php");
			exec("/usr/local/bin/php /usr/local/www/no-tunning.php && /usr/local/bin/php /usr/local/www/tunning.php");
		} else if ($model_ > 2000 && $model_ <= 4500){
			exec("/usr/local/bin/php /usr/local/www/no-tunning.php && /usr/local/bin/php /usr/local/www/tunning.php");
		} else {
			exec("/usr/local/bin/php /usr/local/www/no-tunning.php && /usr/local/bin/php /usr/local/www/tunning.php");
		}
	} else {
		exec("/usr/local/bin/php /usr/local/www/no-tunning.php");
	}

	$save_true=true;

}

if (file_exists('/etc/showInstableACP')) {
	if ($_POST['deleteHardwareMenssages']) {
		foreach (glob("/etc/hardwarelimitACP*") as $fileacp) {
			if (file_exists($fileacp)) {
				if (count(array_filter(explode(";", trim(file_get_contents($fileacp))))) >= 10) {
					unlink($fileacp);
				}
			}
		}
		$save_true=true;
	}
}

if (isset($_POST['createImplementationFile'])) {
	if (!file_exists("/etc/implementation_status")) {
		file_put_contents("/etc/implementation_status", "changeStatus");
		file_put_contents("/etc/implementation_reset_interface_lan", "");
		$save_true=true;
	}
}

if (isset($_POST['createStableImplementationFile'])) {
	if (!file_exists("/etc/implementation_status")) {
		file_put_contents("/etc/implementation_status", "stable");
		$save_true=true;
	}
}

$reaplicarBaseStatus = false;
if (isset($_POST['reaplicarBase'])) {
	if (intval(trim(shell_exec('cat /etc/version.patch | cut -c1-1'))) == 0) {
		mwexec("rm /etc/version_base");
	} elseif (intval(trim(shell_exec('cat /etc/version.patch | cut -c1-1'))) == 1) {
		mwexec("rm /etc/version_base_1");
	}
	if (file_exists('/etc/version_update_broken')) {
		mwexec("rm /etc/version_update_broken");
	}
	mwexec("echo \"6.0.0-DEV\" > /etc/version");
	mwexec("pkill -9 -af bp_monitor_agent");
	mwexec_bg("/usr/local/bin/python3.8 /usr/local/bin/bp_monitor_agent");
	$reaplicarBaseStatus = true;
}

$first_use_utm_6 = false;
if (isset($_POST['first_use_utm_6'])) {
	if (file_exists('/etc/newVersionUTM6')) {
		unlink("/etc/newVersionUTM6");
	}
	$first_use_utm_6 = true;
}

if (isset($_POST['renovarImplementationFile'])) {
	if (file_exists("/etc/implementation_status")) {
		file_put_contents("/etc/implementation_status", "changeStatusRenovation");
		$save_true=true;
		require_once("config.inc");
		require_once("auth.inc");
		require_once("functions.inc");
		require_once("shaper.inc");	
		$admin_user =& getUserEntryByUID(0);
		if (!$admin_user) {
			$admin_user = array();
			$admin_user['uid'] = 0;
			if (!is_array($config['system']['user'])) {
				$config['system']['user'] = array();
			}
			$config['system']['user'][] = $admin_user;
			$admin_user =& getUserEntryByUID(0);
		}
	
		if (isset($admin_user['disabled'])) {
			unset($admin_user['disabled']);
		}
		if (isset($admin_user['expires'])) {
			unset($admin_user['expires']);
		}

		if (isset($admin_user['afautenticator']['enable2af']) && $admin_user['afautenticator']['enable2af'] == "yes") {
			$admin_user['afautenticator']['enable2af'] = "no";
		}
	
		local_user_set_password($admin_user, 'b1uepexutm');
		local_user_set($admin_user);
		write_config(gettext("Password is reset"));
	}
}


$pgtitle = array(gettext("System"), 'BluePex LAB');
include("head.inc");

?>

<style>
#modalImplementationFile {
    margin-left: 25%;
}
#modalImplementationFileChange {
    margin-left: 25%;
}
</style>


<!-- Modal -->
<div class="modal fade" id="ExemploModalCentralizado" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="TituloModalCentralizado"><?=gettext("Provide Password to Access")?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
			<?php if ($reaplicarBaseStatus): ?>
				<div class="btn-success" style="padding: 10px !important; text-align: center; text-transform: capitalize;border-radius:5px;margin:5px;" id="sumir-status">
					<p style="margin: 0px !important;"><?=gettext("Reapplying the base version")?></p>
				</div>
			<?php endif; ?>
			<?php if ($first_use_utm_6): ?>
				<div class="btn-success" style="padding: 10px !important; text-align: center; text-transform: capitalize;border-radius:5px;margin:5px;" id="sumir-status">
					<p style="margin: 0px !important;"><?=gettext("Activated UTM 6 version first use screen before login.")?></p>
				</div>
			<?php endif; ?>
	  		<?php if ($save_true): ?>
				<div class="btn-success" style="padding: 10px !important; text-align: center; text-transform: capitalize;border-radius:5px;margin:5px;" id="sumir-status">
					<p style="margin: 0px !important;"><?=gettext("changes saved")?></p>
				</div>
			<?php endif; ?>
			<form method="POST" action="" style="margin-top: 10px !important; border: 0px solid transparent;">
			        <div class="form-row">
			        	<div class="col">
			            	<label for="recipient-name" class="col-form-label"><?=gettext("Enter password:")?></label>
			            	<input type="password" class="form-control" name="senha" maxlength="50">
			          	</div>
			        </div> 
			        <hr> 
			        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Close")?></button>
			        <button type="submit" name="confirmar_senha" class="btn btn-primary"><?=gettext("Continuar")?></button>
					<?php if (isset($_POST['senha']) && ($_POST['senha'] != $passModal)): ?>
						<script>alert("<?=gettext("Incorrect password!")?>");</script>
					<?php endif; ?>
					</form>
			</div>
		</div>
	</div>
</div>
<!-- jquery -->
<script src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript">
	<?php if($senha == '' || $senha != $passModal): ?>
		$(document).ready(function() {
			$('#ExemploModalCentralizado').modal('show');
		});
	<?php endif; ?>
</script>
<?php if($senha == $passModal): ?>

	<?php
	
	if ($input_errors) {
		print_input_errors($input_errors);
	}
	
	if ($changes_applied) {
		print_apply_result_box($retval);
	}
	
	if (file_exists('/etc/showInstableACP')) {
	
		$showInterfaces = "";
		foreach (glob("/etc/hardwarelimitACP*") as $fileacp) {
			if (file_exists($fileacp)) {
				if (count(array_filter(explode(";", trim(file_get_contents($fileacp))))) >= 10) {
					$showInterfaces = $showInterfaces . trim(explode("_",$fileacp)[1]) . ", ";
				}
			}
		}
	
		if (strlen($showInterfaces) > 0) {
			$showInterfaces = rtrim($showInterfaces,", ");
			print_info_box("<p>As seguintes interfaces estão apresentando problemas de instabilidade com o modo de 'Proteção extra' habilitado: $showInterfaces.</p><br>" . 
							"<p>Estas interfaces apresentaram problemas por mais de 10 minutos seguidos, desta forma, para evitar maiores problemas, as interfaces listadas foram desativas.</p><br>" . 
							"<p>Quando os serviços parados forem ativados, está mensagem será deletada.</p>".
							"<hr>" .
							"<p style='color:red;'>OBS: Esteja ciente que este bloqueio somente ocorre com a ativação do monitoramento do modo de 'Proteção extra'.</p>".
							"<hr>".
							"<p style='color:red;'>Caso a apresentação da mensagem persista mesmo após o inicio das interfaces, clique no botão abaixo parra forçar a limpeza:</p>" . 
							"<button type='click' class='btn btn-success' onclick='removeMensagemHardwareACP()'>Remover a mensagem de alerta</button>", 'warning');
						}
	}

	if (file_exists('/etc/version_update_broken')) {
		if ((filemtime('/etc/version_update_broken')+600) < strtotime('now')) {
			print_info_box(gettext("There was a problem updating on: ") . explode(" ", trim(file_get_contents('/etc/version_update_broken')))[0] . 
			"<br>Occurrence of error happened in the block of lines: " . explode(" ", trim(file_get_contents('/etc/version_update_broken')))[1] . 
			"<br>Reapply foundation to correct installation.");
		}
	}
	?>
	
	<div id="container">

	<?php

	$modelUTM = "";
	if (file_exists('/etc/model')) {
		$modelUTM = file_get_contents('/etc/model');
	}
	$modelsutm = array(
		'UTM 1000' => 'UTM 1000', 
		'UTM 1500' => 'UTM 1500', 
		'UTM 2000' => 'UTM 2000', 
		'UTM 2500' => 'UTM 2500', 
		'UTM 3000' => 'UTM 3000', 
		'UTM 3500' => 'UTM 3500', 
		'UTM 4000' => 'UTM 4000', 
		'UTM 4500' => 'UTM 4500', 
		'UTM 5000' => 'UTM 5000', 
		'UTM 5500' => 'UTM 5500',
		'UTM 6000' => 'UTM 6000'
	);
	$HWUTM = "";
	if (file_exists('/etc/hw')) {
		$HWUTM = file_get_contents('/etc/hw');
	}
	$HWUTMS = array(
		'Generic' => 'Generic',
		'Virtual-Machine' => 'Virtual-Machine',
		'FWA-Advantech' => 'BPHW000',
        'CAD0225' => 'BPHW001',
		'CAF3455-UFS' => 'BPHW002-UFS',
		'CAF3455-ZFS' => 'BPHW002-ZFS',
		'CAF0251' => 'BPHW003',
		'CAR2361' => 'BPHW004',
		'CARB158' => 'BPHW005',
		'CAR2070' => 'BPHW006',
		'CAR3030' => 'BPHW007'  
	);
	$serial = "";
	if (file_exists('/etc/serial')) {
		$serial = file_get_contents('/etc/serial');
	}

	$modeutm = array(
		'stable' => gettext('stable'), 
		'test' => gettext('test')
	);


	$statusEnableField = false;
	if (file_exists("/etc/generatyNewKey")) {
		$statusEnableField = true;
	} 


	if (file_exists('/etc/mode')) {
		$mode = file_get_contents('/etc/mode');
	}

	$capacityList = array(
		'20' => '20', 
		'50' => '50', 
		'75' => '75', 
		'100' => '100', 
		'200' => '200', 
		'300' => '300', 
		'500' => '500', 
		'1000' => '1000', 
		'2000' => '2000', 
		'3000' => '3000',
		'4000' => '4000'
	);

	$capacity = "";
	if (file_exists('/etc/capacity-utm')) {
		$capacity = file_get_contents('/etc/capacity-utm');
	}

	$form = new Form;
	$form->setMultipartEncoding();
	$section = new Form_Section(gettext('Hardware System Config'));

	if (file_exists('/etc/sub_version_base') && (strlen(trim(file_get_contents('/etc/sub_version_base'))) > 0)) {
		$section->addInput(new Form_Input(
			'',
			gettext('Sub Version Release'),
			'hidden',
			''
		))->setHelp("<b style='font-weight:normal;color:#333;'>" . gettext('Version: ') . " " . trim(file_get_contents('/etc/sub_version_base')) . "</b>");
	}

	$section->addInput(new Form_Checkbox(
		'generatyNewKey',
		gettext("Generate new Product Key (2.0)"),
		gettext("Enable this field to activate the new product key model. (New product authentication on the WSUTM platform will be required for the product to be validated)"),
		$statusEnableField
	));


	$implemention_mode = '';
	if (file_exists("/etc/implemention_mode")) {
		if (trim(file_get_contents("/etc/implemention_mode")) == 'true') {
			$implemention_mode = 'true';
		}
	}

	$section->addInput(new Form_StaticText(
		gettext('Compatible execution modes'), 
		return_text_compatible_interfaces()
	));
	
	$section->addInput(new Form_Checkbox(
		'implemention_mode',
		gettext('Enable implementation mode'),
		gettext('Implementation mode'),
		$implemention_mode
	))->setHelp("<p style='color:red;'>" . gettext("NOTE: This option disables several CRON actions and will notify on the main dashboard that this mode is activated.") . "</p>");

	$lock_fapp_acp_limit = '';
	if (!file_exists("/etc/lock_fapp_acp_limit")) {
		file_put_contents('/etc/lock_fapp_acp_limit', 'false');
	} else {
		if (trim(file_get_contents('/etc/lock_fapp_acp_limit')) == 'false') {
			$lock_fapp_acp_limit = '';
		} else {
			$lock_fapp_acp_limit = 'true';
		}
	}

	$section->addInput(new Form_Checkbox(
		'lock_fapp_acp_limit',
		gettext('Disable Lock limit create interfaces in FirewallApp/Active Proteciton'),
		gettext('Disable'),
		$lock_fapp_acp_limit
	))->setHelp("Disabled limit create interfaces in FirewallApp and Active Proteciton");

	$disabled_report_accesses_acp = !file_exists('/etc/disabled_report_accesses_acp');
	$section->addInput(new Form_Checkbox(
		'disabled_report_accesses_acp',
		gettext('Show Active Protection reporting option in top menu'),
		gettext('Enable'),
		$disabled_report_accesses_acp
	))->setHelp("<p>Only select this option if the customer wants easy access to Active Protection reports.</p><p class='text-danger'>Note: Easy access is limited only if you know the URL, the access is still possible.</p>");

	$disabled_check_data = '';
	if (!file_exists("/etc/disabled_check_data")) {
		file_put_contents('/etc/disabled_check_data', 'false');
	} else {
		if (trim(file_get_contents('/etc/disabled_check_data')) == 'false') {
			$disabled_check_data = '';
		} else {
			$disabled_check_data = 'true';
		}
	}

	$section->addInput(new Form_Checkbox(
		'disabled_check_data',
		gettext('Disable Service Assurance Routine/Update (check_data)'),
		gettext('Disable'),
		$disabled_check_data
	))->setHelp("<p style='color:red;'>Disabling this option causes the 'check_data' service to no longer run, keeping in mind that it is not recommended to leave it disabled by default, as it is an important process to be executed for the proper functioning of the system;</p>");
	
	if (file_exists('/etc/performance_extends') && trim(file_get_contents('/etc/performance_extends')) == 'true') {
		$extramsg = "<br><p style='color:red;'>NOTE: The extended protection mode in performance mode is enabled, if you return to \"stable\" mode, it will be disabled;</p>";
	}

	$statusTunning = false;
	if (file_exists('/etc/enableTunning')) {
		$statusTunning = true;
	}

	$section->addInput(new Form_Checkbox(
		'enableTunning',
		gettext("Enable Tuning on the device"),
		gettext("Enables the Tuning of the equipment, this action modifies the parameters of the system and its functioning, only activate this option if the equipment is limited and the need has already been determined."),
		$statusTunning
	));

	$blockShell = false;
	if (file_exists("/etc/block_shell")) {
		$blockShell = trim(file_get_contents("/etc/block_shell")) == 'true' ? true : false;
	}

	$section->addInput(new Form_Checkbox(
		'blockShell',
		"Enable password for shell access",
		"Enables the need for a password to access the SSH shell and directly on the equipment.",
		$blockShell
	));

	$showInstableACP = false;
	if (file_exists('/etc/showInstableACP')) {
		$showInstableACP = true;
	}

	$section->addInput(new Form_Checkbox(
		'showInstableACP',
		"Enable crash log sampling in Active Protection when 'Extra Protection Mode' is enabled",
		"This option will generate alerts about the instability of the 'extra protection' service, however it will not affect its functioning, it only has the value of demonstrating the occurrence.",
		$showInstableACP
	));

	$section->addInput(new Form_Select(
		'mode',
		'*mode',
		$mode,
		$modeutm
	))->setHelp(gettext('Mode Configuration'));

	$section->addInput(new Form_Select(
		'model',
		gettext('*model'),
		$modelUTM,
		$modelsutm
	))->setHelp(gettext('Model for Hardware UTM'));

	$section->addInput(new Form_Select(
		'HW',
		gettext('*HW'),
		$HWUTM,
		$HWUTMS
	))->setHelp(gettext('Type Hardware UTM'));
	
	$section->addInput(new Form_Select(
        'capacity_utm',
        gettext('*Capacity'),
        $capacity,
        $capacityList
    ))->setHelp(gettext('Model for Hardware UTM'));

	$section->addInput(new Form_Input(
		'serial',
		'*Serial',
		'text',
		$serial,
		['placeholder' => 'Serial']
	))->setHelp(gettext('Serial for Hardware UTM'));

	$section->addInput(new Form_Input(
        'capacity',
        '',
        'hidden',
		'',
		array()	
        ));

	$form->add($section);

	print $form;

	$csswarning = sprintf(gettext("%sUser-created themes are unsupported, use at your own risk."), "<br />");

	?>
	</div>

	<form action='./bluepex_lab.php' id="removeMensagemHardwareACP" method='POST' style='border:0px solid transparent !important;margin:0px; display:none;'>
		<input type='hidden' name='deleteHardwareMenssages' id='deleteHardwareMenssages' value='true' style='display:none;'/>
	</form>

	<script>
	function removeMensagemHardwareACP() {
		$("#deleteHardwareMenssages").get(0).type='hidden'; //Force button to hidden -> In HTML is not function
		$("#removeMensagemHardwareACP").submit();
	}
	</script>

<?php else: ?>

	<style type="text/css">

	#header-licenses-information { min-height: 165px; margin-bottom: 65px; background:url(./images/bg-header.png) no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;}
	#description-information h4 {color: #007dc5;}
	#description-information h6 {color: #333; background-color: #efefef; padding: 12px 55px; font-size: 1.4em;}
	#information-support {margin: 0 auto;}
	#footer { position: absolute; right: 0; bottom: 0; left: 0; padding: 25px; background-color: #007dc5; text-align: center; margin-top:10px; min-height:80px; }
	/* Footer Licenses Control */
	.footer-licenses-control {position: absolute; bottom: 0; right: 0; width: 100%; min-height: 66px; z-index: 0; color:#fff; background-color: #007dc5; padding-top: 30px; margin-top: 20px;}
	@media only screen and (max-width : 768px) {
		body { background: #fff; }
		#content { top:inherit; position:inherit; margin:0; width: 100%; font-size:14px; }
		#img-cloud { height:240px; }
	}
	@media only screen and (max-width : 480px) {
		#img-cloud { height:150px; }
	}
	@media only screen and (max-width : 320px) {
		#img-cloud { height:100px; }   
	}
</style>
<div id="wrapper-licenses-control">
	<div class="container-fluid">
		<div class="row" id="header-licenses-information"></div>
			<div class="col-md-12" id="content">
				<div class="row" id="warning-licenses">
					<div class="col-12 col-md-12 mt-5 text-center">
						<div id="description-information">
							<div class="icon-ilustration">
								<img src="./images/cadeado.jpg" class="img-fluid text-center">
							</div>
							<div class="mt-4 text-center">
								<h4><?=gettext("RESTRICTED ACCESS")?></h4>
							</div>
							<div class="col-12 mt-4 text-center">
								<div class="row">
									<div id="information-support">
										<h6><?=gettext(" Please contact us for more information.")?></h6>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
setTimeout(() => {
	if (!!document.getElementById("sumir-status")) {
		document.getElementById("sumir-status").style.display="none";
	}
}, 10000);
</script>
<?php endif; ?>



<?php include("foot.inc"); ?>

<?php if (file_exists("/etc/implementation_status")): ?>

<div class="modal fade" id="modal_implementacao_change" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 style="color:red;"> !!! Atenção !!!</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body my-5">
				<p style="font-size: 14px;">Esteja ciente que continuar com a ação de "Renovar senha do Administrador" irá gerar uma tela após o primeiro login e posteiores que obriga o usuário a entrar com uma nova senha sobre a padrão do usuário Administrador do sistema.</p>
				<p style="font-size: 14px;">Esteja claro tambem que a sessão atual do usuário permanecerá funcionando até que a mesma expire, dessa forma, recomendamos fortemente a finalizar quaisquer tarefas ainda a serem realizadas para depois aplicar essa ação.</p>
				<p style="font-size: 14px;">Vale tambem comentar que quando está ação for aplicada a senha de dupla autenticação será 'desativa', o QRCODE atual não será perdido e nem nada do genero, somente o serviço será parado, sendo assim, necessário reativalo a conta após a troca da senha.</p> 
				<p style="font-size: 14px;">Por fim, a senha do usuário Administrador atual será perdida e será restaurada a senha padrão do equipamento que obriga a trocar após o termino dessa sessão.</p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
				<form action="#" method="POST" style="margin: 0px;border: 0px;">
					<button type="submit" class="btn btn-primary" id="renovarImplementationFile" name="renovarImplementationFile">Continuar</button>
				</form>
			</div>
		</div>
	</div>
</div>

<script>

var btn_implementacao = document.createElement("button");
btn_implementacao.className = 'btn btn-primary';
btn_implementacao.innerHTML = '<i class="fa fa-pencil"></i> Renovar senha do Administrador';
btn_implementacao.id ='modalImplementationFileChange';
btn_implementacao.name ='modalImplementationFileChange';
document.getElementsByClassName("col-sm-12 form-button text-center mt-5")[0].append(btn_implementacao);

document.getElementById("modalImplementationFileChange").addEventListener("click", function(event){
	event.preventDefault();		
	$('#modal_implementacao_change').modal('show');
});

</script>

<?php endif; ?>

<?php if (!file_exists("/etc/implementation_status")): ?>


	<div class="modal fade" id="modal_implementacao" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 style="color:red;"> !!! Atenção !!!</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body my-5">
					<p style="font-size: 14px;">Esteja ciente que continuar com a ação de "Finalizar implementação" irá gerar uma tela após o primeiro login e posteriores que obriga o usuário a entrar com uma nova senha sobre a padrão do usuário Administrador do sistema.</p>
					<p style="font-size: 14px;">Esteja claro tambem que a sessão atual do usuário permanecerá funcionando até que a mesma expire, dessa forma, recomendamos fortemente a finalizar quaisquer tarefas ainda a serem realizadas para depois aplicar essa ação.</p>
					<hr>
					<p style="font-size: 14px;color:red;">OBS: Esta ação somente irá ocorrer caso seja a primeira implementação, o processo irá configurar a interface LAN com os valores padrões de IP e DHCP, os mesmos serão aplicados no momento que fizer "logout" ou reiniciar o equipamento, esteja ciente que o IP de acesso do mesmo será restaurado para 192.168.1.1.</p>	
					<p style="font-size: 14px;color:red;">OBS: Caso o equipamento já esteja corretamente configurado com senha apropriada ao Adminsitrador principal do equipamento ou mesmo que este equipamento já esteja em correto funcionamento a mais tempo, <a onclick="showButtonStableImplementation()" style="color: blue;text-decoration: underline;">clique aqui </a>para habilitar o botão de "gerar arquivo de implementação estável", que não trará mudanças ao sistema, somente altera-ra o estado de "Finalizar implementação" para "Renovar senha de Administrador".</p>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
					<form action="#" method="POST" style="margin: 0px;border: 0px;">
						<button type="submit" class="btn btn-primary" id="createImplementationFile" name="createImplementationFile">Continuar</button>
					</form>
					<form action="#" method="POST" id="showButtonStableImplementation" style="margin: 0px;border: 0px;display: none;">
						<button type="submit" class="btn btn-warning" id="createStableImplementationFile" name="createStableImplementationFile">Criar arquivo de implementação estável</button>
					</form>
				</div>
				<script>
				function showButtonStableImplementation() {
					if (document.getElementById("showButtonStableImplementation").style.display == "none") {
						document.getElementById("showButtonStableImplementation").style.display="block";
					} else {
						document.getElementById("showButtonStableImplementation").style.display="none";
					}
				}
				</script>
			</div>
		</div>
	</div>


	<script>
	
	var btn_implementacao = document.createElement("button");
	btn_implementacao.className = 'btn btn-primary';
	btn_implementacao.innerHTML = '<i class="fa fa-pencil"></i> Finalizar implementação';
	btn_implementacao.id ='modalImplementationFile';
	btn_implementacao.name ='modalImplementationFile';
	document.getElementsByClassName("col-sm-12 form-button text-center mt-5")[0].append(btn_implementacao);


	document.getElementById("modalImplementationFile").addEventListener("click", function(event){
		event.preventDefault();		
		$('#modal_implementacao').modal('show');
	});

	</script>

<?php endif; ?>


<div class="modal fade" id="modal_rebase" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h4 style="color:red;"> !!! Atenção !!!</h4>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body my-5">
				<p style="font-size: 14px;">Esteja ciente que está ação irá reinstalar a versão base do UTM-6.</p>
				</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
				<form action="#" method="POST" style="margin: 0px;border: 0px;">
					<button type="submit" class="btn btn-primary" id="reaplicarBase" name="reaplicarBase">Continuar</button>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
	
var btn_reaplicar = document.createElement("button");
btn_reaplicar.className = 'btn btn-primary';
btn_reaplicar.innerHTML = '<i class="fa fa-pencil "></i> Re-Aplicar Base';
btn_reaplicar.id ='modalReAplicar';
btn_reaplicar.name ='modalReAplicar';
document.getElementsByClassName("col-sm-12 form-button text-center mt-5")[0].append(btn_reaplicar);
document.getElementById("modalReAplicar").addEventListener("click", function(event){
	event.preventDefault();		
	$('#modal_rebase').modal('show');	
});

</script>

<?php if (file_exists("/etc/newVersionUTM6")): ?>

	<div class="modal fade" id="modal_first_use_6" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
		<div class="modal-dialog modal-lg" role="document">
			<div class="modal-content">
				<div class="modal-header">
					<h4 style="color:red;"> !!! Atenção !!!</h4>
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>
				</div>
				<div class="modal-body my-5">
					<p style="font-size: 14px;">Esta ação irá apresentar novamente a tela de primeiro uso da versão BluePex NGFW UTM-6.</p>
					</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
					<form action="#" method="POST" style="margin: 0px;border: 0px;">
						<button type="submit" class="btn btn-primary" id="first_use_utm_6" name="first_use_utm_6">Continuar</button>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script>
		var btn_first_use_6 = document.createElement("button");
		btn_first_use_6.className = 'btn btn-primary';
		btn_first_use_6.innerHTML = '<i class="fa fa-pencil "></i> Re-Aplicar primeiro uso da versão 6';
		btn_first_use_6.id ='button_first_use_6';
		btn_first_use_6.name ='button_first_use_6';
		document.getElementsByClassName("col-sm-12 form-button text-center mt-5")[0].append(btn_first_use_6);
		document.getElementById("button_first_use_6").addEventListener("click", function(event){
			event.preventDefault();		
			$('#modal_first_use_6').modal('show');	
		});
	</script>
<?php endif; ?>
