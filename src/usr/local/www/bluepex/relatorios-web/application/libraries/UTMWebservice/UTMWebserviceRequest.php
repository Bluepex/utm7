<?php

abstract class UTMWebserviceRequest
{
	const DEBUG = false;
	const URL = "http://wsutm.bluepex.com/api";
	private $timeout = 60;
	private $service;
	private $method;
	private $params = [];

	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}

	public function getTimeout()
	{
		return $this->timeout;
	}

	public function setService($service)
	{
		$this->service = $service;
	}

	public function getService()
	{
		return $this->service;
	}

	public function setMethod($method)
	{
		$this->method = $method;
	}

	public function getMethod()
	{
		return $this->method;
	}

	public function setParams($params = [])
	{
		$this->params = $params;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function __construct()
	{
	}

	public function send()
	{
		if (empty($this->service) || empty($this->method)) {
			throw new Exception("Service or Method is empty!");
		}

		$request_url = self::URL . "/{$this->service}/{$this->method}";

		$ch = curl_init($request_url);
		if (is_resource($ch)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->params);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_VERBOSE, self::DEBUG);

			$result = curl_exec($ch);
			curl_close($ch);
		
			if (empty($result))
				return "error";

			$ret = json_decode($result);
			return $ret->response;
		}
	}
}

