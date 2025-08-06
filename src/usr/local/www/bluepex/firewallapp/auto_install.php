<?php
require_once('config.inc');
require_once('util.inc');
require_once('filter.inc');

if (file_exists("/etc/inc/dataclick_report.inc")) {
        require_once('dataclick_report.inc');
}

global $g, $config;

init_config_arr(array('system', 'webfilter', 'nf_reports_settings', 'element0'));
init_config_arr(array('system', 'webfilter', 'bluepexdataclickagent', 'config'));

$settings_report = &$config['system']['webfilter']['nf_reports_settings']['element0'];

// Create webfilter database
$settings_report['remote_reports'] = "on";
$settings_report['log_referer'] = "off";
$settings_report['reports_ip'] = "127.0.0.1";
$settings_report['reports_port'] = "3306";
$settings_report['reports_user'] = "webfilter";
$settings_report['reports_password'] = "webfilter";
$settings_report['reports_db'] = "webfilter";

/*if (function_exists("createEventToReports")) {
	createEventToReports($report_time);
}

if (function_exists("createEventToClearlog")) {
	createEventToClearlog($cleanup_db_time, $cleanup_db_month);
}*/

// Create Fapp report
$settings_report['enable'] = "on";
$settings_report['interfaces'] = "LAN";
$settings_report['gen_report_time'] = ":";
$settings_report['cleanup_db_month'] = "";
$settings_report['cleanup_db_time'] = ":";

if (function_exists("createEventToAlerts")) {
	createEventToAlerts();
}
if (function_exists("createEventToHttp")) {
	createEventToHttp();
}
if (function_exists("createEventToHttps")) {
	createEventToHttps();
}

$settings_dataclick = &$config['system']['webfilter']['bluepexdataclickagent']['config'][0];

$settings_dataclick['enable'] = "on";
$settings_dataclick['interfaces'] = "LAN";
$settings_dataclick['gen_report_time'] = ":";
$settings_dataclick['cleanup_db_month'] = "";
$settings_dataclick['cleanup_db_time'] = ":";

filter_configure();
write_config("Data in config.xml save success!");

/*
// Check Connection
$db = new NetfilterDatabase();
if (!$db->backend) {
	$input_errors[] = dgettext(gettext('BluePexWebFilter'), gettext('Could not to connect to the database!'));
} else {
	$res = $db->Query("SELECT COUNT(*) as total FROM accesses");
	if ($res) {
		$result = $db->fetchAssoc($res);
	}
}

putDataClickSessionData($user_session);
*/

function postDataSyncronizeTest($data_session = [])
{
        global $g, $config;

        if (!is_array($data_session)) {
                log_error(dgettext("DataClick", "Could not to put session data to DataClick!"));
                return;
        }

        $protocol = $config['system']['webgui']['protocol']."://";

        $ip = empty(get_interface_ip("lan")) ? get_interface_ip("wan") : get_interface_ip("lan");
        if (empty($ip)) {
                log_error(dgettext("DataClick", "Could not to put session data to DataClick, LAN IP not found!"));
                return;
        }

        $port = $config['system']['webgui']['port'];
        if ($port == "") {
                $port = ($config['system']['webgui']['protocol'] == "http") ? 80 : 443;
        }

        $url = $protocol . $ip;

        $data = [
                "user_session" => serialize($data_session)
        ];

        $url_api = $url . "/dataclick-web/utm/test-connection-sync/1";

        postCURL($url_api, $data, $port);
}

function postCURL($url, $data, $port){
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_PORT, $port);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, count($data));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        curl_exec($ch);

        curl_close($ch);
}

postDataSyncronizeTest();

?>

