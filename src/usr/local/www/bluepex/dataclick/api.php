<?php
require_once("config.inc");
require_once("auth.inc");

require_once("dataclick.inc");
require_once("dataclick_report.inc");
require_once("dataclick_webfilter.inc");
require_once("dataclick_realtime_data.inc");

require_once("captiveportal.inc");

$methods_registered = [
	"check_conn", "check_connect_sync", "get_dashboard_data", "get_report_data", "get_realtime_data", "get_realtime_data", "get_users_db", "get_users_config_xml", "get_groups_db", "refresh_data", "login_dataclick"
];

if (isset($_POST['auth']['username'], $_POST['auth']['password'], $_POST['method'])) {

	$username = $_POST['auth']['username'];
	$password = $_POST['auth']['password'];
	$method = $_POST['method'];
	$params = $_POST['params'];

	// Authenticate User
	if (!auth($username, $password)) {
		response("failed", 401, "Unauthorized");
	}
	// Validate method
	if (!in_array($method, $methods_registered) || !function_exists($method)) {
		response("error", 405, "Invalid method!");
	}

	$method($params);

} else {
	response("error", 400, "Invalid parameters!");
}

function check_conn()
{
	response("ok", 200, "Connection established with success!");
}

function check_connect_sync()
{
	require_once("bp_webservice.inc");
	$data = [
		"logo" => get_general_logo(),
		"serial" => file_exists('/etc/serial') ? trim(file_get_contents('/etc/serial')) : '',
		"product_key" => getProductKey()
	];
	response("ok", 200, "Synchronized with success!", $data);
}

function get_dashboard_data($params)
{
	$params_limit = $params;
	$params_limit['limit'] = 5;
	$data = [
		"report_top_10_users" => get_top_10_users($params),
		"report_top_5_social_access" => get_top_5_social_access($params),
		"report_top_5_categories" => get_top_categories($params_limit),
		"report_top_10_domains" => get_top_10_domains($params),
		"report_top_10_accessed_sites" => get_top_10_accessed_sites($params),
		"report_top_5_openvpn_control" => get_top_openvpn_control($params_limit),
		"reports_info" => getReportsInfo(),
	];

	response("ok", 200, "", $data);
}

function get_realtime_data($params)
{
	$data = [
		"realtime_access" => online_navigation($params),
		"realtime_access_face" => online_navigation([ "filter_online_navigation" => "facebook.com, facebook.net" ]),
		"realtime_access_youtube" => online_navigation([ "filter_online_navigation" => "youtube.com, googlevideo.com" ]),
		"realtime_access_insta" => online_navigation([ "filter_online_navigation" => "instagram.com" ]),
		"realtime_access_linke" => online_navigation([ "filter_online_navigation" => "linkedin.com" ]),
		"realtime_access_twitter" => online_navigation([ "filter_online_navigation" => "twitter.com" ]),
		"realtime_access_whatsapp" => online_navigation([ "filter_online_navigation" => "whatsapp.com, whatsapp.net" ]),
		"realtime_vpn" => reportRT0003($params),
		"realtime_captive" => reportRT0004($params)
	];
	response("ok", 200, "", $data);
}

function get_report_data($params)
{
	$data = [];
	switch ($params['report']) {
		case "P0001":
			$data = get_top_10_users($params);
			break;
		case "P0002":
			$data = get_top_5_social_access($params);
			break;
		case "P0003":
			$data = get_top_categories($params);
			break;
		case "P0004":
			$data = get_top_10_domains($params);
			break;
		case "P0005":
			$data = get_top_10_accessed_sites($params);
			break;
		case "P0006":
			$data = get_top_openvpn_control($params);
			break;
		case "P0007":
		case "P0008":
		case "P0009":
		case "P0010":
			$data = get_top_access_social_networks($params);
			break;
		case "0001":
			$data = report0001($params);
			break;
		case "0002":
			$data = report0002($params);
			break;
		case "0003":
			$data = report0003($params);
			break;
		case "0004":
			$data = report0004($params);
			break;
		case "0005":
			$data = get_domains_accessed($params);
			break;
		case "0006":
			$data = get_justifications($params);
			break;
		case "0007":
			$data = report0007($params);
			break;
		case "0008":
			$data = report0008($params);
			break;
		case "E0001":
			//$data = reportE0001($params);
			reportE0001($params);
			break;
		case "E0002":
			//$data = reportE0002($params);
			reportE0002($params);
			break;
		case "E0003":
			//$data = reportE0003($params);
			reportE0003($params);
			break;
		default:
			response("error", 400, "Report not found!");
	}

	if (!in_array($params['report'], array("E0001","E0002","E0003"))) {
		$return_data = [
			"data" => $data,
		];
		if (preg_match("/^P[0-9]+/", $params['report'])) {
			$return_data['reports_info'] = getReportsInfo();
		}
		response("ok", 200, "", $return_data);
	}
}

function get_users_db()
{
	$data = get_users_from_database();
	response("ok", 200, "", $data);
}

function get_users_config_xml()
{
        $data = get_users_from_config_xml();
        response("ok", 200, "", $data);
}

function get_groups_db()
{
	$data = get_groups_from_database();
	response("ok", 200, "", $data);
}

function get_reports_info()
{
	$data = getReportsInfo();
	response("ok", 200, "", $data);
}

function refresh_data()
{
	$data = [
		"status" => refreshData(),
	];
	response("ok", 200, "", $data);
}

function login_dataclick()
{
	global $config, $priv_list;

	require_once("priv.inc");

	$user_array = getUserEntry($_POST['auth']['username']);
	$page_match = "";

	if ($user_array['name'] == "admin" && $user_array['scope'] == "system") {
		$page_match = [ "*" ];
	} else {
		$page_match = getAllowedPages($_POST['auth']['username']);
	}

	// DataClick Session
	$user_session = [
		"username" => $_POST['auth']['username'],
		"page-match" => $page_match
	];
	putDataClickSessionData($user_session);
	response("ok", 200, "Authenticated with success!");
}

function response($status, $code, $message, $data = [])
{
	echo json_encode([
		"response_status" => $status,
		"response_code" => $code,
		"response_message" => $message,
		"received" => $_POST,
		"data" => $data
	]);
	exit;
}

function auth($username, $password)
{
	global $config;
	if (isset($config['system']['webgui']['authmode'])) {
		$authcfg = auth_get_authserver($config['system']['webgui']['authmode']);
		if (authenticate_user($username, $password, $authcfg) ||
		    authenticate_user($username, $password)) {
			return true;
		}
	} else if (authenticate_user($username, $password)) {
		return true;
	}
	return false;
}

