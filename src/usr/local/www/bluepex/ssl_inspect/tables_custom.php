<?php


require_once("guiconfig.inc");
require_once("ipsec.inc");
require_once("filter.inc");
require_once("shaper.inc");
require_once("util.inc");
require_once("bluepex/firewallapp_webservice.inc");
require_once("bluepex/firewallapp.inc");
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

if (is_array($erros) && count($erros)) {
    print_input_errors($erros);
}

if (!empty($success) && empty($erros) && isset($success)) {
	print_info_box("{$success}", 'success');
}

$tab_array = array();
$tab_array[] = array(gettext("Real Time"), false, "./ssl_inspect.php");
$tab_array[] = array(gettext("Registers"), false, "./ssl_inspect_registers.php");
$tab_array[] = array(gettext("Tables Custom Rules"), true, "./tables_custom.php");
$tab_array[] = array(gettext("Status"), false, "./netify-fwa_status.php");
$tab_array[] = array(gettext("Applications"), false, "./netify-fwa_apps.php");
$tab_array[] = array(gettext("Protocols"), false, "./netify-fwa_protos.php");
$tab_array[] = array(gettext("Whitelist"), false, "./netify-fwa_whitelist.php");
display_top_tabs($tab_array);

?>

<div class="infoblock">
	<div class="alert alert-info clearfix" role="alert">
		<div class="pull-left">
			<?=gettext("This page demonstrates all Active Protection and FirewallApp custom rules.")?>
		</div>
	</div>
</div>

<?php
if (file_exists('/usr/local/sbin/netifyd')) {

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

			.pb-5, .py-5 {
				padding-bottom: unset!important;
			}

			.table {
				margin-bottom: 0px;
				margin-top: 30px;
			}
		</style>

		<hr style='margin-top:20px;'>
		<br>

		<form action="tables_custom.php" method="POST" style="display:none;" id="formAction" name="formAction">
			<input type="hidden" value="" id="typeTarget" name="typeTarget">
			<input type="hidden" value="" id="sidTarget" name="sidTarget">
			<input type="hidden" value="" id="actionTarget" name="actionTarget">
		</form>

		<div class='table-responsive panel-body' style='margin-bottom:60px;'>
			<div class="title-description pb-5">
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
					if (intval(trim(shell_exec("grep 'msg-custom-acp-tls-http' /usr/local/share/suricata/rules_acp/_ameacas_ext.rules -c"))) > 0) {
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
					}
					?>
				</tbody>
			</table>
		</div>

		<div class='table-responsive panel-body' style='margin-bottom:60px;'>
			<div class="title-description pb-5">
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
						if (file_exists('/usr/local/share/suricata/rules_fapp/custom_rules.rules')) {
							if (intval(trim(shell_exec("grep -v 'sid:2000' /usr/local/share/suricata/rules_fapp/custom_rules.rules -c"))) > 0) { 
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
							}
						}
					?>
				</tbody>
			</table>
		</div>
		
<?php
} else {
	echo "<p>" . gettext("SSL Inspect package is not installed on the device.") . "</p>";
}

include("foot.inc");

?>
<script>
document.getElementsByClassName("form-group col-11 left-border-blue box-white p-6")[0].style.display="none";

function deleteInterface(targetType, interfaceSID) {
	$("#typeTarget").val(targetType);
	$("#sidTarget").val(interfaceSID);
	$("#actionTarget").val("del");
	$("#formAction").submit();
}

</script>