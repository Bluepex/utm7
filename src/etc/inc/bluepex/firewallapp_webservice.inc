<?php
require("config.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

init_config_arr(array('installedpackages', 'suricata', 'rule'));

define("DEBUG", false);
define("WSUTM_CACHE_FILE", "{$g['tmp_path']}/wsutm.cache");

define("MODULE_PATH", $g['www_path'] . "/one_click");
define("LISTS_PATH", MODULE_PATH . "/lists");
define("CACHE_FILE", MODULE_PATH . "/group_categories.json");

define("MODULE_PATH_APP", $g['/'] . "/usr/local/etc/suricata");
define("LISTS_PATH_APP", rtrim(SURICATA_RULES_DIR, "/"));
define("CACHE_FILE_APP", MODULE_PATH_APP . "/group_rules.json");

function create_rules_cache_file() {
	$bluepexfirewallapp = do_webservice_request("bluepex-firewall-app", "group-with-rules");
	if (!isset($bluepexfirewallapp->data->groups_rules) || empty($bluepexfirewallapp->data->groups_rules)) {
		log_error(gettext("FirewallApp warning - Could not install cache file of the group!"));
		return false;
	}
	file_put_contents(CACHE_FILE_APP, json_encode($bluepexfirewallapp->data->groups_rules));
	if (file_exists(CACHE_FILE_APP) && filesize(CACHE_FILE_APP) > 0) {
		log_error(gettext("FirewallApp - Cache file has been installed successfully!"));
		return true;
	}
	return false;
}

function getInterfaceNameRepAcp_tw() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;


	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$all_interfaces_acp = [];

	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		if ($suricatacfg['enable'] == "on") {						
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			if (in_array($if,$all_gtw,true)) {
				if (suricata_is_running($uuid, $if)) {
					$all_interfaces_acp[] = "suricata_".$if.$uuid;
				} 
			}
		}
	}

	return $all_interfaces_acp;

}

function getInterfaceNameRepFapp_tw() {

	$all_gtw = getInterfacesInGatewaysWithNoExceptions();

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;


	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$all_interfaces_acp = [];

	foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
		if ($suricatacfg['enable'] == "on") {						
			$if = get_real_interface($suricatacfg['interface']);
			$uuid = $suricatacfg['uuid'];
			if (!in_array($if,$all_gtw,true)) {
				if (suricata_is_running($uuid, $if)) {
					$all_interfaces_acp[] = "suricata_".$if.$uuid;
				} 
			}
		}
	}

	return $all_interfaces_acp;

}

function getInterfaceNameRepAcp() {

	init_config_arr(array('installedpackages', 'suricata', 'rule'));

	$a_instance = &$config['installedpackages']['suricata']['rule'];
	$a_rule = &$config['installedpackages']['suricata']['rule'];

	$suricata_uuid = $a_instance[$instanceid]['uuid'];
	$if_real = get_real_interface($a_instance[$instanceid]['interface']);

	global $g, $config;

	global $suricata_rules_dir, $suricatalogdir, $if_friendly, $if_real, $suricatacfg;

	$ret = '';

	if (!is_array($config['installedpackages']['suricata']['rule']))
	$config['installedpackages']['suricata']['rule'] = array();

	$a_rule = &$config['installedpackages']['suricata']['rule'];

	for ($id = 0; $id <= count($a_rule)-1; $id++) {

		$if_real = get_real_interface($a_rule[$id]['interface']);

		$suricata_uuid = $a_rule[$id]['uuid'];

		foreach ($config['installedpackages']['suricata']['rule'] as $suricatacfg) {
			$if = get_real_interface($suricatacfg['interface']);
		    $uuid = $suricatacfg['uuid'];

		    if ($suricatacfg['interface'] === 'wan') {
					$ret = "suricata_".$uuid."_".$if;
			}
		}

	}

	return $ret;

}

function is_dir_empty($dir) {
    foreach (new DirectoryIterator($dir) as $fileInfo) {
        if($fileInfo->isDot()) continue;
        return false;
    }
    return true;
}

function install_rules_lists() {

	// New Method Download Rules FApp
	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");

	init_config_arr(array('system', 'firewallapp'));

	if (isset($config['system']['firewallapp']['type'])) {
		//$ptype = &$config['system']['firewallapp']['type'];
		$ptype = 0;
		/*
		if (isset($config['system']['firewallapp']['type']) && !empty($config['system']['firewallapp']['type'])) {
			$ptype = $config['system']['firewallapp']['type'];
		} else {
			$ptype = 0;
		}
		*/
	} else {
		$config['system']['firewallapp']['type'] = 0;
		$savemsg = gettext("Setting default firewallapp");

		write_config($savemsg);
	}

	//Del od Groups 
	//exec("cd /usr/local/share/suricata/rules && rm -f app-layer-events.rules && rm -f decoder-events.rules && rm -f dnp3-events.rules && rm -f dns-events.rules && rm -f files.rules && rm -f http-events.rules && rm -f ipsec-events.rules && rm -f kerberos-events.rules && rm -f modbus-events.rules && rm -f nfs-events.rules && rm -f ntp-events.rules && rm -f smb-events.rules && rm -f smtp-events.rules && rm -f stream-events.rules && rm -f tls-events.rules");
	delete_file_rules_rules_fapp($delete_rules_usullay=true, $delete_rules_fapp=false);

	//Check Version FApp
	mwexec('cd /usr/local/share/suricata/ && rm -f /usr/local/share/suricata/fapp_version && fetch http://wsutm.bluepex.com/packs/fapp_version');

	if (!file_exists("/usr/local/share/suricata/fapp_install")) {
		file_put_contents("/usr/local/share/suricata/fapp_install", "9900099");
	}

	//$fapp_version_install = str_replace(".", "",file_get_contents("/usr/local/share/suricata/fapp_install"));
	//$fapp_version_atual   = str_replace(".", "",file_get_contents("/usr/local/share/suricata/fapp_version"));
	$fapp_version_install = file_get_contents("/usr/local/share/suricata/fapp_install");
	$fapp_version_atual   = file_get_contents("/usr/local/share/suricata/fapp_version");
	$tw_type = trim(file_get_contents("/etc/tw_type"));

	if ( ($fapp_version_atual != $fapp_version_install) || ($changetype == 'yes') ) {

	if(!is_dir("/usr/local/share/suricata/acp/") || !is_dir("/usr/local/share/suricata/rules_acp/") || !is_dir("/usr/local/share/suricata/rules_fapp/"))  {

		if (is_dir_empty("/usr/local/share/suricata/rules")) {
			mwexec("cd /usr/local/share/suricata/ && mkdir rules_fapp && mkdir rules_acp");
		} else {
			mwexec("cd /usr/local/share/suricata/ && mv rules rules_fapp && mkdir rules_acp && mkdir rules");
		}

		mwexec('mkdir /usr/local/share/suricata/acp/');
		mwexec('mkdir /usr/local/share/suricata/acp/rules/');

		mwexec('rm -rf /usr/local/share/suricata/otx/');
		mwexec('rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules');

	}

	//mwexec("cd /usr/local/share/suricata/ && cp rules_fapp/* rules && rm -f /usr/local/share/suricata/rules/_emerging.rules && rm -f /usr/local/share/suricata/rules/reputation.list");

	$typefwd = 0;
	if (isset($ptype) && !empty($ptype)) {
		$typefwd = $ptype;
	}

	$changetype = file_get_contents("/tmp/changetype");
	//$testing_mode = file_get_contents("/tmp/testing_mode");

	//if ($testing_mode == "1") {

		if ( ($fapp_version_atual != $fapp_version_install) || ($changetype == 'yes') ) {

			//FirewallApp é a versão 0 e FirewallApp por Usuário é a versão 1
			if ($typefwd == 0) {
					mwexec('cd /usr/local/share/suricata/rules && rm -rf *', true);

					/*if (($tw_type === "lite") || (empty($tw_type))) {
						mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging.tar.gz && rm /usr/local/share/suricata/_emerging.tar.gz', true);
					} else if ($tw_type === "full"){
						mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_full.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_full.tar.gz && rm /usr/local/share/suricata/_emerging_full.tar.gz', true);
					}*/

					mwexec('rm -f /usr/local/share/suricata/rules0.tar.gz && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/rules0.tar.gz && cd /usr/local/share/suricata/rules/ && tar -zxvf /usr/local/share/suricata/rules0.tar.gz -C /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules/* rules_fapp', true);

					mwexec('rm -f /usr/local/share/suricata/fapp_mix.tar.gz && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/fapp_mix.tar.gz && cd /usr/local/share/suricata/rules/ && tar -zxvf /usr/local/share/suricata/fapp_mix.tar.gz -C /usr/local/share/suricata/ && cd /usr/local/share/suricata/ && cp rules/* rules_fapp', true);
					mwexec('rm -f /usr/local/share/suricata/tables.tar.gz && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/tables.tar.gz && cd /usr/local/share/suricata/rules/ && tar -zxvf /usr/local/share/suricata/tables.tar.gz -C /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules/* rules_fapp', true);

					mwexec('rm -f /usr/local/share/suricata/rules/_emerging.rules && cp /usr/local/share/suricata/rules_fapp/reputation.list /usr/local/share/suricata/acp/rules/reputation.list && cd /usr/local/share/suricata/ && cp rules_fapp/* rules', true);

					mwexec('mv /usr/local/share/suricata/rules/classification.config /usr/local/etc/suricata/');

			} else {
					mwexec('rm -f /usr/local/share/suricata/rules1.tar.gz && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/rules1.tar.gz && cd /usr/local/share/suricata/rules/', true);
					mwexec('tar -zxvf /usr/local/share/suricata/rules1.tar.gz -C /usr/local/share/suricata/rules/');

					mwexec('mv /usr/local/share/suricata/rules/classification.config /usr/local/etc/suricata/');
			}

			mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/bp-class.json && mv bp-class.json /usr/local/www/active_protection && rm -f /usr/local/share/suricata/fapp_install && cp /usr/local/share/suricata/fapp_version /usr/local/share/suricata/fapp_install');

		}

	}

	log_error(gettext("FirewallApp - Lists of the rules has been installed successfully!"));
	return true;

}

function install_rules_lists_acp() {

	// New Method Download Rules FApp
	require("config.inc");
	require_once("/usr/local/pkg/suricata/suricata.inc");

	init_config_arr(array('system', 'firewallapp'));

	if (isset($config['system']['firewallapp']['type'])) {
		//$ptype = &$config['system']['firewallapp']['type'];
		$ptype = 0;
		/*
		if (isset($config['system']['firewallapp']['type']) && !empty($config['system']['firewallapp']['type'])) {
			$ptype = $config['system']['firewallapp']['type'];
		} else {
			$ptype = 0;
		}
		*/
	} else {
		$config['system']['firewallapp']['type'] = 0;
		$savemsg = gettext("Setting default firewallapp");

		write_config($savemsg);
	}

	//Check Version Emerging
	mwexec('cd /usr/local/share/suricata/ && rm -f /usr/local/share/suricata/emerging_version && fetch http://wsutm.bluepex.com/packs/emerging_version');

	$version_install = str_replace(".", "",file_get_contents("/usr/local/share/suricata/emerging_install"));
	$version_atual   = str_replace(".", "",file_get_contents("/usr/local/share/suricata/emerging_version"));
	$tw_type = trim(file_get_contents("/etc/tw_type"));

	if ( intval($version_atual) > intval($version_install) ) {

		if(!is_dir("/usr/local/share/suricata/acp/") || !is_dir("/usr/local/share/suricata/rules_acp/") || !is_dir("/usr/local/share/suricata/rules_fapp/")) {

			if (is_dir_empty("/usr/local/share/suricata/rules")) {
				mwexec("cd /usr/local/share/suricata/ && mkdir rules_fapp && mkdir rules_acp");
			} else {
				mwexec("cd /usr/local/share/suricata/ && mv rules rules_fapp && mkdir rules_acp && mkdir rules");
			}

			mwexec('mkdir /usr/local/share/suricata/acp/');
			mwexec('mkdir /usr/local/share/suricata/acp/rules/');

			mwexec('rm -rf /usr/local/share/suricata/otx/');
			mwexec('rm -f /usr/local/share/suricata/rules/_ameacas_ext.rules');

		}

		//mwexec("cd /usr/local/share/suricata/ && cp rules/* rules_fapp");

		file_put_contents("/tmp/changetype", "no");

		if (!file_exists("/usr/local/share/suricata/emerging_install")) {
			file_put_contents("/usr/local/share/suricata/emerging_install", "0.9.9");
		}

		$testing_mode = file_get_contents("/etc/mode");

		if ($testing_mode != "test") {

			if ( intval($version_atual) > intval($version_install) ) {
				// FIXME
				mwexec('cd /usr/local/share/suricata/rules && rm -rf *', true);
				//mwexec('rm -rf /usr/local/share/suricata/otx/', true);

				/*if (($tw_type === "lite") || (empty($tw_type))) {
					mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging.tar.gz && rm /usr/local/share/suricata/_emerging.tar.gz', true);
				} else if ($tw_type === "full"){
					mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_full.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_full.tar.gz && rm /usr/local/share/suricata/_emerging_full.tar.gz', true);
				}*/

				if (($tw_type === "lite") || (empty($tw_type))) {
					mwexec('rm -f /usr/local/share/suricata/_emerging.rules && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging.tar.gz && rm /usr/local/share/suricata/_emerging.tar.gz && cd /usr/local/share/suricata/ && cp /usr/local/share/suricata/emerging_version /usr/local/share/suricata/emerging_install', true);
				} else if ($tw_type === "full"){
					mwexec('rm -f /usr/local/share/suricata/_emerging_full.rules && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_full.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_full.tar.gz && rm /usr/local/share/suricata/_emerging_full.tar.gz && cd /usr/local/share/suricata/ && cp /usr/local/share/suricata/emerging_version /usr/local/share/suricata/emerging_install', true);
				} else if ($tw_type === "acp_av"){
					mwexec('rm -f /usr/local/share/suricata/_emerging_av.rules && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_av.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_av.tar.gz && rm /usr/local/share/suricata/_emerging_av.tar.gz && cd /usr/local/share/suricata/ && cp /usr/local/share/suricata/emerging_version /usr/local/share/suricata/emerging_install', true);
				}

				//mwexec('rm -f /usr/local/share/suricata/rules0.tar.gz && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/rules0.tar.gz && cd /usr/local/share/suricata/rules/ && tar -zxvf /usr/local/share/suricata/rules0.tar.gz -C /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules/* rules_fapp', true);
				mwexec('rm -f /usr/local/share/suricata/rules/_emerging.rules && cp /usr/local/share/suricata/rules_fapp/reputation.list /usr/local/share/suricata/acp/rules/reputation.list && cd /usr/local/share/suricata/', true);

				mwexec('cp /usr/local/share/suricata/otx/classification.config /usr/local/etc/suricata/');

				//print_r("OK");die;

				mwexec('cp /usr/local/share/suricata/_ameacas.rules /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/_emerging.rules /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/otx/_ameacas_ext.rules /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/acp/rules/', true);

				mwexec('mv /usr/local/share/suricata/_ameacas.rules /usr/local/share/suricata/rules_acp/ && mv /usr/local/share/suricata/_emerging.rules /usr/local/share/suricata/rules_acp/ && mv /usr/local/share/suricata/otx/_ameacas_ext.rules /usr/local/share/suricata/rules_acp/ && mv /usr/local/share/suricata/otx/categories.txt /usr/local/share/suricata/acp/rules/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/rules_fapp/ && cd /usr/local/share/suricata/ && cp rules_fapp/* rules', true);

				//mwexec('rm -rf /usr/local/share/suricata/otx/');

				//mwexec("cd /usr/local/share/suricata/ && mv rules rules_acp && mv rules_fapp rules");

			}

		} else {

			// FIXME
				mwexec('cd /usr/local/share/suricata/rules && rm -rf *', true);
				//mwexec('rm -rf /usr/local/share/suricata/otx/', true);

				/*if (($tw_type === "lite") || (empty($tw_type))) {
					mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging.tar.gz && rm /usr/local/share/suricata/_emerging.tar.gz', true);
				} else if ($tw_type === "full"){
					mwexec('cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_full.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_full.tar.gz && rm /usr/local/share/suricata/_emerging_full.tar.gz', true);
				}*/

				if (($tw_type === "lite") || (empty($tw_type))) {
					mwexec('rm -f /usr/local/share/suricata/_emerging.rules && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_6_homologation_lite.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_6_homologation_lite.tar.gz && rm /usr/local/share/suricata/_emerging_6_homologation_lite.tar.gz && cd /usr/local/share/suricata/ && cp /usr/local/share/suricata/emerging_version /usr/local/share/suricata/emerging_install', true);
				} else if ($tw_type === "full"){
					mwexec('rm -f /usr/local/share/suricata/_emerging_full.rules && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_6_homologation_full.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_6_homologation_full.tar.gz && rm /usr/local/share/suricata/_emerging_6_homologation_full.tar.gz && cd /usr/local/share/suricata/ && cp /usr/local/share/suricata/emerging_version /usr/local/share/suricata/emerging_install', true);
				} else if ($tw_type === "acp_av"){
					mwexec('rm -f /usr/local/share/suricata/_emerging_av.rules && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/packs/_emerging_av.tar.gz && tar -zxvf /usr/local/share/suricata/_emerging_av.tar.gz && rm /usr/local/share/suricata/_emerging_av.tar.gz && cd /usr/local/share/suricata/ && cp /usr/local/share/suricata/emerging_version /usr/local/share/suricata/emerging_install', true);
				}

				//mwexec('rm -f /usr/local/share/suricata/rules0.tar.gz && cd /usr/local/share/suricata/ && fetch http://wsutm.bluepex.com/fapp/rules0.tar.gz && cd /usr/local/share/suricata/rules/ && tar -zxvf /usr/local/share/suricata/rules0.tar.gz -C /usr/local/share/suricata/rules/ && cd /usr/local/share/suricata/ && cp rules/* rules_fapp', true);
				mwexec('rm -f /usr/local/share/suricata/rules/_emerging.rules && cp /usr/local/share/suricata/rules_fapp/reputation.list /usr/local/share/suricata/acp/rules/reputation.list && cd /usr/local/share/suricata/', true);

				mwexec('cp /usr/local/share/suricata/otx/classification.config /usr/local/etc/suricata/');

				//print_r("OK");die;

				mwexec('cp /usr/local/share/suricata/_ameacas.rules /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/_emerging.rules /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/otx/_ameacas_ext.rules /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/rules_acp/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/acp/rules/', true);

				mwexec('mv /usr/local/share/suricata/_ameacas.rules /usr/local/share/suricata/rules_acp/ && mv /usr/local/share/suricata/_emerging.rules /usr/local/share/suricata/rules_acp/ && mv /usr/local/share/suricata/otx/_ameacas_ext.rules /usr/local/share/suricata/rules_acp/ && mv /usr/local/share/suricata/otx/categories.txt /usr/local/share/suricata/acp/rules/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/rules/ && cp /usr/local/share/suricata/otx/reputation.list /usr/local/share/suricata/rules_fapp/ && cd /usr/local/share/suricata/ && cp rules_fapp/* rules', true);

				//mwexec('rm -rf /usr/local/share/suricata/otx/');

				//mwexec("cd /usr/local/share/suricata/ && mv rules rules_acp && mv rules_fapp rules");

		}

	}

	log_error(gettext("ActiveProtection - Lists of the rules has been installed successfully!"));
	return true;


}

//Set config suricate all rules 
function enable_limit_logs_acp_fapp() {
	global $config;

	if (!is_array($config['installedpackages']['suricata']['config'])) {
		$config['installedpackages']['suricata']['config'] = array();
	}

	$config['installedpackages']['suricata']['config'][0]['enable_log_mgmt'] = "off";
	$config['installedpackages']['suricata']['config'][0]['suricataloglimit'] = "on";
	if (
		($config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'] == 0)
		|| ($config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'] == NULL)
	) {
		$config['installedpackages']['suricata']['config'][0]['suricataloglimitsize'] = round(exec('/bin/df -k /var | /usr/bin/grep -v "Filesystem" | /usr/bin/awk \'{print $4}\'') * .1 / 1024);
	}
	$config['installedpackages']['suricata']['config'][0]['clearlogs'] = "on";
	write_config("Suricata pkg: Configurate LOGS MGMT.");
	
}

?>

