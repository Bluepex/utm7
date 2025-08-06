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

$pgtitle = array(gettext("FirewallApp"), gettext("Address ignore Block"));
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
			<p>Registros dos endereços marcados como 'ignoraveis' de bloqueio</p>
			<br>
			<p>Informativo:</p>
			<p><i class='fa fa-search' aria-hidden='true' title='Procurar pela regra/Procurar pela tabela'></i> - Buscar os valore do campo de pesquisa;</p>
			<p><i class='fa fa fa-times' aria-hidden='true' title='Limpar do Iptables'></i> - Remove o endereço listado;</p>
			<p><i class='fa fa fa-floppy-o' aria-hidden='true' title='Limpar do Iptables'></i> - Adicionar novo endereço a listagem;</p>
			<hr>
			<p style="color:red;">OBS: Endereços removidos não serão mais amostrados ou encontrados até que o mesmo seja novamente marcado.</p>
		</div>
	</div>
</div>

<div class="panel-body" style="margin-bottom:60px;">
	<div class="table-responsive">
		<button type="click" onclick="openModalSaveIp()" class="btn btn-success form-control" style="width: auto; float:right;"><i class="fa fa-floppy-o"></i> </button>
		<button type="click" onclick="clearSearchDataRequest()" class="btn btn-danger form-control" style="width: auto; float:right;"><i class="fa fa-times"></i> </button>
		<button type="click" onclick="updateTable()" class="btn btn-success form-control" style="width: auto; float:right;"><i class="fa fa-refresh"></i> </button>		
		<button type="click" onclick="searchDataIptables()" class="btn btn-primary form-control" style="width: auto; float:right;"><i class="fa fa-search"></i> </button>		
		<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-iptables" placeholder="<?=gettext("Search for...")?>">
		<select class="form-control" style="float:right;width:200px;" id="filterQtdAlerts">
		<option value="10" selected>10</option>
		<option value="100">100</option>
			<option value="500">500</option>
			<option value="1000">1000</option>
		</select>
		<table class="table table-bordered" id="table-access">
			<thead>
				<tr>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Address")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Action")?></th>
				</tr>
			</thead>
			<tbody id="geral-table">
			</tbody>
		</table>
	</div>
</div>

<div id="openModalSaveIp" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Adicionar endereço IP </h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body form-group">
				<label for="iptarget" class="col-sm-2"><?=gettext("Address: ")?></label>
				<input type='text' id='iptarget' name='iptarget' class="form-control col-sm-10" style="padding-top:0px;"></input>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="submit" onclick="addIpAddress()"><i class="fa fa-check"></i> <?=gettext("Add")?> </button>
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

<script src="/vendor/jquery/inputmask.min.js?v=<?=filemtime('/usr/local/www/vendor/jquery/inputmask.min.js')?>"></script>

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
	updateTable();
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
			filterQtdAlerts: $("#filterQtdAlerts").val(),
			search_iptables: $("#search-iptables").val()
		},
		method: "POST",
		url: "./fapp_ajax_ignore_control.php",
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

function fixedIpToIgnoreUgid(ipAddress, action) {
	$.ajax({
		data: {
			ipAddressValue: ipAddress,
			actionAddress: action
		},
		method: "POST",
		url: "./fapp_ajax_ignore_control.php",
	}).done(function(data) {
		updateTable();
		searchDataIptables();
		$('#openModalSaveIp').modal('hide');
	});
}

function refreshpage() {
	updateTable();
	searchDataIptables();
}

function openModalSaveIp() {
	$('#openModalSaveIp').modal('show');
}

function addIpAddress() {
	fixedIpToIgnoreUgid($("#iptarget").val(), "add");
	$("#iptarget").val("");
}

var ipv4_address = $('#iptarget');
ipv4_address.inputmask({
    alias: "ip",
    greedy: false //The initial mask shown will be "" instead of "-____".
});

updateTable();
$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Buscando informações a serem apresentadas...")?>");
$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
$('#modal_ativa3').modal('show');
setTimeout(() => {
	$('#modal_ativa3').modal('hide');
}, 5000);

</script>