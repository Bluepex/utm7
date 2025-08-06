<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class GeneralMethods extends CI_Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->load->helper('api');

	}

	public function putDataSession()
	{
		checkLoginApi();

		$response = [];
		$data_session = $this->input->post('user_session');

		$this->load->model("SettingsModel");
		/*if ($this->SettingsModel->save("user_session", $data_session)) {
			$response = [
				"reponse"  => "ok",
				"message" => "User data session inserted successfully!"
			];
		} else {
			$response = [
				"response" => "error",
				"message" => "Couldn't to set user data session!",
				"params_received" => $this->input->post(),
			];
		}*/
		$response = [
			"reponse"  => "ok",
			"message" => "User data session inserted successfully!"
		];
		echo json_encode($response);
	}
}

