<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Util extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function deniedPage()
	{
		$this->simpletemplate->render("pages/denied");
	}

	public function logout()
	{
		logoutDataClick();
	}
}

