<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Dashboard extends CI_Controller
{
	private $response;
	public $utm;

	public function __construct()
	{
		parent::__construct();

		checkIfUtmDefaultExists();

		$this->load->model("UtmModel");

		$utm_id = $this->appsession->getSessionKey('utm_display');
		if (!empty($utm_id)) {
			$this->utm = $this->UtmModel->find($utm_id);
		} else {
			$this->utm = $this->UtmModel->getUtmDefault();
		}
		$conn = [
			"protocol" => $this->utm->protocol,
			"host" => $this->utm->host,
			"port" => $this->utm->port,
			"user" => $this->utm->username,
			"pass" => $this->utm->password,
		];
		$this->load->library("DataClickApi/DataClickApi", $conn);
	}

	public function index($period = "daily", $blocked = false)
	{
		checkPermission("dataclick-web/dashboard");

		$result = $this->dataclickapi->getDashboardData($period, $blocked);
		$data = [
			"utm" => $this->utm,
			"utms" => $this->UtmModel->findAll(),
			"period" => $period,
			"json" => $this->dataclickapi->getDashboardData($period, $blocked),
		];
		$this->simpletemplate->render("dashboard", $data);
	}

	public function refreshData()
	{
		checkPermission("dataclick-web/dashboard");
		$result = $this->dataclickapi->refreshData();
		if (isset($result->status) && $result->status == "ok") {
			$this->response['ok'] = $this->lang->line('utm_cont_response_ok');
		} elseif (isset($result->status) && $result->status == "is_running") {
			$this->response['warning'] = $this->lang->line('generation_data_is_running');
		} else {
			$this->response['error'] = $this->lang->line('utm_cont_response_err');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirect("dashboard");
	}

	public function realtime($max_lines = 10)
	{
		checkPermission("dataclick-web/dashboard/realtime", true);

		$data = [
			"utm" => $this->utm,
			"utms" => $this->UtmModel->findAll(),
			'json' => $this->dataclickapi->getRealTimeData([ "maxlines" => $max_lines ])
		];
		$this->simpletemplate->render("dashboard_realtime", $data);
	}

	public function getRealtimeDataAjax($max_lines = 10)
	{

		$realtime_filter = [
			"maxlines" => $max_lines,
			"filter_online_navigation" => $this->nput->post("filter_online_navigation"),
			"filter_ignore_online_navigation" => $this->input->post("filter_ignore_online_navigation"),
		];

		$result = $this->dataclickapi->getRealTimeData($realtime_filter);
		$data = [
			"reportRT0001"   => isset($result->realtime_access) ? $this->makeGrid_RT0001($result->realtime_access) : '',
			"reportRT0002_1" => isset($result->realtime_access_face) ? $this->makeGrid_RT0002($result->realtime_access_face) : '',
			"reportRT0002_2" => isset($result->realtime_access_youtube) ? $this->makeGrid_RT0002($result->realtime_access_youtube) : '',
			"reportRT0002_3" => isset($result->realtime_access_insta) ? $this->makeGrid_RT0002($result->realtime_access_insta) : '',
			"reportRT0002_4" => isset($result->realtime_access_linke) ? $this->makeGrid_RT0002($result->realtime_access_linke) : '',
			"reportRT0002_5" => isset($result->realtime_access_twitter) ? $this->makeGrid_RT0002($result->realtime_access_twitter) : '',
			"reportRT0002_6" => isset($result->realtime_access_whatsapp) ? $this->makeGrid_RT0002($result->realtime_access_whatsapp) : '',
			"reportRT0003"   => isset($result->realtime_vpn) ? $this->makeGrid_RT0003($result->realtime_vpn) : '',
			"reportRT0004"   => isset($result->realtime_captive) ? $this->makeGrid_RT0004($result->realtime_captive) : ''
		];
		echo json_encode($data);
	}

	public function makeGrid_RT0001($realtime_data)
	{
		if (empty($realtime_data)) {
			return;
		}

		$data = [];
		foreach (array_reverse($realtime_data) as $obj) {
			// GET CATEGORIES
			$categories = [];
			foreach (explode(",", $obj->categories_id) as $cat_id) {
				if (is_numeric($cat_id)) {
					$categories[] = $this->lang->line('wf_cat_' . trim($cat_id));
				}
			}

			// if empty categories, set 99 = Others
			if (empty($categories)) {
				$categories[] = $this->lang->line('wf_cat_99');
			}

			$color = ($obj->status == "allowed") ? "style='background-color:#c6ffb3'" : "style='background-color:#ff9999'";
			$row = "<tr data-hash='{$obj->line_hash}'>";
			$row .= "<td {$color}>" . $obj->date_time . "</td>";
			$row .= "<td {$color}>" . (($obj->username == 'not referenced') ? $this->lang->line('utm_realtime_not_referenced') :  "<span class='badge'><i class='fa fa-user-o'></i> {$obj->username}</span>") . "</td>";
			$row .= "<td {$color}><a href='{$obj->url}' target='_blank'>" . (strlen($obj->url) > 50 ? substr($obj->url, 0, 50) . "..." : $obj->url) . "</a></td>";
			$row .= "<td {$color}><b>" . $this->lang->line('utm_realtime_' . $obj->status) . "</b></td>";
			$row .= "<td {$color}>" . implode(", ", $categories) . "</td>";
			$row .= "<td {$color}>{$obj->ipaddress}</td>";
			$row .= "<td {$color}>" . (($obj->groupname == 'not referenced') ? $this->lang->line('utm_realtime_not_referenced') :  $obj->groupname) . "</td>";
			$row .= "</tr>";

			$data[] = [
				"line_hash" => $obj->line_hash,
				"row" => $row
			];
		}
		return json_encode($data);
	}

	public function makeGrid_RT0002($realtime_data)
	{
		if (empty($realtime_data)) {
			return;
		}

		$data = [];
		foreach($realtime_data as $obj) {
			$color = ($obj->status == "allowed") ? "style='background-color:#c6ffb3'" : "style='background-color:#ff9999'";
			$row  = "<tr data-hash='{$obj->line_hash}'>";
			$row .= "<td {$color}>" . $obj->date_time . "</td>";
			$row .= "<td {$color}><i class='fa fa-user-o'></i> {$obj->username}</td>";
			$row .= "<td {$color}>{$obj->ipaddress}</td>";
			$row .= "<td {$color}>" . (($obj->groupname == 'not referenced') ? $this->lang->line('utm_realtime_not_referenced') :  $obj->groupname) . "</td>";
			$row .= "</tr>";

			$data[] = [
				"line_hash" => $obj->line_hash,
				"row" => $row
			];
		}
		return json_encode($data);
	}

	public function makeGrid_RT0003($realtime_data)
	{
		if (empty($realtime_data)) {
			return;
		}

		$html = "<table class='table text-left font-12'>";
		$html .= "<thead>";
		$html .= "<tr>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_username')) . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_real_ip')) . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_virtual_ip')) . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_connected')) . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_bytes_send')) . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_bytes_rec')) . "</th>";
		$html .= "</tr>";
		$html .= "</thead>";
		$html .= "<tbody>";

		foreach(array_reverse($realtime_data) as $obj) {
			$html .= "<tr>";
			$html .= "<td>{$obj->name}</td>";
			$html .= "<td>{$obj->remote_host}</td>";
			$html .= "<td>{$obj->virtual_addr}</td>";
			$html .= "<td>{$obj->connect_time}</td>";
			$html .= "<td>{$obj->bytes_sent}</td>";
			$html .= "<td>{$obj->bytes_recv}</td>";
			$html .= "</tr>";
		}
		$html .= "</tbody>";
		$html .= "</table>";
		return $html;
	}



	public function makeGrid_RT0004($realtime_data)
	{
		if (empty($realtime_data)) {
			return "";
		}

		$user_agents_captive_portal = [
			'Win311' => "public/images/icons_so/windows.png",
			'Win95' => "public/images/icons_so/windows.png",
			'WinME' => "public/images/icons_so/windows.png",
			'Win98' => "public/images/icons_so/windows.png",
			'Win2000' => "public/images/icons_so/windows.png",
			'WinXP' => "public/images/icons_so/windows.png",
			'WinServer2003' => "public/images/icons_so/windows.png",
			'WinVista' => "public/images/icons_so/windows.png",
			'Windows 7' => "public/images/icons_so/windows.png",
			'Windows 8' => "public/images/icons_so/windows.png",
			'Windows 10' => "public/images/icons_so/windows.png",
			'WinNT' => "public/images/icons_so/windows.png",
			'OpenBSD' => "public/images/icons_so/openbsd.png",
			'SunOS' => "public/images/icons_so/sunos.png",
			'Ubuntu' => "public/images/icons_so/ubuntu.png",
			'Android' => "public/images/icons_so/android.png",
			'Linux' => "public/images/icons_so/linux.png",
			'iPhone' => "public/images/icons_so/iphone.png",
			'iPad' => "public/images/icons_so/ipad.png",
			'MacOS' => "public/images/icons_so/macos.png",
			'QNX' => "public/images/icons_so/qnx.png",
			'BeOS' => "public/images/icons_so/beos.png",
			'OS2' => "public/images/icons_so/os2.png",
		];

		$html = "<table class='table text-left font-12'>";
		$html .= "<thead>";
		$html .= "<tr>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_username')) . "</th>";
		$html .= "<th>" . htmlentities('Login') . "</th>";
		$html .= "<th class='text-center'>" . htmlentities($this->lang->line('utm_cont_disp')) . "</th>";
		$html .= "<th>" . htmlentities('IP') . "</th>";
		$html .= "<th>" . htmlentities('mac') . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_connected')) . "</th>";
		$html .= "<th>" . htmlentities($this->lang->line('utm_cont_last_activ')) . "</th>";
		$html .= "</tr>";
		$html .= "</thead>";
		$html .= "<tbody>";

		$command = '/bin/cat /var/db/macverdor/mac-vendors-export.json | jq -jr \'.[] | .macPrefix, "---", .vendorName, "\n"\' > /tmp/macvendor';
		shell_exec($command);
		$pathIcon = "../../dataclick-web/public/images/icons_so";
		$array_imgs= ['aoc','apple','asus','dell','epson','hp','huawei','ibm','icon-profile-bright','icon-profile-dark',
		'lenovo','lg','logo','motorola','nokia','philco','raspberry','samsung','tcl','toshiba','xiaomi'];

		foreach($realtime_data as $rules) {
			foreach($rules as $obj) {
				$social_login = "local user";
				$user = $obj->username;

				if (strstr($user, 'facebook')) {
					$social_login = "facebook";
					$user = preg_match("/\:(.*)\(/", $user, $match) ? $match[1] : "invalid user";
				} elseif (strstr($user, 'form_auth')) {
					$social_login = "form_auth";
					$user = str_replace('form_auth:', '', $user);
				}

				$mac_vendor = strtoupper(substr($obj->mac,0,5));

				$command = "grep {$mac_vendor} /tmp/macvendor | awk -F\"---\" '{print \$2}' | head -n1";

				$result_vendor = shell_exec($command);

				if (($result_vendor == "unknown") || ($result_vendor == "")) {
					$result_vendor = "Network Interface";
				}

				$iconNow = "interface";
				$lower_comp = strtolower($result_vendor);

				foreach($array_imgs as $img_now) {
					$mystring = 'abc';
					if (strpos($lower_comp, $img_now) !== false) {
						$iconNow = $img_now;
						break;
					}
				}

				$iconCompany = "{$pathIcon}/{$iconNow}.png";
				$user_agent = "<img style='height:30px' src='{$iconCompany}'> <p>{$result_vendor}</p>";
			
				$html .= "<tr>";
				$html .= "<td style='vertical-align: middle;'>" . htmlentities($user) . "</td>";
				$html .= "<td style='vertical-align: middle;'>{$social_login}</td>";
				$html .= "<td style='vertical-align: middle;' class='text-center'>{$user_agent}</td>";
				$html .= "<td style='vertical-align: middle;'>{$obj->ip}</td>";
				$html .= "<td style='vertical-align: middle;'>{$obj->mac}</td>";
				$html .= "<td style='vertical-align: middle;'>" . date("d/m/Y H:i:s", strtotime($obj->connect_start)) . "</td>";
				$html .= "<td style='vertical-align: middle;'>" . date("d/m/Y H:i:s", strtotime($obj->last_activity)) . "</td>";
				$html .= "</tr>";
			}
		}
		$html .= "</tbody>";
		$html .= "</table>";
		return $html;
	}
}
