<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Guilherme R. Brechot <guilherme.brechot@bluepex.com>, 2022
 *
 * ====================================================================
 *
 */
require_once('guiconfig.inc');
require_once('functions.inc');
require_once('notices.inc');
require_once("pkg-utils.inc");
require_once("util.inc");
require("config.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata_acp.inc");

$pgtitle = array(gettext("Services"), gettext("FirewallApp"), gettext("Blocks in performance mode"));
$pglinks = array("", "/firewallapp/services.php", "@self");
include("head.inc");

if (!is_array($config['installedpackages']['suricata']['rule'])) {
	$config['installedpackages']['suricata']['rule'] = array();
}

?>

<style>
	table { background-color:#fff }
	table tr:hover { background-color:#f9f9f9 }
	.btn-disabled { opacity:0.3; }
	.checked { opacity:1 }
	.btn-group-vertical > .btn.active,
	.btn-group-vertical > .btn:active,
	.btn-group-vertical > .btn:focus,
	.btn-group-vertical > .btn:hover,
	.btn-group > .btn.active,
	.btn-group > .btn:active,
	.btn-group > .btn:focus,
	.btn-group > .btn:hover { outline:none }
	.btn-group .btn { margin:0 }
	.panel .panel-body { padding:10px }

	.btn-primary:focus {
		background-color: #286090 !important;
		border-color: transparent !important;
	}

	.status-running {
		color: #43A047;
		font-weight: bold;
		font-size: 14px;
	}

	.status-stopped {
		color:	#f00;
		font-weight: bold;
		font-size: 14px;
	}

	.step-content {
    	padding: unset !important;
	}

	#table-access th {
		background: white !important;
		color: black !important;
	}

	.find-values {
    	margin-right: 10px;
    	height: 40px;
    	border-radius: 10px;
	}

	tbody#geral-table th {
    	vertical-align: middle;
	}
	
	.form-control[disabled], .form-control[readonly], fieldset[disabled] .form-control {
		background-color: #286090 !important;
		opacity: 0.5 !important;
	}

</style>
<?php $show_alert = true; ?>
<?php if (file_exists("/etc/performance_extends")): ?>
	<?php if (trim(file_get_contents("/etc/performance_extends")) == "true"): ?>

		<div class="infoblock">
			<div class="alert alert-info clearfix" role="alert">
				<div class="pull-left">
					<p>Página de demonstração dos endereços detectado e em bloqueio no modo de "Com exceções";</p>
					<br>
					<p>Esta tela se atualiza de tempo em tempos, neste caso, não necessita recarrega-la, caso queira que as informações sejam listadas de no momento, clique no botão de "reload" no campo de pesquisa.</p>
					<br>
					<p>informativo:</p>
					<p><i class='fa fa-search' aria-hidden='true' title='Procurar pela regra/Procurar pela tabela'></i> - Este ícone é apresentado tanto pela procura de uma ocorrência em tabela pelo campo de pesquisa, quanto por procurar regras correspondentes a esta ID;</p>
					<p><i class='fa fa-refresh' aria-hidden='true' title='Atualiza informações listadas'></i> - Atualiza as informações listadas na tabela;</p>
					<p><i class='fa fa-pause' aria-hidden='true' title='Pausa a atualizações da tabela'></i>  - Pausa as atualizações da tabela, quando em cor amarela, significa que a tabela está atualziando normalmente  e se cinza/transparente, significa que as atualiações da tabela estão pausadas e não será atualizado os valores da tabela de forma automática;</p>
					<p><i style='color:red;' class='fa fa-times' aria-hidden='true' title='Listado no Iptables'></i>  - Endereço listado numa tabela de bloqueio/serviço;</p>
					<p><i class='fa fa fa-trash' aria-hidden='true' title='Limpar do Iptables'></i>  - Limpa o endereço listado da tabela do IpTables;</p>
					<hr>
					<p style="color:red;">OBS:</p>
					<ul style="color:red;">
						<li>Quando o endereço de IP é deletado, o mesmo não é mais apresentado na tabela abaixo;</li>
						<li>Caso um  caso o mesmo endereço persista, confirme os parametros da regra em questão e avalie mudar seu modo de operação.</li>
					</ul>
				</div>
			</div>
		</div>

		<div class="panel-body" style="margin-bottom:60px;">
			<div class="table-responsive">
				<button type="click" onclick="clearSearchDataRequest()" class="btn btn-danger form-control" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
				<button type="click" onclick="updateTableWithModal()" class="btn btn-success form-control" style="width: auto; float:right;"><i class="fa fa-refresh"></i> </button>
				<button type="click" onclick="pauseUpdateTable()" id="pauseTable" class="btn btn-warning form-control" style="width: auto; float:right;"><i class="fa fa-pause"></i> </button>		
				<button type="click" onclick="searchDataIptables()" class="btn btn-primary form-control" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>		
				<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-iptables" placeholder="<?=gettext("Search for...")?>"><!--onkeydown="searchDataIptables()" onkeypress="searchDataIptables()" onkeyup="searchDataIptables()">-->	
				<select class="form-control" style="float:right;width:200px;" id="filterQtdAlerts">
					<option value="100" selected>100</option>
					<option value="500">500</option>
					<option value="1000">1000</option>
				</select>
				
				<table class="table table-bordered" id="table-access">
					<thead>
						<tr>
							<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("GID")?></th>
							<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("SID")?></th>
							<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Description")?></th>
							<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("SRC")?></th>
							<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("DTS")?></th>
						</tr>
					</thead>
					<tbody id="geral-table">
					</tbody>
				</table>
			</div>
		</div>

		<form action="./services_acp_rules.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchRule">
			<input type="hidden" id="searchSIDRules" name="searchSIDRules" value=""> 
		</form>			
		<form action="./services_acp_ameacas.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchThread">
			<input type="hidden" id="searchSIDThreads" name="searchSIDThreads" value=""> 
		</form>

		<!-- Modal Ativa -->
		<div class="modal fade" id="modal_ativa3" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-body text-center my-5">
						<h3 class="txt_modal_ativa3" style="color:#007DC5"></h3>
						<br>
						<img id="loader_modal_ativa3" src="../images/spinner.gif"/>
					</div>
				</div>
			</div>
		</div>
		<?php $show_alert = false; ?>
	<?php endif; ?>
<?php endif; ?>	
<?php if ($show_alert): ?>
	<div class="panel-body" style="margin-bottom:60px;">
		<div class="table-responsive">
			Modo Performance (Com Exceções) não está habilitado, para a apresentação correta da página, será necessário ativar o modo global para interface FirewallApp Performance.
		</div>
	</div>
<?php endif; ?>	
<?php include("foot.inc"); ?>
<script>
	function searchDataIptables() {
		var $rows = $('#geral-table tr');
		var val = $.trim($('#search-iptables').val()).replace(/ +/g, ' ').toLowerCase();
		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			return !~text.indexOf(val);
		}).hide();
	}
	function clearSearchDataRequest() {
		$("#search-iptables").val("");
		searchDataIptables();
	}
	function complementFieldSearch(complement) {
		$("#search-iptables").val(complement);
		searchDataIptables();
	}
	let statusPauseTable = false;
	function pauseUpdateTable() {
		if (!statusPauseTable) {
			$("#pauseTable").removeAttr("class").attr("class", "btn btn-secondary form-control");
			statusPauseTable = true;
		} else {
			$("#pauseTable").removeAttr("class").attr("class", "btn btn-warning form-control");
			statusPauseTable = false;
		}
	}
	function updateTable() {
		if (!statusPauseTable) {
			$.ajax({
				data: {
					updateTable: "true",
					filterQtdAlerts: $("#filterQtdAlerts").val(),
					search_iptables: $("#search-iptables").val()
				},
				method: "POST",
				url: "./ajax_block_address.php",
				dataType: "html"
			}).done(function(data) {
				$("#geral-table").html(data);
				setTimeout(() => {
					searchDataIptables();			
				}, 200);
			});
		}
	}
	window.setInterval("updateTable()", 10000);
	function updateTableWithModal() {
		updateTable();
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Buscando informações a serem apresentadas...")?>");
		$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
		$('#modal_ativa3').modal('show');
		setTimeout(() => {
			$('#modal_ativa3').modal('hide');
		}, 5000);
	}
	function deleteAddressTotable(tableTarget, addressTarget) {
		let returnStatusPause = false;
		if (statusPauseTable) {
			returnStatusPause = true;
			statusPauseTable = false;
		}
		$.ajax({
			data: {
				tableTarget: tableTarget,
				addressTarget: addressTarget
			},
			method: "POST",
			url: "./ajax_block_address.php",
		}).done(function(data) {
			updateTable();
			searchDataIptables();
		});
		setTimeout(() => {
			if (returnStatusPause) {
				statusPauseTable = true;
			}
		}, 100);
	}
	updateTable();
	$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Buscando informações a serem apresentadas...")?>");
	$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
	$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
	$('#modal_ativa3').modal('show');
	setTimeout(() => {
		$('#modal_ativa3').modal('hide');
	}, 5000);
</script>