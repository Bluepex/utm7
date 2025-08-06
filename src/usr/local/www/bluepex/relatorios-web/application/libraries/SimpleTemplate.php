<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');

class SimpleTemplate
{
	protected $ci;
	private $template = "templates/app";
	private $template_vars = [
		"page_title" => "",
		"styles" => "",
		"content" => "",
		"scripts" => ""
	];

	public function __construct() 
	{
		$this->ci = &get_instance();
		$this->template_vars['page_title'] = $this->ci->config->item('page_title');
	}

	public function setTemplate($template)
	{
		$this->template = $template;
	}

	public function getTemplate()
	{
		return $this->template;
	}

	public function render($view, $vars = [])
	{
		$this->template_vars = array_merge($this->template_vars, $vars);
		$data = [];
		foreach ($this->template_vars as $var => $value) {
			$data[$var] = $value;
		}

		$content = $this->ci->load->view($view, $vars, true);

		// GET STYLES
		$pattern_styles = "/(<link\b[^>]*>)|(<style\b[^>]*>(.*?)<\/style>)/s";
		preg_match_all($pattern_styles, $content, $result);
		if (isset($result[0]) && !empty($result[0])) {
			$data['styles'] = implode("\n", $result[0]);
			$content = preg_replace($pattern_styles, '', $content);
		}

		// GET SCRIPTS
		$pattern_scripts = '/<script\b[^>]*>(.*?)<\/script>/s';
		preg_match_all($pattern_scripts, $content, $result);
		if (isset($result[0]) && !empty($result[0])) {
			$data['scripts'] = implode("\n", $result[0]);
			$content = preg_replace($pattern_scripts, '', $content);
		}

		$data["content"] = $content;

		$this->ci->load->vars($data);
		$this->ci->load->view($this->getTemplate());
	}
}

