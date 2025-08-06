<?php

abstract class RelatoriosApiRequest
{
	const DEBUG = 1;
	private $timeout = 10; // 60 = 1 min
	private $host;
	private $username;
	private $password;
	private $path = "/relatorios/api.php"; // Path to script in server
	private $method;
	private $params = [];

	public function __construct()
	{
	}

	public function setHost($host)
	{
		$this->host = $host;
	}

	public function getHost()
	{
		return $this->host;
	}

	public function setUsername($username)
	{
		$this->username = $username;
	}

	public function getUsername()
	{
		return $this->username;
	}

	public function setPassword($password)
	{
		$this->password = $password;
	}

	public function getPassword()
	{
		return $this->password;
	}

	public function setTimeout($timeout)
	{
		$this->timeout = $timeout;
	}

	public function getTimeout()
	{
		return $this->timeout;
	}

	public function setMethod($method)
	{
		$this->method = $method;
	}

	public function getMethod()
	{
		return $his->method;
	}

	public function setParams($params)
	{
		$this->params = $params;
	}

	public function getParams()
	{
		return $this->params;
	}

	public function send()
	{
		$post_data = [
			"auth" => [
				"username" => $this->username,
				"password" => $this->password
			],
			"method" => $this->method,
			"params" => $this->params
		];
		$ch = curl_init($this->host . $this->path);
		if (is_resource($ch)) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_VERBOSE, self::DEBUG);

			$result = curl_exec($ch);
			
			$host = $this->host . $this->path;

			curl_close($ch);

			if (empty($result)) {
				return "error";
			}
			$res = json_decode($result);
			if (!empty($res)) {
				return $res;
			}
		}
	}
}
