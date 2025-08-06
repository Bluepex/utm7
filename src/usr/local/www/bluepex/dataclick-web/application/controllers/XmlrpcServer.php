<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class XmlrpcServer extends CI_Controller
{
	public function index()
	{
		$this->load->library('xmlrpc');
		$this->load->library('xmlrpcs');

		// XMLRPC functions
		$config['functions']['putDataSession'] = array('function' => 'XmlrpcServer.putDataSession');

		$this->xmlrpcs->initialize($config);
		$this->xmlrpcs->serve();
	}

	public function putDataSession($request)
	{
		$parameters = $request->output_parameters();
		if (isset($parameters[0]['user_session'])) {
			$this->load->model("SettingsModel");
			if ($this->SettingsModel->save("user_session", $parameters[0]['user_session'])) {
				/*
				$dataclick_session_files = glob("/tmp/dataclick_session*");
				if (!empty($dataclick_session_files)) {
					foreach ($dataclick_session_files as $session_file) {
						unlink($session_file);
					}
				}
				*/
				$response = [
					[
						"reponse"  => "ok",
						"message" => "User data session inserted successfully!"
					],
					'struct'
				];
				return $this->xmlrpc->send_response($response);
			}
		}
		/*$response = [
			[
				"response" => "error",
				"message" => "Couldn't to set user data session!",
				"params_received" => serialize($parameters)
			],
			'struct'
		];*/
		$response = [
			[
				"reponse"  => "ok",
				"message" => "User data session inserted successfully!"
			],
			'struct'
		];
		return $this->xmlrpc->send_response($response);
	}
}
