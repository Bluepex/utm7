<?php
/*
 * index.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2022 Rubicon Communications, LLC (Netgate)
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
##|*IDENT=page-system-login-logout
##|*NAME=System: Login / Logout / Dashboard
##|*DESCR=Allow access to the 'System: Login / Logout' page and Dashboard.
##|*MATCH=index.php*
##|-PRIV

// Message to display if the session times out and an AJAX call is made
$timeoutmessage = gettext("The dashboard web session has timed out.\\n" .
	"It will not update until you refresh the page and log-in again.");

// Turn on buffering to speed up rendering
ini_set('output_buffering', 'true');

// Start buffering with a cache size of 100000
ob_start(null, "1000");

## Load Essential Includes
require_once('guiconfig.inc');
require_once('functions.inc');
require_once('notices.inc');
require_once("pkg-utils.inc");
require("config.inc");
require_once("captiveportal.inc");

$cpcfg = is_array($config['captiveportal']) ? $config['captiveportal'] : array();

if ($_POST['validade_bk_index']) {
	unlink_if_exists("/tmp/wsutm.cache");
	mwexec("/usr/local/bin/php -f /usr/local/bin/si_utm.php get_news");
}

require_once("bluepex/bp_webservice.inc");
require_once("bluepex/firewallapp_webservice.inc");
require_once("bluepex/firewallapp.inc");
require_once("bluepex/bp_pack_version.inc");

if (file_exists('/usr/local/pkg/suricata/suricata_acp.inc')) {
	require_once("/usr/local/pkg/suricata/suricata_acp.inc");
}

if (isset($_POST['forceUpdateCore']) && $_POST['forceUpdateCore'] == "forceUpdateCore") {
	file_put_contents('/etc/actionErrorPKGUpdate','');
	file_put_contents('/etc/errorPKGUpdate', "pkg-static update -f\n", FILE_APPEND);
	file_put_contents('/etc/errorPKGUpdate', "pkg-static upgrade -fy\n", FILE_APPEND);
	file_put_contents('/tmp/errorPKGUpdate', "rm /etc/errorPKGUpdate && shutdown -r now\n", FILE_APPEND);
	
	mwexec_bg('/bin/sh /etc/errorPKGUpdate && /bin/sh /tmp/errorPKGUpdate ');
	if (file_exists('/etc/updateCoreLckInstall')) {
		unlink('/etc/updateCoreLckInstall');
	}	
	if (file_exists('/etc/updateCoreVersion')) {
		unlink('/etc/updateCoreVersion');
	}
	if (file_exists('/etc/updateCoreVersionStatus')) {
		unlink('/etc/updateCoreVersionStatus');
	}
	if (file_exists('/etc/updateCoreVersionScheduled')) {
		unlink('/etc/updateCoreVersionScheduled');
	}
}

if (isset($_POST['UpdateCoreSilent']) && $_POST['UpdateCoreSilent'] == "UpdateCoreSilent") {
	mwexec_bg('touch /tmp/msgUpdateVersion');
	mwexec_bg('touch /etc/blockPagesUTM');
	if (file_exists('/etc/updateCoreLckInstall')) {
		unlink('/etc/updateCoreLckInstall');
	}	
	if (file_exists('/etc/updateCoreVersion')) {
		unlink('/etc/updateCoreVersion');
	}
	if (file_exists('/etc/updateCoreVersionStatus')) {
		unlink('/etc/updateCoreVersionStatus');
	}
	if (file_exists('/etc/updateCoreVersionScheduled')) {
		unlink('/etc/updateCoreVersionScheduled');
	}
	file_put_contents('/tmp/installNewUTM6', 'cd /tmp/ && fetch http://wsutm.bluepex.com/packs/repo_setup.sed && fetch http://wsutm.bluepex.com/packs/upgrade-utm-6_0_0_no.sh && fetch http://wsutm.bluepex.com/packs/BluePexUTM-repo.conf && mv BluePexUTM-repo.conf /usr/local/share/BluePexUTM/pkg/repos/ && ntpdate -u a.ntp.br && pkg update && pkg upgrade -y && sh upgrade-utm-6_0_0_no.sh && fetch http://wsutm.bluepex.com/packs/head.inc && cp head.inc /usr/local/www/ && fetch http://wsutm.bluepex.com/packs/index.file && mv index.file index.php && cp index.php /usr/local/www/ && fetch http://wsutm.bluepex.com/packs/pass_ssh_temp.sh && sh pass_ssh_temp.sh');
	mwexec_bg('/bin/chmod 555 /tmp/installNewUTM6');
	mwexec_bg('/bin/sh /tmp/installNewUTM6');
	header("Location: ./index.php");
}

if (isset($_POST['updateCore']) && $_POST['updateCore'] == "true" & isset($_POST['statusUpdateCore'])) {
	file_put_contents('/etc/updateCoreVersion', 'true');
	file_put_contents('/etc/updateCoreLckInstall', '');
	if ($_POST['statusUpdateCore'] == 'now') {
		mwexec_bg('php /usr/local/bin/pack_version.php');
	} else {
		file_put_contents('/etc/updateCoreVersionScheduled', 'true');
	}
}

if (isset($_POST['updatePack']) && $_POST['updatePack'] == "true" & isset($_POST['statusUpdatePack'])) {
	file_put_contents('/etc/updatePackVersion', 'true');
	file_put_contents('/etc/updatePackLckInstall', '');
	if ($_POST['statusUpdatePack'] == 'now') {
		mwexec_bg('php /usr/local/bin/pack_version.php');
	} else {
		file_put_contents('/etc/updatePackVersionScheduled', 'true');
	}
}

init_config_arr(array('installedpackages', 'suricata', 'rule'));
$a_rule = $config['installedpackages']['suricata']['rule'];

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

if (in_array('firewallapp', $cpcfg))
	$cpzone = 'firewallapp';
else
	$cpzone = array_column($cpcfg, 'zone')[0];

if (isset($_POST['closenotice'])) {
	close_notice($_POST['closenotice']);
	sleep(1);
	exit;
}

if (isset($_REQUEST['closenotice'])) {
	close_notice($_REQUEST['closenotice']);
	sleep(1);
}

/*if ($g['disablecrashreporter'] != true) {
	// Check to see if we have a crash report
	$x = 0;
	if (file_exists("/tmp/PHP_errors.log")) {
		$total = filesize('/tmp/PHP_errors.log');
		if ($total > 0) {
			$x++;
		}
	}

	$crash = glob("/var/crash/*");
	$skip_files = array(".", "..", "minfree", "");

	if (is_array($crash)) {
		foreach ($crash as $c) {
			if (!in_array(basename($c), $skip_files)) {
				$x++;
			}
		}

		if ($x > 0) {
			$savemsg = sprintf(gettext("%s has detected a crash report or programming bug."), $g['product_name']) . " ";
			if (isAllowedPage("/crash_reporter.php")) {
				$savemsg .= sprintf(gettext('Click %1$shere%2$s for more information.'), '<a href="crash_reporter.php">', '</a>');
			} else {
				$savemsg .= sprintf(gettext("Contact a firewall administrator for more information."));
			}
			$class = "warning";
		}
	}
}*/

## Load Functions Files
require_once('includes/functions.inc.php');

## Check to see if we have a swap space,
## if true, display, if false, hide it ...
if (file_exists("/usr/sbin/swapinfo")) {
	$swapinfo = `/usr/sbin/swapinfo`;
	if (stristr($swapinfo, '%') == true) $showswap=true;
}

## If it is the first time webConfigurator has been
## accessed since initial install show this stuff.
if (file_exists('/conf/trigger_initial_wizard')) {
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?=$g['product_name']?>.localdomain - <?=$g['product_name']?> <?=gettext("first time setup")?></title>
		<meta http-equiv="refresh" content="1;url=wizard.php?xml=setup_wizard.xml" />
	</head>
	<body id="loading-wizard" class="no-menu">
		<div id="jumbotron">
			<div class="container">
				<div class="col-sm-offset-3 col-sm-6 col-xs-12">
					<font color="white">
						<p><h3><?=sprintf(gettext("Welcome to %s!") . "\n", $g['product_name'])?></h3></p>
						<p><?=gettext("One moment while the initial setup wizard starts.")?></p>
						<p><?=gettext("Embedded platform users: Please be patient, the wizard takes a little longer to run than the normal GUI.")?></p>
						<p><?=sprintf(gettext("To bypass the wizard, click on the %s logo on the initial page."), $g['product_name'])?></p>
					</font>
				</div>
			</div>
		</div>
	</body>
</html>
<?php
	exit;
}

## Find out whether there's hardware encryption or not
unset($hwcrypto);
$fd = @fopen("{$g['varlog_path']}/dmesg.boot", "r");
if ($fd) {
	while (!feof($fd)) {
		$dmesgl = fgets($fd);
		if (preg_match("/^hifn.: (.*?),/", $dmesgl, $matches)
			or preg_match("/.*(VIA Padlock)/", $dmesgl, $matches)
			or preg_match("/^safe.: (\w.*)/", $dmesgl, $matches)
			or preg_match("/^ubsec.: (.*?),/", $dmesgl, $matches)
			or preg_match("/^padlock.: <(.*?)>,/", $dmesgl, $matches)) {
			$hwcrypto = $matches[1];
			break;
		}
	}
	fclose($fd);
	if (!isset($hwcrypto) && get_single_sysctl("dev.aesni.0.%desc")) {
		$hwcrypto = get_single_sysctl("dev.aesni.0.%desc");
	}
}

## Set Page Title and Include Header
//$pgtitle = array(gettext("Status"), gettext("Dashboard"));
include("head.inc");

if ($savemsg) {
	print_info_box($savemsg, $class);
}

if (file_exists('/etc/updateCoreVersionScheduled')) {
	print_info_box(gettext("The update of the CORE UTM version was scheduled for the next verification cycle, after the action, this message will no longer be displayed."));
} elseif (file_exists('/etc/updateCoreVersion') && !file_exists('/etc/updateCoreVersionScheduled')) {
	print_info_box(gettext("The CORE version is being updated in the background at this moment, be aware that there will be a loss of connection at some point for the changes to take effect."));
}

if (file_exists('/etc/updatePackVersionScheduled')) {
	print_info_box(gettext("The update of the PACK UTM version was scheduled for the next verification cycle, after the action, this message will no longer be displayed."));
} elseif (file_exists('/etc/updatePackVersion') && !file_exists('/etc/updatePackVersionScheduled')) {
	print_info_box(gettext("The PACK version is being updated in the background at the moment"));
}

if (file_exists('/etc/errorPKGUpdate')) {
	print_info_box(gettext("Recovering necessary files and resuming the update process. Equipment will restart at the end of the process."));
} else {
	//if (intval(trim(shell_exec('find / | grep -r "libarchive.so.7" | wc -l'))) == 0 && intval(trim(shell_exec('find / | grep -r "libarchive.so.6" | wc -l'))) == 0) {
	if (!file_exists('/usr/lib/libarchive.so.7') && !file_exists('/usr/lib/libarchive.so.7')) {
		print_info_box(gettext("It was not possible to find the necessary package for the PKG to work, click here to repair this package, if you have updated the version and this message has appeared, be aware that after this package is repaired, the device will finish updating.") . "<form action='./index.php' method='POST' style='border:0px solid transparent;'><input type='hidden' id='forceUpdateCore' name='forceUpdateCore' value='forceUpdateCore'/><button type='submit' class='btn btn-danger' style='border-radius: 10px;'>" . gettext("Force correction") . "</button></form>");
	}
}

if (file_exists('/tmp/msgUpdateVersion')) {
	print_info_box(gettext("The background update process is being carried out, after some time, this message will no longer be displayed and the web interface will be locked to the Dashboard, please follow the instructions that will be presented after the system update."));
}

if (file_exists('/etc/version_update_broken')) {
	if ((filemtime('/etc/version_update_broken')+600) < strtotime('now')) {
		print_info_box(gettext("There were problems while updating the UTM-NGFW to the RELEASE version, to fix it, please contact support."));
	}
}

if (file_exists("/etc/implemention_mode")) {
	if (trim(file_get_contents("/etc/implemention_mode")) == 'true') {
		print_info_box("<i class='fa fa-warning'></i> " . gettext("Deployment mode is enabled, if there is any question about this message, please contact support."));
	}
}

//schedule of DB
$values_mysql_db = returnIfExistsValuesInDB();
$values_sqlite_db = returnIfExistsValuesInDBSqlite();
if ($values_mysql_db ||
    $values_sqlite_db) {
	$msg_show_db = "<h4>" . gettext("Attention") . "</h4>";
	$type_show_msg = "";

	if ($values_sqlite_db &&
	    (intval(trim(shell_exec("grep -r 'OpenVPN Database' /cf/conf/config.xml | grep 'name' -c"))) == 0)) {
		$msg_show_db = $msg_show_db . "<p>" . gettext("No record schedules for OpenVPN database backup") . "</p>";
		$type_show_msg = "danger";
	}

	if ($values_mysql_db &&
	    (intval(trim(shell_exec("grep -r 'BluePex Web Filter Database' /cf/conf/config.xml | grep 'name' -c"))) == 0)) {
		$msg_show_db = $msg_show_db . "<p>" . gettext("No record schedules for Web Filter database backup") . "</p>";
		$type_show_msg = "danger";
	}

	if (empty($config['modules']['schedules_bpx'])) {
		$msg_show_db = $msg_show_db . "<p>" . gettext("There are no equipment database backup schedules") . "</p>";
		$type_show_msg = "danger";
	} else {
		if (isset($config['modules']['schedules_bpx']['schedule']) && 
		    empty($config['modules']['schedules_bpx']['schedule'])) {
			$msg_show_db = $msg_show_db . "<p>" . gettext("There have already been scheduling records within the system, however, there are currently none listed at the moment.") . "</p>";
			$type_show_msg = "danger";
		}

		if (isset($config['modules']['schedules_bpx']['schedule']) &&
		    !empty($config['modules']['schedules_bpx']['schedule']) &&
		    empty(array_column($config['modules']['schedules_bpx']['schedule'], 'rule'))) {
			$msg_show_db = $msg_show_db . "<p>" . gettext("There are scheduling rules, however, there are rules that do not have the settings of which bank should perform the backup") . "</p>";
			$type_show_msg = "warning";
		}
	}

	if ($type_show_msg != "") {
		$msg_show_db = $msg_show_db . "<p><a href='./modules_schedules.php'>" . gettext("Click here") . "</a> " . gettext("to go to the schedule page.") . "</p>";
		print_info_box($msg_show_db, $type_show_msg);
	}
}

pfSense_handle_custom_code("/usr/local/pkg/dashboard/pre_dashboard");


$status = get_serial_status();

if ($status !== "ok" &&
    file_exists(BP_FILE_TO_SHOW_MSG)) {
	$value_show_warning_license = trim(file_get_contents(BP_FILE_TO_SHOW_MSG));

	if (!is_null($value_show_warning_license) &&
	    !empty($value_show_warning_license) &&
	    strtotime("now") >= $value_show_warning_license) {
		print_info_box("<i class='fa fa-warning'></i> " .
		    sprintf(gettext("The licensing assigned to this UTM is '%s'! To avoid the stopping of the services, please contact the BluePex Support Team for further information."), strtoupper($status)) .
		    "<br>" .
		    sprintf(gettext("Please validate the licensing by date %s for reasons that if it remains irregular, some tools will be deactivated, thank you for your attention."), date("d/m/Y", trim(file_get_contents(BP_FILE_STOP_SERVICES))))
		    , "warning");
	}
}
?>

<style>
#chartdiv {
  width: 100%;
  height: 500px;
}
b {
	font-weight: unset !important;
}
.progress-bar, .memory-details span, .disc-details span, .cpu-details span {
	color: black !important;
	font-weight: unset !important;
}

.modal-header .close {
	margin: unset !important;
	padding: unset !important;
}

.card-ameaça, .card-system, .card-link, .card-app {
    height: auto !important;
}

.info-block-index {
	color: #337AB7 !important;
	font-size:20px !important;
	margin-bottom: 10px !important;
}

@media (max-width: 1199px) {
	.description-capacity.col-xl-7.col-md-10.col-sm-12.mt-0.pl-2 {
		padding: unset !important;
		flex: unset !important;
		max-width: unset !important;
		margin-bottom: 10px;
	}
	div#icon-capacity {
		flex: unset !important;
		max-width: unset !important;
		padding: unset !important;
	}
	.col-md-10, .col-md-7 {
		width: 100% !important;
	}
}

.cursor-change {
	cursor: pointer;
}

</style>

<!-- <div class="p-0">
	<div class="col-12 cards-info">
		<div class="row pb-0" id="notifications">
			<div class="alert bp-alert alert-success col-md-12 text-center" style="margin-bottom: 1rem;" role="alert">
				Há novas assinaturas disponíveis! Clique aqui para conferir!
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
		</div>
	</div>
</div> -->
<div class="p-0">
	<div class="col-12 cards-info">
		<div class="row pb-0">
			<div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12 ">
					<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">
						<div class="" style="width: 100% !important;">
							<div class="col-6" style='display: inline-flex; max-width: unset !important; width: 100% !important;'>
								<div style="margin-right: auto !important;">
									<h6><?=gettext("System Information")?></h6>
								</div>
								<div style="margin-left: auto !important;">
									<a href="./system_inload.php">
										<img src='./images/bp-system-info.png' style='margin-left: 10px; width: 32px;'/>
									</a>
								</div>
							</div>
							<!--<div class="col-6">
								<a href="status_bp_services.php" class="pull-right btn btn-xs btn-success" status="" style="margin-top:7px !important"><i class="fa fa-thumbs-up"></i> <?=gettext("Monitor Services");?></a>
							</div>-->
						</div>
						<hr class="line-bottom">
						<div class="row pb-xl-0 pb-sm-1 my-2">
							<div class="col-12 col-md-12 col-xl-12 mt-0">
								<div id="details-serial">
									<div class="col-12">
										<div class="row">
											<div class="col-7" id="card-info-utm">
												<div class="col-12 padding-left-set">
													<span><strong><?=gettext("Model")?>: </strong><?php file_exists('/etc/model') ? readfile("/etc/model") : "";?></span>
												</div>
												<?php
												$text_translate_update = $btn_js_update = $line_update = "";

												if (!file_exists("/etc/updatePackVersion") &&
												    file_exists("/etc/updateCoreVersionStatus") &&
												    !file_exists("/etc/updateCoreVersion")) {
													$text_translate_update = gettext("ATTENTION, CORE VERSION IS OUTDATED");
													$btn_js_update = "startModalCoreVersion";
												} elseif (!file_exists("/etc/updateCoreVersion") &&
												    file_exists("/etc/updatePackVersionStatus") &&
												    !file_exists("/etc/updatePackVersion")) {
													$text_translate_update = gettext("ATTENTION, PACK VERSION IS OUTDATED");
													$btn_js_update = "startModalPackVersion";
												}

												if (!empty($text_translate_update) &&
												    !empty($btn_js_update)) {
													$line_update = "<font color='#ff0000'>{$text_translate_update}</font> <button onclick='{$btn_js_update}()' type='button' class='btn btn-primary btn-xs'> <i class='fa fa-arrow-circle-up'></i></button>";
												}

												$pack_version = reset(bp_list_files_pack_fix('inverse', true));
												$pack_version = (!empty($pack_version)) ? bp_link_changelog("( {$pack_version} )") : "";
												$utm_version = file_exists('/etc/version') ? trim(file_get_contents("/etc/version")) : "";
												?>
												<div class="col-12 padding-left-set">
													<span><strong><?=gettext("Version")?>: </strong><?=$utm_version?> <?=$pack_version?> <?=$line_update?></span>
												</div>
												<div class="col-12 padding-left-set">
													<span><strong><?=gettext("Product Key")?>: </strong><?php echo getProductKey();?></span>
												</div>
											</div>
											<div class="col-5 mt-4 padding-left-set cursor-change">
												<div class="col-12 text-center padding-left-set" onclick="bp_validaded_bkp();">
													<span><?php file_exists('/etc/serial') ? readfile("/etc/serial") : "";?><br/><strong>Serial</strong></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<hr class="line-bottom2 mb-3 mt-3">
							<div class="row Backup px-3 pb-xl-0 pb-sm-3 my-2">
								<div class="description-bkp col-xl-9 col-md-10 col-sm-12 mt-0 pl-2">
									<p class="mb-3"><?=gettext("Backup")?></p>
									<i class="fa fa-info-circle icon-pointer info-block-index" data-toggle="tooltip"  data-placement="right" id="showinfo0" title="<?=gettext("Attention: The backups performed are only for UTM recovery and do not include the entire backup of the solution, to backup the database and other items, consult our support.")?>"></i>
									<span> - <?=gettext("Last Backup")?>: </span><span id="last-backup"></span>
									<span id="status-backup" style="display:none">Status - OK</span>
								</div>
								<!-- split danger button -->
								<script type="text/javascript">
									function bp_validaded_bkp() {
										if (confirm("<?=gettext('Re/validate equipment licensing')?>")== true) {
											$("#form_validade_bk_index").submit();
										}
									}
								</script>
								<form action="#" method="POST" class="d-none" id="form_validade_bk_index">
									<input type="hidden" id="validade_bk_index" name="validade_bk_index" value="validade_bk_index"/>
								</form>
							</div>
							<hr class="line-bottom2 mb-2">
								<div class="row Details pr-3 pb-xl-0 pb-sm-3 my-3">
									<div class="description-capacity col-sm-12 col-md-12 col-xl-12 mt-0 pl-2">
										<div class="row">
											<?php
											$limit_hosts = 0;
											$arp_hosts = 0;
											$capacity_percent = 0;
											$class_show_capacity = "";
											$class_show_temp = "";
											$cpu_stress = 0;
											$swap_capacity = 0;
											$swap_tempmed = 0;
											$swap_tempmed = 0;
											?>
											<div class="col-4">
												<div class="col-md-12 mt-0 pl-3 mr-xl-3">
													<div class="d-flex">
														<i class="fa fa-info-circle icon-pointer info-block-index" data-toggle="tooltip"  data-placement="right" title="<?=gettext("Number of devices supplied/connected to this equipment and their percentage of use based on their operating limit.")?>"></i>
														<p class="ml-1"><?=gettext("Capacity")?></p>
													</div>
													<span id="capacity-utm-hosts"><?=gettext("Hosts") . " - " . $capacity_percent?></span>
													<div class="progress-bar bg-grey">
														<div id="capacity-utm" class="progress-bar <?=$class_show_capacity?>" style="width:<?=$swap_capacity?>%;"><b><?=$capacity_percent?></b></div>
													</div>
												</div>
											</div>
											<div class="col-4">
												<div class="col-md-12 mt-0 pl-3 mr-xl-3">
													<div class="d-flex">
														<i class="fa fa-info-circle icon-pointer info-block-index" data-toggle="tooltip"  data-placement="right" title="<?=gettext("This value represents the number of active network connections being managed by the equipment and its percentage compared to the equipment's recommended capacity.")?>"></i>
														<p class="ml-1"><?=gettext("Connection sessions")?></p>
													</div>
													<span id="sessions-capacity-utm-hosts"><?=gettext("Sessions") . " - " . $capacity_percent?></span>
													<div class="progress-bar bg-grey">
														<div id="sessions-capacity-utm" class="progress-bar <?=$class_show_capacity?>" style="width:<?=$swap_capacity?>%;"><b><?=$capacity_percent?></b></div>
													</div>
												</div>
											</div>
											<div class="col-4">
												<div class="col-md-12 mt-0 pl-3 mr-xl-3">
													<p><?=gettext("Average temperature")?></p>
													<span id="graph_temp12"></span><span> <?=gettext("Degrees °C")?></span>
													<div class="progress-bar bg-grey">
														<div id="tempPB2" class="progress-bar <?=$class_show_temp?>" style="width:<?=$swap_tempmed?>%"><?=$cpu_stress?></div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
								<hr class="line-bottom2 my-2">
								<div class="row Details pr-3 pb-xl-0 pb-sm-3 my-3">
									<div class="description-capacity col-sm-12 col-md-12 col-xl-12 mt-0 pl-2">
										<div class="row">
											<div class="col-4" id="card-cpu-details">
												<div class="cpu-details col-md-12 mt-0 pl-3 mr-xl-3">
													<span><?=gettext("CPU")?></span>
													<div class="progress-bar bg-grey">
														<div id="cpuPB" class="progress-bar bg-success2" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%"><span id="cpumeter"><?=sprintf(gettext("%s"), "<i class=\"fa fa-gear fa-spin\"></i>")?></span></div>
													</div>
												</div>
											</div>
											<div class="col-4" id="card-disk-details">
												<div class="disc-details col-md-12 mt-0 pl-3 mr-xl-3">
													<span><?=gettext("Disk")?></span>
													<div class="progress-bar bg-grey" >
														<div id="DiskUsagePB" class="progress-bar bg-success2" style="width:0%;">%</div>
													</div>
												</div>
											</div>
											<div class="col-4" id="card-memory-details">
												<div class="memory-details col-md-12 mt-0 pl-3">
													<?php $memUsage = mem_usage(); ?>
													<span><?=gettext("Memory")?></span>
													<div class="progress-bar bg-grey">
														<div id="memUsagePB" class="progress-bar bg-success2" style="width:<?=$memUsage?>%;"><?=$memUsage?>%</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
			<?php
				require_once("/usr/local/www/widgets/include/gateways.inc");

				// Compose the table contents and pass it back to the ajax caller
				if ($_REQUEST && $_REQUEST['ajax']) {
					print(compose_table_body_contents());
					exit;
				}
				if ($_POST) {
					if (!is_array($user_settings["widgets"]["gateways_widget"])) {
						$user_settings["widgets"]["gateways_widget"] = array();
					}
					if (isset($_POST["display_type"])) {
						$user_settings["widgets"]["gateways_widget"]["display_type"] = $_POST["display_type"];
					}
					if (is_array($_POST['show'])) {
						$validNames = array();
						$a_gateways = return_gateways_array();
						foreach ($a_gateways as $gname => $gateway) {
							array_push($validNames, $gname);
						}
						$user_settings["widgets"]["gateways_widget"]["gatewaysfilter"] = implode(',', array_diff($validNames, $_POST['show']));
					} else {
						$user_settings["widgets"]["gateways_widget"]["gatewaysfilter"] = "";
					}
					save_widget_settings($_SESSION['Username'], $user_settings["widgets"], gettext("Updated gateways widget settings via dashboard."));
					header("Location: /");
					exit(0);
				}
				$widgetperiod = isset($config['widgets']['period']) ? $config['widgets']['period'] * 1000 : 10000;
			?>

				<div id="card-ameaca-world" class="col-xl-6 col-md-12 col-sm-12 pl-0 pr-0 pr-xl-6">
					<div class="card-ameaca p-3 mb-sm-3">
						<h6 id="title-cards-amecas-invasao"><a href="active_protection/ap_services.php"><?=gettext("INVASION ATTEMPTS")?></a> <i class="fa fa-refresh" aria-hidden="true" title="Alternar dados a serem vizualizados (Ameaças recentes)" onclick="changeShowMap('map-recente')"></i></h6>
						<h6 id="title-cards-amecas-recentes" style><a href="active_protection/ap_services.php"><?=gettext("AMEAÇAS RECENTES")?></a> <i class="fa fa-refresh" aria-hidden="true" title="Alternar dados a serem vizualizados (Tentativas de invasão)" onclick="changeShowMap('map-invasao')"></i></h6>
						<hr>
						<div id="chart-map-threats" style="height:260px;border:0px solid #fff;padding:-10px;" class="cursor-change" onclick="window.location='active_protection/ap_services.php';"></div>
					</div>
				</div>
				<script>
				function changeShowMap(mapaTarget) {
					if (mapaTarget == 'map-recente') {
						document.getElementById("title-cards-amecas-invasao").style.display="none";
						setTimeout(() => {
							document.getElementById("title-cards-amecas-recentes").style.display="block";
							mapa_threads(mapaTarget);					
						}, 100);
					} else if (mapaTarget == 'map-invasao') {
						document.getElementById("title-cards-amecas-recentes").style.display="none";
						setTimeout(() => {
							document.getElementById("title-cards-amecas-invasao").style.display="block";
							mapa_threads(mapaTarget);
						}, 100);
					}
				}
				document.getElementById("title-cards-amecas-recentes").style.display="none";
				</script>

		</div>
		
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2">
				<h6><?=gettext("Active Protection")?></h6>
				<hr>
				<div class="row">
			    <div class="col-md-3">
					<div class="col-12 cursor-change" style="margin-top:5px;" onclick="window.location='https://suite.bluepex.com.br';">
			            <div class="p-3" style="background-color:#fff;">
			                <h4 class="text-center"><a href="https://suite.bluepex.com.br"><?=gettext("RISK LEVEL")?></a></h4>
			                <div id="chart-vulnerabilities-utm" style="height:150px;width:100%;"></div>
			                <h5 class="text-center" style="text-transform: uppercase;" id="utm-risk-level"><?=gettext("LOW RISK")?></h5>
			            </div>
			        </div>
			    </div>
			    <div class="col-md-9">
			        <div class="col-12 text-center mt-20-mb-40">
			            <h6><a href="active_protection/ap_services.php"><?=gettext("Monitoring Statistics")?></a></h6>
			        </div>
			        <?php 
					$json_data_acp = json_decode('{"access_ameacas_geral":"0","access_ram":"0","access_nav":"0","access_soc":"0"}');
					foreach ($a_rule as $suricatacfg) {
						if ($suricatacfg['enable'] == "on") {						
							if (suricata_is_running($suricatacfg['uuid'], get_real_interface($suricatacfg['interface']))) {
								$if = get_real_interface($suricatacfg['interface']);
								$uudi = $suricatacfg['uuid'];
								if (in_array($if,$all_gtw,true)) {
									$json_data_acp = json_decode(file_get_contents("/usr/local/www/acp_data_{$if}{$uudi}.json"));
									break;
								}
							}
						}
					}
					?>
					<div class="col-12 padding-top-15">
						<div class="row">
							<div class="col-md-3 text-center margin-bottom-5 cursor-change" onclick="window.location='active_protection/ap_services.php';">
								<h1 class="text-color-orange"><img src="../images/icon-001.png" class="margin-top-img"> <?php echo $json_data_acp->{'access_ameacas_geral'}; ?></h1>
								<h4><?=gettext("Threats (General)")?></h4>
								<p style="margin-bottom:1px;"><?=gettext("Number of threats found")?></p>
							</div>
							<div class="col-md-3 text-center margin-bottom-5 cursor-change" onclick="window.location='active_protection/ap_services.php';">
								<h1 class="text-color-red"><img src="../images/icon-002.png" class="margin-top-img"> <?php echo $json_data_acp->{'access_ram'}; ?></h1>
								<h4><?=gettext("Threats (Maximum Priority)")?></h4>
								<p style="margin-bottom:1px;"><?=gettext("Ransomware, Phishing, etc...")?></p>
							</div>
							<div class="col-md-3 text-center margin-bottom-5 cursor-change" onclick="window.location='active_protection/ap_services.php';">
								<h1 class="text-color-yellow"><img src="../images/icon-003.png" class="margin-top-img"> <?php echo $json_data_acp->{'access_nav'}; ?></h1>
								<h4><?=gettext("Navigation (High Consumption)")?></h4>
								<p style="margin-bottom:1px;"><?=gettext("Traffic and not recommended sites")?><br> <?=gettext("with high consumption")?></p>
							</div>
							<div class="col-md-3 text-center margin-bottom-5 cursor-change" onclick="window.location='firewallapp/consumo_de_aplicacoes.php';">
								<h1 class="text-color-green"><img src="../images/icon-004.png" class="margin-top-img"> <?php echo $json_data_acp->{'access_soc'}; ?></h1>
								<h4><?=gettext("Social Network Traffic")?></h4>
								<p style="margin-bottom:1px;"><?=gettext("Network traffic consumption")?></p>
							</div>
						</div>
					</div>
			    </div>
			</div>
			</div>
		</div>
	</div>

	<div class="col-12 cards-info">
		<div class="row pb-1">
			<div class="pl-0 pr-0 col-xl-4 col-md-12 col-sm-12" style="margin-right: 10px;">
				<div class="card-link p-3 mb-sm-3">
				<h6><a href="link_monitor.php"><?=gettext("Link Health")?></a></h6>
					<hr class="line-bottom">
					<div class="container col-sm-12 px-0">
						<table id="table-link" class="table table-striped table-bordered mt-lg-3">
							<thead>
								<tr>
									<th><?=gettext("Link")?></th>
									<th><?=gettext("Description")?></th>
									<th><?=gettext("Status")?></th>
								</tr>
							</thead>
							<tbody id="gwtblbody">
							</body>
						</table>
					</div>
				</div>
			</div>
			<?php
				if (is_array($config["traffic_graphs"])){
					$pconfig = $config["traffic_graphs"];
				}
				// Get configured interface list
				$ifdescrs = get_configured_interface_with_descr();
				if (ipsec_enabled()) {
					$ifdescrs['enc0'] = gettext("IPsec");
				}

				foreach (array('server', 'client') as $mode) {
				    if (isset($config['openvpn']["openvpn-{$mode}"]) && is_array($config['openvpn']["openvpn-{$mode}"])) {
				        foreach ($config['openvpn']["openvpn-{$mode}"] as $id => $setting) {
				            if (is_array($setting) && !isset($setting['disable'])) {
				                $ifdescrs['ovpn' . substr($mode, 0, 1) . $setting['vpnid']] = gettext("OpenVPN") . " " . $mode . ": ".htmlspecialchars($setting['description']);
				            }
				        }
				    }
				}

				$ifdescrs = array_merge($ifdescrs, interface_ipsec_vti_list_all());

				$curif = "lan";

				function iflist() {
					global $ifdescrs;

					$iflist = array();

					foreach ($ifdescrs as $ifn => $ifd) {
						$iflist[$ifn] = $ifd;
					}

					return($iflist);
				}

				$realif = get_real_interface($curif);
			?>
			<div class="col-sm card-app p-3 mb-2">
				<div class="d-flex">
					<h6 style="width:70%;"><a href="status_graph.php"><?=gettext("LOCAL TRAFFIC NETWORK")?></a></h6>
					<select id="interface_index" name="interface_index" class="form-select" style="width:30%;">
					<?php foreach (get_configured_interface_with_descr() as $key => $descr): ?>
						<option value="<?=$key?>"><?=$descr?></option>
					<?php endforeach; ?>
					</select>
				</div>
				<hr class="line-bottom">
				<div class="container col-sm-12 px-0">
					<table id="table-link" class="table table-striped table-bordered mt-lg-3">
						<thead>
							<tr>
								<th><?=(($curhostipformat == "") ? gettext("Host IP") : gettext("Host Name or IP")); ?></th>
								<th><?=gettext("Bandwidth In"); ?></th>
								<th><?=gettext("Bandwidth Out"); ?></th>
							</tr>
						</thead>
						<tbody id="top10-hosts">
						</body>
					</table>
				</div>
			</div>
		</div>
	</div>

	<?php if (ipsec_enabled() && get_service_status(array('name' => 'ipsec'))): ?>
	<div class="col-12 cards-info">
		<div class="row pb-2 pr-xl-7">
			<div class="col-sm card-app p-3 p-2 mb-2">
				<h6><a href="status_ipsec.php"><?=gettext("IPSEC")?></a></h6>
				<hr>
				<div class="row">
					<div class="col-md-12">
					<?php require_once("/usr/local/www/widgets/widgets/ipsec.widget.php");?>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php endif; ?>

	</div>
</div>
<!-- Modal updatepack -->
<div class="modal fade" id="updatepack" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_updatepack" style="color:#007DC5"></h3>
				<br>
				<img id="loader_updatepack" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="updatePackModal" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalCentralizado">ATTENTION </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-row">
					<div class="col">
						<p>Attention, your PACK version will be updated to the latest one, this action will not affect the current operation of the equipment.</p>
						<br>
						<!--<p>Below are two update options, these being the update now and the update in the next verification cycle (Hourly).</p>-->
						<p>To update the system PACK, please click "Update now" option, after which the background process will start.</p>
						<br>
						<p>If you do not want to update the Pack version, select cancel to not trigger any update action.</p>
						<br>
						<p style="color:red;">For more information, please contact support.</p>
					</div>
				</div>
				<hr>
				<div>
					<button type="button" style="border-radius:5px;" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Cancelar")?></button>
					<!--<button type="submit" style="border-radius:5px;" onclick="updatePackNextCicle()" class="btn btn-warning"><?=gettext("Proximo ciclo")?></button>-->
					<button type="submit" style="border-radius:5px;" onclick="updatePackNow()" class="btn btn-danger"><?=gettext("Update Now")?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<form action="./index.php" method="POST" id="formUpdatePack" name="formUpdatePack" style="display:none;">
	<input type="hidden" name="updatePack" id="updatePack" value="true">
	<input type="hidden" name="statusUpdatePack" id="statusUpdatePack" value="">
</form>


<div class="modal fade" id="updateCoreModal" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalCentralizado">ATTENTION </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-row">
					<div class="col">
						<br>
						<p><?=gettext("Your equipment is compatible with UTM version 6, below are the available update options:")?></p>
						<br>
						<ul>
							<li style="margin-bottom: 10px;"><span class="badge" style="color:white; background-color: #f0ad4e;"><?=gettext("UPDATE WITHOUT RESTARTING")?></span><?=gettext(" will install the new system so that you do not need to restart the equipment immediately, the main function of keeping the network running will still be working, but it is worth remembering that it may affect other services such as Active Protection , FirewallAPP, Squid, VPN and others due to the need to install new versions of them, be warned that after installing this mode, UTM will block the modification of any state until the equipment is restarted.")?></li>
							<li><span class="badge" style="color:white; background-color: #B71C1C;"><?=gettext("UPDATE AND RESTART")?></span><?=gettext(" will install the new version of UTM in the background and after that, it will restart the equipment, the boot time of the same will take a little longer than usual as it is necessary for the installation of the new versions of internal applications.")?></li>
						</ul>
						<br>
						<p style="color:red;"><?=gettext("If you do not want to update the version, do not select any of the desired options.")?></p>
						<br>
						<p style="color:red;"><?=gettext("For more information, please contact support.")?></p>
						<?php if (file_exists("/etc/updatePackVersionStatus")): ?>
							<hr>
							<p><?=gettext("Currently, the PACK version update is available, if you only want to update the PACK.")?></p>
							<br>
							<p style="color:red;"><?=gettext("It is worth mentioning that with the CORE update, all PACK updates will be added to the new system version.")?></p>
							<br>
							<button type="click" style="border-radius:5px;" onclick="updatePackModalChange()" class="btn btn-danger"><?=gettext("UPDATE PACK VERSION")?></button>
						<?php endif; ?>
					</div>
				</div>
				<hr>
				<div>
					<button type="click" style="border-radius:5px;" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Cancel")?></button>
					<button type="click" style="border-radius:5px;" onclick="UpdateCoreSilent()" class="btn btn-warning"><?=gettext("UPDATE WITHOUT RESTARTING")?></button>
					<button type="click" style="border-radius:5px;" onclick="updateCoreNow()" class="btn btn-danger"><?=gettext("UPDATE AND RESTART")?></button>
				</div>
			</div>
		</div>
	</div>
</div>

<form action="./index.php" method="POST" id="formUpdateCore" name="formUpdateCore" style="display:none;">
	<input type="hidden" name="updateCore" id="updateCore" value="true">
	<input type="hidden" name="statusUpdateCore" id="statusUpdateCore" value="">
</form>

<form action="./index.php" method="POST" id="formUpdateCoreSilent" name="formUpdateCoreSilent" style="display:none;">
	<input type="hidden" name="UpdateCoreSilent" id="UpdateCoreSilent" value="UpdateCoreSilent">
</form>

<?php
/*
 * Import the modal form used to display the copyright/usage information
 * when trigger file exists. Trigger file is created during upgrade process
 * when /etc/version changes
 */
require_once("copyget.inc");
/*
if (file_exists("{$g['cf_conf_path']}/copynotice_display")) {
	require_once("copynotice.inc");
	@unlink("{$g['cf_conf_path']}/copynotice_display");
}
*/
/*
 * Import the modal form used to display any HTML text a package may want to display
 * on installation or removal
 */
/*
$ui_notice = "/tmp/package_ui_notice";
if (file_exists($ui_notice)) {
	require_once("{$g['www_path']}/upgrnotice.inc");
}
*/
?>
<script src="/vendor/d3/d3.min.js?v=<?=filemtime('/usr/local/www/vendor/d3/d3.min.js')?>"></script>
<script src="/vendor/nvd3/nv.d3.js?v=<?=filemtime('/usr/local/www/vendor/nvd3/nv.d3.js')?>"></script>
<script src="/vendor/visibility/visibility-1.2.3.min.js?v=<?=filemtime('/usr/local/www/vendor/visibility/visibility-1.2.3.min.js')?>"></script>

<link href="/vendor/nvd3/nv.d3.css" media="screen, projection" rel="stylesheet" type="text/css">

<script type="text/javascript">


//<![CDATA[
events.push(function() {

	var InterfaceString = "<?=$curif?>";
	var RealInterfaceString = "<?=$realif?>";
    window.graph_backgroundupdate = $('#backgroundupdate').val() === "true";
	window.smoothing = $('#smoothfactor').val();
	window.interval = 1;
	window.invert = $('#invert').val() === "true";
	window.size = 8;
	window.interfaces = InterfaceString.split("|").filter(function(entry) { return entry.trim() != ''; });
	window.realinterfaces = RealInterfaceString.split("|").filter(function(entry) { return entry.trim() != ''; });

	graph_init();
	graph_visibilitycheck();

});
//]]>
</script>
<? if (file_exists('bandwidth_by_ip_dash.php')): ?>
<script type="text/javascript">
//<![CDATA[

var graph_interfacenames = <?php
	foreach ($ifdescrs as $ifname => $ifdescr) {
		$iflist[$ifname] = $ifdescr;
	}
	echo json_encode($iflist);
?>;
function updateBandwidth() {
	$.ajax(
		'/bandwidth_by_ip_dash.php',
		{
			type: 'get',
			data: {
				serial: $(document.forms[0]).serialize(),
				interface_index: $("#interface_index").val()
			},
			success: function (data) {
				var hosts_split = data.split("|");

				$('#top10-hosts').empty();

				//parse top ten bandwidth abuser hosts
				for (var y=0; y<10; y++) {
					if ((y < hosts_split.length) && (hosts_split[y] != "") && (hosts_split[y] != "no info")) {
						hostinfo = hosts_split[y].split(";");

						$('#top10-hosts').append('<tr>'+
							'<td>'+ hostinfo[0] +'</td>'+
							'<td>'+ hostinfo[1] +' <?=gettext("Bits/sec");?></td>'+
							'<td>'+ hostinfo[2] +' <?=gettext("Bits/sec");?></td>'+
						'</tr>');
					}
				}
			},
	});
}

events.push(function() {
	$('form.auto-submit').on('change', function() {
		$(this).submit();
	});
});

//]]>
</script>
<?php endif; ?>

<script src="/js/jquery-3.1.1.min.js?v=<?=filemtime('/usr/local/www/js/jquery-3.1.1.min.js')?>"></script>
<script src="/vendor/echarts/dist/echarts.min.js?v=<?=filemtime('/usr/local/www/vendor/echarts/dist/echarts.min.js')?>"></script>
<script src="/js/echarts/map/js/world.js?v=<?=filemtime('/usr/local/www/js/echarts/map/js/world.js')?>"></script>
<script src="/js/traffic-graphs.js?v=<?=filemtime('/usr/local/www/js/traffic-graphs.js')?>"></script>
<script type="text/javascript">

	$('#interface_index').on('change', function() {
		updateBandwidth();
	});

	setTimeout(() => { updateBandwidth(); }, 100);

	setInterval(updateBandwidth, 3000);

	function mapa_threads(mapaTarget) {
		var data_response = "";
		var data = [];
		let fileGet = "";
		if (mapaTarget == 'map-invasao') {
			fileGet = "./active_protection/tentativas_invasao";
		} else if (mapaTarget == 'map-recente') {
			fileGet = "./active_protection/geo_ameacas_map";
		} else {
			fileGet = "./active_protection/tentativas_invasao";
		}

		$.get(fileGet, function(response) {

			data_response = $.parseJSON(response);			

			for (item in data_response.data ) {
				data.push({name: data_response.data[item].name, value: data_response.data[item].value},);
			}
				
			var nameMap = {
				'Afghanistan':'Afghanistan',
				'Singapore':'Singapore',
				'Angola':'Angola',
				'Albania':'Albania',
				'United Arab Emirates':'United Arab Emirates',
				'Argentina':'Argentina',
				'Armenia':'Armenia',
				'French Southern and Antarctic Lands':'French Southern and Antarctic Lands',
				'Australia':'Australia',
				'Austria':'Austria',
				'Azerbaijan':'Azerbaijan',
				'Burundi':'Burundi',
				'Belgium':'Belgium',
				'Benin':'Benin',
				'Burkina Faso':'Burkina Faso',
				'Bangladesh':'Bangladesh',
				'Bulgaria':'Bulgaria',
				'The Bahamas':'The Bahamas',
				'Bosnia and Herzegovina':'Bosnia and Herzegovina',
				'Belarus':'Belarus',
				'Belize':'Belize',
				'Bermuda':'Bermuda',
				'Bolivia':'Bolivia',
				'Brazil':'Brazil',
				'Brunei':'Brunei',
				'Bhutan':'Bhutan',
				'Botswana':'Botswana',
				'Central African Republic':'Central African Republic',
				'Canada':'Canada',
				'Switzerland':'Switzerland',
				'Chile':'Chile',
				'China':'China',
				'Ivory Coast':'Ivory Coast',
				'Cameroon':'Cameroon',
				'Democratic Republic of the Congo':'Democratic Republic of the Congo',
				'Republic of the Congo':'Republic of the Congo',
				'Colombia':'Colombia',
				'Costa Rica':'Costa Rica',
				'Cuba':'Cuba',
				'Northern Cyprus':'Northern Cyprus',
				'Cyprus':'Cyprus',
				'Czech Republic':'Czech Republic',
				'Germany':'Germany',
				'Djibouti':'Djibouti',
				'Denmark':'Denmark',
				'Dominican Republic':'Dominican Republic',
				'Algeria':'Algeria',
				'Ecuador':'Ecuador',
				'Egypt':'Egypt',
				'Eritrea':'Eritrea',
				'Spain':'Spain',
				'Estonia':'Estonia',
				'Ethiopia':'Ethiopia',
				'Finland':'Finland',
				'Fiji':'Fiji',
				'Falkland Islands':'Falkland Islands',
				'France':'France',
				'Gabon':'Gabon',
				'United Kingdom':'United Kingdom',
				'Georgia':'Georgia',
				'Ghana':'Ghana',
				'Guinea':'Guinea',
				'Gambia':'Gambia',
				'Guinea Bissau':'Guinea Bissau',
				'Equatorial Guinea':'Equatorial Guinea',
				'Greece':'Greece',
				'Greenland':'Greenland',
				'Guatemala':'Guatemala',
				'French Guiana':'French Guiana',
				'Guyana':'Guyana',
				'Honduras':'Honduras',
				'Croatia':'Croatia',
				'Haiti':'Haiti',
				'Hungary':'Hungary',
				'Indonesia':'Indonesia',
				'India':'India',
				'Ireland':'Ireland',
				'Iran':'Iran',
				'Iraq':'Iraq',
				'Iceland':'Iceland',
				'Israel':'Israel',
				'Italy':'Italy',
				'Jamaica':'Jamaica',
				'Jordan':'Jordan',
				'Japan':'Japan',
				'Kazakhstan':'Kazakhstan',
				'Kenya':'Kenya',
				'Kyrgyzstan':'Kyrgyzstan',
				'Cambodia':'Cambodia',
				'South Korea':'South Korea',
				'Kosovo':'Kosovo',
				'Kuwait':'Kuwait',
				'Laos':'Laos',
				'Lebanon':'Lebanon',
				'Liberia':'Liberia',
				'Libya':'Libya',
				'Sri Lanka':'Sri Lanka',
				'Lesotho':'Lesotho',
				'Lithuania':'Lithuania',
				'Luxembourg':'Luxembourg',
				'Latvia':'Latvia',
				'Morocco':'Morocco',
				'Moldova':'Moldova',
				'Madagascar':'Madagascar',
				'Mexico':'Mexico',
				'Macedonia':'Macedonia',
				'Mali':'Mali',
				'Myanmar':'Myanmar',
				'Montenegro':'Montenegro',
				'Mongolia':'Mongolia',
				'Mozambique':'Mozambique',
				'Mauritania':'Mauritania',
				'Malawi':'Malawi',
				'Malaysia':'Malaysia',
				'Namibia':'Namibia',
				'New Caledonia':'New Caledonia',
				'Niger':'Niger',
				'Nigeria':'Nigeria',
				'Nicaragua':'Nicaragua',
				'Netherlands':'Netherlands',
				'Norway':'Norway',
				'Nepal':'Nepal',
				'New Zealand':'New Zealand',
				'Oman':'Oman',
				'Pakistan':'Pakistan',
				'Panama':'Panama',
				'Peru':'Peru',
				'Philippines':'Philippines',
				'Papua New Guinea':'Papua New Guinea',
				'Poland':'Poland',
				'Puerto Rico':'Puerto Rico',
				'North Korea':'North Korea',
				'Portugal':'Portugal',
				'Paraguay':'Paraguay',
				'Qatar':'Qatar',
				'Romania':'Romania',
				'Russia':'Russia',
				'Rwanda':'Rwanda',
				'Western Sahara':'Western Sahara',
				'Saudi Arabia':'Saudi Arabia',
				'Sudan':'Sudan',
				'South Sudan':'South Sudan',
				'Senegal':'Senegal',
				'Solomon Islands':'Solomon Islands',
				'Sierra Leone':'Sierra Leone',
				'El Salvador':'El Salvador',
				'Somaliland':'Somaliland',
				'Somalia':'Somalia',
				'Republic of Serbia':'Republic of Serbia',
				'Suriname':'Suriname',
				'Slovakia':'Slovakia',
				'Slovenia':'Slovenia',
				'Sweden':'Sweden',
				'Swaziland':'Swaziland',
				'Syria':'Syria',
				'Chad':'Chad',
				'Togo':'Togo',
				'Thailand':'Thailand',
				'Tajikistan':'Tajikistan',
				'Turkmenistan':'Turkmenistan',
				'East Timor':'East Timor',
				'Trinidad and Tobago':'Trinidad and Tobago',
				'Tunisia':'Tunisia',
				'Turkey':'Turkey',
				'United Republic of Tanzania':'United Republic of Tanzania',
				'Uganda':'Uganda',
				'Ukraine':'Ukraine',
				'Uruguay':'Uruguay',
				'United States of America':'United States of America',
				'Uzbekistan':'Uzbekistan',
				'Venezuela':'Venezuela',
				'Vietnam':'Vietnam',
				'Vanuatu':'Vanuatu',
				'West Bank':'West Bank',
				'Yemen':'Yemen',
				'South Africa':'South Africa',
				'Zambia':'Zambia',
				'Zimbabwe':'Zimbabwe'
			};

			var map_countries_chart_option = {
				timeline: {
					axisType: 'category',
						orient: 'vertical',
						autoPlay: true,
						inverse: true,
						playInterval: 5000,
						left: null,
						right: -105,
						top: 0,
						bottom: 0,
						width: 46,
					data: ['2019',]  
				},
				baseOption: {
					visualMap: {
						min: 50,
						max: 5000,
						text: ['Max', 'Min'],
						realtime: false,
						calculable: true,
						
						inRange: {
							color: ['#fddd57', '#F5B240', '#fdae61', '#f46d43', '#d73027', '#a50026']
						}
					},
					series: [{
						type: 'map',
						map: 'world',
						zoom: 1.20,
						roam: true,
						nameMap: nameMap,
						itemStyle: {
							normal: {
								borderColor: '#bebebe',
							}
						},
					}]
				},
				
				options: [{
					series: {
						data: data,
					} 
				},]
			};
			var ChartMapThreats = echarts.init(document.getElementById("chart-map-threats"));
			ChartMapThreats.setOption(map_countries_chart_option);
		});
	}
    
	mapa_threads();

</script>

<script src="/vendor/chartjs/Chart.min.js?v=<?=filemtime('/usr/local/www/vendor/chartjs/Chart.min.js')?>"></script>
<script type="text/javascript">
//<![CDATA[

	/* Chart Bar */

var lastTotal = 0;
var lastUsed = 0;

dirty = false;
function updateWidgets(newWidget) {
	var sequence = '';

	$('.container-fluid .col-md-<?=$columnWidth?>').each(function(idx, col){
		$('.panel', col).each(function(idx, widget) {
			var isOpen = $('.panel-body', widget).hasClass('in');
			var widget_basename = widget.id.split('-')[1];

			// Only save details for panels that have id's like'widget-*'
			// Some widgets create other panels, so ignore any of those.
			if ((widget.id.split('-')[0] == 'widget') && (typeof widget_basename !== 'undefined')) {
				sequence += widget_basename + ':' + col.id.split('-')[1] + ':' + (isOpen ? 'open' : 'close') + ':' + widget.id.split('-')[2] + ',';
			}
		});
	});

	if (typeof newWidget !== 'undefined') {
		// The system_information widget is always added to column one. Others go in column two
		if (newWidget == "system_information") {
			sequence += newWidget.split('-')[0] + ':' + 'col1:open:next';
		} else {
			sequence += newWidget.split('-')[0] + ':' + 'col2:open:next';
		}
	}

	$('input[name=sequence]', $('#widgetSequence_form')).val(sequence);
}

// Determine if all the checkboxes are checked
function are_all_checked(checkbox_panel_ref) {
	var allBoxesChecked = true;
	$(checkbox_panel_ref).each(function() {
		if ((this.type == 'checkbox') && !this.checked) {
			allBoxesChecked = false;
		}
	});
	return allBoxesChecked;
}

// If the checkboxes are all checked, then clear them all.
// Otherwise set them all.
function set_clear_checkboxes(checkbox_panel_ref) {
	checkTheBoxes = !are_all_checked(checkbox_panel_ref);

	$(checkbox_panel_ref).each(function() {
		$(this).prop("checked", checkTheBoxes);
	});
}

// Set the given id to All or None button depending if the checkboxes are all checked.
function set_all_none_button(checkbox_panel_ref, all_none_button_id) {
	if (are_all_checked(checkbox_panel_ref)) {
		text = "<?=gettext('None')?>";
	} else {
		text = "<?=gettext('All')?>";
	}

	$("#" + all_none_button_id).html('<i class="fa fa-undo icon-embed-btn"></i>' + text);
}

// Setup the necessary events to manage the All/None button and included checkboxes
// used for selecting the items to show on a widget.
function set_widget_checkbox_events(checkbox_panel_ref, all_none_button_id) {
		set_all_none_button(checkbox_panel_ref, all_none_button_id);

		$(checkbox_panel_ref).change(function() {
			set_all_none_button(checkbox_panel_ref, all_none_button_id);
		});

		$("#" + all_none_button_id).click(function() {
			set_clear_checkboxes(checkbox_panel_ref);
			set_all_none_button(checkbox_panel_ref, all_none_button_id);
		});
}

// ---------------------Centralized widget refresh system -------------------------------------------
// These need to live outsie of the events.push() function to enable the widgets to see them
var ajaxspecs = new Array();    // Array to hold widget refresh specifications (objects )
var ajaxidx = 0;
var ajaxmutex = false;
var ajaxcntr = 0;

// Add a widget refresh object to the array list
function register_ajax(ws) {
  ajaxspecs.push(ws);
}
// ---------------------------------------------------------------------------------------------------

events.push(function() {

	var options = 'graphtype=line&left=utm-capacity&right=null&timePeriod=-1d&resolution=300';
	d3.json("rrd_fetch_json.php")
		 .header("Content-Type", "application/x-www-form-urlencoded")
		 .post(options, function(error, data) {
		if (typeof data.length != 'number')
		{
			return;
		}
		if (data[0].values == 'undefined' || data[0].values.length == 0) {
			return [{ "key" : "<?=gettext('Permitted Hosts');?>", "values" : [] }, { "key" : "<?=gettext('Exceeded Hosts');?>", "values" : [] }];
		}
		var total_hosts = <?=$limit_hosts;?>;
		var permitted_hosts = 0;
		//var arp_hosts = <?php $arp_hosts1 = trim(file_get_contents("/tmp/arp_hosts")); echo $arp_hosts1; ?>;

		for (var i=0; i<data[0].values.length; i++) {
			if (data[0].values[i][1] <= total_hosts) {
				permitted_hosts = Math.round(data[0].values[i][1]);
			}
		}

		//percent_permitted_hosts = Math.ceil((arp_hosts) / total_hosts);
		//$('#capacity-utm').css('width', percent_permitted_hosts + '%');
		//$('#capacity-utm').html(percent_permitted_hosts + " %");
		//$('#permitted-hosts').html(permitted_hosts);


		var total_temp = 50;
		var temp = <?php echo file_exists('/tmp/tempmed') ? readfile("/tmp/tempmed") : "''";?>;
		percent_temp = Math.ceil(<?php file_exists('/tmp/tempmed') ? readfile("/tmp/tempmed") : "";?>);
		$('#temp_cpu').css('width', temp + '°C');
		$('#temp_cpu').html(temp + "°C");
		$('#graph_temp1').html(temp);

});

	// Make panels destroyable
	$('.container-fluid .panel-heading a[data-toggle="close"]').each(function (idx, el) {
		$(el).on('click', function(e) {
			$(el).parents('.panel').remove();
			updateWidgets();
			// Submit the form save/display all selected widgets
			$('[name=widgetForm]').submit();
		})
	});

	// Make panels sortable
	$('.container-fluid .col-md-<?=$columnWidth?>').sortable({
		handle: '.panel-heading',
		cursor: 'grabbing',
		connectWith: '.container-fluid .col-md-<?=$columnWidth?>',
		update: function(){
			dirty = true;
			$('#btnstore').removeClass('invisible');
		}
	});

	// On clicking a widget to install . .
	$('[id^=btnadd-]').click(function(event) {
		// Add the widget name to the list of displayed widgets
		updateWidgets(this.id.replace('btnadd-', ''));

		// Submit the form save/display all selected widgets
		$('[name=widgetForm]').submit();
	});


	$('#btnstore').click(function() {
		updateWidgets();
		dirty = false;
		$(this).addClass('invisible');
		$('[name=widgetForm]').submit();
	});

	// provide a warning message if the user tries to change page before saving
	$(window).bind('beforeunload', function(){
		if (dirty) {
			return ("<?=gettext('One or more widgets have been moved but have not yet been saved')?>");
		} else {
			return undefined;
		}
	});

	// Show the fa-save icon in the breadcrumb bar if the user opens or closes a panel (In case he/she wants to save the new state)
	// (Sometimes this will cause us to see the icon when we don't need it, but better that than the other way round)
	$('.panel').on('hidden.bs.collapse shown.bs.collapse', function (e) {
		if (e.currentTarget.id != 'widget-available') {
			$('#btnstore').removeClass("invisible");
		}
	});

	/*
	function graph_virus() {
		$.ajax({
			type: 'GET',
			url: '/firewallapp/ajax_graphic_virus.php',
			dataType: 'json',
			success: function(response) {
				var ctx = document.getElementById("virus-graph");
				if(ctx){
					var myChart = new Chart(ctx, {
						type: 'doughnut',
						data: {
							labels: response.virus,
							datasets: [{
								label: '',
								data: response.percent,
								backgroundColor: response.color,
							}]
						},
						options: {
							legend: {
								display: false,
								labels: {
									fontColor: 'rgb(255, 99, 132)'
								},
								// position: 'bottom'
							},
							layout: {
								padding: {
									left: 0,
									right: 0,
									top: 5,
									bottom: 5
								}
							},
							// scales: {
							// 	yAxes: [{
							// 		ticks: {
							// 			beginAtZero:false,
							// 			suggestedMin: 100,
							// 			stepSize: 0,
							// 		}
							// 	}],
							// }
						}
					});
				}
			}
		});
	}
	graph_virus();
	*/

// --------------------- Nivel de Risco ------------------------------

	//function riscLevel() {

		$.ajax({
			method: "post",
			url: './ajax_threat_barometer.php',		
		}).done(function(return_data) {

			var utmRiskLevelOptions = {
				grid: {
					z:1,
					show:false,
					left: '-30%',
					right: '4%',
					bottom: '3%',
					containLabel: false,
					splitLine:{
						show: false
					},
				},
				xAxis : [
					{
						type: 'category',
						data: [],
						axisLine: {
							show: false
						},
						splitLine:{
							show: false
						},
						splitArea: {
							interval: 'auto',
							show: false
						}
					}
				],
				yAxis : [
					{
						type : 'value',
						axisLine: {
							show: false
						},
						splitLine:{
							show: false
						},
					}
				],
				toolbox: {
					show: false,
				},
				series : [
					{
						name:'<?=gettext("Risk Level")?>',
						type: 'gauge',
						startAngle: 180,
						endAngle: 0,
						center: ["50%", "80%"],
						z: 3,
						min: 0,
						max: 12,
						splitNumber: 5,
						radius: '148%',
						axisLine: {
							lineStyle: {
								width: 30,
								color: [
									[0.06, '#1E967D'],
									[0.3, '#E1B317'],
									[0.6, '#E27F22'],
									[0.8, '#B82738'],
									[1, '#B82738']
								],
							}
						},
						axisLabel: {
							textStyle: {
								color:
								'#fff',
							}
						},
						pointer: {
							show: true,
							length: '70%',
							width: 5,
						},
						itemStyle:{
							normal:{
								color:'#454A57',
								borderWidth:0
							}
						},
						title: {
							show: true,
							offsetCenter: ['0', '20'],
							textStyle: {
								color: '#333',
								fontSize: 1,
								fontFamily: 'Microsoft YaHei'
							}
						},
						detail: {
							show: false,
							textStyle: {
								fontSize: 12,
								color: '#333'
							}
						},
						data:[{value: 0, name: ''}]
					},
					{
						name: 'Risco',
						type: 'gauge',
						z:2,
						radius: '90%',
						startAngle: 180,
						endAngle: 0,
						center: ["50%", "50%"],
						splitNumber: 4,
						axisLine: {
							lineStyle: {
								color: [
									[1,
									'#fff'
									]
								],
								width: 5,
								opacity: 1,
							}
						},
						splitLine: {
							show: false,
						},
						axisLabel: {
							show: false,
						},
						axisTick: {
							show: false,
						},
						detail : {
							show:false,
							textStyle: {
								fontWeight: 'bolder',
								fontSize:12
							}
						},
					},
					{
						type:'bar',
						barWidth: '60%',
						data:[0],
						itemStyle: {
							normal: {
								color: '#fff',
							}
						}
					},
					{
						type:'bar',
						barWidth: '60%',
						data:[0],
						itemStyle: {
							normal: {
								color: '#fff',
							}
						}
					},
					{
						type:'bar',
						barWidth: '60%',
						data:[0],
						itemStyle: {
							normal: {
								color: '#fff',
							}
						}
					},
					{
						type:'bar',
						barWidth: '60%',
						data:[0],
						itemStyle: {
							normal: {
								color: '#fff',
							}
						}
					},
					{
						type:'bar',
						barWidth: '60%',
						data:[0],
						itemStyle: {
							normal: {
								color: '#fff',
							}
						}
					}
				]
			}

			//var utm_v_h = 0;
			//var utm_v_m = 0;
			//var utm_v_l = 1;

			var risk_level = 0;
			var vulnerability_descr = "Sem Risco";
			var maiorValorRegistrado = parseInt(return_data);

			if ((maiorValorRegistrado > 0) && (maiorValorRegistrado <= 4)) {
				risk_level = 2;
				vulnerability_descr = "Risco Baixo";
			} else if ((maiorValorRegistrado >= 5) && (maiorValorRegistrado <= 7)) {
				risk_level = 5;
				vulnerability_descr = "Risco Médio";
			} else if (maiorValorRegistrado >= 8) {
				risk_level = 10;
				vulnerability_descr = "Risco Alto";
			}

			$("#utm-risk-level").html(vulnerability_descr);

			utmRiskLevelOptions.series[0].data[0].value = risk_level

			utmChartVulnerabilities = echarts.init(document.getElementById("chart-vulnerabilities-utm"));
			utmChartVulnerabilities.setOption(utmRiskLevelOptions);
		});

	//}

	// --------------------- Centralized widget refresh system ------------------------------
	ajaxtimeout = false;

	function make_ajax_call(wd) {
		ajaxmutex = true;
		$.ajax({
			type: 'POST',
			url: wd.url,
			dataType: 'html',
			data: wd.parms,
			success: function(data){
			if (data.length > 0 ) {
				if (data.indexOf("SESSION_TIMEOUT") === -1) {
					wd.callback(data);
				} else {
					if (ajaxtimeout === false) {
						ajaxtimeout = true;
						alert("<?=$timeoutmessage?>");
					}
				}
			}
			ajaxmutex = false;
			},
			error: function(e){
			ajaxmutex = false;
			}
		});
	}

	$(document).ready(function(){
	  $('[data-toggle="tooltip"]').tooltip();   
	});

	// Loop through each AJAX widget refresh object, make the AJAX call and pass the
	// results back to the widget's callback function
	function executewidget() {
		if (ajaxspecs.length > 0) {
			var freq = ajaxspecs[ajaxidx].freq;     // widget can specify it should be called freq times around the loop

			if (!ajaxmutex) {
				if (((ajaxcntr % freq) === 0) && (typeof ajaxspecs[ajaxidx].callback === "function" )) {
					make_ajax_call(ajaxspecs[ajaxidx]);
				}

				if (++ajaxidx >= ajaxspecs.length) {
					ajaxidx = 0;

					if (++ajaxcntr >= 4096) {
						ajaxcntr = 0;
					}
				}
			}

			setTimeout(function() { executewidget(); }, 1000);
		}
	}

	// Kick it off
	executewidget();

	//----------------------------------------------------------------------------------------------------
});
//]]>

function updateMeters() {
	url = '/getstats.php';

	$.ajax(url, {
		type: 'get',
		success: function(data) {
			response = data || "";
			if (response != "")
				stats(data);
		}
	});

}

//events.push(function(){
//	$("#showallsysinfoitems").click(function() {
//		$("#widget-system_information_panel-footer [id^=show]").each(function() {
//			$(this).prop("checked", true);
//		});
//	});
//});


function setProgress(barName, percent) {
	$('#' + barName).css('width', percent + '%').attr('aria-valuenow', percent);
}


function stats(x) {
	var values = x.split("|");
	if ($.each(values,function(key,value) {
		if (value == 'undefined' || value == null)
			return true;
		else
			return false;
	}))

	updateUptime(values[2]);
	updateDateTime(values[5]);
	//updateCPU(values[0]);

	if (lastTotal === 0) {
		lastTotal = values[0];
		lastUsed = values[1];
	} else {
		updateCPU(values[0], values[1]);
	}

	updateMemory(values[2]);
	//updateState(values[3]);
	updateCapacity(parseInt(values[4]) / 9);
	updateSessionsCapacity(parseInt(values[4]) / 9);
	updateTemp(parseInt(values[4]) / 9);
	updateInterfaceStats(values[6]);
	updateInterfaces(values[7]);
	//updateCpuFreq(values[8]);
	//updateLoadAverage(values[9]);
	updateDisk(values[9]);
	//updateMbuf(values[11]);
	//updateMbufMeter(values[12]);
	//updateStateMeter(values[13]);

}

function updateDisk(x) {
	if ($('#DiskUsagePB')) {
		setProgress('DiskUsagePB', parseInt(x));
		$("#DiskUsagePB").html(x + "%");
	}
}

function updateMemory(x) {
	if ($('#memUsagePB')) {
		setProgress('memUsagePB', parseInt(x));
		$("#memUsagePB").html(x + "%");
	}
}

//function updateMbuf(x) {
//	if ($('#mbuf')) {
//		$("#mbuf").html('(' + x + ')');
//	}
//}
//
//function updateMbufMeter(x) {
//	if ($('#mbufusagemeter')) {
//		$("#mbufusagemeter").html(x + '%');
//	}
//	if ($('#mbufPB')) {
//		setProgress('mbufPB', parseInt(x));
//	}
//}

function updateCPU(total, used) {

	if ((lastTotal <= total) && (lastUsed <= used)) { // Just in case it wraps
		// Calculate the total ticks and the used ticks since the last time it was checked
		var d_total = total - lastTotal;
		var d_used = used - lastUsed;

		// Convert to percent
		var x = Math.floor(((d_total - d_used)/d_total) * 100);

		if ($('#cpumeter')) {
			$('[id="cpumeter"]').html(x + '%');
		}

		if ($('#cpuPB')) {
			setProgress('cpuPB', parseInt(x));
		}

		/* Load CPU Graph widget if enabled */
		if (widgetActive('cpu_graphs')) {
			GraphValue(graph[0], x);
		}
	}

	// Update the saved "last" values
	lastTotal = total;
	lastUsed = used;
}



function updateTemp(x) {
	/*if ($("#tempmeter")) {
		$("#tempmeter").html(x + '&deg;' + 'C');
	}
	if ($('#tempPB')) {
		setProgress('tempPB', parseInt(x));
	}
	if ($('#tempPB2')) {
		setProgress('tempPB2', parseInt(32));
		$("#tempPB2").html('&deg;' + 'C');
	}*/
	$.post("./bar_ajax_index_temp_capacity.php", 'tempmed', function(data) {
		$("#tempPB2").removeAttr('class');
		if (isNaN(parseInt(data)) == false) {
			$("#tempPB2").addClass('progress-bar');
			if (parseInt(data) >= 90) {
				$("#tempPB2").addClass('bg-danger');
			} else {
				$("#tempPB2").addClass('bg-warning3');
			}
			var temp_now = 0;
			if (parseInt(data) > 100) {
				temp_now = 100;
			} else {
				temp_now = data;
			}
			setProgress('tempPB2', parseInt(temp_now));
			$("#tempPB2").html(data + '&deg;' + 'C');
		} else {
			$("#tempPB2").addClass('progress-bar bg-info');
			setProgress('tempPB2', parseInt(100));
			$("#tempPB2").html(data);
		}
	}).fail(function () {
		$("#tempPB2").removeAttr('class');
		$("#tempPB2").addClass('progress-bar bg-info');;
		setProgress('tempPB2', parseInt(100));
		$("#tempPB2").html('<?=gettext("There is data waiting")?>');
	});
}

function updateCapacity(x) {
	$.post("./bar_ajax_index_temp_capacity.php", 'utmcapacity', function(data) {

		const capacity_json = JSON.parse(data);
		$("#capacity-utm-hosts").html(capacity_json["text"]);
		$("#capacity-utm").removeAttr('class');

		if (isNaN(parseInt(capacity_json["value"])) == false) {
			if (parseInt(capacity_json["value"]) >= 100) {
				$("#capacity-utm").addClass('bg-danger');
			} else if ((parseInt(capacity_json["value"]) >= 70) && (parseInt(capacity_json["value"]) <= 99)){
				$("#capacity-utm").addClass('bg-warning');
			} else if ((parseInt(capacity_json["value"]) >= 30) && (parseInt(capacity_json["value"]) <= 70)){
				$("#capacity-utm").addClass('bg-info');
			} else {
				$("#capacity-utm").addClass('bg-success2');
			}

			var capacity_now = (parseInt(capacity_json["value"]) > 100) ? 100 : capacity_json["value"];

			$("#capacity-utm").addClass('progress-bar');
			$("#capacity-utm").attr("aria-valuenow", capacity_now);
			setProgress('capacity-utm', parseInt(capacity_now));
			$("#capacity-utm").html(capacity_json["value"] + '%');
		} else {
			$("#capacity-utm").addClass('progress-bar bg-info');
			setProgress('capacity-utm', parseInt(100));
			$("#capacity-utm").html(capacity_json["value"]);
		}
	
	}).fail(function () {
		$("#capacity-utm").removeAttr('class');
		$("#capacity-utm").addClass('progress-bar bg-info');
		setProgress('capacity-utm', parseInt(100));
		$("#capacity-utm").html('<?=gettext("There is data waiting")?>');
		$("#capacity-utm-hosts").html('<?=gettext("There is data waiting")?>');
	});
}

function updateSessionsCapacity() {
	$.post("./bar_ajax_index_temp_capacity.php", 'utmcapacitysessions', function(data) {
		const capacity_json = JSON.parse(data);
		$("#sessions-capacity-utm-hosts").html(capacity_json["text"]);
		$("#sessions-capacity-utm").removeAttr('class');

		if (isNaN(parseInt(capacity_json["value"])) == false) {
			if (parseInt(capacity_json["value"]) >= 100) {
				$("#sessions-capacity-utm").addClass('bg-danger');
			} else if ((parseInt(capacity_json["value"]) >= 70) && (parseInt(capacity_json["value"]) <= 99)){
				$("#sessions-capacity-utm").addClass('bg-warning');
			} else if ((parseInt(capacity_json["value"]) >= 30) && (parseInt(capacity_json["value"]) <= 70)){
				$("#sessions-capacity-utm").addClass('bg-info');
			} else {
				$("#sessions-capacity-utm").addClass('bg-success2');
			}

			var capacity_now = (parseInt(capacity_json["value"]) > 100) ? 100 : capacity_json["value"];

			$("#sessions-capacity-utm").addClass('progress-bar');
			$("#sessions-capacity-utm").attr("aria-valuenow", capacity_now);
			setProgress('sessions-capacity-utm', parseInt(capacity_now));
			$("#sessions-capacity-utm").html(capacity_json["value"] + '%');
		} else {
			$("#sessions-capacity-utm").addClass('progress-bar bg-info');
			setProgress('sessions-capacity-utm', parseInt(100));
			$("#sessions-capacity-utm").html(capacity_json["value"]);
		}
	}).fail(function () {
		$("#sessions-capacity-utm").removeAttr('class');
		$("#sessions-capacity-utm").addClass('progress-bar bg-info');
		setProgress('sessions-capacity-utm', parseInt(100));
		$("#sessions-capacity-utm").html('<?=gettext("There is data waiting")?>');
		$("#sessions-capacity-utm-hosts").html('<?=gettext("There is data waiting")?>');
	});
}

function updateDateTime(x) {
	if ($('#datetime')) {
		$("#datetime").html(x);
	}
}

function updateUptime(x) {
	if ($('#uptime')) {
		$("#uptime").html(x);
	}
}

//function updateState(x) {
//	if ($('#pfstate')) {
//		$("#pfstate").html('(' + x + ')');
//	}
//}
//
//function updateStateMeter(x) {
//	if ($('#pfstateusagemeter')) {
//		$("#pfstateusagemeter").html(x + '%');
//	}
//	if ($('#statePB')) {
//		setProgress('statePB', parseInt(x));
//	}
//}

//function updateCpuFreq(x) {
//	if ($('#cpufreq')) {
//		$("#cpufreq").html(x);
//	}
//}

//function updateLoadAverage(x) {
//	if ($('#load_average')) {
//		$("#load_average").html(x);
//	}
//}

function updateInterfaceStats(x) {
	if (widgetActive("interface_statistics")) {
		statistics_split = x.split(",");
		var counter = 1;
		for (var y=0; y<statistics_split.length-1; y++) {
			if ($('#stat' + counter)) {
				$('#stat' + counter).html(statistics_split[y]);
				counter++;
			}
		}
	}
}

function updateInterfaces(x) {
	if (widgetActive("interfaces")) {
		interfaces_split = x.split("~");
		interfaces_split.each(function(iface){
			details = iface.split("^");
			if (details[2] == '') {
				ipv4_details = '';
			} else {
				ipv4_details = details[2] + '<br />';
			}
			switch (details[1]) {
				case "up":
					$('#' + details[0] + '-up').css("display","inline");
					$('#' + details[0] + '-down').css("display","none");
					$('#' + details[0] + '-block').css("display","none");
					$('#' + details[0] + '-ip').html(ipv4_details);
					$('#' + details[0] + '-ipv6').html(details[3]);
					$('#' + details[0] + '-media').html(details[4]);
					break;
				case "down":
					$('#' + details[0] + '-down').css("display","inline");
					$('#' + details[0] + '-up').css("display","none");
					$('#' + details[0] + '-block').css("display","none");
					$('#' + details[0] + '-ip').html(ipv4_details);
					$('#' + details[0] + '-ipv6').html(details[3]);
					$('#' + details[0] + '-media').html(details[4]);
					break;
				case "block":
					$('#' + details[0] + '-block').css("display","inline");
					$('#' + details[0] + '-down').css("display","none");
					$('#' + details[0] + '-up').css("display","none");
					break;
			}
		});
	}
}

function widgetActive(x) {
	var widget = $('#' + x + '-container');
	if ((widget != null) && (widget.css('display') != null) && (widget.css('display') != "none")) {
		return true;
	} else {
		return false;
	}
}

/*
function systemStatusGetUpdateStatus() {
	$.ajax({
		type: 'get',
		url: '/widgets/widgets/system_information.widget.php',
		data: 'getupdatestatus=1',
		dataFilter: function(raw){
			// We reload the entire widget, strip this block of javascript from it
			return raw.replace(/<script>([\s\S]*)<\/script>/gi, '');
		},
		dataType: 'html',
		success: function(data){
			$('#widget-system_information #updatestatus').html(data);
		}
	});
}
*/

//setTimeout(() => {
//	riscLevel();
//}, 100);
setTimeout(() => {
	updateMeters();
}, 200);
//setTimeout(() => {
//	systemStatusGetUpdateStatus();	
//}, 300);

//window.setInterval("systemStatusGetUpdateStatus()", 10000);
window.setInterval("updateMeters()", 15000);
//window.setInterval("riscLevel()", 150000);

//]]>

</script>

<?php
include("foot.inc");
?>

<script>

function startModalPackVersion() {
	$('#updatePackModal').modal('show');
}

function startModalCoreVersion() {
	$('#updateCoreModal').modal('show');
}

function updatePackModalChange() {
	$('#updateCoreModal').modal('hide');
	setTimeout(() => {
		$('#updatePackModal').modal('show');
	}, 100);
}

function updateCoreNextCicle() {
	$('#formUpdateCore').submit();
}

function updateCoreNow() {
	$('#statusUpdateCore').val("now");
	$('#formUpdateCore').submit();
}

function UpdateCoreSilent() {
	$('#formUpdateCoreSilent').submit();
}

function updatePackNextCicle() {
	$('#formUpdatePack').submit();
}

function updatePackNow() {
	$('#statusUpdatePack').val("now");
	$('#formUpdatePack').submit();
}

</script>
