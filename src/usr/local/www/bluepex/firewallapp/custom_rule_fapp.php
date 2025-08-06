<?php


require_once("guiconfig.inc");
require_once("ipsec.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("util.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");


#/usr/local/share/suricata/rules/Custom.rules
#/usr/local/share/suricata/rules_fapp/Custom.rules

#alert udp any any -> any any (msg:"netflix"; content:"nflxvideo.net"; nocase; pcre:"/nflxvideo.net$/"; flow:to_server,established; sid:9650017; classtype:netflix; gid:32; rev:1; reference:url,nflxvideo.net;)
#alert tls any any -> any any (msg:"flickr"; tls_sni; content:"www.flickr.com"; nocase; pcre:"/flickr.com$/"; flow:to_server,established; sid:9000060; classtype:misc-activity; rev:1; reference:url,www.flickr.com; metadata: updated_at 2022_04_19;)

$reloadInterfacesAction = false;

if (isset($_POST['sidTarget']) && !empty($_POST['sidTarget']) && isset($_POST['actionTarget']) && $_POST['actionTarget'] ==  "del") {
	mwexec("grep -v '{$_POST['sidTarget']}' /usr/local/share/suricata/rules_fapp/custom_rules.rules > /usr/local/share/suricata/rules_fapp/custom_rules.rules.tmp && mv /usr/local/share/suricata/rules_fapp/custom_rules.rules.tmp /usr/local/share/suricata/rules_fapp/custom_rules.rules");
	$success = "Deletada a regra com sucesso";
	$reloadInterfacesAction = true;
}

if (isset($_POST['action']) && !empty($_POST['action'])) {

	$sids = "";
	$sidLimpos = [];

	$action = "";
	$protocol = "";
	$origin = "";
	$portOrigin = "";
	$direcao = "";
	$destino = "";
	$portDestino = "";
	$typeRule = "";
	$texto = "";

	$rule = "";

	$erros = [];
	$success = "";


	$maiorValor = 0;
	if (file_exists("/usr/local/share/suricata/rules_fapp/custom_rules.rules")) {
		$sids = explode("\n", shell_exec("cat /usr/local/share/suricata/rules_fapp/custom_rules.rules | awk -F\"sid:\" '{ print $2 }' | awk -F\";\" '{ print $1 }'"));
		foreach ($sids as $limpando) {
			$sidLimpos[] = $limpando;
		}
		array_filter($sidLimpos);
		foreach($sidLimpos as $sidNow) {
			if ($sidNow > $maiorValor) {
				$maiorValor = $sidNow;
			}
		}
		$maiorValor += 1;
	} else {
		if (file_exists("/usr/local/etc/suricata/classification.config")) {
			file_put_contents("/usr/local/etc/suricata/classification.config", "config classification: custom-rule,custom-rule-group,3", FILE_APPEND);
		}
		if (file_exists("/usr/local/share/suricata/classification.config")) {
			file_put_contents("/usr/local/share/suricata/classification.config", "config classification: custom-rule,custom-rule-group,3", FILE_APPEND);
		}
		file_put_contents("/usr/local/share/suricata/rules_fapp/custom_rules.rules", "pass http any any -> any any (msg:\"Bluepex-Web\"; content:\"bluepex.com\"; pcre:\"/bluepex.com$/\"; flow:to_server,established; sid:2000; classtype:custom-rule; rev:1; reference:url,bluepex.com;)\n", FILE_APPEND);
		$maiorValor=2001;
	}

	if (isset($_POST['action']) && !empty($_POST['action'])) {
		$action = $_POST['action'];
	} else {
		$erros[] = "Sem action";
	}

	if (isset($_POST['protocol']) && !empty($_POST['protocol'])) {
		$protocol = $_POST['protocol'];
	} else {
		$erros[] = "Sem protocol";
	}

	if (isset($_POST['origin']) && !empty($_POST['origin'])) {
		$origin = $_POST['origin'];
	} else {
		$erros[] = "Sem origin";
	}

	if (isset($_POST['portOrigin']) && !empty($_POST['portOrigin'])) {
		$portOrigin = $_POST['portOrigin'];
	} else {
		$erros[] = "Sem portOrigin";
	}

	if (isset($_POST['direcao']) && !empty($_POST['direcao'])) {
		$direcao = $_POST['direcao'];
	} else {
		$erros[] = "Sem direção da ação";
	}

	if (isset($_POST['destino']) && !empty($_POST['destino'])) {
		$destino = $_POST['destino'];
	} else {
		$erros[] = "Sem destino";
	}

	if (isset($_POST['portDestino']) && !empty($_POST['portDestino'])) {
		$portDestino = $_POST['portDestino'];
	} else {
		$erros[] = "Sem Porta Destino";
	}

	if (isset($_POST['typeRule']) && !empty($_POST['typeRule'])) {

		if ($_POST['typeRule'] == "---") {
			$erros[] = "Erro typeRule";
		}

		if ($_POST['typeRule'] == "contexto") {
			if (isset($_POST['valorContexto']) && !empty($_POST['valorContexto'])) {
				$valorContexto = preg_replace('/http:\/\/|https:\/\//','',$_POST['valorContexto']);
				$valorContextoPcre = preg_replace('/www./','',$valorContexto);
				$rule = "(msg:\"msg-custom-{$valorContexto}\"; content:\"{$valorContexto}\"; pcre:\"/{$valorContextoPcre}$/\"; flow:to_server,established; sid:{$maiorValor}; classtype:custom-rule; rev:1; reference:url,{$valorContexto};)";
				$success = "Regra com contexto gerada";
			} else {
				$erros[] = "Sem typeRule";
			}
		}

		if ($_POST['typeRule'] == "regex") {
			if (isset($_POST['valorRegex']) && !empty($_POST['valorRegex'])) {
				$rule = $_POST['valorRegex'];
				$rule = str_replace("/;[A-Za-z]/","/; [A-Za-z]/",$rule);
				if (strpos($rule, "sid:") === false) {
					$erros[] = "Sem sid";
				} else {	
					if (!empty(array_search(explode(";", explode("sid:", $_POST['valorRegex'])[1])[0], array_filter(explode("\n", shell_exec("cat /usr/local/share/suricata/rules_fapp/*.rules | awk -F\"sid:\" '{ print $2 }' | awk -F\";\" '{ print $1 }'")))))) {
						$erros[] = "Sid ja existe";
					}
				}

				if (strpos($rule, "gid:") !== false) {
					if (empty(array_search(explode(";", explode("gid:", $_POST['valorRegex'])[1])[0], array_filter(explode("\n", shell_exec("cat /usr/local/share/suricata/rules_fapp/*.rules | awk -F\"gid:\" '{ print $2 }' | awk -F\";\" '{ print $1 }'")))))) {
						$erros[] = "Gid não existe";
					}
				}

			} else {
				$erros[] = "Sem rule";
			}
		}
			
	} else {
		$erros[] = "Sem typeRule";
	}

	if (empty($erros)) {
		$current = "{$action} {$protocol} {$origin} {$portOrigin} {$direcao} {$destino} {$portDestino} {$rule}\n";
		file_put_contents("/usr/local/share/suricata/rules_fapp/custom_rules.rules", $current, FILE_APPEND);
		mwexec("cp /usr/local/share/suricata/rules_fapp/custom_rules.rules /usr/local/share/suricata/rules/custom_rules.rules");
		$success = "Regra registrada com sucesso";
		$reloadInterfacesAction = true;
	}

	//alert http any any -> any any (msg:"anydesk"; content:"anydesk"; http_user_agent; nocase; flow:to_server,established; sid:2000; classtype:anydesk; gid:1; rev:1;)

}

if ($reloadInterfacesAction) {
	global $suricata_rules_dir;
	
	$all_gtw = getInterfacesInGatewaysWithNoExceptions();
	
	exec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");
	exec("rm /usr/local/share/suricata/rules_fapp/_emerging.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas_ext.rules && rm /usr/local/share/suricata/rules_fapp/_ameacas.rules");
	exec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");			
		
	foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
		$if = get_real_interface($suricatacfg['interface']);
		$uuid = $suricatacfg['uuid'];
		if (suricata_is_running($uuid, $if)) {
			if (!in_array($if_real, $all_gtw,true)) {
				if ($suricatacfg['enable'] != 'on' || get_real_interface($suricatacfg['interface']) == "") {
					continue;
				}
				$ruledir = "{$suricata_rules_dir}";
				$currentfile = $_POST['currentfile'];
				$rulefile = "{$ruledir}{$currentfile}";
				$a_rule = &$config['installedpackages']['suricata']['rule'][$key];
				$rules_map = suricata_load_rules_map($rulefile);
				suricata_modify_sids_action($rules_map, $a_rule);
				$rebuild_rules = true;
				suricata_generate_yaml($a_rule);
				$rebuild_rules = false;
				/* Signal Suricata to "live reload" the rules */
				suricata_reload_config($a_rule);
				// Sync to configured CARP slaves if any are enabled
				suricata_sync_on_changes();
				//print_r("teste");die;
			}
		}
	}
	
	$reloadInterfacesAction = false;
}




$pglinks = array("./firewallapp/services.php", "@self");
$pgtitle = array(gettext("FirewallApp"), gettext("Custom Rules"));

include("head.inc");

if (count($erros) > 0 && isset($erros)) {
	print_input_errors($erros);
}

if (!empty($success) && empty($erros) && isset($success)) {
	print_info_box("{$success}", 'success');
}

?>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			When registering any rule on this page, it will be presented in the list of "Custom_Rules", it is worth mentioning that this rule will affect the FirewallApp service like any other rule, so use this option with awareness of possible consequences or unexpected occurrences.
			<br>
			If any custom rule causes a problem, manipulate it through the FirewallApp rules menu, it works the same as all other existing rules.
			<!--<br>
			<p style="color:red;">Note: If there is no custom rule set yet, after the first inclusion it will be possible to notice that there will be a rule referring to BluePex, it is a valid rule for web access to the company's website and serves as a validator of the customized rules, in this way , do not be surprised if it is presented.</p>-->
		</div>
	</div>
</div>

<?php

$form = new Form();
$form->addGlobal(new Form_Input(
	'',
	null,
	'hidden',
	'yes'
));


$section = new Form_Section('Página de regras customizada');


$section->addInput(new Form_Select(
	'action',
	'Estado inicial da regra',
	$numstate,
	array(
		'pass' => "PASS",
		'drop' => "DROP",
		'alert' => "ALERT"
	)
));

$section->addInput(new Form_Select(
	'protocol',
	'Selecione o protocolo da regra',
	'',
	array(
		'ip' => "IP",
		'udp' => "UDP",
		'tcp' => "TCP",
		'dns' => "DNS",
		'tls' => "TLS",
		'http' => "HTTP",
	)
));

$section->addInput(new Form_Input(
	'origin',
	'Origem',
	'text',
	'any',
	['placeholder' => 'any', 'readonly' => true]
))->setHelp('');

$section->addInput(new Form_Input(
	'portOrigin',
	'Porta de Origem',
	'text',
	'any',
	['placeholder' => 'any', 'readonly' => true]
))->setHelp('');


$section->addInput(new Form_Select(
	'direcao',
	'Direção',
	'',
	array(
		'->' => "->",
		'<>' => "<>"
	)
));

$section->addInput(new Form_Input(
	'destino',
	'Destino',
	'text',
	'',
	['placeholder' => 'any']
))->setHelp('');

$section->addInput(new Form_Input(
	'portDestino',
	'Porta de destino',
	'text',
	'',
	['placeholder' => 'any']
))->setHelp('');

$section->addInput(new Form_Select(
	'typeRule',
	'Tipo da regra',
	'',
	array(
		'---' => '---',
		'contexto' => "CONTEXTO"
	)
));

$section->addInput(new Form_Input(
	'valorContexto',
	'Entre com o endereço da página alvo (Tipo contexto)',
	'text',
	'',
	['placeholder' => 'http://example.com']
))->setHelp('');

$section->addInput(new Form_Input(
	'valorRegex',
	'Entre com a regra customizada (Tipo regex)',
	'text',
	'',
	['placeholder' => '(msg:"anydesk"; content:"anydesk"; http_user_agent; nocase; flow:to_server,established; sid:2000; classtype:anydesk; gid:1; rev:1;)']
))->setHelp('Esteja ciente que uma regra com Regex possar causar conflitos a demais já existente');

$form->add($section);
echo $form;

if (file_exists('/usr/local/share/suricata/rules_fapp/custom_rules.rules')) {
?>
	<hr style='margin-top:20px;'>
	<br>

	<form action="custom_rule_fapp.php" method="POST" style="display:none;" id="formAction" name="formAction">
		<input type="hidden" value="" id="sidTarget" name="sidTarget">
		<input type="hidden" value="" id="actionTarget" name="actionTarget">
	</form>

	<div class='table-responsive panel-body' style='margin-bottom:60px;'>
		<table class='table table-hover table-striped table-condensed'>
			<thead>
				<tr>
					<th>Estado</th>
					<th>Protocolo</th>
					<th>Origem</th>
					<th>Porta de Origem</th>
					<th>Direção</th>
					<th>Destino</th>
					<th>Porta de Destino</th>
					<th>Endereço</th>
					<th>Ação</th>
				</tr>
			</thead>
			<tbody>
				<?php
					foreach (array_filter(explode("\n", shell_exec("grep -v 'sid:2000' /usr/local/share/suricata/rules_fapp/custom_rules.rules | awk -F\" \" '{print $12 \"___\" $1 \"___\" $2 \"___\" $3 \"___\" $4 \"___\" $5 \"___\" $6 \"___\" $7 \"___\" $9}'"))) as $line_now) {
						echo "<tr>";
							$line = explode("___", $line_now);
							echo "<td>" . $line[1] . "</td>";
							echo "<td>" . $line[2] . "</td>";
							echo "<td>" . $line[3] . "</td>";
							echo "<td>" . $line[4] . "</td>";
							echo "<td>" . $line[5] . "</td>";
							echo "<td>" . $line[6] . "</td>";
							echo "<td>" . $line[7] . "</td>";
							echo "<td>" . explode("\"", $line[8])[1] . "</td>";
							echo "<td>";
								echo "<i onclick=\"deleteInterface('$line[0]')\" class='fa fa-trash'></i>";
							echo "</td>";
						echo "</tr>";
					}
				?>
			</tbody>
		</table>
	</div>
	
	<hr>

<?php

}

include("foot.inc");

?>
<script>
document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[8].style.display="none";
document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[9].style.display="none";
document.getElementById("typeRule").setAttribute("onChange", "selectTypeRule()");
function selectTypeRule() {
	valorSelecao = (document.getElementById("typeRule").value);
	if (valorSelecao == "regex") {
		document.getElementById("valorContexto").value="";
		document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[8].style.display="none";
		document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[9].style.display="block";
	}

	if (valorSelecao == "contexto") {
		document.getElementById("valorRegex").value="";
		document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[8].style.display="block";
		document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[9].style.display="none";
	}

	if (valorSelecao == "---") {
		document.getElementById("valorRegex").value="";
		document.getElementById("valorContexto").value="";
		document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[8].style.display="none";
		document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[9].style.display="none";
	}
}

function deleteInterface(interfaceSID) {
	$("#sidTarget").val(interfaceSID);
	$("#actionTarget").val("del");
	$("#formAction").submit();
}

</script>