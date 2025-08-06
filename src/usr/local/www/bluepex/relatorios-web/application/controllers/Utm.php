<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Utm extends CI_Controller
{
	private $response;

	public function __construct()
	{
		parent::__construct();

		checkPermission("dataclick-web/utm", true);

		$this->load->library('form_validation');
		$this->load->model("UtmModel");
	}

	public function index()
	{
		$utms = $this->UtmModel->findAll();
		$this->simpletemplate->render("utm/list", ["utms" => $utms]);
	}

	public function create()
	{
		$this->simpletemplate->render("utm/create");
	}

	public function insert()
	{
		if (!$this->validateUTMForm()) {
			$this->create();
		} else {
			$data = $this->input->post();
			if ($this->UtmModel->insert($data)) {
				$this->response['ok'] = $this->lang->line('utm_insert_ok_message');
				$this->testConnectionSync($this->UtmModel->getIdInserted());
			} else {
				$this->response['error'] = $this->lang->line('utm_insert_error_message');
			}
			$this->session->set_flashdata('messages', $this->response);
			redirect("utm");
		}
	}

	public function edit($utm_id)
	{
		$utm = $this->UtmModel->find($utm_id);
		if (!empty($utm)) {
			$this->simpletemplate->render("utm/edit", ["utm" => $utm]);
		} else {
			$this->response['error'] = $this->lang->line('utm_edit_error_message');
			$this->session->set_flashdata('messages', $this->response);
			redirect("utm");
		}
	}

	public function update($utm_id)
	{
		if (!$this->validateUTMForm(['password'])) {
			$this->edit($utm_id);
		} else {
			$data = $this->input->post();
			// If empty the password, not update
			if (empty($data['password'])) {
				unset($data['password']);
			}
			if ($this->UtmModel->update($utm_id, $data)) {
				$this->response['ok'] = $this->lang->line('utm_update_ok_message');
			} else {
				$this->response['error'] = $this->lang->line('utm_update_error_message');
			}
			$this->session->set_flashdata('messages', $this->response);
			redirect("utm");
		}
	}

	public function remove($utm_id)
	{
		$utm = $this->UtmModel->find($utm_id);
		if (!empty($utm)) {
			if ($utm->is_default == 1) {
				$this->response['warning'] = $this->lang->line('utm_default_remove_error_message');
			} else {
				if ($this->UtmModel->delete($utm_id)) {
					$this->response['ok'] = $this->lang->line('utm_remove_ok_message');
				} else {
					$this->response['error'] = $this->lang->line('utm_remove_error_message');
				}
			}
		} else {
			$this->response['error'] = $this->lang->line('utm_remove_error_message');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirect("utm");
	}

	public function setDefault($utm_id)
	{
		if ($this->UtmModel->setDefault($utm_id)) {
			$this->response['ok'] = $this->lang->line('utm_set_default_ok_message');
		} else {
			$this->response['error'] = $this->lang->line('utm_set_default_error_message');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirect("utm");
	}

	public function testConnectionSync($utm_id)
	{
		$utm = $this->UtmModel->find($utm_id);
		if (!empty($utm)) {
			$data = [
				"protocol" => $utm->protocol,
				"host" => $utm->host,
				"port" => $utm->port,
				"user" => $utm->username,
				"pass" => $utm->password,
			];
			$this->load->library("DataClickApi/DataClickApi", $data);
			$result = $this->dataclickapi->checkConnectAndSync();
			if (!empty($result)) {
				$this->response['ok'] = $this->lang->line('utm_test_conn_ok_message');

				// Update general logo of the UTM
				$logo =  $result->logo;
				$settings = [
					"logo_name"    => $logo->name,
					"logo_content" => $logo->content,
					"serial"       => $result->serial,
					"product_key"  => $result->product_key
				];
				if ($this->UtmModel->update($utm_id, $settings)) {
					$this->response['ok'] = $this->lang->line('utm_sync_ok_message');
				} else {
					$this->response['error'] = $this->lang->line('utm_sync_error_message');
				}
			} else {
				$this->response['error'] = $this->lang->line('utm_test_conn_error_message');
			}
		} else {
			$this->response['alert'] = $this->lang->line('utm_test_conn_alert_message');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirect("utm");
	}

	public function changeDisplay($utm_id)
	{
		$utm = $this->UtmModel->find($utm_id);
		if (!empty($utm)) {
			$this->appsession->appendSession('utm_display', $utm->id);
			$this->response['ok'] = $this->lang->line('utm_change_display_ok_message');
		} else {
			$this->response['error'] = $this->lang->line('utm_change_display_error_message');
		}
		$this->session->set_flashdata('messages', $this->response);
		redirectBack();
	}

	private function validateUTMForm($ignore_fields = [])
	{
		$rule_valid_host = (preg_match("/^[0-9]+/", $this->input->post('host'))) ? "valid_ip" : "valid_url";
		$config = [
			[
				'field' => 'name',
				'label' => $this->lang->line('utm_create_name'),
				'rules' => 'required'
			],
			[
				'field' => 'host',
				'label' => $this->lang->line('utm_create_host'),
				'rules' => 'required|' . $rule_valid_host
			],
			[
				'field' => 'port',
				'label' => $this->lang->line('utm_create_port'),
				'rules' => 'integer'
			],
			[
				'field' => 'username',
				'label' => $this->lang->line('utm_create_user'),
				'rules' => 'required'
			],
			[
				'field' => 'password',
				'label' => $this->lang->line('utm_create_pass'),
				'rules' => 'required',
				'errors' => [
					'required' => $this->lang->line('utm_form_validation') . ' %s.',
				],
			]
		];
		if (!empty($ignore_fields)) {
			$i = 0;
			foreach ($config as $cf) {
				if (in_array($cf['field'], $ignore_fields)) {
					unset($config[$i]);
				}
				$i++;
			}
		}
		$this->form_validation->set_rules($config);
		if ($this->form_validation->run() !== FALSE) {
			return true;
		}
		return false;
	}
}

