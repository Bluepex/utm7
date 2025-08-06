<?php

class AppSession 
{
	public $ci;
	public $session_name = "dataclick";

	public function __construct()
	{
		$this->ci = &get_instance();
		$this->ci->load->library('session');
	}

	public function getSession()
	{
		$session_data = $this->ci->session->userdata($this->session_name);
		return $session_data;
	}

	public function getSessionKey($key)
	{
		$session_data = $this->getSession();
		if (isset($session_data[$key])) {
			return $session_data[$key];
		}
	}

	public function appendSession($key, $value)
	{
		$session_data = $this->getSession();
		$session_data[$key] = $value;
		$this->saveSession($session_data);
	}

	public function unsetSession($key)
	{
		$session_data = $this->getSession();
		if (isset($session_data[$key])) {
			unset($session_data[$key]);
			$this->saveSession($session_data);
		}
	}

	public function saveSession($data)
	{
		$this->ci->session->set_userdata($this->session_name, $data);
	}

	public function destroySession()
	{
		$this->ci->session->sess_destroy();
	}
}

