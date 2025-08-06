<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Categorization extends CI_Controller
{
	private $response;

	public function __construct()
	{
		parent::__construct();

		checkPermission("dataclick-web/tools/categorization", true);

		$this->load->library('form_validation');
	}

	public function index()
	{
		$this->simpletemplate->render("tools/categorization");
	}

	public function send()
	{
		if (!$this->validateCategorizationForm()) {
			$this->index();
		} else {
			$this->load->library('UTMWebservice');

			$this->load->model("UtmModel");
			$utm = $this->UtmModel->getUtmDefault();

			$data = [
				"serial" => $utm->serial,
				"product_key" => $utm->product_key,
				"url" => $this->input->post("url"),
				"category" => $this->input->post("category"),
				"category_suggest" => $this->input->post("category_suggested")
			];

			$res = $this->utmwebservice->insertURLQuarantine($data);
			if (isset($res->status) && $res->status == "ok") {
				$this->response['ok'] = $this->lang->line('categorization_send_ok_message');
			} else {
				$this->response['alert'] = $this->lang->line('categorization_send_alert_message');
			}
			$this->session->set_flashdata('messages', $this->response);
			redirectBack();
		}
	}

	private function validateCategorizationForm($ignore_fields = [])
	{
		$config = [
			[
				'field' => 'url',
				'label' => 'Url',
				'rules' => 'required|valid_url'
			],
			[
				'field' => 'category',
				'label' => 'Category',
				'rules' => 'required'
			],
			[
				'field' => 'category_suggested',
				'label' => 'Category Suggested',
				'rules' => 'required'
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

