<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Guilherme R.Brechot <guilherme.brechot@bluepex.com>, 2022
 *
 * ====================================================================
 *
 */

require_once("guiconfig.inc");
require_once("service-utils.inc");

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

if (file_exists('/tmp/tables_show')) {
	unlink('/tmp/tables_show');
}
if (file_exists('/tmp/database_show')) {
	unlink('/tmp/database_show');
}

function returnStatusCounterProcessMysql() {
	return intval(trim(shell_exec("ps aux | grep mysqld | grep -v grep | wc -l"))) > 0;
}

$alertDelete = false;
$alertDeleteError = false;
if (isset($_POST['table_target']) && strlen($_POST['table_target']) > 0 && isset($_POST['time_delete_table']) && strlen($_POST['time_delete_table']) > 0) {
	if (returnStatusCounterProcessMysql()) {
		mwexec("mysql -uwebfilter -pwebfilter -hlocalhost webfilter -e\"select max(time_date) from {$_POST['table_target']}\" > /tmp/dateOperation.tmp && tail -n1 /tmp/dateOperation.tmp > /tmp/dateOperation && rm /tmp/dateOperation.tmp");
		$date_target = file_get_contents("/tmp/dateOperation");
		unlink("/mtp/dateOperation");
		mwexec("mysql -uwebfilter -pwebfilter -hlocalhost webfilter -e \"DELETE FROM {$_POST['table_target']} WHERE (time_date < DATE_SUB('{$date_target}', INTERVAL {$_POST['time_delete_table']}));\"");
		mwexec("mysql -uwebfilter -pwebfilter -hlocalhost webfilter -e \"OPTIMIZE TABLE {$_POST['table_target']};\"");
		$alertDelete = true;
	} else {
		$alertDeleteError = true;
	}
}

if (returnStatusCounterProcessMysql()) {
	mwexec("mysql -uwebfilter -pwebfilter webfilter -e \"SELECT table_schema AS 'Database_Name', ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size_in_(MB)' FROM information_schema.TABLES where table_schema = 'webfilter'  GROUP BY table_schema;\" > /tmp/database_show.tmp");
	mwexec("grep -v \"Database_Name\" /tmp/database_show.tmp | grep -v \"mysql\" | awk -F\" \" '{print $1 \"___\" $2}' > /tmp/database_show");
	mwexec("mysql -uwebfilter -pwebfilter webfilter -e \"SELECT table_name AS 'Table_Name', ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'Size_in_(MB)' FROM information_schema.TABLES WHERE table_schema = 'webfilter' ORDER BY (data_length + index_length) DESC;\" > /tmp/tables_show.tmp");
	mwexec("grep -v \"Table_Name\" /tmp/tables_show.tmp | grep -v \"mysql\" | awk -F\" \" '{print $1 \"___\" $2}' > /tmp/tables_show");
}

function get_status_services_by_wf_monitor() {
	global $g, $config;

	//@shell_exec("/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py");
	$services_status_file = "/usr/local/etc/webfilter/wf_monitor_services";
	if (!file_exists($services_status_file) || filesize($services_status_file) == 0) {
		return;
	}
	$services = array();
	$status_services = explode("\n", trim(file_get_contents($services_status_file)));

	$wf_extra_services = array("wfrotate");
	if ($g['platform'] == "BluePexUTM") {
		$wf_extra_services[] = "mysql";
	}

	// Organize services with status
	foreach ($status_services as $status_svc) {
		$get_services_status = explode(",", $status_svc);
		if (count($get_services_status) >= 6) {
			list($service_name, $service_status, $ntlm_status, $ads_status) = array_map("trim", explode(",", str_replace("\"", "", $status_svc)));
		} else {
			list($service_name, $service_status) = array_map("trim", explode(",", $status_svc));
		}
		if (preg_match("/^(squid|interface).*/", $service_name, $matches)) {
			$instance_name = str_replace("{$matches[1]}_", "", $service_name);
			if ($service_status != "ok") {
				$service_status = "error";
			}
			$services['webfilter'][$instance_name][] = array($matches[1] => $service_status);
			continue;
		}
		$services[$service_name] = $service_status;
	}

	if (!isset($services['webfilter'])) {
		return $services;
	}

	// Set status for WebFilter instances
	foreach ($services['webfilter'] as $instance => $wf_services) {
		$wf_status_services = array();
		foreach ($wf_services as $service_name => $service_status_array) {
			foreach ($service_status_array as $service_status) {
				$wf_status_services[] = $service_status;
			}
		}
		foreach ($wf_extra_services as $wf_extra_service) {
			if (isset($services[$wf_extra_service])) {
				$wf_status_services[] = $services[$wf_extra_service];
			}
		}
		if (in_array("error", $wf_status_services)) {
			$services['webfilter'][$instance] = "error";
		} elseif (in_array("alert", $wf_status_services)) {
			$services['webfilter'][$instance] = "alert";
		} else {
			$services['webfilter'][$instance] = "ok";
		}
	}
	return $services;
}

$services_status = get_status_services_by_wf_monitor();

function get_button_status($service, $status) {
	switch (trim($status)) {
		case "ok":
			$btn = "<div class=\"btn btn-success\" style=\"width:100%;\"><i class=\"fa fa-check-circle\"></i><br />{$service}</div>";
			break;
		case "error":
		case "off":
			$btn = "<div class=\"btn btn-danger no-confirm\" style=\"width:100%;\"><i class=\"fa fa-times-circle\"></i><br />{$service}</div>";
			break;
		case "alert":
			$btn = "<div class=\"btn btn-warning\" style=\"width:100%;\"><i class=\"fa fa-warning\"></i><br />{$service}</div>";
			break;
		case "disabled":
			$btn = "<div class=\"btn btn-default disabled\" style=\"width:100%;\"><i class=\"fa fa-times-circle\"></i><br />{$service}</div>";
			break;
		default:
			$btn = "";
			break;
	}
	return $btn;
}

$pgtitle = array("FirewallApp", "Status Database");
$pglinks = array("./firewallapp/services.php", "@self");
include("head.inc");

if (isset($_GET['per']) || ($_GET['per'] == $passModal)) {

	$value_disk = intval(trim(shell_exec("df -m | grep ROOT | head -n1 | awk -F\" \" '{print $5}'")));
	$service_disk = sprintf(gettext("Disk Usage %s"), $value_disk . "%");
	$statusDisk = ($value_disk > 80) ? "alert" : "ok";

	if (file_exists('/tmp/database_show')) {
		$blocks_1mb_disk = intval(trim(shell_exec("df -m | grep ROOT | head -n1 | awk -F\" \" '{print $2}'")));
		$blocks_1mb_database = intval(trim(shell_exec("tail -n1 /tmp/database_show | awk -F\"___\" '{print $2}'")));
		$value_database = intval(($blocks_1mb_disk - $blocks_1mb_database) / $blocks_1mb_disk);
		$service_db = sprintf(gettext("Espaço do banco em disco: %s"), $value_database . "%");
		$statusDB = ($value_database > 80) ? "alert" : "ok";
	} else {
		$service_db = sprintf(gettext("Espaço do banco em disco: %s"), "Sem conexão");
		$statusDB = "off";
	}

	if ($value_disk > 80) {
		print_info_box("O disco está com 80% ou mais de sua capacidade total em uso.", 'warning');
	}
	if ($value_database > 80) {
		print_info_box("O banco de dados está com 80% ou mais de consumo sobre o espaço de disco total.", 'warning');
	}
}

if ($alertDelete) {
	print_info_box("Ação de delete dos registros com mais de <b style=\"text-transform: uppercase;\">{$_POST['time_delete_table']}</b> foi acionada na tabela: <b style=\"text-transform: uppercase;\">{$_POST['table_target']}</b>", 'warning');
}
if ($alertDeleteError) {
	print_info_box("Ação de delete dos registro não pode ser realizada: <b>Sem conexão com o banco de dados.</b>", 'danger');
}

$tab_array = array();
$tab_array[] = array(gettext('General'), false, '/firewallapp/report_settings.php');
$tab_array[] = array(gettext('By services'), false, '/firewallapp/enable_by_interface_rotate.php');
$tab_array[] = array(gettext('Informative'), true, '/firewallapp/firewallapp_status_data_tables.php');
display_top_tabs($tab_array);

if (!isset($_GET['per']) || ($_GET['per'] != $passModal)) {
?>
<style type="text/css">
	#header-licenses-information { margin-bottom: 65px; background:url(./images/bg-header.png) no-repeat; -webkit-background-size: cover; -moz-background-size: cover; -o-background-size: cover; background-size: cover;}
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
								<img src="../images/cadeado.jpg" class="img-fluid text-center">
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
		  <?php if ($save_true): ?>
				<div class="btn-success" style="padding: 10px !important; text-align: center; text-transform: capitalize;" id="sumir-status">
					<p style="margin: 0px !important;"><?=gettext("changes saved")?></p>
				</div>
			<?php endif; ?>
			<form method="GET" action="" style="margin-top: 10px !important; border: 0px solid transparent;">
			        <div class="form-row">
			        	<div class="col">
			            	<label for="recipient-name" class="col-form-label"><?=gettext("Enter password:")?></label>
			            	<input type="password" class="form-control" name="per" maxlength="50">
			          	</div>
			        </div> 
			        <hr> 
			        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Close")?></button>
			        <button type="submit" class="btn btn-primary"><?=gettext("Continuar")?></button>
					<?php if (isset($_GET['per']) && ($_POST['per'] != $passModal)): ?>
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

<?php
} else {
	?>
	<div class="col-sm-6 subservice" style="width: 33.33%; margin-bottom: 10px; padding-left:3px; padding-right: 5px;">
		<?=get_button_status($service_disk, $statusDisk);?>
	</div>
	<?php if (isset($services_status['mysql']) && file_exists('/tmp/database_show')): ?>
		<div class="col-sm-6 subservice" style="width: 33.33%; margin-bottom: 10px; padding-left:3px; padding-right: 3px;">
			<?=get_button_status(gettext("Database"), $services_status['mysql']);?>
		</div>
	<?php else: ?>
		<div class="col-sm-6 subservice" style="width: 33.33%; margin-bottom: 10px; padding-left:3px; padding-right: 3px;">
			<?=get_button_status(gettext("Database"), 'off');?>
		</div>
	<?php endif; ?>
	<div class="col-sm-6 subservice" style="width: 33.33%; margin-bottom: 10px; padding-left:5px; padding-right: 3px;">
		<?=get_button_status($service_db, $statusDB);?>
	</div>

	<div class='table-responsive panel-body'>
		<table class='table table-hover table-striped table-condensed'>
			<thead>
				<tr>
					<th>Partição</th>
					<th>Disco total (MB)</th>
					<th>Uso do disco (MB)</th>
					<th>Espaço livre do disco (MB)</th>
				</tr>
			</thead>
			<tbody>
				<tr>
				<?php
					foreach (explode("___", trim(shell_exec("df -m | grep ROOT | head -n1 | awk -F\" \" '{print $6 \"___\" $2 \"___\" $3 \"___\" $4}'"))) as $values) {
						echo "<td style='border-bottom: 1px solid black;'>{$values}</td>";	
					}
				?>
				</tr>
			</tbody>
		</table>
	</div>
	<br>

	<?php if (file_exists('/tmp/database_show')): ?>
		<div class='table-responsive panel-body'>
			<table class='table table-hover table-striped table-condensed'>
				<thead>
					<tr>
						<th>Database</th>
						<th>Size(MB)</th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach (array_filter(explode("\n", file_get_contents('/tmp/database_show'))) as $line_now) {
					echo "<tr>";
					foreach (explode("___", $line_now) as $values) {
						echo "<td style='border-bottom: 1px solid black;'>{$values}</td>";	
					}
					echo "</tr>";
				}
				?>
				</tbody>
			</table>
		</div>
		<br>
	<?php else: ?>
		<?=print_info_box("Could not establish connection to database", 'danger');?>
	<?php endif; ?>

	<?php if (file_exists('/tmp/tables_show')): ?>
		<div class="infoblock">
			<div class="alert alert-info clearfix" role="alert">
				Ao clicar na ação de limpeza, será apagado todos os registros mais velhos que o tempo definido no campo da coluna de ações.
			</div>
		</div>

		<div class='table-responsive panel-body' style="margin-bottom: 60px;">
			<table class='table table-hover table-striped table-condensed'>
				<thead>
					<tr>
						<th style="vertical-align: middle;">Tables</th>
						<th style="vertical-align: middle;">Size(MB)</th>
						<th style="vertical-align: middle;">Action
							<select id="timeDelete" name="timeDelete" class="form-control" style="text-align:center;">
								<option value="1 WEEK">1 WEEK</option>	
								<option value="2 WEEK">2 WEEK</option>	
								<option value="3 WEEK">3 WEEK</option>	
								<option value="1 MONTH">1 MONTH</option>	
								<option value="2 MONTH">2 MONTH</option>	
								<option value="3 MONTH" selected="selected">3 MONTH</option>
							</select>
						</th>
					</tr>
				</thead>
				<tbody>
				<?php
					foreach (array_filter(explode("\n", file_get_contents('/tmp/tables_show'))) as $line_now) {
						echo "<tr>";
						$line_now = explode("___", $line_now);
						echo "<td style='border-bottom: 1px solid black;'>{$line_now[0]}</td>";	
						echo "<td style='border-bottom: 1px solid black;'>{$line_now[1]}</td>";	
						echo "<td style='border-bottom: 1px solid black;'><i class='fa fa-trash' aria-hidden='true' title='Ação de limpeza de registros antigos' onclick=\"cleanTable('{$line_now[0]}')\"></i></td>";	
						echo "</tr>";
					}
				?>
				</tbody>
			</table>
		</div>

		<form action="" method="POST" style="display:none;" id="form_table_target">
			<input type="hidden" value="" name="table_target" id="table_target"/>
			<input type="hidden" value="" name="time_delete_table" id="time_delete_table"/>
		</form>
	<?php else: ?>
		<?=print_info_box("Could not establish connection to database", 'danger');?>
	<?php endif; ?>

<?php
}
include('foot.inc');
?>

<script>
function cleanTable(tableTarget) {
	$("#table_target").val(tableTarget);
	$("#time_delete_table").val($("#timeDelete").val());
	$("#form_table_target").submit();
}
</script>
