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

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

$pgtitle = array(gettext("Active Protection"), gettext("Analyze signature / traffic"));
$pglinks = array("/active_protection/ap_services.php", "@self");
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
			<p>informativo:</p>
			<p>Esta página tem como objetivo apresentar e analisar todo o trafego de rede pelas interfaces acionadas com o Active Protection.</p>
			<br>
			<p><i class='fa fa-search' aria-hidden='true' title='Procurar pela regra/Procurar pela tabela'></i> - Este ícone é apresentado tanto pela procura de uma ocorrência em tabela pelo campo de pesquisa, quanto por procurar regras correspondentes a esta ID;</p>
			<p><i class='fa fa-refresh' aria-hidden='true' title='Atualiza informações listadas'></i> - Atualiza as informações listadas na tabela;</p>
			<p><i class='fa fa-pause' aria-hidden='true' title='Pausa a atualizações da tabela'></i>  - Pausa as atualizações da tabela, quando em cor amarela, significa que a tabela está atualziando normalmente  e se cinza/transparente, significa que as atualiações da tabela estão pausadas e não será atualizado os valores da tabela de forma automática;</p>
			<p><i style='color:red;' class='fa fa-times' aria-hidden='true' title='Listado no Iptables'></i>  - Endereço listado numa tabela de bloqueio/serviço;</p>
			<p><i class='fa fa fa-rss' aria-hidden='true' title='Teste de conexão telnet'></i>  - Aciona o formulário de testes de conexão telnet cliente/destino;</p>
			<p><i class='fa fa fa-bookmark' aria-hidden='true' title='Marcar trafego como falso positivo'></i>  - Este ícone marca um tráfego como falso/positivo a uma assinatura, quando marcado, uma central será notificada;</p>
			<p><i class='fa fa-send icon-primary' aria-hidden='true' title='Marca que a linha já foi enviada como uma requisição a ser analisda'></i> - Este ícone marca que a requisição já foi marcada em algum momento para ser analisada;</p>
			<p><i class="fa fa-sitemap" aria-hidden='true'  title='Buscar pelo endereço de domínio da conexão'></i> - Este ícone é referente ao modal para fazer uma pesquisa com base no domínio;</p>
			<hr>
			<p style='color:red'>OBS: Esteja ciente que no modo de pesquisa com base em domínio, possa ser que não aja retornos das informações, poís o endereço IP de retorno do domínio foi alterado</p>
			<p style='color:red'>OBS: Ao deletar um endereço junto a regra do Active Protection, a ação irá afetar todas as interfaces com o serviço por compartilharem o mesmo livro de regras</p>
		</div>
	</div>
</div>

<div class="alert alert-success clearfix" role="alert" id='showBoxDomainSearch' style='display:none !important;'>
	<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>
	<div class="pull-left" id="showTextBoxDomainSearch"></div>
</div>

<div class="panel-body" style="margin-bottom:60px;">
	<div class="table-responsive">
		<button type="click" onclick="telnet_test_connection_only()" class="btn btn-secondary form-control" style="width: auto; float:right;" title='Teste de conexão telnet'><i class="fa fa-rss"></i> </button>
		<button type="click" onclick="modal_form_get_address()" class="btn btn-primary form-control" style="width: auto; float:right;" title='Teste de conexão telnet'><i class="fa fa-sitemap"></i> </button>
		<button type="click" onclick="clearSearchDataRequest()" class="btn btn-danger form-control" style="width: auto; float:right;"><i class="fa fa-times" title='Listado no Iptables'></i> </button>
		<button type="click" onclick="updateTableWithModal()" class="btn btn-success form-control" style="width: auto; float:right;"><i class="fa fa-refresh" title='Atualiza informações listadas'></i> </button>
		<button type="click" onclick="pauseUpdateTable()" id="pauseTable" class="btn btn-warning form-control" style="width: auto; float:right;"><i class="fa fa-pause" title='Pausa a atualizações da tabela'></i> </button>		
		<button type="click" onclick="searchDataIptables()" class="btn btn-primary form-control" style="width: auto; float:right;"><i class="fa fa-search" title='Procurar pela regra/Procurar pela tabela'></i> </button>		
		<input type="text" class="form-control" style="background-color: #FFF!important; float:right; width:400px;" id="search-iptables" placeholder="<?=gettext("Search for...")?>">
				<select class="form-control" style="float:right;width:200px;" id="filterQtdAlerts">
			<option value="100" selected>100</option>
			<option value="500">500</option>
			<option value="1000">1000</option>
		</select>
		<select class="form-control" style="float:right;width:200px;" id="filterInterface">
			<?php
			foreach ($config['installedpackages']['suricata']['rule'] as $key => $interface) {
				$real_interface = get_real_interface($interface['interface']);
				if (in_array($real_interface, $all_gtw)) {
			?>
					<option value="<?=$real_interface?>"><?=strtoupper($interface['descr']) . " ({$real_interface})"?></option>
			<?php 
				}
			}
			?>
		</select>
		<select class="form-control" style="float:right;width:200px;" id="filterMarkShow">
			<option value="all" selected>Exibir tudo</option>
			<option value="onlymark">Marcados</option>
			<option value="onlysend">Enviados</option>
		</select>
		<table class="table table-bordered" id="table-access">
			<thead>
				<tr>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Interface")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("GID")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("SID")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Status")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Description")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("IP SRC")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("PORT SRC")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("IP DTS")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("PORT DST")?></th>
					<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Actions")?></th>
				</tr>
			</thead>
			<tbody id="geral-table">
				<tr>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
	        		<th>-</th>
        		</tr>
			</tbody>
		</table>
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


<div id="modal_form_telnet" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Test telnet Connection</h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body" style="text-align:center">
				<table style="width:100% !important;">
					<tbody>
						<tr>
							<th style="vertical-align: middle; text-align: center;"><label for="addressSRC">IP SRC: </label></th>
							<th><input class="form-control" id="addressSRC" placeholder="Endereço IP requisitante"></input></th>
						</tr>
						<tr>
							<th style="vertical-align: middle; text-align: center;"><label for="addressDST">IP DST: </label></th>
							<th><input class="form-control" id="addressDST" placeholder="Endereço IP destino"></input></th>
						</tr>
						<tr>
							<th style="vertical-align: middle; text-align: center;"><label for="addressSRC">Port DST: </label></th>
							<th><input class="form-control" id="portDST" placeholder="Porta do endereço destino"></input></th>
						</tr>
					</tbody>
				</table>
				<div id='return_telnet'>
				</div>		
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="submit" onclick="action_telnet_test_connection()"><i class="fa fa-check"></i> <?=gettext("Test Telnet")?> </button>
			</div>
		</div>
	</div>
</div>

<form action="../active_protection/services_acp_rules.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchRule">
	<input type="hidden" id="searchSIDRules" name="searchSIDRules" value=""> 
</form>			
<form action="../active_protection/services_acp_ameacas.php" method="POST" style="border: 0px solid transparent;display: none;" id="submitSearchThread">
	<input type="hidden" id="searchSIDThreads" name="searchSIDThreads" value=""> 
</form>

<div id="modal_form_get_address" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Find traffic/connection</h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body" style="text-align:center">
				<table style="width:100% !important;">
					<tbody>
						<tr>
							<th style="vertical-align: middle; text-align: center;"><label for="getInterface">Interface: </label></th>
							<th>
								<select class="form-control" id="getInterface">
									<?php
									foreach ($config['installedpackages']['suricata']['rule'] as $key => $interface) {
										$real_interface = get_real_interface($interface['interface']);
										if (in_array($real_interface, $all_gtw)) {
									?>
											<option value="<?=$real_interface?>"><?=strtoupper($interface['descr']) . " ({$real_interface})"?></option>
									<?php 
										}
									}
									?>
								</select>	
							</th>
						</tr>
						<tr>
							<th style="vertical-align: middle; text-align: center;"><label for="getURL">URL: </label></th>
							<th><input class="form-control" id="getURL" placeholder="URL do site alvo"></input></th>
						</tr>
						<tr>
							<th style="vertical-align: middle; text-align: center;"><label for="getPort">Porta: </label></th>
							<th><input class="form-control" id="getPort" placeholder="Porta de teste para a URL (Opcional)"></input></th>
						</tr>
					</tbody>
				</table>
				<div id='return_telnet'>
				</div>		
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="submit" onclick="find_specific_telnet_test_connection()"><i class="fa fa-check"></i> <?=gettext("Find information")?> </button>
			</div>
		</div>
	</div>
</div>

<div id="openModalDeleteAddressTotable" class="modal fade" tabindex="-1" role="dialog">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<font color="black"><h4 class="modal-title">Delete traffic/connection</h4></font>
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			</div>
			<div class="modal-body" style="text-align:left">
				<p>Selecione a opção de remoção segundo suas necessidades:</p>
				<ul>
					<li style='margin-bottom: 10px !important;line-height: 30px;'><decoration class="btn-primary" style='border-radius: 10px;font-size: 14px;padding: 5px;'>Somente remover da tabela</decoration> -> O endereço <addressTarget id="addressTargetShow"></addressTarget> somente será removido da tabela <tableTarget id="tableTargetShow"></tableTarget>;</li>
					<li style='line-height: 30px;'><decoration class="btn-warning" style='border-radius: 10px;font-size: 14px;padding: 5px;'>Remove o IP da tabela e regra de bloqueio</decoration> -> Está opção faz as mesmas ações que a opção acima e remove a regra de bloqueio referente ao ID <sidTarget id="sidTargetShow"></sidTarget> do grupo <groupTarget id="groupDescShow"></groupTarget> (<gidTarget id="gidTargetShow"></gidTarget>) das configurações da interface;</li>	
				</ul>
			</div>
			<div class="modal-body" style="text-align:center">
				<p style="color:red;">OBS: Esteja ciente que o segundo modo irá remover a regra totalmente do modo de bloqueio, junto a todas as suas configurações da mesma, motivo disso é para não causar efeitos colaterais não esperados pela ação.</p>
				<input type='hidden' id='tableDeleteValue' name='tableDeleteValue'></input>
				<input type='hidden' id='addressDeleteValue' name='addressDeleteValue'></input>
				<input type='hidden' id='interfaceRealDeleteValue' name='interfaceRealDeleteValue'></input>
				<input type='hidden' id='sidDeleteValue' name='sidDeleteValue'></input>
				<input type='hidden' id='gidDeleteValue' name='gidDeleteValue'></input>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger" data-dismiss="modal" style="margin-right: auto;"><i class="fa fa-ban"></i> <?=gettext("Cancel")?></button>
				<button class="btn btn-primary" type="submit" onclick="deleteAddressTotable()"><i class="fa fa-check"></i> <?=gettext("Only Delete Ip from Tables information")?> </button>
				<button class="btn btn-warning" type="submit" onclick="deleteAddressTotableDeleteRule()"><i class="fa fa-check"></i> <?=gettext("Delete IP from tables and remove rule from block")?> </button>
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
				filterInterface: $("#filterInterface").val(),
				filterQtdAlerts: $("#filterQtdAlerts").val(),
				search_iptables: $("#search-iptables").val(),
				filterMarkShow: $("#filterMarkShow").val()
			},
			method: "POST",
			url: "./acp_ajax_all_traffic.php",
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

function openModalDeleteAddressTotable(tableTarget, addressTarget, groupDesc, interfaceReal, gidRule, sidRule) {
	$('#tableDeleteValue').val(tableTarget);
	$('#addressDeleteValue').val(addressTarget);
	$('#interfaceRealDeleteValue').val(interfaceReal);
	$('#sidDeleteValue').val(sidRule);
	$('#gidDeleteValue').val(gidRule);

	$('#addressTargetShow').html(addressTarget);
	$('#tableTargetShow').html(tableTarget);
	$('#groupDescShow').html(groupDesc);
	$('#sidTargetShow').html(sidRule);
	$('#gidTargetShow').html(gidRule);

	$('#openModalDeleteAddressTotable').modal('show');
}

function deleteAddressTotable() {
	$.ajax({
		data: {
			tableTarget: $('#tableDeleteValue').val(),
			addressTarget: $('#addressDeleteValue').val()
		},
		method: "POST",
		url: "./acp_ajax_all_traffic.php",
	}).done(function(data) {
		$('#openModalDeleteAddressTotable').modal('hide');
		updateTable();
		searchDataIptables();
	});
}

function deleteAddressTotableDeleteRule() {
	$('#openModalDeleteAddressTotable').modal('hide');
	$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Removendo regra de bloqueio...")?>");
	$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
	$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
	$('#modal_ativa3').modal('show');
	$.ajax({
		data: {
			tableTargetDeleteRule: $('#tableDeleteValue').val(),
			addressTargetDeleteRule: $('#addressDeleteValue').val(),
			interfaceRealDeleteRule: $('#interfaceRealDeleteValue').val(),
			sidTargetDeleteRule: $('#sidDeleteValue').val(),
			gidTargetDeleteRule: $('#gidDeleteValue').val()
		},
		method: "POST",
		url: "./acp_ajax_all_traffic.php",
	}).done(function(data) {
		$('#modal_ativa3').modal('hide');
		updateTable();
		searchDataIptables();
	});
}

$("#filterMarkShow").change(function() {
	updateTable();
	searchDataIptables();
});

$("#filterInterface").change(function() {
	updateTable();
	searchDataIptables();
});

updateTable();
//updateTableWithModal()

function remove_add_false_rule(remove_add_false_rule) {
	$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Alterando estado de falso positivo...")?>");
	$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
	$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
	$('#modal_ativa3').modal('show');
	setTimeout(() => {
		$.ajax({
		data: {
			remove_add_false_rule: remove_add_false_rule
		},
		method: "post",
		url: "./acp_ajax_all_traffic.php",
		}).done(function(data) {
			updateTable();
			searchDataIptables();
			setTimeout(() => {
				$('#modal_ativa3').modal('hide');
			}, 5000);
		});
	}, 1000)
}

function submitSearchRulesThreads(sidRuleThread) {

	$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Looking for SID information")?>");
	$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
	$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
	$('#modal_ativa3').modal('show');

	setTimeout(() => {
		$.ajax({
		data: {
			targetSID: sidRuleThread
		},
		method: "post",
		url: '../active_protection/ajax_retuns_inspect_real_time.php',
		}).done(function(data) {
			setTimeout(() => {
				if (data == "emerging") {
					document.getElementById("searchSIDRules").value=sidRuleThread;
					document.getElementById("submitSearchRule").submit();
				} else if (data == "ameacas") {
					document.getElementById("searchSIDThreads").value=sidRuleThread;
					document.getElementById("submitSearchThread").submit();
				}
			}, 4000);

		});
	}, 1000)

	setTimeout(function() {
		$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Selected card is not a rule/threat")?>")
		$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/bp-logout.png')
	}, 10000);

	setTimeout(function() {$('#modal_ativa3').modal('hide')}, 12000);
}

function telnet_test_connection_only() {
	$('#addressSRC').val('');
	$('#addressDST').val('');
	$('#portDST').val('');
	$("#return_telnet").html('');
	$('#modal_form_telnet').modal('show');
}

function telnet_test_connection(addressSRC, addressDST, portDST) {
	$('#addressSRC').val(addressSRC);
	$('#addressDST').val(addressDST);
	$('#portDST').val(portDST);
	$("#return_telnet").html('');
	$('#modal_form_telnet').modal('show');
}

function action_telnet_test_connection() {

	var continue_operation = true;
	if ($('#addressSRC').val() == '') {
		continue_operation = false;
	}
	if ($('#addressDST').val() == '') {
		continue_operation = false;
	}
	if ($('#portDST').val() == '') {
		continue_operation = false;
	}

	if (continue_operation) {
		$('#modal_form_telnet').modal('hide');

		setTimeout(() => {

			$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Telnet test connection, please a moment...")?>");
			$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
			$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
			$('#modal_ativa3').modal('show');

			setTimeout(() => {
				$.ajax({
				data: {
					getConnection: true,
					addressSRC: $('#addressSRC').val(),
					addressDST: $('#addressDST').val(),
					portDST: $('#portDST').val()
				},
				method: "post",
				url: "./acp_ajax_all_traffic.php",
				}).done(function(data) {
					$('#modal_ativa3').modal('hide');
					setTimeout(() => {
						if (parseInt(data) > 0) {
							$("#return_telnet").html('<hr><div class="alert alert-success" role="alert">Connection success</div>');
						} else {
							$("#return_telnet").html('<hr><div class="alert alert-danger" role="alert">Connection fail</div>');
						}
						$('#modal_form_telnet').modal('show');
					}, 200);
				});
			}, 1000)
		
		}, 200);

	} else {
		alert('Needs to feed all the fields with information to carry out the request, otherwise it will not be possible to execute the test.');
	}

}

function modal_form_get_address() {
	$('#getURL').val('');
	$('#getPort').val('');
	$('#modal_form_get_address').modal('show');
}

function find_specific_telnet_test_connection() {

	var continue_operation = true;
	if ($('#getInterface').val() == '') {
		continue_operation = false;
	}
	if ($('#getURL').val() == '') {
		continue_operation = false;
	}

	if (continue_operation) {
		
		$('#modal_form_get_address').modal('hide');

		setTimeout(() => {

			$('#modal_ativa3 .txt_modal_ativa3').text("<?=gettext("Find the information, please a moment...")?>");
			$('#modal_ativa3 #loader_modal_ativa3').attr('style', 'width:100px;height:auto');
			$('#modal_ativa3 #loader_modal_ativa3').attr('src', '../images/spinner.gif');
			$('#modal_ativa3').modal('show');

			setTimeout(() => {
				$.ajax({
				data: {
					getConnection: true,
					getInterface: $('#getInterface').val(),
					getURL: $('#getURL').val(),
					getPort: $('#getPort').val()
				},
				method: "post",
				url: "./acp_ajax_all_traffic.php",
				}).done(function(data) {
					$('#modal_ativa3').modal('hide');
					$("#filterInterface").val($('#getInterface').val());
					$('#search-iptables').val(data);
					$("#showBoxDomainSearch").removeAttr('style');
					if (data.length <= 5) {
						$("#showBoxDomainSearch").removeAttr('class').attr('class', 'alert alert-danger clearfix');
						$("#showTextBoxDomainSearch").html('Realizada buscas de informações registradas na interface: ' + $('#getInterface').val() + ', pelo domínio: ' + $('#getURL').val() + '<br>' + 'Não foi possível estabelecer uma conexão ao domínio para o retorno do endereço IP desejado.');
					} else {
						$("#showBoxDomainSearch").removeAttr('class').attr('class', 'alert alert-success clearfix');
						$("#showTextBoxDomainSearch").html('Realizada buscas de informações registradas na interface: ' + $('#getInterface').val() + ', pelo domínio: ' + $('#getURL').val() + '<br>' + 'Endereço de retorno encontrado: ' + data);
						updateTable();
						searchDataIptables();
					}
				});
			}, 1000)
		
		}, 200);

	} else {
		alert('Needs to feed all the fields with information to carry out the request, otherwise it will not be possible to execute the test.');
	}

}

$('#modal_form_get_address').modal('show');

</script>	