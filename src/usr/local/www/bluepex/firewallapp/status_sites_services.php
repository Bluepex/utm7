<?php
/* ====================================================================
 * Copyright (C) BluePex Security Solutions - All rights reserved
 * Unauthorized copying of this file, via any medium is strictly prohibited
 * Proprietary and confidential
 * Written by Wesley F. Peres <wesley.peres@bluepex.com>, 2019
 *
 * ====================================================================
 *
 */
require_once("guiconfig.inc");
require_once("bp_webservice.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");


$pgtitle = array(gettext("FirewallApp"), gettext("Status dos serviços/aplicações/sites"));
$pglinks = array("./firewallapp/services.php", "@self");
include("head.inc");

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
</style>
<?php
if ($savemsg)
	print_info_box($savemsg, 'success');
?>


<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<p>Este serviço visa fornecer informações sobre o status de sites e serviços, o mesmo é atualizado automáticamente de tempos em tempos, dessa forma a aplicação não funciona em tempo real de primeiro momento, vale comentar que está página é somente uma representação com certa imprecisão, já que há vários cenários onde não é possível realizar uma conexão ao endereço desejado ou apresentado</p>
			<hr>
			<dl class="dl-horizontal responsive">
				<dt><?=gettext('Legenda')?></dt><dd></dd>
				<dt><i class="fa fa-check-circle text-success icon-primary"></i></dt><dd><?=gettext("GREEN = Status do serviço/site está OK");?></dd>
				<dt><i class="fa fa-times-circle text-danger icon-primary"></i></dt><dd><?=gettext("RED = O serviço/site não está respondendo");?></dd>
			</dl>
			<hr>
			<p style="color:red">Obs: Fique ciente que o status de não acesso pode ser ocorrência de uma ou mais circunstancias, exemplos das mesmas são: O endereço pode ter bloqueado o ICMP, alguma regra pode estar bloquando o acesso externo a este endereço, o serviço pode estar forá do ar no momento, problemas de redirecionamento pelo provedor e demais"</p>
		</div>
	</div>
</div>


<div class="outer-container">
	<div id="wizard" class="aiia-wizard" style="display:none;">
		<div class="aiia-wizard-step">
			<h1><?=gettext("Categorias de Sites/Aplicações/Serviços")?></h1>
			<div class="step-content">
			<?php
				$files = glob("/tmp/categorias/*.rules.status");

				if (count($files) > 0):
					foreach ($files as $file):
						$filename = basename($file, ".rules.status");
			?>
				<div class="list-group">
					<a href="javascript:void(0);" onclick="setarCampo('<?=$filename?>')" class="list-group-item list-group-item-action" data-category="<?php echo $filename . '.rules.status';?>"><?php echo strtoupper($filename);?></a>
				</div>
			<?php
					endforeach;
				else:
			?>
				<div class="list-group text-center">
					<p><?=gettext("No categories found.")?></p>
				</div>
			<?php
				endif;
			?>
			</div>
		</div>
		<div class="aiia-wizard-step">
			<h1><?=gettext("Status de Sites/Aplicações/Serviços")?></h1>
			<div class="step-content">
				<div class="panel-body">
					<div class="col-sm-12">
						<div class="panel panel-default" style="margin-top:20px">
							<div class="panel-heading">
								<h2 class="panel-title" id="title-regra"><?=gettext("Status")?> - <?php echo strtoupper(substr($files[$j], 0, strlen($files[$j]) - 6));?></h2>
							</div>
							<?php
							$files = glob("/tmp/categorias/*.rules.status");
							if (count($files) > 0) { 
								foreach ($files as $file) {
									$filename = basename($file, ".rules.status");
									$linhas = file_get_contents("/tmp/categorias/" . $filename . ".rules.status");
									$linhas = explode("\n", $linhas);
									$linhas = array_filter($linhas);
							?>
								<div class="panel-body todas_tabelas_alvos" id="tabela-<?=$filename?>" style="display:none;">
									<div class="table-responsive">
										<div style="margin: 0px; padding: 0px; border: 0px solid transparent; width: 80%;margin-left: auto; display: flex;">
											<input type="text" class="form-control find-values" style="background-color:#FFF!important; float:right; width:60%; margin-left:auto; min-width:240px;" id="search-status_<?=$filename?>" placeholder="<?=gettext("Search for...")?>">
											<button type="click" class="btn btn-primary form-control" style="width:25%;margin-left:10px;min-width:120px;border-radius:5px;" onclick="searchButton('search-status_<?=$filename?>', 'lines_<?=$filename?>')"><i class="fa fa-search"></i> <?=gettext("Find")?></button>
											<button type="click" class="btn btn-danger form-control" style="width:25%;margin-left:10px;min-width:40px;border-radius:5px;" onclick="cleanFieldSerch('search-status_<?=$filename?>', 'lines_<?=$filename?>')"><i class="fa fa-times"></i> <?=gettext("Clean")?></button>
										</div>
										<table class="table table-bordered">
											<thead>
												<tr>
													<th style="vertical-align: inherit !important;"><i class="fa fa-navicon"></i> <?=gettext("Endereço")?></th>
													<th><?=gettext("Site")?>
													<th><?=gettext("Service")?>
												</tr>
											</thead>
											<tbody>
											<?php
												foreach ($linhas as $linha) {
													$valores = explode("||", $linha);

													if ($valores[1] != "1") {
														if (($valores[1] >= "200") && ($valores[1] <= "399")) {
															$valores[1] = "<i class='fa fa-check-circle text-success icon-primary' title='Status code {$valores[1]}'></i>";
														} else {
															$valores[1] = "<i class='fa fa-times-circle text-danger icon-primary' title='Status code {$valores[1]}'></i>";
														}
													} else {
														$valores[1] = "<i class='fa fa-times-circle text-danger icon-primary' title='Sem resposta'></i>";
													}

													if ($valores[2] == "0") {
														$valores[2] = "<i class='fa fa-check-circle text-success icon-primary' title='Respondendo'></i>";
													} else {
														$valores[2] = "<i class='fa fa-times-circle text-danger icon-primary' title='Sem resposta'></i>";
													}


													echo "<tr name='lines_$filename'>";
														echo "<td>{$valores[0]}</td>";
														echo "<td>{$valores[1]}</td>";
														echo "<td>{$valores[2]}</td>";
													echo "</tr>";
												}
											?>
											</tbody>
											<thead>
												<tr>
													<th style="vertical-align: inherit !important;"><i class="fa fa-navicon"></i> <?=gettext("Endereço")?></th>
													<th><?=gettext("Site")?>
													<th><?=gettext("Service")?>
												</tr>
											</thead>
										</table>
									</div>
								</div>
							<?php
								}
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>



<?php include("foot.inc"); ?>

<script>
function setarCampo(entrada) {
	document.getElementById("title-regra").innerHTML = "<?=gettext("Status categoria")?>" + " - " + entrada.split('_').join(' ').split('-').join(' ');
	for(i=0;i<=document.getElementsByClassName("todas_tabelas_alvos").length-1;i++) {
		document.getElementsByClassName("todas_tabelas_alvos")[i].style.display="none";
	}
	document.getElementById("tabela-" + entrada).style.display="block";
}

function searchButton(buttonSearch, linesTable) {
	var val = $.trim($('#' + buttonSearch).val()).replace(/ +/g, ' ').toLowerCase();
	var alllines = document.getElementsByName(linesTable);

	for(i=0;i<=alllines.length-1;i++) {
		var text = alllines[i].textContent.toLocaleLowerCase()
		if (text.indexOf(val) >= 0) {
			alllines[i].style.display='table-row';
		} else {
			alllines[i].style.display='none';
		}
	}
}

function cleanFieldSerch(buttonSearch, linesTable) {
	$('#' + buttonSearch).val("");
	var alllines = document.getElementsByName(linesTable);
	for(i=0;i<=alllines.length-1;i++) {
		alllines[i].style.display='table-row';
	}	
}
</script>