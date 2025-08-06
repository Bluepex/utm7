<?php
defined('BASEPATH') OR exit('No direct script access allowed');

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', '3600');
set_time_limit(3600);

class ReportsCommandLine extends CI_Controller
{
	private $utm;
	private $my_pid;

	public function __construct()
	{
		parent::__construct();

		$this->load->helper("reports");
		$this->load->model("UtmModel");
		$this->load->model("ReportsQueueModel");
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
		} else {
			echo $this->lang->line('reports_cmd_default_utm');
		}
		$this->my_pid = getmypid();
	}

	public function index()
	{
		$reports = $this->ReportsQueueModel->findAll();
		$errors = [];
		foreach ($reports as $report) {
			sleep(1);
			if (empty($report->process_id) || !$this->checkPidExists($report->process_id)) {
				$cmd = "php index.php ReportsCommandLine report {$report->id} > /dev/null &";
				@exec($cmd, $out, $err);
				if ($err) {
					$errors[] = $this->lang->line('reports_cmd_err1') . $cmd;
				}
			}
		}
		if (!empty($errors)) {
			print_r($errors);
		}
	}

	public function checkPidExists($pid)
	{
		exec("ps -p {$pid} -o pid=", $out, $err);
		if (isset($out[0]) && !empty($out[0])) {
			if ($pid == $out[0]) {
				return true;
			}
		}
		return false;
	}

	public function report($schedule_id)
	{
		$report = $this->ReportsQueueModel->find($schedule_id);
		if (empty($report)) {
			return;
		}

		$params = unserialize($report->params);

		//if (!in_array($params['report'], array("E0001","E0002","E0003"))) {

			$my_pid_file = "public/tmp/" . time() . "-report{$params['report']}_schedule{$report->id}.pid";
			file_put_contents($my_pid_file, $this->my_pid);

			if (file_exists($my_pid_file)) {
				if (!$this->ReportsQueueModel->setProcessId($report->id, $this->my_pid)) {
					unlink($my_pid_file);
					echo $this->lang->line('reports_cmd_err2');
					exit;
				}
			} else {
				echo $this->lang->line('reports_cmd_err3');
				exit;
			}

			$result = $this->dataclickapi->getReport($params);

			if ($params['format'] == "pdf") {
				$content_data = [];
				$header_data  = [];
				$filename = time() . "-{$params['report']}.pdf";

				$landscape = false;
				switch ($params['report']) {
					case "E0001":
					case "E0002":
					case "E0003":
					case "0004":
						$landscape = true;
						break;
				}

				if (isset($result->reports_info->last_generated_reports)) {
					if ($params['period'] == "daily") {
						$start = subDate($result->reports_info->last_generated_reports, "days", 1, "d/m/Y H:i:s");
					} elseif ($params['period'] == "weekly") {
						$start = subDate($result->reports_info->last_generated_reports, "weeks", 1, "d/m/Y H:i:s");
					} elseif ($params['period'] == "monthly") {
						$start = subDate($result->reports_info->last_generated_reports, "months", 1, "d/m/Y H:i:s");
					}
					$end = convertDate($result->reports_info->last_generated_reports, 'Y-m-d H:i:s', 'd/m/Y H:i:s');
					$content_data["interval_from"]  = $start;
					$content_data["interval_until"] = $end;
				}

				$content_data["data"] = $result->data;
				$content_data["filter"] = $params;
				$content_data["interval"] = isset($params["period"]) ? getPeriods($params["period"]) : '';
				$content_data["utm"] = $this->utm;

				$bp_logo_file = "logo-blue.png";
				$bp_logo_path = "public/images/{$bp_logo_file}";
				$bp_logo_content = file_exists($bp_logo_path) ? base64_encode(file_get_contents($bp_logo_path)) : "";

				$header_data["utm_default"] = $this->utm;
				$header_data["report_title"] = getReports($params['report']);
				$header_data["bp_logo_file"] = $bp_logo_file;
				$header_data["bp_logo_content"] = $bp_logo_content;

				// Get report content
				$content = $this->load->view("reports/{$params['report']}", $content_data, true);
				// get report header
				$html_header = $this->load->view("reports/header", $header_data, true);
				// Get css style
				$stylesheet = file_get_contents('public/css/reports.css');

				if ($landscape) {
					$mpdf = new mPDF('utf-8','A4-L','','','15','15','25','18');
				} else {
					$mpdf = new mPDF('utf-8','A4','','','15','15','25','18');
				}
				$mpdf->WriteHTML($stylesheet,1);
				$mpdf->SetHTMLHeader($html_header);
				$mpdf->SetFooter('|' . $this->lang->line('reports_cmd_footer'));
				$mpdf->writeHTML($content);
				$mpdf->Output("public/files/reports/{$filename}",'F');

				unset($content_data, $content, $header_data, $html_header, $stylesheet);
			}

			if ($params['format'] == "csv") {
				$filename = time() . "-{$params['report']}.csv";
				$lines = [];
				$fp = fopen("public/files/reports/{$filename}", 'w');
				if (is_resource($fp)) {
					if ($params['report'] == "P0001") {
						$total = 0;
						foreach ($result->data as $res) {
							$total = $total+$res->value;
						}
						$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_consumer'), "(%)" ];
						fputcsv($fp, $first_line);
						if (!empty($result->data)) {
							foreach ($result->data as $res) {
								$percentage = ($res->value > 0 && $total > 0) ? number_format((($res->value/$total)*100), 2) : '0';
								$line = [ $res->item, $res->value, $percentage ];
								fputcsv($fp, $line);
							}
						}
					} elseif ($params['report'] == "P0002") {
						$total = 0;
						if (isset($result->data->sites) && !empty($result->data->sites)) {
							foreach ($result->data->sites as $res) {
								$total = $total+$res->total;
							}
							$first_line = [ "Site", $this->lang->line('reports_cmd_access'), "(%)" ];
							fputcsv($fp, $first_line);
							foreach ($result->data->sites as $res) {
								$percentage = ($res->total > 0 && $total > 0) ? number_format((($res->total/$total)*100), 2) : '0';
								$line = [ $res->site, $res->total, $percentage ];
								fputcsv($fp, $line);
							}
						}
					} elseif ($params['report'] == "P0003") {
						$total = 0;
						foreach ($result->data as $res) {
							$total = $total+$res->value;
						}
						$first_line = [ $this->lang->line('reports_cmd_categ'), $this->lang->line('reports_cmd_access'), "(%)" ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$percentage = ($res->value > 0 && $total > 0) ? number_format((($res->value/$total)*100), 2) : '0';
							$line = [ $res->item, $res->value, $percentage ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "P0004") {
						$total = 0;
						foreach ($result->data as $res) {
							$total = $total+$res->value;
						}
						$first_line = [ $this->lang->line('reports_cmd_domain'), $this->lang->line('reports_cmd_consumer'), "(%)" ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$percentage = ($res->value > 0 && $total > 0) ? number_format((($res->value/$total)*100), 2) : '0';
							$line = [ $res->item, $res->value, $percentage ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "P0005") {
						$total = 0;
						foreach ($result->data as $res) {
							$total = $total+$res->value;
						}
						$first_line = [ "Site", $this->lang->line('reports_cmd_access'), "(%)" ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$percentage = ($res->value > 0 && $total > 0) ? number_format((($res->value/$total)*100), 2) : '0';
							$line = [ $res->item, $res->value, $percentage ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "P0006") {
						$total = 0;
						foreach ($result->data as $res) {
							$total = $total+$res->value;
						}
						$first_line = [ $this->lang->line('reports_cmd_domain'), $this->lang->line('reports_cmd_connect_time'), "(%)" ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$percentage = ($res->value > 0 && $total > 0) ? number_format((($res->value/$total)*100), 2) : '0';
							$line = [ $res->item, $res->value, $percentage ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "P0007" || $params['report'] == "P0008" || $params['report'] == "P0009" || $params['report'] == "P0010") {
						$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_ip'), $this->lang->line('reports_cmd_access'), $this->lang->line('reports_cmd_consumer') ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$line = [ $res->username, $res->ipaddress, $res->total, $res->size_bytes ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "0001") {
						$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_access'), $this->lang->line('reports_cmd_consumer') ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$line = [ $res->username, $res->total_accessed, $res->total_consumed ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "0002") {
						$first_line = [ $this->lang->line('reports_cmd_ip'), $this->lang->line('reports_cmd_access'), $this->lang->line('reports_cmd_consumer') ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$line = [ $res->ipaddress, $res->total_accessed, $res->total_consumed ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "E0001") {
						/*$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_group'), $this->lang->line('reports_cmd_date'), "URL", $this->lang->line('reports_cmd_categ'), $this->lang->line('reports_cmd_consumer'), $this->lang->line('reports_cmd_blocked')];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							foreach ($res->sites as $site) {
								$blocked = ($site->blocked == 0) ? $this->lang->line('reports_cmd_no') : $this->lang->line('reports_cmd_yes');
								$line = [ $site->username, $site->groupname, $site->time_date, $site->url, $site->categories, $site->total_consumed, $blocked ];
								fputcsv($fp, $line);
							}
						}*/
					} elseif ($params['report'] == "E0002") {
						/*$first_line = [ $this->lang->line('reports_cmd_ip'), $this->lang->line('reports_cmd_group'), $this->lang->line('reports_cmd_date'), "URL", $this->lang->line('reports_cmd_categ'), $this->lang->line('reports_cmd_consumer'), $this->lang->line('reports_cmd_blocked')];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							foreach ($res->sites as $site) {
								$blocked = ($site->blocked == 0) ? $this->lang->line('reports_cmd_no') : $this->lang->line('reports_cmd_yes');
								$line = [ $res->ipaddress, $res->groupname, $site->time_date, $site->url, $site->categories, $site->total_consumed, $blocked ];
								fputcsv($fp, $line);
							}
						}*/
					} elseif ($params['report'] == "E0003") {
						/*$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_group'), $this->lang->line('reports_cmd_date'), "URL", $this->lang->line('reports_cmd_categ'), $this->lang->line('reports_cmd_consumer'), $this->lang->line('reports_cmd_blocked')];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							foreach ($res->sites as $site) {
								$blocked = ($site->blocked == 0) ? $this->lang->line('reports_cmd_no') : $this->lang->line('reports_cmd_yes');
								$line = [ $res->username, $res->groupname, $site->time_date, $site->url, $site->categories, $site->total_consumed, $blocked ];
								fputcsv($fp, $line);
							}
						}*/
					} elseif ($params['report'] == "0003") {
						$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_rem_ip'), $this->lang->line('reports_cmd_local_ip'), $this->lang->line('reports_cmd_port'), $this->lang->line('reports_cmd_bytes_send'), $this->lang->line('reports_cmd_bytes_send'), $this->lang->line('reports_cmd_connect_time') ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$line = [ $res->username, $res->host_remote, $res->host_local, $res->port, $res->bytes_sent, $res->bytes_received, $res->connected_time ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "0004") {
						$first_line = [ $this->lang->line('reports_cmd_zone'), $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_ip'), $this->lang->line('reports_cmd_mac'), $this->lang->line('reports_cmd_beg_connect'), $this->lang->line('reports_cmd_last_active'), $this->lang->line('reports_cmd_last_disp') ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $zone => $users) {
							foreach ($users as $user) {
								$line = [ $zone, $user->username, $user->ip, $user->mac, $user->connect_start, $user->last_activity, $user->user_agent ];
								fputcsv($fp, $line);
							}
						}
					} elseif ($params['report'] == "0005") {
						$first_line = [ "Domínio", "Categories", "Status", "Qtde. Acesso", "Total Consumed (MB)", "%" ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$status = ($res->blocked == 0) ? "Liberado" : "Bloqueado";
							$percentage = ($res->value > 0 && $total > 0) ? number_format((($res->value/$total)*100), 2) : '0';
							$line = [ $res->domain, $res->categories, $status, $res->total_accessed, $res->total_consumed, $percentage ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "0006") {
						$first_line = [ $this->lang->line("reports_cmd_date"), $this->lang->line("reports_cmd_username"), $this->lang->line("reports_cmd_ip"), "URL", $this->lang->line("reports_c_justification"), $this->lang->line("reports_c_rejected") ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$rejected = ($res->rejected == "1") ? $this->lang->line("reports_cmd_yes") : $this->lang->line("reports_cmd_no");
							$line = [ $res->time_date, $res->username, $res->ip, $res->url_blocked, $res->reason, $rejected ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "0007") {
						$first_line = [ $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_rem_ip'), $this->lang->line('reports_cmd_local_ip'), $this->lang->line('reports_cmd_port'), $this->lang->line('reports_cmd_bytes_send'), $this->lang->line('reports_cmd_bytes_send'), $this->lang->line('reports_cmd_time_connect'), $this->lang->line('reports_cmd_time_disconnect') ];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$line = [ $res->username, $res->host_remote, $res->host_local, $res->port, $res->bytes_sent, $res->bytes_received, $res->time_connect, $res->time_disconnect ];
							fputcsv($fp, $line);
						}
					} elseif ($params['report'] == "0008") {
						$first_line = [$this->lang->line('reports_cmd_date'), $this->lang->line('reports_cmd_session'), $this->lang->line('reports_cmd_username'), $this->lang->line('reports_cmd_descr')];
						fputcsv($fp, $first_line);
						foreach ($result->data as $res) {
							$line = [ $res->event_date, $res->session, $res->username, vsprintf($this->lang->line($res->descr), explode("|", $res->complement)) ];
							fputcsv($fp, $line);
						}
					}
					fclose($fp);
				}
			}

			if (file_exists("public/files/reports/{$filename}")) {
				echo "success\n";
				$this->ReportsQueueModel->delete($report->id);
			} else {
				echo "error\n";
			}

			if (file_exists($my_pid_file)) {
				unlink($my_pid_file);
			}
			unset($res, $result, $params);
		//}

		shell_exec("find /usr/local/www/dataclick-web/public/files/reports/ -type f -size 0 -exec rm -rf '{}' +");
		shell_exec("find /tmp/ -type f -atime +36h | grep report | awk '{print \"rm -rf \" $1}' > /tmp/clearReports && sh /tmp/clearReports");
	}
}

