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

$pgtitle = array(gettext("FirewallApp"), gettext("Related Traffic"));
$pglinks = array("./firewallapp/services.php", "@self");
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

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<p>Página de demonstração do relacionamento do tráfego bloqueado do equipamento com os endereços listado nas tabelas internas de endereços;</p>
			<br>
			<p>Esta tela se atualiza de tempo em tempos, neste caso, não necessita recarrega-la, caso queira que as informações sejam listadas de no momento, clique no botão de "reload" no campo de pesquisa.</p>
			<br>
			<p>Informativo:</p>
			<p><i class='fa fa-search' aria-hidden='true' title='Procurar pela regra/Procurar pela tabela'></i> - Este ícone é apresentado tanto pela procura de uma ocorrência em tabela pelo campo de pesquisa, quanto por procurar regras correspondentes a esta ID;</p>
			<p><i class='fa fa-refresh' aria-hidden='true' title='Atualiza informações listadas'></i> - Atualiza as informações listadas na tabela;</p>
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
		<button type="click" onclick="openModalCleanIptables()" class="btn btn-warning form-control" style="width: auto; float:right;"><i class="fa fa-recycle"></i> </button>
		<button type="click" onclick="clearSearchDataRequest()" class="btn btn-danger form-control" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
		<button type="click" onclick="updateTableWithModal()" class="btn btn-success form-control" style="width: auto; float:right;"><i class="fa fa-refresh"></i> </button>		
		<button type="click" onclick="searchDataIptables()" class="btn btn-primary form-control" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>		
		<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-iptables" placeholder="<?=gettext("Search for...")?>"><!--onkeydown="searchDataIptables()" onkeypress="searchDataIptables()" onkeyup="searchDataIptables()">-->	
		<select class="form-control" style="float:right;width:200px;" id="filterQtdAlerts">
			<option value="100" selected>100</option>
			<option value="500">500</option>
			<option value="1000">1000</option>
		</select>
		<select class="form-control" style="float:right;width:200px;" id="interface_select">
			<?php
			$all_gtw = getInterfacesInGatewaysWithNoExceptions();
			$real_interfaces_array = [];
			foreach ($config['installedpackages']['suricata']['rule'] as $interface) {
				$real_interface = get_real_interface($interface['interface']);
				if (!in_array($real_interface, $all_gtw)) {
					?>
						<option value="<?="{$interface['descr']}_{$real_interface}___{$real_interface}{$interface['uuid']}"?>"><?=$interface['descr']?> (<?=$real_interface?>)</option>
					<?php
				}
			}
			?>
		</select>
		<table class="table table-bordered" id="table-access">
			<thead>
				<tr>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Status")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("GID")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("SID")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Interface")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Description")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Protocol")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("IP DST")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("PORT DST")?></th>
				</tr>
			</thead>
			<tbody id="geral-table">
			</tbody>
		</table>
	</div>
</div>

<div id="openModalDeleteAddressTotable" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Delete IP connection</h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body" style="text-align:center">
				<p style="color:red;">OBS: Esteja ciente que você só está deletando o valor (<ipTableValue id="addressDeleteValue"></ipTableValue>) alimentado nas tabelas de fapp2c do IpTables, caso a regra de bloqueio sobre o IP ainda esteja ativo, o mesmo será alimentado novamente a tabela no futuro.</p>
				<input type='hidden' id='tableDeleteValue' name='tableDeleteValue'></input>
				<input type='hidden' id='addressDeleteValue' name='addressDeleteValue'></input>
				<input type='hidden' id='addressDeleteValue' name='addressDeleteValue'></input>
				<input type='hidden' id='gidRuleDeleteValue' name='gidRuleDeleteValue'></input>
				<input type='hidden' id='sidRuleDeleteValue' name='sidRuleDeleteValue'></input>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="submit" onclick="deleteAddressTotable()"><i class="fa fa-check"></i> <?=gettext("Delete Ip Address")?> </button>
			</div>
		</div>
	</div>
</div>

<div id="openModalDeleteAllAddress" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Limpar endereços da interface: <interfaceTarget name="interfaceShowNow"></interfaceTarget></h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body form-group">
				<br>
				<p style="color:red;">OBS: Está ação ira limpar todos os endereços listados na interfaces <interfaceTarget name="interfaceShowNow"></interfaceTarget> do FirewallApp.</p>	
				<input type='hidden' id='interfaceTableTarget' name='interfaceTableTarget'></input>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-warning" type="submit" onclick="cleanTableInterfacesNow()"><i class="fa fa-check"></i> <?=gettext("Limpar registros")?> </button>
			</div>
		</div>
	</div>
</div>

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

function updateTable() {
	$.ajax({
		data: {
			updateTable: "true",
			interface_select: $("#interface_select").val(),
			filterQtdAlerts: $("#filterQtdAlerts").val()
		},
		method: "POST",
		url: "./ajax_extra_protection.php",
		dataType: "html"
	}).done(function(data) {
		$("#geral-table").html(data);
		setTimeout(() => {
			searchDataIptables();			
		}, 200);
	});
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

$("#interface_select").change(function(){
	updateTable();
	searchDataIptables();
});

$("#filterQtdAlerts").change(function(){
	updateTable();
	searchDataIptables();
});

function openModalDeleteAddressTotable(gidRule, sidRule, tableTarget, addressTarget) {
	$('#tableDeleteValue').val(tableTarget);
	$('#addressDeleteValue').val(addressTarget);
	$('#gidRuleDeleteValue').val(gidRule);
	$('#sidRuleDeleteValue').val(sidRule);
	
	$('#addressDeleteValue').html(addressTarget);
	$('#openModalDeleteAddressTotable').modal('show');
}

function deleteAddressTotable() {
	$.ajax({
		data: {
			tableTarget: $("#tableDeleteValue").val(),
			addressTarget: $("#addressDeleteValue").val(),
			gidTarget: $("#gidRuleDeleteValue").val(),
			sidTarget: $("#sidRuleDeleteValue").val()
		},
		method: "POST",
		url: "./ajax_extra_protection.php",
		dataType: "html"
	}).done(function(data) {
		$('#openModalDeleteAddressTotable').modal('hide');
		updateTable();
		searchDataIptables();
	});
}

function openModalCleanIptables() {
	var infoInterface = $("#interface_select").val();
	var descInterface = infoInterface.split("___")[0].split("_")
	var valueInterface = infoInterface.split("___")[1]
	$('[name=interfaceShowNow]').html(descInterface[0] + " (" + descInterface[1] + ")");
	$("#interfaceTableTarget").val(valueInterface)
	$('#openModalDeleteAllAddress').modal('show');
}

function cleanTableInterfacesNow() {
	$.ajax({
		data: {
			interfaceTableTarget: $("#interfaceTableTarget").val(),
		},
		method: "POST",
		url: "./ajax_extra_protection.php",
		dataType: "html"
	}).done(function(data) {
		$('#openModalDeleteAllAddress').modal('hide');
		updateTable();
		searchDataIptables();
	});
}

function fixedIpToIgnoreUgid(ipAddress, action) {
	$.ajax({
		data: {
			ipAddressValue: ipAddress,
			actionAddress: action
		},
		method: "POST",
		url: "./fapp_ajax_all_traffic.php",
	}).done(function(data) {
		updateTable();
		searchDataIptables();
	});
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