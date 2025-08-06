<?php
require("DataClickApiRequest.php");

class DataClickApi extends DataClickApiRequest
{
	public function __construct($data)
	{
		parent::__construct($data);

		if (!isset($data['protocol'], $data['host'], $data['port'], $data['user'], $data['pass'])) {
			throw new Exception('DataClickRequest: Invalid params!');
		}

		$host = $data['protocol'] . $data['host'];
		if (($data['protocol'] == "http://" && $data['port'] != "80")
		    || ($data['protocol'] == "https://" && $data['port'] != "443")) {
			$host .= ":{$data['port']}";
		}

		$this->setHost($host);
		$this->setUsername($data['user']);
		$this->setPassword($data['pass']);
	}

	public function checkConnectAndSync()
	{
		$this->setMethod("check_connect_sync");
		$this->setTimeout(10);
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}

	public function refreshData()
	{
		$this->setTimeout(5);
		$this->setMethod("refresh_data");
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}

	public function getDashboardData($period = "daily", $blocked = false)
	{
		$this->setParams([ "period" => $period, "blocked" => $blocked, "limit" => 10 ]);
		$this->setMethod("get_dashboard_data");
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}
    
	public function getRealTimeData($params = [])
	{
		$this->setTimeout(5);
		$this->setParams($params);
		$this->setMethod("get_realtime_data");
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}

	public function getReport($params)
	{
		$this->setParams($params);
		$this->setMethod("get_report_data");
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}

	public function getUsersFromDatabase()
	{
		$this->setMethod("get_users_db");
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}

	public function getUsersFromConfigXML()
        {
                $this->setMethod("get_users_config_xml");
                $res = $this->send();
                if (isset($res->response_status) && $res->response_status == "ok") {
                        return $res->data;
                }
        }

	public function getGroupsFromDatabase()
	{
		$this->setMethod("get_groups_db");
		$res = $this->send();
		if (isset($res->response_status) && $res->response_status == "ok") {
			return $res->data;
		}
	}
}

