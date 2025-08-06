<?php
require_once("functions.inc");
require_once("bp_webservice.inc");
require_once("service-utils.inc");
require_once('firewallapp_webservice.inc');
require_once('firewallapp_functions.inc');
require_once('firewallapp.inc');
require_once('util.inc');

$total_args = count($argv);
for ($i=1; $i<=$total_args; $i++) {
	if ($argv[$i] == "for_30m") {
		for_30m();
	} elseif ($argv[$i] == "for_24h") {
		for_24h();
	} elseif ($argv[$i] == "") {
		for_test();
	} elseif ($argv[$i] == "update") {
		update();
	} elseif ($argv[$i] == "update_fapp") {
		update_fapp();
	} elseif ($argv[$i] == "update_acp") {
		update_acp();
	} elseif ($argv[$i] == "for_test") {
		for_test();
	}
}

function for_30m() {
	// processa paises
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 9');

	// soma dados
	mwexec('/usr/local/bin/php /usr/local/www/active_protection/run_ameacas.php');

	// mapa index
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 8');

	// dash fapp 2.0
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 12');
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 13');
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 14');
}

function for_24h() {
	//limpa dados
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 10');

	// processa paises
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 9');

	// soma dados
	mwexec('/usr/local/bin/php /usr/local/www/active_protection/run_ameacas.php');

	// mapa index
	mwexec('/usr/local/bin/python3.8 /usr/local/www/active_protection/ameacas.py 0 8');

	clean();
	control_state_interface();
}

function update() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	$changetype = file_get_contents("/tmp/changetype");

	exec("rm /usr/local/share/suricata/rules_fapp/_emerging.rules");
	exec("rm /usr/local/share/suricata/rules_fapp/_ameacas_ext.rules");
	exec("rm /usr/local/share/suricata/rules_fapp/_ameacas.rules");

	exec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");

	exec("cp /usr/local/share/suricata/rules_fapp/reputation.list /usr/local/share/suricata/acp/rules/ && cp /usr/local/share/suricata/rules_fapp/categories.txt /usr/local/share/suricata/acp/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/acp/rules/");

	//Check Version FApp
	mwexec('cd /usr/local/share/suricata/ && rm -f /usr/local/share/suricata/fapp_version && fetch http://wsutm.bluepex.com/packs/fapp_version');

	if (!file_exists("/usr/local/share/suricata/fapp_install")) {
		file_put_contents("/usr/local/share/suricata/fapp_install", "9900099");
	}

	$fapp_version_install = file_get_contents("/usr/local/share/suricata/fapp_install");
	$fapp_version_atual   = file_get_contents("/usr/local/share/suricata/fapp_version");

	//Check Version Emerging
	mwexec('cd /usr/local/share/suricata/ && rm -f /usr/local/share/suricata/emerging_version && fetch http://wsutm.bluepex.com/packs/emerging_version');

	if (!file_exists("/usr/local/share/suricata/emerging_install")) {
		file_put_contents("/usr/local/share/suricata/emerging_install", "0.9.9");
	}

	$version_install = str_replace(".", "",file_get_contents("/usr/local/share/suricata/emerging_install"));
	$version_atual   = str_replace(".", "",file_get_contents("/usr/local/share/suricata/emerging_version"));

	if ( (intval($version_atual) > intval($version_install)) || ($changetype == 'yes') ) {

		global $g, $config, $rebuild_rules;

		global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

		init_config_arr(array('installedpackages', 'suricata', 'rule'));

		clean();

		install_rules_lists_acp();
		sleep(2);

		exec("cp -f /usr/local/pkg/suricata/yalm/acp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

		$tw_includes1 = file_get_contents("/etc/tw_includes");

		if (($tw_includes1 == "with traffic") || (empty($tw_includes1))) {
				exec("cd /usr/local/share/suricata/rules/ && rm * && cp /usr/local/share/suricata/rules_acp/_ameacas.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules_fapp/rede_sociais.rules /usr/local/share/suricata/rules/ && cp rules_fapp/portais.rules /usr/local/share/suricata/rules/ && cp rules_fapp/outros.rules /usr/local/share/suricata/rules/ && cp rules_fapp/streaming.rules /usr/local/share/suricata/rules/");
		} else if ($tw_includes1 == "only") {
				exec("cd /usr/local/share/suricata/rules/ && rm * && cp /usr/local/share/suricata/rules_acp/_ameacas.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/");	
		}	
		//marged customize ameacas_ext
		mwexec("/bin/sh /etc/mergeListsOfACP.sh"); 	
		exec("cp /usr/local/share/suricata/rules_acp/_ameacas_ext.rules /usr/local/share/suricata/rules/");

		if (!is_array($config['installedpackages']['suricata']['rule'])) {
			$config['installedpackages']['suricata']['rule'] = array();
		}

		foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
			
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			
			if (in_array($if, $all_gtw,true)) {
				if (suricata_is_running($uuid, $if)) {

					if ($suricatacfg['mixed_mode'] == 'on') {

		                exec("cd /usr/local/share/suricata/rules/ && rm *");
		                exec("cp /usr/local/share/suricata/acp_mix/suricata2.rules /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/");
		                exec("cp /usr/local/share/suricata/rules_acp/_emerging.rules /usr/local/share/suricata/rules/");

		            }
				
					$dh  = opendir("/usr/local/share/suricata/rules_acp");
					$rulesetsfile = "";
					while (false !== ($filename = readdir($dh))) {
						if (substr($filename, -5) != "rules") {
							continue;
						}
						$rulesetsfile .= basename($filename) . "||";
					}
					
					$config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");
					file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');
					// Save configuration changes
					write_config("Active Protection pkg: modified ruleset configuration");
					exec("cp /usr/local/share/suricata/otx/ransomd5/* /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/");
					
					$ruledir = "{$suricata_rules_dir}";
					$currentfile = $_POST['currentfile'];
					$rulefile = "{$ruledir}{$currentfile}";
					$a_rule = &$config['installedpackages']['suricata']['rule'][$key];
					//print_r($rulefile);die;
					$rules_map = suricata_load_rules_map($rulefile);
					suricata_modify_sids_action($rules_map, $a_rule);
					$rebuild_rules = true;
					suricata_generate_yaml($a_rule);
					$rebuild_rules = false;
					/* Signal Suricata to "live reload" the rules */
					suricata_reload_config($a_rule);
					// Sync to configured CARP slaves if any are enabled
					suricata_sync_on_changes();

					if ($suricatacfg['mixed_mode'] == 'on') {
						if (intval(trim(shell_exec("ps aux | grep 'suricata_{$uuid}_{$if}' | grep -v grep | grep 'suricata2.yaml' -c"))) > 0) {
							exec("kill -9 `ps aux | grep 'suricata_{$uuid}_{$if}' | grep 'suricata2.yaml' | grep -v grep | awk -F\" \" '{print $2}'`");
							sleep(2);
						}
						suricata_start_mixed_mode($suricatacfg, $if, $uuid);
					}
					
				}
			}

		}
		mwexec_bg('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');

		exec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules");

	}

	if ( ($fapp_version_atual != $fapp_version_install) || ($changetype == 'yes') ) {

		global $g, $config, $rebuild_rules;

		global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

		init_config_arr(array('installedpackages', 'suricata', 'rule'));
		
		clean();

		install_rules_lists();
		sleep(2);

		exec("cp -f /usr/local/pkg/suricata/yalm/fapp/suricata_yaml_template.inc /usr/local/pkg/suricata/");

		mwexec("cd /usr/local/share/suricata/rules/ && rm * && cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_ameacas.rules && rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules && rm -f /usr/local/share/suricata/rules/_emerging.rules");
		
		foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
				
			$if = get_real_interface(strtolower($suricatacfg['interface']));
			$uuid = $suricatacfg['uuid'];

			if (!in_array($if, $all_gtw,true)) {
				if (suricata_is_running($uuid, $if)) {
			
					if ($suricatacfg['enable'] != 'on' || $if == "") {
						continue;
					}

					exec("find /usr/local/etc/suricata/suricata_{$uuid}_{$if}/rules/ -iname \"*.txt\" -type f -exec rm -rfv {} \;");

					$dh  = opendir("/usr/local/share/suricata/rules");

					$rulesetsfile = "";
					while (false !== ($filename = readdir($dh))) {
						if (substr($filename, -5) != "rules") {
							continue;
						}
						$rulesetsfile .= basename($filename) . "||";
					}

					$config['installedpackages']['suricata']['rule'][$key]['rulesets'] = rtrim($rulesetsfile, "||");

					// Save configuration changes
					write_config("FirewallApp pkg: modified ruleset configuration");

					file_put_contents("/var/run/suricata_{$if}{$uuid}_starting", '');

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

				}
			}

		}
		mwexec_bg('/usr/local/bin/python3.8 /usr/local/bin/wf_monitor.py');

	}

}

function update_fapp() {

	$changetype = file_get_contents("/tmp/changetype");

	//Check Version FApp
	mwexec('cd /usr/local/share/suricata/ && rm -f /usr/local/share/suricata/fapp_version && rm -f /usr/local/share/suricata/fapp_install && fetch http://wsutm.bluepex.com/packs/fapp_version');

	if (!file_exists("/usr/local/share/suricata/fapp_install")) {
		file_put_contents("/usr/local/share/suricata/fapp_install", "9900099");
	}

	$fapp_version_install = file_get_contents("/usr/local/share/suricata/fapp_install");
	$fapp_version_atual   = file_get_contents("/usr/local/share/suricata/fapp_version");


	if ( ($fapp_version_atual != $fapp_version_install) || ($changetype == 'yes') ) {

		clean();
		install_rules_lists();

	}

}


function update_acp() {

	$changetype = file_get_contents("/tmp/changetype");

	//Check Version Emerging
	mwexec('cd /usr/local/share/suricata/ && rm -f /usr/local/share/suricata/emerging_version && rm -f /usr/local/share/suricata/emerging_install && fetch http://wsutm.bluepex.com/packs/emerging_version');

	if (!file_exists("/usr/local/share/suricata/emerging_install")) {
		file_put_contents("/usr/local/share/suricata/emerging_install", "0.9.9");
	}

	$version_install = str_replace(".", "",file_get_contents("/usr/local/share/suricata/emerging_install"));
	$version_atual   = str_replace(".", "",file_get_contents("/usr/local/share/suricata/emerging_version"));

	if ( (intval($version_atual) > intval($version_install)) || ($changetype == 'yes') ) {

		clean();
		install_rules_lists_acp();
	}

}

function for_test() {

	//control_state_interface();
	
}

?>
