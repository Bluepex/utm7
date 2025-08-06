<?php
if ( !defined('BASEPATH')) exit('No direct script access allowed');

class LanguageSwitcher extends CI_Controller
{
	public $response;

	public function __construct()
	{
		parent::__construct();
	}

	public function switchLang($language = "")
	{
		$language = ($language != "") ? $language : "pt-br";
		$this->appsession->appendSession("lang", $language);

		$this->response['ok'] = $this->lang->line('lang_switch_ok');
		$this->session->set_flashdata('messages', $this->response);

		redirectBack();
	}
}

