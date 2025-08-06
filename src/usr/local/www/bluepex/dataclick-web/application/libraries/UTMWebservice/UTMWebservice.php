<?php
require("UTMWebserviceRequest.php");

class UTMWebservice extends UTMWebserviceRequest
{
	public function insertURLQuarantine($data = [])
	{
		$this->setService("quarantine-urls");
		$this->setMethod("insert");
		$this->setParams($data);
		$res = $this->send();
		return $res;
	}
}

