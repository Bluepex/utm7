<?php
/*
 * teste.php
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2013 BSD Perimeter
 * Copyright (c) 2013-2016 Electric Sheep Fencing
 * Copyright (c) 2014-2022 Rubicon Communications, LLC (Netgate)
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
##|*IDENT=page-diagnostics-system-activity
##|*NAME=Diagnostics: System Activity
##|*DESCR=Allows access to the 'Diagnostics: System Activity' page
##|*MATCH=diag_system_activity.php*
##|-PRIV

require_once("guiconfig.inc");
require_once("bluepex/bp_webservice.inc");


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

$pgtitle = array(gettext("Diagnostics"), gettext("Details of the processes"));
include("head.inc");

$destravar_modal = false;
if (!empty($_POST['matarProcesso']) && !empty($_POST['senha']) && !empty($_POST['modalLiberado'])) {
	if ($_POST['senha'] == $passModal) {
		$destravar_modal = true;
		mwexec("kill -9 {$_POST['matarProcesso']}");
		print_info_box(gettext("Process finished:") . " " . $_POST['matarProcessoDescricao'] . " - PID: " . $_POST['matarProcesso'], "success");
	} else {
		print_info_box(gettext("Wrong password!"), "danger");
	}
}

if (!empty($_POST['senhaLiberar'])) {
	if ($_POST['senhaLiberar'] == $passModal) {
		$destravar_modal = true;
		print_info_box(gettext("Process table released"), "success");
	} else {
		print_info_box(gettext("Wrong password!"), "danger");
	}
}

if (!empty($_POST['deslogar'])) {
	$destravar_modal = false;
}

?>

<style>
.ajuste-th {
	color: black !important;
	background: white !important;
	border: 0px solid white !important;
	border-bottom: 1px solid gray !important;
    vertical-align: middle !important;
}
.margins-content {
	margin: 0px !important;
}
.card-ameaca, .card-system, .card-link, .card-app {
	height: auto !important;
}

.table-access th {
    background: #177bb4;
    color: #fff;
    text-align: center;
}
</style>

<div>
	<div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12 ">
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;" style="display: flex;">
			<h4><?=gettext("General")?></h4>
			<hr>
			<div style="display: flex; text-align: left;">
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;" id="getDate"></p></div>
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;" id="getUptime"></p></div>
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;" id="getTemp"></p></div>
			</div>
			<hr>
			<div style="display: flex; text-align: left;">
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;"><b><?=gettext("Model")?>:</b> <?=trim(file_get_contents("/etc/model"))?></p></div>
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;"><b><?=gettext("Serial")?>:</b> <?=trim(file_get_contents("/etc/serial"))?></p></div>
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;"><b><?=gettext("Product Key")?>:</b> <?=getProductKey()?></p></div>
			</div>
			<hr>
			<div style="display: flex; text-align: left;">
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;"><b><?=gettext("Product Name: ")?></b><?=shell_exec('sh /etc/uname_customizaded')?></p></div>
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;"><b><?=gettext("Version")?>:</b> <?=file_get_contents('/etc/version')?></p></div>
				<div style="width: 33%;"><p style="font-size: 14px; margin-left: 10px;"><b><?=gettext("Licensing Status")?>:</b> <?=trim(get_serial_status())?></p></div>
			</div>
		</div>
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">			
			<h4><?=gettext("Devices in Use")?></h4>
			<table class="table table-striped table-bordered-alerts table-access">
				<thead>
					<tr>
						<th style="text-transform:uppercase;"><?=gettext("Device")?></th>
						<th style="text-transform:uppercase;"><?=gettext("r/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("w/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("kr/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("kw/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/r")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/w")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/o")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/t")?></th>
						<th style="text-transform:uppercase;"><?=gettext("qlen")?></th>
						<th style="text-transform:uppercase;"><?=gettext("%b")?></th>
					</tr>
				</thead>
				<tbody id="device-use">
				</tbody>
			</table>
			<hr>
			<br>
			<h4><?=gettext("All Devices")?></h4>
			<table class="table table-striped table-bordered-alerts table-access">
				<thead>
					<tr>
						<th style="text-transform:uppercase;"><?=gettext("Device")?></th>
						<th style="text-transform:uppercase;"><?=gettext("r/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("w/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("kr/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("kw/s")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/r")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/w")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/o")?></th>
						<th style="text-transform:uppercase;"><?=gettext("ms/t")?></th>
						<th style="text-transform:uppercase;"><?=gettext("qlen")?></th>
						<th style="text-transform:uppercase;"><?=gettext("%b")?></th>
					</tr>
				</thead>
				<tbody id="device-all">
				</tbody>
			</table>
			<hr style="margin-top:0px;">
			<div class="infoblock">
				<div class="alert alert-info clearfix" role="alert">
					<div class="pull-left">
						<p>Descritivo das colunas:</p>
						<ul>
							<li>r/s: Leitura/segundo</li>
							<li>w/s: Escrita/segundo</li>
							<li>kr/s: kilobytes lidos/segundo</li>
							<li>kw/s: kilobytes gravados/segundo</li>
							<li>ms/r: tempo médio (milissegundos)/leitura</li>
							<li>ms/w: tempo médio (milissegundos)/gravação</li>
							<li>ms/o: tempo médio (milissegundos)/requisição</li>
							<li>ms/t: tempo médio (milissegundos)/transação (leitura ou gravação)</li>
							<li>qlen: Transações em fila de espera</li>
							<li>%b: Porcentagem ocupada do dispositivo.</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">
			<h4><?=gettext("Partitions - Space")?> (MB)</h4>
			<div id="getDisc" style="width: 90%;height:600px;margin:auto;"></div>
			<hr>
			<h4><?=gettext("Partitions - Inodes")?></h4>
			<div id="getDiscInode" style="width: 90%;height:600px;margin:auto;"></div>
		</div>
	</div>
	<div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12 ">
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">
			<h4><?=gettext("XML File")?> (MB)</h4>
			<div id="getSizeXML" style="width: 90%;height:160px;margin:auto;"></div>
			<div id="alertaSizeXMl" style="display:none;">
				<div class="alert alert-danger" role="alert" style="margin-bottom:0xp;" id="alarmeXML"></div>
			</div>
		</div>
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">
			<h4><?=gettext("Conexões do equipamento")?></h4>
			<div id="getAllConnectionsUTM" style="width: 90%;height:160px;margin:auto;"></div>
		</div>
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">
			<div class="col-12 margins-content pl-0 table-responsive" style="border:0px solid transparent!important;">
				<?php if (!$destravar_modal) { ?>
				<div style='margin: auto; text-align:end;'>
					<img style='width: 32px;' src='./images/bp-gear.png' onclick="mostrarModalProcessos();">
				</div>
				<?php } else { ?>
				<div style='margin: auto; text-align:end;'>
					<form action="" method="POST" style="margin: 0px;border: 0px solid transparent;">
						<input type="hidden" id="deslogar" name="deslogar">
						<button type="submit" style="background: transparent;border: 0px solid transparent;">
							<img style='width: 32px;' src='./images/bp-logout.png'>
						</button>
					</form>
				</div>
				<hr>
				<div class="col-12 px-0 margins-content-top" id="table-details-access">
					<div class="Access-table">
						<div style='display: flex;'>
							<div style='margin-right: auto;margin-bottom: 10px;'>
								<h4><?=gettext("Current system processes")?></h4>
							</div>
							<div style='margin-left: auto;margin-top: 10px;'>
								<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:200px;" id="search-firewall-app" onkeydown="searchData()" onkeyup="searchData()" placeholder="<?=gettext("Search for...")?>">
							</div>
						</div>
						<div class="container col-sm-12 pl-0 table-responsive" style="height:218px;">
							<table id="table-access" class="table table-striped table-bordered-alerts">
								<thead>
									<tr>
										<th><?=gettext("PID")?></th>
										<th><?=gettext("Memory")?></th>
										<th><?=gettext("CPU")?></th>
										<th><?=gettext("Process")?></th>
										<th><?=gettext("Action")?></th>
									</tr>
								</thead>
								<tbody id="geral-table">
								</tbody>
							</table>
						</div>
					</div>
				</div>
				<?php } ?>
			</div>
			<input type="hidden" id="processName" name="processName"/>
			<input type="hidden" id="pidProcess" name="pidProcess"/>
			<hr>
			<h4><?=gettext("Hardware health")?></h4>
			<div id="loadCPU" style="width: 100%;height:240px;margin:auto;"></div>
			<div id="alertaLoadCCPU" style="display:none;">
				<div class="alert alert-danger" role="alert" style="margin-bottom:0xp;" id="alarmeLoadCPU"></div>
			</div>		
			<hr id="sumirGetCPUUse1">
			<h4 id="sumirGetCPUUse2"><?=gettext("CPU usage")?></h4>
			<div id="getCPUUse" style="width: 100%;height:120px;margin:auto;"></div>
			<hr>
			<h4><?=gettext("Processes running")?></h4>
			<div id="getInfoSys" style="width: 100%;height:120px;margin:auto;"></div>
		</div>
	</div>
	<div class="pl-0 pr-0 pr-xl-6 col-xl-6 col-md-12 col-sm-12 ">
		<div class="card-system p-3 mb-sm-3" style="margin-right: 10px;">
			<h4><?=gettext("General system memory")?></h4>
			<div id="getMEM" style="width: 90%;height:120px;margin:auto;"></div>
			<hr>
			<h4><?=gettext("Memory in use")?></h4>
			<div id="getMemoryUse" style="width: 90%;height:120px;margin:auto;"></div>
			<hr>
			<h4><?=gettext("Use of SWAP")?></h4>
			<div id="getMemorySwap" style="width: 90%;height:120px;margin:auto;"></div>
			<hr>
			<h4><?=gettext("Info of SWAP Partitions")?></h4>
			<div id="getMemorySwapInfo" style="width: 90%;height:120px;margin:auto;"></div>
		</div>
	</div>

	<?php if (!$destravar_modal): ?>

	<div class="modal fade" id="mosgtrarModalprocessos" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalCentralizado"><?=gettext("Release process table")?> </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="POST" action="" style="margin-top: 10px !important;border:0px solid transparent;">
					<div class="form-row">
						<div class="col">					
							<label for="recipient-name" class="col-form-label"><?=gettext("Enter password:")?></label>
							<input type="password" class="form-control" id="senhaLiberar" name="senhaLiberar" maxlength="50">
						</div>
					</div>
					<hr>
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Close")?></button>
					<button type="submit" name="confirmar_senha_liberar_modal" id="confirmar_senha_liberar_modal" class="btn btn-primary"><?=gettext("Continue")?></button>
				</form>
			</div>
			</div>
		</div>
	</div>

	<?php endif; ?>

	<div class="modal fade" id="modalMatarProcesso" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalCentralizado"><?=gettext("Kill process:")?> </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<form method="POST" action="" style="margin-top: 10px !important;border:0px solid transparent;">
					<div class="form-row">
						<div class="col">
							<input type="hidden" value="" id="matarProcesso" name="matarProcesso">
							<input type="hidden" value="" id="matarProcessoDescricao" name="matarProcessoDescricao">
							<input type="hidden" value="<?=$destravar_modal?>" id="modalLiberado" name="modalLiberado">
							<label for="recipient-name" class="col-form-label"><?=gettext("Enter password:")?></label>
							<input type="password" class="form-control" id="senha" name="senha" maxlength="50">
						</div>
					</div>
					<hr>
					<button type="button" class="btn btn-secondary" data-dismiss="modal"><?=gettext("Close")?></button>
					<button type="submit" name="confirmar_senha" id="confirmar_senha" class="btn btn-primary"><?=gettext("Continue")?></button>
				</form>
			</div>
			</div>
		</div>
	</div>

	<div class="modal fade" id="mostrarDetalhesProcesso" tabindex="-1" role="dialog" aria-labelledby="TituloModalCentralizado" aria-hidden="true">
	<div class="modal-dialog modal-dialog-centered" role="document">
		<div class="modal-content" style="font-size: 14px;">
			<div class="modal-header">
				<h5 class="modal-title" id="TituloModalDescriptProcess"><?=gettext("Description")?>: </h5>
				<button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
			<div class="modal-body">
				<div class="form-row">
					<div class="col">
						<div id="id1"></div>
						<div id="id2"></div>
						<div id="id3"></div>
						<div id="id4"></div>
						<div id="id5"></div>
						<div id="id6"></div>
						<div id="id7"></div>
						<div id="id8"></div>
						<div id="id9"></div>
						<div id="id10"></div>
						<div id="id11"></div>
						<div id="id12"></div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
</main>

<script src="/js/jquery-3.1.1.min.js?v=<?=filemtime('/usr/local/www/js/jquery-3.1.1.min.js')?>"></script>
<?php if (file_exists('/usr/local/www/vendor/echarts/dist/echarts.min.js')): ?>
<script src="/vendor/echarts/dist/echarts.min.js?v=<?=filemtime('/usr/local/www/vendor/echarts/dist/echarts.min.js')?>"></script>
<?php
endif;
if (file_exists('/usr/local/www/vendor/echarts/echarts.min.js')): 
?>
<script src="/vendor/echarts/echarts.min.js?v=<?=filemtime('/usr/local/www/vendor/echarts/echarts.min.js')?>"></script>
<?php endif; ?>
<script src="/js/echarts/map/js/world.js?v=<?=filemtime('/usr/local/www/js/echarts/map/js/world.js')?>"></script>
<script src="/js/traffic-graphs.js?v=<?=filemtime('/usr/local/www/js/traffic-graphs.js')?>"></script>
<script>

let colorPalette = ['#b82738','#e27f22', '#e1b317', '#86ae4e', '#007dc5'];


function mostrarModalProcessos() {
	$('#mosgtrarModalprocessos').modal('show');
}

function convertValueToMB(valor) {
	if (valor.substr(-1) == "K") {
		return parseFloat(parseInt(valor.split("K")[0])/1024).toFixed(2);
	} else if (valor.substr(-1) == "G") {
		return parseFloat(parseFloat(valor.split("G")[0])*1024).toFixed(2);	
	} else {
		return parseFloat(parseInt(valor.split("M")[0])).toFixed(2);
	}
}

function searchData() {
	var $rows = $('#table-access #geral-table tr');
	var val = $.trim($('#search-firewall-app').val()).replace(/ +/g, ' ').toLowerCase();
	$rows.show().filter(function() {
		var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
		return !~text.indexOf(val);
	}).hide();
}

function killProcess(descricao, valor) {
	$('#mostrarDetalhesProcesso').modal('hide');
	$.post("./bar_post_use_system.php", {'process': descricao, 'pid': valor}, function(data) {
		valores = JSON.parse(data);
		if ((valor > 0) && (descricao.length > 0) && (valores.length > 0)) {
			setTimeout(() => {
				$('#modalMatarProcesso').modal('show');
				document.getElementById('TituloModalCentralizado').innerHTML="<?=gettext("Kill process:")?> " + descricao + " - " + valor;
				document.getElementById('matarProcesso').value=valor;
				document.getElementById('matarProcessoDescricao').value=descricao;
			}, 500);
		} else {
			$('#mostrarDetalhesProcesso').modal('show');
			document.getElementById('TituloModalDescriptProcess').innerHTML="<?=gettext("Description:")?> " + descricao + " - " + valor;
			document.getElementById("id1").innerHTML="<p><?=gettext("Process already completed")?></p>";
			document.getElementById("id2").innerHTML="";
			document.getElementById("id3").innerHTML="";
			document.getElementById("id4").innerHTML="";
			document.getElementById("id5").innerHTML="";
			document.getElementById("id6").innerHTML="";
			document.getElementById("id7").innerHTML="";
			document.getElementById("id8").innerHTML="";
			document.getElementById("id9").innerHTML="";
			document.getElementById("id10").innerHTML="";
			document.getElementById("id11").innerHTML="";
			document.getElementById("id12").innerHTML="";
			document.getElementById('processName').value="";
			document.getElementById('pidProcess').value="";
		}
	});
}


tempo_geral = 5000;

function detailProcess(process, pid) {
	document.getElementById('processName').value=process;
	document.getElementById('pidProcess').value=pid;
	$('#mostrarDetalhesProcesso').modal('show');
	getProcesstarget();
}

function getProcesstarget() {
	var process = document.getElementById('processName').value;
	var pid = document.getElementById('pidProcess').value;

	$.post("./bar_post_use_system.php", {'process': process, 'pid': pid}, function(data) {
		valores = JSON.parse(data);
		if ((process.length > 0) && (pid.length > 0) && (valores.length > 0)) {
			document.getElementById('TituloModalDescriptProcess').innerHTML="<?=gettext("Description:")?> " + process + " - " + pid;
			document.getElementById("id1").innerHTML="<p><strong><?=gettext("PID:")?></strong> " + valores[0] + "</p>";
			document.getElementById("id2").innerHTML="<p><strong><?=gettext("Username:")?></strong> " + valores[1] + "</p>";
			document.getElementById("id3").innerHTML="<p><strong><?=gettext("Threads:")?></strong> " + valores[2] + "</p>";
			document.getElementById("id4").innerHTML="<p><strong><?=gettext("Priority")?>:</strong> " + valores[3] + "</p>";
			document.getElementById("id5").innerHTML="<p><strong><?=gettext("Nice:")?></strong> " + valores[4] + "</p>";
			document.getElementById("id6").innerHTML="<p><strong><?=gettext("Total process memory:")?></strong> " + convertValueToMB(valores[5]) + "MB</p>";
			document.getElementById("id7").innerHTML="<p><strong><?=gettext("Memory in use:")?></strong> " + convertValueToMB(valores[6]) + "MB</p>";
			document.getElementById("id8").innerHTML="<p><strong><?=gettext("State")?>:</strong> " + valores[7] + "</p>";
			document.getElementById("id9").innerHTML="<p><strong><?=gettext("CPU time:")?></strong> " + valores[9] + "</p>";
			document.getElementById("id10").innerHTML="<p><strong><?=gettext("CPU Usage:")?></strong> " + valores[10] + "</p>";
			commandStatus = [];
			for(i=11;i<=valores.length;i++) {
				commandStatus.push(valores[i]);
			}
			document.getElementById("id11").innerHTML="<p><strong><?=gettext("Command:")?></strong> " + commandStatus.join(" ") + "</p>";
			document.getElementById("id12").innerHTML="<button class='btn btn-danger' style='margin: 5px;' onclick='killProcess(\"" + process + "\"," + pid + ")'><?=gettext("Kill process")?></button>";
		} else {
			document.getElementById('TituloModalDescriptProcess').innerHTML="<?=gettext("Description:")?> " + process + " - " + pid;
			document.getElementById("id1").innerHTML="<p><?=gettext("Process already completed")?></p>";
			document.getElementById("id2").innerHTML="";
			document.getElementById("id3").innerHTML="";
			document.getElementById("id4").innerHTML="";
			document.getElementById("id5").innerHTML="";
			document.getElementById("id6").innerHTML="";
			document.getElementById("id7").innerHTML="";
			document.getElementById("id8").innerHTML="";
			document.getElementById("id9").innerHTML="";
			document.getElementById("id10").innerHTML="";
			document.getElementById("id11").innerHTML="";
			document.getElementById("id12").innerHTML="";
			document.getElementById('processName').value="";
			document.getElementById('pidProcess').value="";
		}
	});
	setTimeout(getProcesstarget, tempo_geral);
}

events.push(function(){

	function gerarTop() {
		$.post("./bar_post_use_system.php", 'gerarTop');
		setTimeout(gerarTop, tempo_geral);
	}
	/*function getCPU() {
		$.post("./bar_post_use_system.php", 'getCPU', function(data) {
			$('#xhrOutput1').text(data);
		}).fail(function () {
			console.log("test");
		});
	}*/
	function getMEM() {
		$.post("./bar_post_use_system.php", 'getMEM', function(data) {
			//$('#xhrOutput2').text(data);
			memorias = ["<?=gettext("Recognized memory")?>", "<?=gettext("Memory mapped")?>"];
			contador=0;
			limite = 0;
			series_teste = [];

			mapeado = JSON.parse(data);
			resto_livre = parseInt(mapeado[1]) - parseInt(mapeado[0]);
				
			var chartDom = document.getElementById('getMEM');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				tooltip: {
					trigger: 'axis',
					axisPointer: {
						type: 'shadow'
					}
				},
				legend: {},
				grid: {
					left: '1%',
					right: '1%',
					bottom: '1%',
					containLabel: true
				},
				xAxis: {
					type: 'value',
					data: limite
				},
				yAxis: {
					type: 'category',
					data: ["<?=gettext("Memory")?> (MB)"]
				},
				series: [
					{
						name: memorias[1],
						type: 'bar',
						data: [parseFloat(parseInt(((parseInt(resto_livre)/1024)/1024))).toFixed(2)],
						itemStyle: {
                    	    color: colorPalette[1]
                    	}
					},
					{
						name: memorias[0],
						type: 'bar',
						data: [parseFloat(parseInt(((parseInt(mapeado[0])/1024)/1024))).toFixed(2)],
						itemStyle: {
                    	    color: colorPalette[3]
                    	}
					}
				] 
			};

			option && myChart.setOption(option);

				
		}).fail(function () {
			console.log("test");
		});
		setTimeout(getMEM, tempo_geral);
	}
	function getInfoSys() {
		$.post("./bar_post_use_system.php", 'getInfoSys', function(data) {
			//$('#xhrOutput3').text(data);

			var tipos = [];
			var series_teste = [];
			var qtdProcessos = 0;
			var counter=0;
			JSON.parse(data).map(function(valores) {
				numero = valores.split(" ")[0];
				tipo = valores.split(" ")[1];
				tipos.push(tipo);
				qtdProcessos += numero; 
				series_teste.push({
					name: tipo,
					type: 'bar',
					stack: 'total',
					label: {
						show: true
					},
					emphasis: {
						focus: 'series'
					},
					data: [numero],
					itemStyle: {
                        color: colorPalette[counter]
                    }
				});
				counter+=1;
			});

			var chartDom = document.getElementById('getInfoSys');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				tooltip: {
					trigger: 'axis',
					axisPointer: {
						type: 'shadow'
					}
				},
				legend: {},
				grid: {
					left: '1%',
					right: '1%',
					bottom: '1%',
					containLabel: true
				},
				xAxis: {
					type: 'value',
					data: qtdProcessos
				},
				yAxis: {
					type: 'category',
					data: ["<?=gettext("Current system processes")?>"]
				},
				series: series_teste 
			};

			option && myChart.setOption(option);



		}).fail(function () {
			console.log("test");
		});

		setTimeout(getInfoSys, tempo_geral);
	}
	function getMemoryUse() {
		$.post("./bar_post_use_system.php", 'getMemoryUse', function(data) {
			//$('#xhrOutput4').text(data);
			var tipos = [];
			var series_teste = [];
			var qtdProcessos = 0;
			var counter = 0;
			JSON.parse(data).map(function(valores) {
				numero = convertValueToMB(valores.split(" ")[0]);
				tipo = valores.split(" ")[1];
				tipos.push(tipo);
				qtdProcessos += numero; 
				series_teste.push({
					name: tipo,
					type: 'bar',
					stack: 'total',
					label: {
						show: true
					},
					emphasis: {
						focus: 'series'
					},
					data: [numero],
					itemStyle: {
                        color: colorPalette[counter]
                    }
				});
				counter+=1;
			});

			var chartDom = document.getElementById('getMemoryUse');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				tooltip: {
					trigger: 'axis',
					axisPointer: {
						type: 'shadow'
					}
				},
				legend: {},
				grid: {
					left: '1%',
					right: '1%',
					bottom: '1%',
					containLabel: true
				},
				xAxis: {
					type: 'value',
					data: qtdProcessos
				},
				yAxis: {
					type: 'category',
					data: ["<?=gettext("Current system processes")?> (MB):"]
				},
				series: series_teste 
			};

			option && myChart.setOption(option);

		}).fail(function () {
			console.log("test");
		});
		setTimeout(getMemoryUse, tempo_geral);
	}
	function getMemorySwap() {
		$.post("./bar_post_use_system.php", 'getMemorySwap', function(data) {
			//$('#xhrOutput5').text(data);	


			var tipos = [];
			var series_teste = [];
			var qtdProcessos = 0;
			var swapUse = ["<?=gettext("Swap in use")?>", "<?=gettext("Free swap")?>"];

			valores = JSON.parse(data);

			totalSwap = convertValueToMB(valores[0]);
			totalLivre = convertValueToMB(valores[valores.length-2]);
			totalUsado = totalSwap - totalLivre;

			var chartDom = document.getElementById('getMemorySwap');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				tooltip: {
					trigger: 'axis',
					axisPointer: {
						type: 'shadow'
					}
				},
				legend: {},
				grid: {
					left: '1%',
					right: '1%',
					bottom: '1%',
					containLabel: true
				},
				xAxis: {
					type: 'value',
					data: totalSwap
				},
				yAxis: {
					type: 'category',
					data: ["Swap (MB):"]
				},
				series: [
					{
						name: swapUse[0],
						type: 'bar',
						stack: 'total',
						label: {
							show: true
						},
						emphasis: {
							focus: 'series'
						},
						data: [totalUsado],
						itemStyle: {
                        	color: colorPalette[1]
                    	}
					}, 
					{
						name: swapUse[1],
						type: 'bar',
						stack: 'total',
						label: {
							show: true
						},
						emphasis: {
							focus: 'series'
						},
						data: [totalLivre],
						itemStyle: {
                        	color: colorPalette[3]
                    	}
					}
				] 			
			};

			option && myChart.setOption(option);

		}).fail(function () {
			console.log("test");
		});
		setTimeout(getMemorySwap, tempo_geral);
	}
	function getMemorySwapInfo() {
		$.post("./bar_post_use_system.php", 'getMemorySwapInfo', function(data) {

			var swapNames = [];
			var espacoEmUso = [];
			var espacoTotal = [];
			var espacoLivre = [];
			JSON.parse(data).map(function(valores) {
				swapNames.push(valores.split("---")[0]);
				espacoTotal.push(valores.split("---")[1]);
				espacoEmUso.push(valores.split("---")[2]);
				espacoLivre.push(valores.split("---")[3]);
			});

			var chartDom = document.getElementById('getMemorySwapInfo');
			var myChart = echarts.init(chartDom);
			var option;

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: swapNames.reverse()
				},
				series: [
					{
						name: 'Swap Em Uso (MB)',
						type: 'bar',
						data: espacoEmUso.reverse(),
						itemStyle: {
                        	color: colorPalette[0]
                    	}
					},
					{
						name: 'Swap Livre (MB)',
						type: 'bar',
						data: espacoLivre.reverse(),
						itemStyle: {
                        	color: colorPalette[4]
                    	}
					},
					{
						name: 'Swap Total (MB)',
						type: 'bar',
						data: espacoTotal.reverse(),
						itemStyle: {
                        	color: colorPalette[3]
                    	}
					}

				]
			};

			option && myChart.setOption(option);

		}).fail(function () {
			console.log("test");
		});
		setTimeout(getMemorySwapInfo, tempo_geral);
	}
	function getCPUUse() {
		$.post("./bar_post_use_system.php", 'getCPUUse', function(data) {
			//$('#xhrOutput6').text(data);
			var tipos = [];
			var series_teste = [];
			var porcentagem = [];
			var porcentagemMaxima = 100;
			var count = 0;	

			if (JSON.parse(data)[0].length > 0) {
				document.getElementById('getCPUUse').style.display="block";
				document.getElementById('sumirGetCPUUse1').style.display="block";
				document.getElementById('sumirGetCPUUse2').style.display="block";
				JSON.parse(data).map(function(valores) {
					porcentagem = valores.split("% ")[0];
					tipo = valores.split("% ")[1];
					if (tipo != "idle") {
						tipos.push(tipo);
						series_teste.push({
							name: [tipo],
							type: 'bar',
							stack: 'total',
							label: {
								show: true
							},
							emphasis: {
								focus: 'series'
							},
							data: [porcentagem],
							itemStyle: {
                        		color: colorPalette[count]
                    		}
						});
					}
					count+=1;
				});	
				var chartDom = document.getElementById('getCPUUse');
				var myChart = echarts.init(chartDom);
				var option;	
				option = {
					tooltip: {
						trigger: 'axis',
						axisPointer: {
							type: 'shadow'
						}
					},
					legend: {},
					grid: {
						left: '1%',
						right: '1%',
						bottom: '1%',
						containLabel: true
					},
					xAxis: {
						type: 'value',
						data: [porcentagemMaxima]
					},
					yAxis: {
						type: 'category',
						data: ["<?=gettext("Processes in %")?>"]
					},
					series: series_teste 
				};	
				option && myChart.setOption(option);
			} else {
				document.getElementById('getCPUUse').style.display="none";
				document.getElementById('sumirGetCPUUse1').style.display="none";
				document.getElementById('sumirGetCPUUse2').style.display="none";
			}


		}).fail(function () {
			console.log("test");
		});
		setTimeout(getCPUUse, tempo_geral);
	}

	<?php if ($destravar_modal): ?>
	function getAllProcess() {
		$.post("./bar_post_use_system.php", 'getAllProcess', function(data) {
			//$('#xhrOutput7').text(JSON.parse(data));
			document.getElementById('geral-table').innerHTML = "";
			colunas = [];
			todosprocessos = JSON.parse(data);
			todosprocessos.map(function(valores) {
				if ((valores[0].length > 0) && (valores[3].length > 0)) {
					pid = valores[0];
					if (pid != "PID") {
						comando = valores[3];
						if ((comando.substr(0, 1) == "<") && (comando.substr(comando.length-1, 1) == ">")) {
							comando = comando.substr(1, comando.length-2);
						}
						colunas.push("<tr>" + 
						"<th class='ajuste-th'>" + pid + "</th >" + 
						"<th class='ajuste-th'>" + convertValueToMB(valores[1]) + "MB</th>" + 
						"<th class='ajuste-th'>" + valores[2] + "%</th>" + 
						"<th class='ajuste-th'>" + comando + "</th>" + 
						"<th class='ajuste-th'>" + 
						"<button class='btn btn-danger' style='margin: 5px;' onclick='killProcess(\"" + comando + "\"," + pid + ")'><?=gettext("Kill process")?></button>" + 
						"<button class='btn btn-primary' style='margin: 5px;' onclick='detailProcess(\"" + comando + "\"," + pid + ")'><?=gettext("Process details")?></button>" + 
						"</th>" + 
						"</tr>");
					}
				}
			});
			document.getElementById('geral-table').innerHTML = colunas.join('');
			searchData();
		}).fail(function () {
			console.log("test");
		});
		setTimeout(getAllProcess, tempo_geral);
	}
	<?php endif; ?>

	function getDisc() {
		$.post("./bar_post_use_system.php", 'getDisc', function(data) {
			//$('#xhrOutput8').text(data);
			
			var chartDom = document.getElementById('getDisc');
			var myChart = echarts.init(chartDom);
			var option;

			jsonAllValues = JSON.parse(data);
			valuesData = [];
			espacoLivre = [];
			espacoUsado = [];
			jsonAllValues.map(function(valores) {
				valuesData.push(valores[0] + " - " + valores[8]);
				espacoLivre.push(convertValueToMB(valores[1]))
				espacoUsado.push(convertValueToMB(valores[2]));
			});

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: valuesData.reverse()
				},
				series: [
					{
						name: "<?=gettext("Used Space")?>",
						type: 'bar',
						data: espacoUsado.reverse(),
						itemStyle: {
                    	    color: colorPalette[1]
                    	}
					},
					{
						name: "<?=gettext("Free Space")?>",
						type: 'bar',
						data: espacoLivre.reverse(),
						itemStyle: {
                    	    color: colorPalette[3]
                    	}
					}
				]
			};

			option && myChart.setOption(option);

		}).fail(function () {
			console.log("test");
		});
		setTimeout(getDisc, tempo_geral);
	}
	function getDiscInode() {
		$.post("./bar_post_use_system.php", 'getDiscInode', function(data) {
			//$('#xhrOutput8').text(data);
			
			var chartDom = document.getElementById('getDiscInode');
			var myChart = echarts.init(chartDom);
			var option;

			jsonAllValues = JSON.parse(data);
			valuesData = [];
			espacoLivre = [];
			espacoUsado = [];
			jsonAllValues.map(function(valores) {
				valuesData.push(valores[0] + " - " + valores[8]);
				espacoUsado.push(valores[5]);
				espacoLivre.push(valores[6])
			});

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: valuesData.reverse()
				},
				series: [
					{
						name: 'Used Inode',
						type: 'bar',
						data: espacoUsado.reverse(),
						itemStyle: {
                    	    color: colorPalette[1]
                    	}
					},
					{
						name: 'Free Inode',
						type: 'bar',
						data: espacoLivre.reverse(),
						itemStyle: {
                    	    color: colorPalette[3]
                    	}
					}

				]
			};

			option && myChart.setOption(option);

		}).fail(function () {
			console.log("test");
		});
		setTimeout(getDiscInode, tempo_geral);
	}
	function alertaSizeXML($arquivo) {
		if ($arquivo >= 1024) {
			document.getElementById('alertaSizeXMl').style.display="block";

			document.getElementById('alarmeXML').removeAttribute("class");
			document.getElementById('alarmeXML').setAttribute("class", "alert alert-warning");
			document.getElementById('alarmeXML').style="display: block;margin-bottom: 0px;margin-top:20px;text-align: center;border-radius:15px;font-size:16px"
			document.getElementById('alarmeXML').innerHTML="<?=gettext("File is too big")?>";
			if ($arquivo >= 1536) {
				document.getElementById('alarmeXML').removeAttribute("class");
				document.getElementById('alarmeXML').setAttribute("class", "alert alert-danger");
				document.getElementById('alarmeXML').style="display: block;margin-bottom: 0px;margin-top:20px;text-align: center;border-radius:15px;font-size:16px"
				document.getElementById('alarmeXML').innerHTML="<?=gettext("File is unusually large")?>";
			}
		} else {
			document.getElementById('alertaSizeXMl').style.display="none";
		}
	}
	function getSizeXML() {
		$.post("./bar_post_use_system.php", 'getSizeXML', function(data) {

			var chartDom = document.getElementById('getSizeXML');
			var myChart = echarts.init(chartDom);
			var option;

			var tamanho_arquivo = parseFloat((JSON.parse(data)/1024)/1024).toFixed(2);
			alertaSizeXML(tamanho_arquivo);

			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("XML Size")?>"]
				},
				series: [
					{
						name: "<?=gettext("Current file size:")?>",
						type: 'bar',
						data: [tamanho_arquivo],
						itemStyle: {
                    	    color: colorPalette[1]
                    	}
					},
					{
						name: "<?=gettext("Recommended limit size:")?>",
						type: 'bar',
						data: ["2048"],
						itemStyle: {
                    	    color: colorPalette[3]
                    	}
					}
				]
			};

			option && myChart.setOption(option);


		}).fail(function () {
			console.log("test");
		});
		setTimeout(getSizeXML, tempo_geral);
	}
	function alertaLoadCCPU(loadCPU) {
		if (loadCPU >= 2.0) {
			document.getElementById('alertaLoadCCPU').style.display="block";
			document.getElementById('alarmeLoadCPU').removeAttribute("class");
			document.getElementById('alarmeLoadCPU').setAttribute("class", "alert alert-warning");
			document.getElementById('alarmeLoadCPU').style="display: block;margin-bottom: 0px;margin-top:20px;text-align: center;border-radius:15px;font-size:16px"
			document.getElementById('alarmeLoadCPU').innerHTML="<?=gettext("System is under high load")?><br>P.S: CPU <?=gettext("Load Avarege")?> >= 2.0";
			if (loadCPU >= 5.0) {
				document.getElementById('alarmeLoadCPU').removeAttribute("class");
				document.getElementById('alarmeLoadCPU').setAttribute("class", "alert alert-danger");
				document.getElementById('alarmeLoadCPU').style="display: block;margin-bottom: 0px;margin-top:20px;text-align: center;border-radius:15px;font-size:16px"
				document.getElementById('alarmeLoadCPU').innerHTML="<?=gettext("System is under huge load")?><br>P.S: CPU <?=gettext("Load Avarege")?> >= 5.0";
			}
		} else {
			document.getElementById('alertaLoadCCPU').style.display="none";
		}
	}
	function loadCPU() {
		$.post("./bar_post_use_system.php", 'loadCPU', function(data) {

			var chartDom = document.getElementById('loadCPU');
			var myChart = echarts.init(chartDom);
			var option;

			var valoresLoad = JSON.parse(data);

			alertaLoadCCPU(valoresLoad[0])
			
			option = {
				title: {
				  	text: ""
				},
				tooltip: {
				  	trigger: 'axis',
				  	axisPointer: {
				    	type: 'shadow'
				  	}
				},
				legend: {},
				grid: {
				  	left: '1%',
				  	right: '1%',
				  	bottom: '1%',
				  	containLabel: true
				},
				xAxis: {
				  	type: 'value',
				  	boundaryGap: [0, 0.01]
				},
				yAxis: {
				  	type: 'category',
				  	data: ["<?=gettext("Load Avarege")?>"]
				},
				series: [
					{
						name: "1 <?=gettext("Minute")?>",
						type: 'bar',
						data: [valoresLoad[0]],
						itemStyle: {
                    	    color: colorPalette[0]
                    	}
					},
					{
						name: "5 <?=gettext("Minutes")?>",
						type: 'bar',
						data: [valoresLoad[1]],
						itemStyle: {
                    	    color: colorPalette[1]
                    	}
					},
					{
						name: "15 <?=gettext("Minutes")?>",
						type: 'bar',
						data: [valoresLoad[2]],
						itemStyle: {
                    	    color: colorPalette[2]
                    	}
					}
				]
			};

			option && myChart.setOption(option);


		}).fail(function () {
			console.log("test");
		});
		setTimeout(loadCPU, tempo_geral);
	}
	function getStatusUsedDevices(){
		var returnTable = "";
		$.post("./bar_post_use_system.php", 'getStatusUsedDevices', function(data) {
			var tableData = JSON.parse(data);
			tableData = tableData.split("_break");
			for(var count=0;count<=tableData.length-2;count++) {
				var valuesTableData = tableData[count].split("___");
				returnTable += "<tr>";
				returnTable += "<td>" + valuesTableData[0] + "</td>";
				returnTable += "<td>" + valuesTableData[1] + "</td>";
				returnTable += "<td>" + valuesTableData[2] + "</td>";
				returnTable += "<td>" + valuesTableData[3] + "</td>";
				returnTable += "<td>" + valuesTableData[4] + "</td>";
				returnTable += "<td>" + valuesTableData[5] + "</td>";
				returnTable += "<td>" + valuesTableData[6] + "</td>";
				returnTable += "<td>" + valuesTableData[7] + "</td>";
				returnTable += "<td>" + valuesTableData[8] + "</td>";
				returnTable += "<td>" + valuesTableData[9] + "</td>";
				returnTable += "<td>" + valuesTableData[10] + "</td>";
				returnTable += "</tr>";
			}
			$("#device-use").html(returnTable);
		});
		setTimeout(getStatusAllDevices, tempo_geral);
	}
	function getStatusAllDevices(){
		var returnTable = "";
		$.post("./bar_post_use_system.php", 'getStatusAllDevices', function(data) {
			var tableData = JSON.parse(data);
			tableData = tableData.split("_break");
			for(var count=0;count<=tableData.length-2;count++) {
				var valuesTableData = tableData[count].split("___");
				returnTable += "<tr>";
				returnTable += "<td>" + valuesTableData[0] + "</td>";
				returnTable += "<td>" + valuesTableData[1] + "</td>";
				returnTable += "<td>" + valuesTableData[2] + "</td>";
				returnTable += "<td>" + valuesTableData[3] + "</td>";
				returnTable += "<td>" + valuesTableData[4] + "</td>";
				returnTable += "<td>" + valuesTableData[5] + "</td>";
				returnTable += "<td>" + valuesTableData[6] + "</td>";
				returnTable += "<td>" + valuesTableData[7] + "</td>";
				returnTable += "<td>" + valuesTableData[8] + "</td>";
				returnTable += "<td>" + valuesTableData[9] + "</td>";
				returnTable += "<td>" + valuesTableData[10] + "</td>";
				returnTable += "</tr>";
			}
			$("#device-all").html(returnTable);
		});
		setTimeout(getStatusAllDevices, tempo_geral);
	}
	
	function getDate(){
		$.post("./bar_post_use_system.php", 'getDate', function(data) {
			$("#getDate").html("<b>Data:</b> " + data);
		});
		setTimeout(getDate, tempo_geral);
	}

	function getUptime(){
		$.post("./bar_post_use_system.php", 'getUptime', function(data) {
			$("#getUptime").html("<b>Uptime:</b> " + data);
		});
		setTimeout(getUptime, tempo_geral);
	}

	function getTemp(){
		$.post("./bar_post_use_system.php", 'getTemp', function(data) {
			$("#getTemp").html("<b>Temperatura(CPU):</b> " + data);
		});
		setTimeout(getTemp, tempo_geral);
	}

	function getAllConnectionsUTM() {
		$.post("./bar_post_use_system.php", 'getAllConnectionsUTM', function(data) {

			var chartDom = document.getElementById('getAllConnectionsUTM');
			var myChart = echarts.init(chartDom);
			var option;

			var connections_all = JSON.parse(data);
			
			option = {
				title: {
					text: ""
				},
				tooltip: {
					trigger: 'axis',
					axisPointer: {
						type: 'shadow'
					}
				},
				legend: {},
				grid: {
					left: '1%',
					right: '1%',
					bottom: '1%',
					containLabel: true
				},
				xAxis: {
					type: 'value',
					boundaryGap: [0, 0.01]
				},
				yAxis: {
					type: 'category',
					data: ["<?=gettext("Conexões do equipamento")?>"]
				},
				series: [
					{
						name: "<?=gettext("Quantidade de conexões totais (Implícitos)")?>",
						type: 'bar',
						data: [connections_all[0]],
						itemStyle: {
							color: colorPalette[0]
						}
					},
					{
						name: "<?=gettext("Quantidade de conexões únicas (Implícitos)")?>",
						type: 'bar',
						data: [connections_all[1]],
						itemStyle: {
							color: colorPalette[1]
						}
					},
					{
						name: "<?=gettext("Quantidade de hosts detectado pelo ARP")?>",
						type: 'bar',
						data: [connections_all[2]],
						itemStyle: {
							color: colorPalette[2]
						}
					},
					{
						name: "<?=gettext("Capacidade de hosts recomendado para o modelo")?>",
						type: 'bar',
						data: [connections_all[3]],
						itemStyle: {
							color: colorPalette[3]
						}
					}
				]
			};

			option && myChart.setOption(option);


			}).fail(function () {
				console.log("test");
			});

		setTimeout(getAllConnectionsUTM, tempo_geral);
	}


	gerarTop()
	getMEM();
	getInfoSys();
	getMemoryUse();
	getMemorySwap();
	getMemorySwapInfo();
	getCPUUse();
	<?php if ($destravar_modal): ?>
	getAllProcess();
	<?php endif; ?>
	getDisc();
	getDiscInode();
	getSizeXML();
	loadCPU();
	getStatusUsedDevices();
	getStatusAllDevices();
	getDate();
	getUptime();
	getTemp();
	getAllConnectionsUTM();

});


</script>
<?php include("foot.inc"); ?>
