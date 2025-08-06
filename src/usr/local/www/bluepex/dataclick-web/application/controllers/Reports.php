<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Reports extends CI_Controller
{
	public $response = [];

	public function __construct()
	{
		parent::__construct();

		checkPermission("dataclick-web/reports", true);

		checkIfUtmDefaultExists();

		$this->load->helper("reports");
		$this->load->library('form_validation');
		$this->load->model("UtmModel");

		$this->utm = $this->UtmModel->getUtmDefault();
		if (!empty($this->utm)) {
			$conn = [
				"protocol" => $this->utm->protocol,
				"host" => $this->utm->host,
				"port" => $this->utm->port,
				"user" => $this->utm->username,
				"pass" => $this->utm->password,
			];
			$this->load->library("DataClickApi/DataClickApi", $conn);
		}
	}

	public function index()
	{
		$data = [
			"reports" => getReports(),
			"periods" => getPeriods(),
			"users" => $this->dataclickapi->getUsersFromDatabase(),
			"users_config" => $this->dataclickapi->getUsersFromConfigXML(),
			"groups" => $this->dataclickapi->getGroupsFromDatabase(),
		];

		$this->simpletemplate->render("reports", $data);
	}

	public function generate()
	{
		$post = $this->input->post();

		$data = [
			"report" => $post['report_id']
		];

		if ($post['form'] == 'form_pre') {
			$data['period'] = $post['period'];
		} else {
			if (!$this->validateFormReportCustom()) {
				$response = [
					"status" => "error",
					"message" => validation_errors(),
				];
				echo json_encode($response);
				exit;
			}
			$data['interval_from'] = $post['interval_from'];
			$data['interval_until'] = $post['interval_until'];
			if (empty($post['username'][0])) {
				if ((isset($post['username'][1])) && (!empty($post['username']))) {
					$data['username'] = $post['username'][1];
				} else {
					$data['username'] = '-';
				}
			} else {
				$data['username'] = $post['username'][0];
			}
			$data['ipaddress'] = $post['ipaddress'];
			$data['category_id'] = $post['category_id'];
			$data['groupname'] = isset($post['groupname']) ? $post['groupname'] : "";
		}
		$data['limit'] = $post['limit'];
		$data['format'] = $post['format'];

		if ($post['report_id'] == "P0007") {
			$data['social_network'] = "facebook";
		} elseif ($post['report_id'] == "P0008") {
			$data['social_network'] = "youtube";
		} elseif ($post['report_id'] == "P0009") {
			$data['social_network'] = "instagram";
		} elseif ($post['report_id'] == "P0010") {
			$data['social_network'] = "linkedin";
		}

		$params = serialize($data);

		$this->load->model("ReportsQueueModel");
		$data = [
			"report_id" => $post['report_id'],
			"params" => $params,
		];
		if ($this->ReportsQueueModel->insert($data)) {
			$cmd = "nohup php index.php ReportsCommandLine index > /dev/null &";
			@exec($cmd, $out, $err);
			if (!$err) {
				$response = [
					"status" => "ok",
					"message" => $this->lang->line('reports_generating') . " " . getReports($post['report_id']) . "...",
				];
			} else {
				$response = [
					"status" => "error",
					"message" => $this->lang->line('reports_err_generating') . " " . getReports($post['report_id']) . "!",
				];
			}
			echo json_encode($response);
		}
	}

	public function stopReport($pid)
	{
		$reports_pid = $this->getReportsPid();
		foreach ($reports_pid as $pid_file) {
			$_pid = trim(file_get_contents($pid_file));
			if ($_pid != $pid) {
				continue;
			}
			exec("pgrep -F {$pid_file}", $out, $err);
			if (isset($out[0]) && !empty($out[0])) {
				exec("kill -9 {$out[0]}", $out, $err);
				if (!$err) {
					unlink($pid_file);
				}
			}
			break;
		}
	}

	public function checkReport()
	{
		$reports_pid = $this->getReportsPid();
		$reports_is_running = $this->getReportsInProcessing();
		if (!empty($reports_is_running)) {
			$data = [
				"running" => true,
				"total" => count($reports_is_running),
				"reports" => $reports_is_running
			];
		} else {
			$data = [
				"running" => false,
			];
		}

		echo json_encode($data);
	}

	public function getReportsInProcessing()
	{
		$reports_pid = $this->getReportsPid();
		$reports_in_processing = [];
		if (!empty($reports_pid)) {
			foreach ($reports_pid as $pidfile) {
				if (!file_exists($pidfile)) {
					continue;
				}
				exec("pgrep -F {$pidfile}", $out, $err);
				if (!isset($out[0]) || empty($out[0])) {
					unlink($pidfile);
					continue;
				}
				preg_match("/report([E|P]?[0-9]+)/", $pidfile, $match);
				if (isset($match[1]) && !empty($match[1])) {
					$reports_in_processing[] = [
						'id' => $out[0],
						'name' => $match[1] . " - " . getReports($match[1])
					];
				}
			}
		}
		$counterInteral = 1000;
		if (empty($reports_in_processing)) {
			if (intval(trim(shell_exec('ls /tmp/ | grep report | grep -c csv'))) > 0) {
				foreach(explode("\n", trim(shell_exec('ls /tmp/ | grep report | grep csv | awk -F"-" \'{print $3}\' | awk -F"." \'{print $1}\''))) as $line) {
					if (!empty($line)) {
						$returnDesc = "";
						if ("E0001" == $line) {
							$returnDesc = "E0001 - " . $this->lang->line('reports_helpers_erel1');
						} elseif ("E0002" == $line) {
							$returnDesc = "E0002 - " . $this->lang->line('reports_helpers_erel2');
						} elseif ("E0003" == $line) {
							$returnDesc = "E0003 - " . $this->lang->line('reports_helpers_erel3');
						}
						$reports_in_processing[] = [
							'id' => $counterInteral,
							'name' => $returnDesc
						];
						$counterInteral++;
					}
				}
			}
		}
		return $reports_in_processing;
	}

	public function getReportsPid()
	{
		$reports_pid = glob("public/tmp/*report*.pid");
		return $reports_pid;
	}

	public function getReportFilesTable()
	{
		$this->load->model("ReportsQueueModel");
		$data = [
			"reports_queue" => $this->ReportsQueueModel->findAll(),
			"report_files" => $this->listReportsFiles(),
		];
		$table = $this->load->view("reports/report_files", $data, true);
		echo $table;
	}

	public function listReportsFiles()
	{
		$files = glob("public/files/reports/*");
		//Order desc by date
		array_multisort(array_map( 'filemtime', $files ), SORT_NUMERIC, SORT_DESC, $files);
		$report_files = [];
		if (!empty($files)) {
			foreach ($files as $file) {
				$filename = basename($file);
				$filename_without_extension = pathinfo($filename, PATHINFO_FILENAME);
				list($datetime, $report) = explode("-", $filename_without_extension);
				$report_files[] = [
					"datetime" => date("d/m/Y H:i:s", $datetime),
					"file" => $filename,
					"filesize" => filesize($file),
					"report" => $report . " - " . getReports($report),
				];
			}
		}
		return $report_files;
	}

	public function downloadReport($filename)
	{
		$report_files = $this->listReportsFiles();
		foreach ($report_files as $rf) {
			if ($rf['file'] == $filename) {
				if (!file_exists("public/files/reports/{$filename}")) {
					return;
				}
				downloadFile("public/files/reports/{$filename}", $rf['report']);
				break;
			}
		}
	}

	public function removeAllReports()
	{
		$files = glob('public/files/reports/*.*');
		foreach($files as $file){
			if (is_file($file)) {
				unlink($file);
			}
		}
		$this->response['ok'] = $this->lang->line('reports_success_removed_all');
		$this->session->set_flashdata('messages', $this->response);
		redirect("reports");
	}

	public function removeReport($filename)
	{
		if (file_exists("public/files/reports/{$filename}")) {
			unlink("public/files/reports/{$filename}");
			$this->response['ok'] = $this->lang->line('reports_success_removed');
		} else {
			$this->response['error'] = $this->lang->line('reports_file_not_search');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirect("reports");
	}

	public function removeReportQueue($id)
	{
		$this->load->model("ReportsQueueModel");
		if ($this->ReportsQueueModel->delete($id)) {
			$this->response['ok'] = $this->lang->line('reports_queue_success_remove');
		} else {
			$this->response['error'] = $this->lang->line('reports_queue_error_remove');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirect("reports");
	}

	private function validateFormReportCustom()
	{
		$config = [
			[
				'field' => 'interval_from',
				'rules' => 'required',
				'errors' => [
					'required' => $this->lang->line('reports_provide_interv_from'),
				]
			],
			[
				'field' => 'interval_until',
				'rules' => 'required',
				'errors' => [
					'required' => $this->lang->line('reports_provide_interv_until'),
				]
			],
		];
		$this->form_validation->set_rules($config);
		if ($this->form_validation->run() !== FALSE) {
			return true;
		}
		return false;
	}
}
