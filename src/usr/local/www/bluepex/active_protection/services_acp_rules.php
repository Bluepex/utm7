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
require_once("bluepex/bp_webservice.inc");
require_once("bluepex/firewallapp_webservice.inc");
require_once("bluepex/firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata_acp.inc");

$pgtitle = array(gettext("Active Protection"), gettext("Regras"));
$pglinks = array("./active_protection/ap_services.php", "@self");
include("head.inc");

/*
if (file_exists('/usr/local/share/suricata/rules_acp/_emerging.customize.rules')) {
	if (
		(filemtime('/usr/local/share/suricata/rules_acp/_emerging.original.rules') < filemtime('/usr/local/share/suricata/rules_acp/_emerging.rules')) ||
		(!file_exists('/usr/local/share/suricata/rules_acp/_emerging.original.rules'))
	) {
*/

/*
* NOTE: Files work in rotate rules ACP
* Fonte -> Arquivo base
* BK -> Voltar tudo caso der ruim
* temporario -> Aquele que será trabalhado para substituir o fonte
* customizado -> Aquele que segura as alteracoes
*/
$fonte = "/usr/local/share/suricata/rules_acp/_emerging.rules"; 
$backup = "/usr/local/share/suricata/rules_acp/_emerging.bk.rules";
$temporario = "/usr/local/share/suricata/rules_acp/_emerging.temp.rules";
$customizado = "/usr/local/share/suricata/rules_acp/_emerging.customize.rules";

/*
//Gera os arquivos iniciais com base no original
if (!file_exists($backup)) {
	mwexec("/bin/cp {$fonte} {$backup}");
} else {
	if (filemtime($fonte) >= filemtime($backup)+604800) {
		mwexec("/bin/cp {$fonte} {$backup}");
	}
}

//Se o fonte for mais novo que esses, significa que as regras foram atualizada
//Sendo assim, pode gerar novos deles
//Nessa novo, as regras fonte ficaram iguais, gerando um arquivo de BK, 
//um temp que recebera todas as antigas customizacoes e um custom pareado ao temp
if ((filemtime($temporario) < filemtime($fonte)) &&
	(filemtime($backup) < filemtime($fonte)) && 
	(filemtime($customizado) < filemtime($fonte)) 
) {
	//Recria o bk com base no novo fonte e já recria um temp para fazer merge com o customize
	mwexec("/bin/cp {$fonte} {$backup}");
	mwexec("/bin/cp {$fonte} {$temporario}");
	//Faz merge do arquivo temporario com o arquivo temporario
	//Temporario recebe todas as alterações do customizado
	//Após isso, tem que apertar o botão de aplicar o temp sobre o rules
	$arrayOriginalFile = [];
	$arrayCustomizadelFile = [];
	foreach(file($temporario) as $linhaOriginalNovo) {
		$arrayOriginalFile[] = $linhaOriginalNovo;
	}
	foreach(file($customizado) as $linhaCustomizada) {
		$arrayCustomizadelFile[] = $linhaCustomizada;
	}
	//Linhas novas do emerging
	$novasLinhasRulesDiferentes = [];
	foreach(array_diff($arrayOriginalFile, $arrayCustomizadelFile) as $linha) {
		$novasLinhasRulesDiferentes[] = $linha;
	}
	//Regras customizadas
	$linhasCustomizadas = [];
	foreach(array_diff($arrayCustomizadelFile, $arrayOriginalFile) as $linha) {
		$linhasCustomizadas[] = $linha;
	}
	//Regras iguais para ambos os arquivos
	$valoresIguaisEntreRegras = array_intersect($arrayOriginalFile, $arrayCustomizadelFile);
	//echo $novasLinhasRulesDiferentes[0] . "<br><br><br>";
	//echo count($novasLinhasRulesDiferentes) . "<br><br><br>";
	//echo $linhasCustomizadas[0] . "<br><br><br>";
	//echo count($linhasCustomizadas) . "<br><br><br>";
	foreach($novasLinhasRulesDiferentes as $linhaDiferente) {
		$linhaDiferenteExplode = explode(" ", $linhaDiferente);
		unset($linhaDiferenteExplode[0]);
		$contador=0;
		foreach($linhasCustomizadas as $linhaCustomizada) {
			$linhaCustomizadaExplode = explode(" ", $linhaCustomizada);
			unset($linhaCustomizadaExplode[0]);
			if (implode(" ", $linhaCustomizadaExplode) == implode(" ", $linhaDiferenteExplode)) {
				$novasLinhasRulesDiferentes[$contador] = $linhaCustomizada;
			}
			$contador++;
		}
	}
	//echo var_dump($novasLinhasRulesDiferentes) . "<br><br><br>";
	//echo count($novasLinhasRulesDiferentes) . "<br><br><br>";
	mwexec("/bin/rm {$temporario}");
	foreach(array_merge($valoresIguaisEntreRegras, $novasLinhasRulesDiferentes) as $linha) {
		#echo $linha . "<br>";
		file_put_contents($temporario, $linha, FILE_APPEND);
	}
	mwexec("/bin/cp {$temporario} {$customizado}");
}
*/
if (!file_exists($temporario)) {
	mwexec("/bin/cp {$fonte} {$temporario}");
}

if (!file_exists($customizado)) {
	mwexec("/bin/cp {$fonte} {$customizado}");
} else {
	mwexec("/bin/sh /etc/mergeListsOfACP.sh"); 	
}

if (isset($_POST['deslogar'])) {
	unset($_POST['pesquisarCategoria']);
}

if (isset($_POST['pesquisarCategoria'])) {
	unset($_POST['deslogar']);
}

//Carregando as regras customizadas
$allMsgs = [];
$allStatus = [];
$allACPMsg = [];

$contador = 0;
$contadorRules = 0;
$contadorFiltrado = 0;
foreach(file($customizado) as $linhaTratamento) {
	$contador++;
	$linhaTratamento = trim($linhaTratamento);
	if (!empty($linhaTratamento)) {
		if (substr($linhaTratamento, 0, 1) != "#") {

			$tratamento = explode(";", $linhaTratamento)[0];
			$status = explode(" ", $tratamento)[0];
			$msg = explode("(msg:\"", $tratamento)[1];
			$msg = substr($msg, 0, -1);
			$sid = explode("; ", explode("sid:", $linhaTratamento)[1])[0];

			$liberarPUSH = false;
			if (isset($_POST['pesquisarCategoria'])) {
				if ((is_numeric($_POST['pesquisarCategoria']) && $_POST['pesquisarCategoria'] == $sid) ||
					#($_POST['pesquisarCategoria'] == explode(" ", $msg)[0] . " " . explode(" ", $msg)[1])) {
					($_POST['pesquisarCategoria'] == explode(" ", $msg)[0])) {
						$liberarPUSH = true;
				}
			} elseif (isset($_POST['search-rules-acp-categorias-advanced'])) {
				if ((is_numeric($_POST['search-rules-acp-categorias-advanced']) && $_POST['search-rules-acp-categorias-advanced'] == $sid) ||
					(strpos(strtolower($msg), strtolower($_POST['search-rules-acp-categorias-advanced'])) !== false)) {
						$liberarPUSH = true;
				}
			} elseif (isset($_POST['searchSIDRules']) && $_POST['searchSIDRules'] == trim($sid)) {
				$liberarPUSH = true;

			} else {
				$allACPMsg[] = explode(" ", $msg)[0];# . " " . explode(" ", $msg)[1];
			}
			if ($liberarPUSH) {
				$allMsgs[$contador] = $msg;
				$allStatus[$contador] = $status;
				$allSIDs[$contador] = $sid;
				$contadorFiltrado++;
			} elseif (isset($_POST['search-rules-acp-categorias-advanced']) && strpos(strtolower($msg), strtolower($_POST['search-rules-acp-categorias-advanced'])) !== false) {
				$allMsgs[$contador] = $msg;
				$allStatus[$contador] = $status;
				$contadorFiltrado++;
			} elseif (isset($_POST['searchSIDRules']) && $_POST['searchSIDRules'] == trim($sid)) {
				$allMsgs[$contador] = $msg;
				$allStatus[$contador] = $status;
				$contadorFiltrado++;
			} else {
				$allACPMsg[] = explode(" ", $msg)[0];# . " " . explode(" ", $msg)[1];
			}

			$contadorRules++;
		}
	}
}


if (isset($_POST['pesquisarCategoria']) || isset($_POST['search-rules-acp-categorias-advanced']) || isset($_POST['searchSIDRules'])) {
	asort($allMsgs);
} else {
	$allACPMsgUniques = array_unique($allACPMsg);
	asort($allACPMsgUniques);
}

if (!is_array($config['installedpackages']['suricata']['rule'])) {
	$config['installedpackages']['suricata']['rule'] = array();
}

$a_nat = &$config['installedpackages']['suricata']['rule'];
$id_gen = count($config['installedpackages']['suricata']['rule']);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

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

<div class="aiia-wizard-step">
	<div class="step-content">
		<div class="panel-body">
			<div class="col-sm-12">
			<div class="panel panel-default" style="margin-top:20px">
				<?php if (isset($_POST['pesquisarCategoria']) || isset($_POST['search-rules-acp-categorias-advanced']) || isset($_POST['searchSIDRules'])) { ?>
					<hr>
						<div class="infoblock">
							<div class="alert alert-info clearfix" role="alert">
								<div class="pull-left">
									<p><?=gettext("Rules state change")?></p>
									<hr>
									<p></p>
									<dl class="dl-horizontal responsive">
										<dt><?=gettext("About states")?></dt><dd></dd>				
										<dt><i class="fa fa-check"></i></dt><dd><?=gettext("Pass - This option causes this 'rule' to be ignored, releasing your access to the internal network");?></dd>
										<dt><i class="fa fa-info"></i></dt><dd><?=gettext("Alert - This option alerts about the access attempt, preventing access, but returning that access was denied to the rules");?></dd>
										<dt><i class="fa fa fa-times"></i></dt><dd><?=gettext("Drop - This option completely blocks the rules from accessing the internal network");?></dd>
										<dt><i class="fa fa-cog"></i></dt><dd><?=gettext("Save - If the rule has suffered a state change, this is the option that will save the new state and apply it to the Active protection system");?></dd>
										<dt><i class="fa fa-cog"></i></dt><dd><?=gettext("Reset Rule - If the rule has suffered a state change, this option will restore the rule to the original state before the change, it is worth mentioning that after saving, the new saved state will be the reset state if the rule suffers a new change");?></dd>
									</dl>
									<p><?=gettext("Note: The search filter is only valid for the rules listed within the table, to search for other unlisted rules, return to the main menu by clicking the red X in the upper right corner")?></p>
								</div>
							</div>
						</div>
					<hr>
					<div class="panel panel-default" style="margin-top:20px">
						<div class="panel-body" style="display: inline-flex;border:0px solid transparent; width: 100%;">
							<?php if ((isset($_POST['search-rules-acp-categorias-advanced']) && is_numeric($_POST['search-rules-acp-categorias-advanced'])) || (isset($_POST['searchSIDRules']) && is_numeric($_POST['searchSIDRules']))): ?>
								<?php if ((isset($_POST['searchSIDRules']) && is_numeric($_POST['searchSIDRules']))): ?>
									<h2 style="margin: 0px; font-size: 18px;margin-top:12px;"><?=gettext("Rules")?> Active Protection - SID: <?=$_POST['searchSIDRules']?></h2>
								<?php else: ?>
									<h2 style="margin: 0px; font-size: 18px;margin-top:12px;"><?=gettext("Rules")?> Active Protection - SID: <?=$_POST['search-rules-acp-categorias-advanced']?></h2>
								<?php endif; ?>
							<?php else: ?>
								<h2 style="margin: 0px; font-size: 18px;margin-top:12px;"><?=gettext("Rules")?> Active Protection - <?=$_POST['pesquisarCategoria'] . $_POST['search-rules-acp-categorias-advanced']?></h2>
							<?php endif; ?>
							<input type="text" class="form-control find-values" style="background-color: #FFF!important; float:right; width: 20%;margin-left: auto;" id="search-rules-acp" placeholder="<?=gettext("Search for...")?>">
							<button type="click" class="btn btn-primary form-control find-values" style="width: auto;" onclick="searchDataRules()"><i class="fa fa-search"></i> <?=gettext("Find")?></button>
							<button type="click" class="btn btn-primary form-control find-values" style="width: auto;" onclick="cleanSearch()" id="disabledBTN" disabled><i class="fa fa-times"></i> <?=gettext("Filter")?></button>
							<form action="./services_acp_rules.php" method="POST" style="margin: 0px; padding: 0px; border: 0px solid transparent; width:5%;">
								<input type="hidden" id="deslogar" name="deslogar" value="deslogar">
								<button type="submit" class="btn btn-danger form-control find-values" style="width: auto;"><i class="fa fa-times"></i></button>
							</form>
						</div>

						<hr>
						<div class="panel-body">
							<div class="table-responsive">
								<table class="table table-bordered" id="table-access">
									<thead>
										<tr>
											<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("SID")?></th>
											<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><i class="fa fa-navicon"></i> <?=gettext("Websites/Applications/Services")?> - <?=gettext("Rules")?>: <p id="contadorRules" style="display: contents;"><?=$contadorFiltrado?></p></th>
											<th style="vertical-align: inherit !important; background: #177bb4 !important; color: white !important;"><?=gettext("Actions")?></th>
										</tr>
									</thead>
									<tbody id="geral-table">
									<?php
									//foreach ($linhas as $line_now) {
									foreach ($allMsgs as $array_key => $array_value) {
										echo "<tr>";
										echo "<th>{$allSIDs[$array_key]}</th>";
										echo "<th><p id='rule_{$array_key}' style='text-transform:uppercase;'>{$array_value}</p><input type='hidden' class='values_in_hidden' value='{$allStatus[$array_key]}_{$array_key}' name='linhaStatus_{$array_key}' id='linhaStatus_{$array_key}'></th>";
										echo "<th>";
										$css_disable_pass = "";
										$css_disable_drop = "";
										$css_disable_alert = "";
										#if (trim($line_now[1]) == "alert") { 
										if (trim($allStatus[$array_key]) == "alert") {
											$css_disable_drop = "btn-disabled";
											$css_disable_pass = "btn-disabled";
										} elseif (trim($allStatus[$array_key]) == "pass") {
											$css_disable_alert = "btn-disabled";
											$css_disable_drop = "btn-disabled";
										} else {
											$css_disable_alert = "btn-disabled";
											$css_disable_pass = "btn-disabled";
										}
										echo "<button style='margin-right: 5px;margin-top: 5px;' onclick='alterarEstado(" . "\"" . 'pass_' . $array_key . "\"" . ")' id='btn_pass_" . $array_key . "' class='btn btn-success btn-pass-change-state no-confirm " . $css_disable_pass . "'><i class='fa fa-check'></i> " . gettext('Pass') . "</button>";
										echo "<button style='margin-right: 5px;margin-top: 5px;' onclick='alterarEstado(" . "\"" . 'alert_' . $array_key . "\"" . ")' id='btn_alert_" . $array_key . "' class='btn btn-warning btn-warning-change-state no-confirm " . $css_disable_alert . "'><i class='fa fa-info'></i> " . gettext('Alert') . "</button>";
										echo "<button style='margin-right: 5px;margin-top: 5px;' onclick='alterarEstado(" . "\"" . 'drop_' . $array_key . "\"" . ")' id='btn_drop_" . $array_key . "' class='btn btn-danger btn-danger-change-state no-confirm " . $css_disable_drop . "'><i class='fa fa-times'></i> " . gettext('Drop') . "</button>";
										echo "<button style='margin-right: 5px;margin-top: 5px;' onclick='saveTarget(" . "\"". 'linhaStatus_' . $array_key . "\"" . ")' id='btn_save_" . $array_key . "' class='btn btn-secondary btn-secondary-change-state no-confirm btn-disabled' disabled><i class='fa fa-cog'></i> " . gettext('Save') . "</button>";
										echo "<button style='margin-right: 5px;margin-top: 5px;' onclick='resetTarget(" . "\"". 'linhaStatus_' . $array_key . "\"" . ", " . "\"". $allStatus[$array_key] . "_" . $array_key . "\"" . ")' id='btn_reset_" . $array_key . "' class='btn btn-secondary btn-secondary-change-state no-confirm btn-disabled' disabled><i class='fa fa-cog'></i> " . gettext('Reset rule') . "</button>";
										echo "</th></tr>";
									}
									?>
									</tbody>
								</table>
							</div>
						</div>
						</div>
						<?php } else { ?>
						<hr>
						<div class="infoblock">
							<div class="alert alert-info clearfix" role="alert">
								<div class="pull-left">
									<p><?=gettext("Rules search")?></p>
									<hr>
									<ul>
										<li><?=gettext("Simple method - Performs only rule search by a certain category")?></li>
										<li><?=gettext("Advanced method - Performs a search for all rules that have a description compatible with the one you are looking for")?></li>
									</ul>
									<p><?=gettext("Note: The search field changes according to the search, the simple mode only returns categories with compatible search, when advanced mode is activated, the search will return all rules compatible with the typed one, remembering that in advanced mode it is required at least 5 input characters to filter the rules more precisely")?></p>
								</div>
							</div>
						</div>
						<hr>
						<div class="panel-body" style="display: inline-flex;border:0px solid transparent; width: 100%;">
							<h2 style="margin: 0px; font-size: 24px;margin-top:12px;" id="desscriptionFilter"><?=gettext("Rules")?> Active Protection - <?=gettext("Simple")?></h2>
							<div id="simpleFindRules" style="margin: 0px; padding: 0px; border: 0px solid transparent; width: 45%;margin-left: auto; display: flex;">
								<input type="text" class="form-control find-values" style="background-color: #FFF!important; float:right; width: 100%; margin-left: auto;" id="search-rules-acp-categorias-simple" placeholder="<?=gettext("Search for...")?>">
								<button type="click" class="btn btn-primary form-control find-values" style="width: auto;" id="searchButton" onclick="searchButton()"><i class="fa fa-search"></i> <?=gettext("Find")?></button>
							</div>
							<form action="./services_acp_rules.php" method="POST" id="advancedFindRules" style="margin: 0px; padding: 0px; border: 0px solid transparent; width: 45%;margin-left: auto; display: none;">
								<input type="text" class="form-control find-values" style="background-color: #FFF!important; float:right; width: 100%; margin-left: auto;" id="search-rules-acp-categorias-advanced" name="search-rules-acp-categorias-advanced" placeholder="<?=gettext("Search for...")?>">
								<button type="submit" class="btn btn-primary form-control find-values" style="width: auto;" id="confirmPostRequestSearch"><i class="fa fa-search"></i> <?=gettext("Find")?></button>
							</form>
							<button type="click" class="btn btn-primary form-control find-values" style="width: auto;" onclick="cleanSearchFilter()"><i class="fa fa-times"></i> <?=gettext("Clear")?></button>
							<button type="click" class="btn btn-primary form-control find-values" style="width: auto;" id="searchAdvanceValue" onclick="searchAdvanceValue()"><i class="fa fa-search"></i> <?=gettext("Advanced")?></button>
							<button type="click" class="btn btn-danger form-control find-values" style="width: auto; display:none;" id="closeAdvanceSearch" onclick="closeAdvanceSearch()"><i class="fa fa-times"></i></button>
						</div>
						<hr>
						<div class="panel-body" style="text-align: center;" id="painelFilterCategory">
							<h4 id="resultadoSimpleFilter"><?=gettext("Click to search by category")?></h4>
							<form action="./services_acp_rules.php" method="POST" style="border:0px solid transparent !important;">
								<input type="hidden" id="pesquisarCategoria" name="pesquisarCategoria" value="">
								<?php foreach($allACPMsgUniques as $campo) { echo "<button type='submit' onclick='findPostCategoria(" . "\"" .  $campo . "\"" . ")' name='btnruleacp' class='btn btn-primary btn-warning-change-state no-confirm find-values' style='margin: 5px; text-transform: uppercase;'>" . $campo . "</button>"; } ?>
							</form>
						</div>
						<div class="panel-body" style="text-align: center; display: none;" id="painelFilterAutocomplete">
							<h4><?=gettext("Sugestões de preenchimento")?></h4>
							<?php foreach($allACPMsgUniques as $campo) { echo "<button type='submit' onclick='autoCompleteSearch(" . "\"" .  $campo . "\"" . ")' class='btn btn-primary btn-warning-change-state no-confirm find-values' style='margin: 5px; text-transform: uppercase;'>" . $campo . "</button>"; } ?>
						</div>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="modal fade" id="modal_ativa_regras" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-body text-center my-5">
				<h3 class="txt_modal_ativa_regras" style="color:#007DC5"></h3>
				<br>
				<h3 class="txt_modal_ativa_real_rule" style="color:#007DC5"></h3>
				<br>
				<img id="loader_modal_ativa_regras" src="../images/spinner.gif"/>
			</div>
		</div>
	</div>
</div>



<div class="modal fade" id="modal_save_applicate_rules" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
	<div class="modal-dialog  modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="exampleModalLabel" style="color:#007DC5"><?=gettext("Apply saved rules?")?></h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="color:red;font-size:16px;border: 0px solid transparent;background: transparent;" onclick="showNoneModalPass()"><i class="fa fa-times"></i></button>
			</div>
			<div class="modal-body text-center my-5" style="font-size:16px;">
				<?=gettext("The operation of applying the rules may take a few minutes, I recommend that you only apply them when you have made all the changes you want.")?>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="font-size:16px;" onclick="showNoneModalPass()"><?=gettext("Return the rules")?></button>
				<button type="button" class="btn btn-primary" style="font-size:16px;" onclick="applicateInInterfacesACP()"><?=gettext("Apply Rules")?></button>
			</div>
		</div>
	</div>
</div>



<?php include("foot.inc"); ?>


<script>


function showNoneModalPass() {
	$('#modal_save_applicate_rules').modal('hide');
}

function showBlockModalPass() {
	$('#modal_save_applicate_rules').modal('show');
}

function findPostCategoria(pesquisar) {
	document.getElementById("pesquisarCategoria").value=pesquisar;
}


$('#confirmPostRequestSearch').click(function(event){
	if (document.getElementById("search-rules-acp-categorias-advanced").value.length <= 4) {
		alert("<?=gettext("Fill the search field with at least 5 characters, this will make the search faster and more accurate")?>");
		event.preventDefault();
	}
});


function searchButton() {
	var val = $.trim($('#search-rules-acp-categorias-simple').val()).replace(/ +/g, ' ').toLowerCase();
	var buttonsACP = document.getElementsByName("btnruleacp");

	var displayInFlex = 0;

	for(i=0;i<=buttonsACP.length-1;i++) {
		var text = buttonsACP[i].textContent.toLocaleLowerCase()
		if (text.indexOf(val) >= 0) {
			buttonsACP[i].style.display='inline-flex';
			displayInFlex++;
		} else {
			buttonsACP[i].style.display='none';
		}
	}
	if (val.length == 0) {
		document.getElementById("resultadoSimpleFilter").textContent = "<?=gettext("Click to search by category")?>";
	} else {
		if (displayInFlex > 0) {
			document.getElementById("resultadoSimpleFilter").textContent = "Resultados encontrados";
		} else {
			document.getElementById("resultadoSimpleFilter").textContent = "Nenhum resultado encontrado";
		}
	}
}

//disabledBTN

function searchDataRules(msgCustom = "") {

	if (document.getElementById("search-rules-acp").value.length > 0) {
		document.getElementById("disabledBTN").disabled=false;
	} else {
		document.getElementById("disabledBTN").disabled=true;
	}

	$('#modal_ativa_regras #loader_modal_ativa_regras').attr('style', 'width:200px;height:200px');
	$('#modal_ativa_regras .txt_modal_ativa_real_rule').text("");
	$('#modal_ativa_regras #loader_modal_ativa_regras').attr('src', '../images/spinner.gif');
	if (msgCustom.length > 0) {
		$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Clearing the current search")?>");
	} else {
		$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Search Rules")?>");
	}
	$('#modal_ativa_regras').modal('show');

	setTimeout(() => {
		contadorAllRules = <?=$contadorFiltrado?>;
		contadorJS = 0;

		var $rows = $('#table-access #geral-table tr');
		var val = $.trim($('#search-rules-acp').val()).replace(/ +/g, ' ').toLowerCase();
		$rows.show().filter(function() {
			var text = $(this).text().replace(/\s+/g, ' ').toLowerCase();
			if (!~text.indexOf(val)) {
				contadorJS++;
			}
			return !~text.indexOf(val);
		}).hide();		
		document.getElementById("contadorRules").textContent=contadorAllRules-contadorJS;
	}, 1000);

	setTimeout(() => {
		$('#modal_ativa_regras').modal('hide');
	}, 5000);

}


function cleanSearch() {
	document.getElementById("search-rules-acp").value="";
	$('#modal_ativa_regras .txt_modal_ativa_real_rule').text("");
	document.getElementById("disabledBTN").disabled=true;
	searchDataRules("filter");
}

function searchRule(entrada) {
	document.getElementById("search-rules-acp").value=entrada;
	$('#modal_ativa_regras .txt_modal_ativa_real_rule').text("");
	searchDataRules();
}

let estadoASerAlterado = "";

function alterarEstado(valor) {
	if ((estadoASerAlterado == valor.split("_")[1]) || (estadoASerAlterado == "")) {
		estadoASerAlterado = valor.split("_")[1];
		document.getElementById("linhaStatus_" + valor.split("_")[1]).value=valor;
		document.getElementById("btn_save_" + valor.split("_")[1]).disabled=false;
		document.getElementById("btn_reset_" + valor.split("_")[1]).disabled=false;
		document.getElementById("btn_save_" + valor.split("_")[1]).className="btn btn-secondary btn-secondary-change-state no-confirm";
		document.getElementById("btn_reset_" + valor.split("_")[1]).className="btn btn-secondary btn-secondary-change-state no-confirm";
		if (valor.split("_")[0] == "alert") {
			document.getElementById("btn_alert_" + valor.split("_")[1]).className="btn btn-warning btn-warning-change-state no-confirm";
			document.getElementById("btn_drop_" + valor.split("_")[1]).className="btn btn-danger btn-danger-change-state no-confirm btn-disabled";
			document.getElementById("btn_pass_" + valor.split("_")[1]).className="btn btn-success btn-pass-change-state no-confirm btn-disabled";
		} else if (valor.split("_")[0] == "pass") {
			document.getElementById("btn_alert_" + valor.split("_")[1]).className="btn btn-warning btn-warning-change-state no-confirm btn-disabled";
			document.getElementById("btn_drop_" + valor.split("_")[1]).className="btn btn-danger btn-danger-change-state no-confirm btn-disabled";
			document.getElementById("btn_pass_" + valor.split("_")[1]).className="btn btn-success btn-pass-change-state no-confirm ";
		} else {
			document.getElementById("btn_alert_" + valor.split("_")[1]).className="btn btn-warning btn-warning-change-state no-confirm btn-disabled";
			document.getElementById("btn_drop_" + valor.split("_")[1]).className="btn btn-danger btn-danger-change-state no-confirm";
			document.getElementById("btn_pass_" + valor.split("_")[1]).className="btn btn-success btn-pass-change-state no-confirm btn-disabled";
		}
	} else {
		$('#modal_ativa_regras #loader_modal_ativa_regras').attr('style', 'width:200px;height:200px');
		$('#modal_ativa_regras #loader_modal_ativa_regras').attr('src', '../images/bp-gear.png');
		$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("To change other rules, it is necessary to apply the changes already made to the rules (Click to go to the rules):")?>");
		$('#modal_ativa_regras .txt_modal_ativa_real_rule').text(document.getElementById("rule_" + estadoASerAlterado).innerHTML);
		$('#modal_ativa_regras .txt_modal_ativa_real_rule').attr("onclick","searchRule('" + document.getElementById("rule_" + estadoASerAlterado).innerHTML + "')");       
		$('#modal_ativa_regras').modal('show');
	}
}


function resetTarget(linhaStatus_array_key, valorOriginal) {
	document.getElementById("btn_save_" + valorOriginal.split("_")[1]).disabled=true;
	document.getElementById("btn_reset_" + valorOriginal.split("_")[1]).disabled=true;
	document.getElementById("btn_save_" + valorOriginal.split("_")[1]).className="btn btn-secondary btn-secondary-change-state no-confirm btn-disabled";
	document.getElementById("btn_reset_" + valorOriginal.split("_")[1]).className="btn btn-secondary btn-secondary-change-state no-confirm btn-disabled";
	document.getElementById(linhaStatus_array_key).value=valorOriginal;
	if (valorOriginal.split("_")[0] == "alert") {
			document.getElementById("btn_alert_" + valorOriginal.split("_")[1]).className="btn btn-warning btn-warning-change-state no-confirm";
			document.getElementById("btn_drop_" + valorOriginal.split("_")[1]).className="btn btn-danger btn-danger-change-state no-confirm btn-disabled";
			document.getElementById("btn_pass_" + valorOriginal.split("_")[1]).className="btn btn-success btn-pass-change-state no-confirm btn-disabled";
	} else if (valorOriginal.split("_")[0] == "pass") {
		document.getElementById("btn_alert_" + valorOriginal.split("_")[1]).className="btn btn-warning btn-warning-change-state no-confirm btn-disabled";
		document.getElementById("btn_drop_" + valorOriginal.split("_")[1]).className="btn btn-danger btn-danger-change-state no-confirm btn-disabled";
		document.getElementById("btn_pass_" + valorOriginal.split("_")[1]).className="btn btn-success btn-pass-change-state no-confirm ";
	} else {
		document.getElementById("btn_alert_" + valorOriginal.split("_")[1]).className="btn btn-warning btn-warning-change-state no-confirm btn-disabled";
		document.getElementById("btn_drop_" + valorOriginal.split("_")[1]).className="btn btn-danger btn-danger-change-state no-confirm";
		document.getElementById("btn_pass_" + valorOriginal.split("_")[1]).className="btn btn-success btn-pass-change-state no-confirm btn-disabled";
	}
	estadoASerAlterado = "";
}

function selecionarFiltro(filtroValue) {
	document.getElementById("search-rules-acp").value=filtroValue;
	searchDataRules();
}

function filterCategorias() {
	if (document.getElementById("painelFilterCategory").style.display == "none") {
		document.getElementById("painelFilterCategory").style.display = "block";
	} else {
		document.getElementById("painelFilterCategory").style.display = "none";
	}
}


function cleanSearchFilter() {
	document.getElementById("search-rules-acp-categorias-simple").value="";
	document.getElementById("search-rules-acp-categorias-advanced").value="";
	searchButton();
}

function autoCompleteSearch(entrada) {
	document.getElementById("search-rules-acp-categorias-advanced").value=entrada;
}

function searchAdvanceValue() {
	document.getElementById("searchAdvanceValue").style.display = "none";
	document.getElementById("painelFilterCategory").style.display = "none";
	document.getElementById("closeAdvanceSearch").style.display = "block";
	document.getElementById("painelFilterAutocomplete").style.display = "block";
	document.getElementById("search-rules-acp-categorias-simple").value="";
	document.getElementById("simpleFindRules").style.display = "none";
	document.getElementById("advancedFindRules").style.display = "flex";
	document.getElementById("desscriptionFilter").textContent = "<?=gettext("Rules")?> Active Protection - <?=gettext("Advanced")?>";
}

function closeAdvanceSearch() {
	document.getElementById("searchAdvanceValue").style.display = "block";
	document.getElementById("painelFilterCategory").style.display = "block";
	document.getElementById("closeAdvanceSearch").style.display = "none";
	document.getElementById("painelFilterAutocomplete").style.display = "none";
	document.getElementById("search-rules-acp-categorias-advanced").value="";
	document.getElementById("simpleFindRules").style.display = "flex";
	document.getElementById("advancedFindRules").style.display = "none";
	document.getElementById("desscriptionFilter").textContent = "<?=gettext("Rules")?> Active Protection - <?=gettext("Simple")?>";
}

						
//painelFilterAutocomplete


function saveTarget(pegarValor) {

	document.getElementById("btn_pass_" + pegarValor.split("_")[1]).disabled=true;
	document.getElementById("btn_alert_" + pegarValor.split("_")[1]).disabled=true;
	document.getElementById("btn_drop_" + pegarValor.split("_")[1]).disabled=true;
	document.getElementById("btn_save_" + pegarValor.split("_")[1]).disabled=true;
	document.getElementById("btn_reset_" + pegarValor.split("_")[1]).disabled=true;

	document.getElementById("btn_pass_" + pegarValor.split("_")[1]).className="btn btn-success btn-secondary-change-state no-confirm btn-disabled";
	document.getElementById("btn_alert_" + pegarValor.split("_")[1]).className="btn btn-warning btn-secondary-change-state no-confirm btn-disabled";
	document.getElementById("btn_drop_" + pegarValor.split("_")[1]).className="btn btn-danger btn-secondary-change-state no-confirm btn-disabled";
	document.getElementById("btn_save_" + pegarValor.split("_")[1]).className="btn btn-secondary btn-secondary-change-state no-confirm btn-disabled";
	document.getElementById("btn_reset_" + pegarValor.split("_")[1]).className="btn btn-secondary btn-secondary-change-state no-confirm btn-disabled";

	$('#modal_ativa_regras #loader_modal_ativa_regras').attr('style', 'width:200px;height:200px');
	$('#modal_ativa_regras #loader_modal_ativa_regras').attr('src', '../images/spinner.gif');
	$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Updating Active Protection rules")?>");
	$('#modal_ativa_regras .txt_modal_ativa_real_rule').text("");
	$('#modal_ativa_regras').modal('show');

	setTimeout(() => {
		$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Saving changes")?>");
		$.ajax({
			data: {'save_rule': document.getElementById(pegarValor).value},
			method: "POST",
			url: './save_post_acp.php',
			async: false,
			beforeSend: function() {
				setTimeout(() => {
					$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Changes have been saved")?>");
				}, 3000);
				setTimeout(() => {
					applicateInInterfacesACP();
				}, 4000);
			}
		});
	}, 3000);

}
		
function applicateInInterfacesACP() {
	setTimeout(() => {

		$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Applying threats changes in Active Protection")?>");
		
		setTimeout(() => {
			$.ajax({
				data: {'apply_ameacas_ext': 'apply'},
				method: "POST",
				url: './save_post_acp.php',
				async: false
			});
		}, 1000);

		<?php if (getStatusNewAcp() >= 1) : ?>
		
			setTimeout(() => {
				$.ajax({
					url: './update_interfaces_rules.php',
				});
			}, 2000);

			setTimeout(() => {
				$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Operation performed successfully!")?>");
				$('#modal_ativa_regras #loader_modal_ativa_regras').attr('src', '../images/update_rules_ok.png');	
			}, 5000);
			setTimeout(() => {
				$('#modal_ativa_regras').modal('hide');
			}, 7000);
			setTimeout(() => {
				window.location.reload();
			}, 8100);

		<?php else: ?>

			setTimeout(() => {
				$('#modal_ativa_regras .txt_modal_ativa_regras').text("<?=gettext("Operation performed successfully!")?>");
				$('#modal_ativa_regras #loader_modal_ativa_regras').attr('src', '../images/update_rules_ok.png');	
			}, 5000);
			setTimeout(() => {
				$('#modal_ativa_regras').modal('hide');
			}, 6000);
			setTimeout(() => {
				window.location.reload();
			}, 6100);

		<?php endif; ?>

		}, 1000);
}

</script>