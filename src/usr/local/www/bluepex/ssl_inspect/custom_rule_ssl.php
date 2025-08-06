<?php


require_once("guiconfig.inc");
require_once("ipsec.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("util.inc");
require_once("firewallapp_webservice.inc");
require_once("firewallapp.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");
$all_gtw = getInterfacesInGatewaysWithNoExceptions();
$all_gtw = array_filter(array_unique($all_gtw));

#/usr/local/share/suricata/rules/Custom.rules
#/usr/local/share/suricata/rules_fapp/Custom.rules

#8000000 - SID BASE PARA REGRAS DE ACP
#alert udp any any -> any any (msg:"netflix"; content:"nflxvideo.net"; nocase; pcre:"/nflxvideo.net$/"; flow:to_server,established; sid:9650017; classtype:netflix; gid:32; rev:1; reference:url,nflxvideo.net;)
#alert tls any any -> any any (msg:"flickr"; tls_sni; content:"www.flickr.com"; nocase; pcre:"/flickr.com$/"; flow:to_server,established; sid:9000060; classtype:misc-activity; rev:1; reference:url,www.flickr.com; metadata: updated_at 2022_04_19;)

$reloadInterfacesAction = false;
$selectedService = "";

if (isset($_POST['typeTarget']) && !empty($_POST['typeTarget']) && isset($_POST['sidTarget']) && !empty($_POST['sidTarget']) && isset($_POST['actionTarget']) && $_POST['actionTarget'] ==  "del") {
	if ($_POST['typeTarget'] == "fapp") {
		mwexec("grep -v '{$_POST['sidTarget']}' /usr/local/share/suricata/rules_fapp/custom_rules.rules > /usr/local/share/suricata/rules_fapp/custom_rules.rules.tmp && mv /usr/local/share/suricata/rules_fapp/custom_rules.rules.tmp /usr/local/share/suricata/rules_fapp/custom_rules.rules");
		$selectedService = "fapp";
	}
	if ($_POST['typeTarget'] == "acp") {
		mwexec("grep -v '{$_POST['sidTarget']}' /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules > /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp && mv /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules.tmp /usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules");
		mwexec("grep -v '{$_POST['sidTarget']}' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules > /usr/local/share/suricata/rules_acp/_ameacas_ext.rules.tmp && mv /usr/local/share/suricata/rules_acp/_ameacas_ext.rules.tmp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules");
		$selectedService = "acp";
	}
	$success = "Deletada a regra com sucesso";
	$reloadInterfacesAction = true;
}

$erros = [];
$success = "";

if (isset($_POST['action']) && !empty($_POST['action']) && isset($_POST['interface']) && empty($_POST['interface'])) {
	$erros[] = "Não foi identificada uma interface para se aplicar a nova regra no Active Protection / FirewallAPP.";
}

function confirmClassificationEtc() {
	if (file_exists('/usr/local/etc/suricata/classification.config')) {
		return intval(trim(shell_exec("grep -r \"config classification: custom-rule,custom-rule-group,3\" /usr/local/etc/suricata/classification.config -c")));
	}
}

function confirmClassificationShare() {
	if (file_exists('/usr/local/share/suricata/classification.config')) {
		return intval(trim(shell_exec("grep -r \"config classification: custom-rule,custom-rule-group,3\" /usr/local/share/suricata/classification.config -c")));
	}
}

if (isset($_POST['action']) && !empty($_POST['action']) && isset($_POST['interface']) && !empty($_POST['interface'])) {

	$sids = "";
	$sidLimpos = [];

	$action = "";
	$protocol = "";
	$origin = "";
	$portOrigin = "";
	$direcao = "";
	$destino = "";
	$portDestino = "";
	$texto = "";

	$rule = "";

	$maiorValor = 0;

	$interfaceRule = $_POST['interface'];

	$interfacesServices = [];
	foreach ($config['installedpackages']['suricata']['rule'] as $key => $interface) {
		$interfacesServices[] = get_real_interface($interface['interface']);
	}

	if (in_array($interfaceRule, $interfacesServices)) {
		if (in_array($interfaceRule,$all_gtw,true)) {
			$selectedService = "acp";
		} 
		if (!in_array($interfaceRule,$all_gtw,true)) {
			$selectedService = "fapp";
		}
	} else {
		$erros[] = "Não é possível criar uma regra para uma intância não existente de Active Protection / FirewallAPP";
	}


	if ($selectedService == "fapp") {
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
				if (confirmClassificationEtc() == 0) {
					file_put_contents("/usr/local/etc/suricata/classification.config", "config classification: custom-rule,custom-rule-group,3", FILE_APPEND);
				}
			}
			if (file_exists("/usr/local/share/suricata/classification.config")) {
				if (confirmClassificationShare() == 0) {
					file_put_contents("/usr/local/share/suricata/classification.config", "config classification: custom-rule,custom-rule-group,3", FILE_APPEND);
				}
			}
			file_put_contents("/usr/local/share/suricata/rules_fapp/custom_rules.rules", "pass http any any -> any any (msg:\"Bluepex-Web\"; content:\"bluepex.com\"; pcre:\"/bluepex.com$/\"; flow:to_server,established; sid:2000; classtype:custom-rule; rev:1; reference:url,bluepex.com;)\n", FILE_APPEND);
			$maiorValor=2001;
		}
	} elseif ($selectedService == "acp") {
		if (intval(trim(shell_exec("grep 'msg-custom-acp-tls-http' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules -c"))) > 0) {
			$sids = explode("\n", shell_exec("grep 'msg-custom-acp-tls-http' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules | awk -F\"sid:\" '{ print $2 }' | awk -F\";\" '{ print $1 }'"));
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
				if (confirmClassificationEtc() == 0) {
					file_put_contents("/usr/local/etc/suricata/classification.config", "config classification: custom-rule,custom-rule-group,3", FILE_APPEND);
				}
			}
			if (file_exists("/usr/local/share/suricata/classification.config")) {
				if (confirmClassificationShare() == 0) {
					file_put_contents("/usr/local/share/suricata/classification.config", "config classification: custom-rule,custom-rule-group,3", FILE_APPEND);
				}
			}
			$maiorValor=8000000;
		}
	
	} else {
		$erros[] = "Não pode ser encontrado um serviço para adicionar está nova regra.";	
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

	if (isset($_POST['valorContexto']) && !empty($_POST['valorContexto'])) {
		$valorContexto = preg_replace('/http:\/\/|https:\/\//','',$_POST['valorContexto']);
		$valorContextoPcre = preg_replace('/www./','',$valorContexto);
		if ($selectedService == "fapp") {
			$rule = "(msg:\"msg-custom-{$valorContexto}\"; content:\"{$valorContexto}\"; pcre:\"/{$valorContextoPcre}$/\"; flow:to_server,established; sid:{$maiorValor}; classtype:custom-rule; rev:1; reference:url,{$valorContexto};)";
		}
		if ($selectedService == "acp") {
			$rule = "(msg:\"msg-custom-acp-tls-http-{$valorContexto}\"; content:\"{$valorContexto}\"; pcre:\"/{$valorContextoPcre}$/\"; flow:to_server,established; sid:{$maiorValor}; classtype:custom-rule; rev:1; reference:url,{$valorContexto};)";
		}
	} else {
		$erros[] = "Sem valor de contexto";
	}

	if (empty($erros)) {
		$current = "{$action} {$protocol} {$origin} {$portOrigin} {$direcao} {$destino} {$portDestino} {$rule}\n";
		if ($selectedService == "fapp") {
			file_put_contents("/usr/local/share/suricata/rules_fapp/custom_rules.rules", $current, FILE_APPEND);
			mwexec("cp /usr/local/share/suricata/rules_fapp/custom_rules.rules /usr/local/share/suricata/rules/custom_rules.rules");
		}
		if ($selectedService == "acp") {
			file_put_contents("/usr/local/share/suricata/rules_acp/_ameacas_ext.customize.rules", $current, FILE_APPEND);
			file_put_contents("/usr/local/share/suricata/rules_acp/_ameacas_ext.rules", $current, FILE_APPEND);
			mwexec("/bin/sh /etc/mergeListsOfACP.sh");
		}
		
		$success = "Regra registrada com sucesso";
		$reloadInterfacesAction = true;
	}

	//alert http any any -> any any (msg:"anydesk"; content:"anydesk"; http_user_agent; nocase; flow:to_server,established; sid:2000; classtype:anydesk; gid:1; rev:1;)

}

if ($reloadInterfacesAction) { 
	if (!empty($selectedService)) {
		if ($selectedService == "fapp") {
			global $suricata_rules_dir;
				
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

		if ($selectedService == "acp") {
			mwexec("/usr/local/bin/php /usr/local/www/active_protection/update_interfaces_rules.php");
		}
	}
}

function prepareURLTarget($protocol, $address) {
	if ($protocol == "tls") {
		return "https://" . $address;
	} elseif ($protocol == "http") {
		return "http://" . $address;
	} else {
		return $address;
	}
}



$pglinks = array("", "./ssl_inspect/ssl_inspect.php", "@self");
$pgtitle = array(gettext("Services"), gettext("SSL Inspect"), gettext("Custom Rules"));

include("head.inc");

if (count($erros) > 0 && isset($erros)) {
	print_input_errors($erros);
}

if (!empty($success) && empty($erros) && isset($success)) {
	print_info_box("{$success}", 'success');
}


$tab_array = array();
$tab_array[] = array(gettext("Real Time"), false, "./ssl_inspect.php");
$tab_array[] = array(gettext("Registers"), false, "./ssl_inspect_registers.php");
$tab_array[] = array(gettext("Tables Custom Rules"), false, "./tables_custom.php");
$tab_array[] = array(gettext("Custom Rules"), true, "./custom_rule_ssl.php");
$tab_array[] = array(gettext("Status"), false, "./netify-fwa_status.php");
$tab_array[] = array(gettext("Applications"), false, "./netify-fwa_apps.php");
$tab_array[] = array(gettext("Protocols"), false, "./netify-fwa_protos.php");
$tab_array[] = array(gettext("Whitelist"), false, "./netify-fwa_whitelist.php");

display_top_tabs($tab_array);

?>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<?=gettext("This page gives the option to generate rules for Active Protection and FirewallApp based on selected traffic information, be aware that if the above services are not activated, the rules will not be added.")?>
		</div>
	</div>
</div>

<?php
if (file_exists('/usr/local/sbin/netifyd')) {

	if (isset($_POST['interface']) && !empty($_POST['interface']) && isset($_POST['desc_interface']) && !empty($_POST['desc_interface'])) {

		$form = new Form();
		$form->addGlobal(new Form_Input(
			'',
			null,
			'hidden',
			'yes'
		));

		$section = new Form_Section(gettext("Create custom rule"));
		
		$section->addInput(new Form_Input(
			'',
			gettext("Informative"),
			'hidden',
			''
		))->setHelp(gettext("The rule created in the interface") . " " . strtoupper($_POST['desc_interface']) . ' (' . $_POST['interface'] . ') ' . gettext("will only affect services to which it relates."));

		$section->addInput(new Form_Input(
			'interface',
			'interface',
			'text',
			$_POST['interface'],
			['placeholder' => 'any']
		));

		$section->addInput(new Form_Select(
			'action',
			gettext("Initial rule state"),
			$numstate,
			array(
				'pass' => "PASS",
				'drop' => "DROP",
				'alert' => "ALERT"
			)
		))->setHelp(gettext("Rule action state"));

		$section->addInput(new Form_Select(
			'protocol',
			gettext('Select rule protocol'),
			$_POST['protocol'],
			array(
				'tls' => "TLS",
				'http' => "HTTP",
			)
		))->setHelp(gettext('Protocols the rule will affect.'));

		$section->addInput(new Form_Input(
			'origin',
			gettext('Source'),
			'text',
			$_POST["ip_source"],
			['placeholder' => 'any']
		))->setHelp(gettext("If you want all internal addresses to prevent traffic to a certain external address, enter the value of \"any\" in the field."));

		if (isset($_POST["port_source"])) {
			$section->addInput(new Form_Input(
				'portOrigin',
				gettext('Source Port'),
				'text',
				'any',
				['placeholder' => 'any']
			))->setHelp('<p style="color:red;">' . gettext('The port has been identified') . ' ' . $_POST["port_source"] . ' ' . gettext("was used for this connection, we recommend that you use \"any\" in the ports field as these values ​​are normally random."));
		} else {
			$section->addInput(new Form_Input(
				'portOrigin',
				gettext('Source Port'),
				'text',
				'any',
				['placeholder' => 'any']
			))->setHelp('<p style="color:red;">' . gettext("We recommend using \"any\" in the ports field as these values ​​are normally random.") . '</p>');
		}

		$section->addInput(new Form_Select(
			'direcao',
			gettext('Direction'),
			'',
			array(
				'->' => "->",
				'<>' => "<>"
			)
		))->setHelp(gettext("Direction the rule should affect."));
		
		$section->addInput(new Form_Input(
			'destino',
			gettext('Destination'),
			'text',
			$_POST['ip_external'],
			['placeholder' => 'any']
		))->setHelp(gettext("If you want all external addresses not to be accessed based on the given URL, enter the value of \"any\" in the field."));

		if (isset($_POST["port_source"])) {
			$section->addInput(new Form_Input(
				'portDestino',
				gettext('Destination Port'),
				'text',
				'any',
				['placeholder' => 'any']
			))->setHelp('<p style="color:red;">' . gettext("It was identified that the port") . ' ' . $_POST["port_external"] . ' ' . gettext("was used for this connection, we recommend that you use \"any\" in the ports field as these values ​​are normally random."));
		} else {
			$section->addInput(new Form_Input(
				'portDestino',
				gettext('Destination Port'),
				'text',
				'any',
				['placeholder' => 'any']
			))->setHelp('<p style="color:red;">' . gettext("We recommend using \"any\" in the ports field as these values ​​are normally random."));
		}

		$section->addInput(new Form_Input(
			'valorContexto',
			gettext("Enter target page address (Context type)"),
			'text',
			prepareURLTarget($_POST['protocol'], $_POST['address']),
			['placeholder' => 'http://example.com']
		))->setHelp(gettext('Address that will be worked on in the rule.'));

		$form->add($section);
		echo $form;
	
	} else {
		echo "<hr style='margin-top:10px;'><div style='margin-top:10px;' class='alert alert-primary'>" . gettext("The custom rule record form is only available when there is information brought by the actions of the 'Real time' and 'Records' tables, in addition, if you have already registered a custom rule and this message is being displayed, this is for reasons that information brought has already been used and cleaned.") . "</div>";
	}

	?>
		<style>
			.title-description {
				margin-top: unset !important;
				text-align: center;
				padding-right: unset !important;
				padding-left: 15px;
				background-color: #fff;
				width: 43%;
				position: absolute;
				color: #333;
				margin-right: -50%;
				margin-left: 47%;
				transform: translate(-50%, -50%);
			}

			.table {
				margin-bottom: 0px;
				margin-top: 30px;
			}
		</style>

		<br>

		<form action="custom_rule_ssl.php" method="POST" style="display:none;" id="formAction" name="formAction">
			<input type="hidden" value="" id="typeTarget" name="typeTarget">
			<input type="hidden" value="" id="sidTarget" name="sidTarget">
			<input type="hidden" value="" id="actionTarget" name="actionTarget">
		</form>

	<?php if (intval(trim(shell_exec("grep 'msg-custom-acp-tls-http' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules -c"))) > 0) { ?>

		<hr style='margin-top:20px;'>

		<div class='table-responsive panel-body' style='margin-bottom:60px;'>
			<div class="title-description pb-5" style="padding-bottom: unset!important;">
				<h5 class="text-color-blue"><?=gettext("Custom Active Protection Rules")?></h5>
			</div>
			<table class='table table-hover table-striped table-condensed'>
				<thead>
					<tr>
						<th><?=gettext("State")?></th>
						<th><?=gettext("Protocol")?></th>
						<th><?=gettext("Source")?></th>
						<th><?=gettext("Source Port")?></th>
						<th><?=gettext("Direction")?></th>
						<th><?=gettext("Destination")?></th>
						<th><?=gettext("Destination Port")?></th>
						<th><?=gettext("Address")?></th>
						<th><?=gettext("Action")?></th>
					</tr>
				</thead>
				<tbody>
					<?php
						foreach (array_filter(explode("\n", shell_exec("grep 'msg-custom-acp-tls-http' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules | awk -F\" \" '{print $12 \"___\" $1 \"___\" $2 \"___\" $3 \"___\" $4 \"___\" $5 \"___\" $6 \"___\" $7 \"___\" $9}'"))) as $line_now) {
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
									echo "<i onclick=\"deleteInterface('acp','$line[0]')\" class='fa fa-trash'></i>";
								echo "</td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>
		</div>

	<?php 
	}
	if (file_exists('/usr/local/share/suricata/rules_fapp/custom_rules.rules')) {
		if (intval(trim(shell_exec("grep -v 'sid:2000' /usr/local/share/suricata/rules_fapp/custom_rules.rules -c"))) > 0) { 
	?>

		<hr style='margin-top:20px;'>

		<div class='table-responsive panel-body' style='margin-bottom:60px;'>
			<div class="title-description pb-5" style="padding-bottom: unset!important;">
				<h5 class="text-color-blue"><?=gettext("FirewallApp Custom Rules")?></h5>
			</div>
			<table class='table table-hover table-striped table-condensed'>
				<thead>
					<tr>
						<th><?=gettext("State")?></th>
						<th><?=gettext("Protocol")?></th>
						<th><?=gettext("Source")?></th>
						<th><?=gettext("Source Port")?></th>
						<th><?=gettext("Direction")?></th>
						<th><?=gettext("Destination")?></th>
						<th><?=gettext("Destination Port")?></th>
						<th><?=gettext("Address")?></th>
						<th><?=gettext("Action")?></th>
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
									echo "<i onclick=\"deleteInterface('fapp', '$line[0]')\" class='fa fa-trash'></i>";
								echo "</td>";
							echo "</tr>";
						}
					?>
				</tbody>
			</table>
		</div>
		
<?php
		}
	}
} else {
	echo "<p>" . gettext("SSL Inspect package is not installed on the device.") . "</p>";
}

include("foot.inc");

?>
<script>
document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[1].style.display="none";

function deleteInterface(targetType, interfaceSID) {
	$("#typeTarget").val(targetType);
	$("#sidTarget").val(interfaceSID);
	$("#actionTarget").val("del");
	$("#formAction").submit();
}

</script>