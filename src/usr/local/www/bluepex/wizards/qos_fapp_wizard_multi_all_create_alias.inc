<?php
/*
 * traffic_shaper_wizard_multi_all.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2019 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

require_once("config.inc");
require_once("interfaces.inc");
require_once("util.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("shaper.inc");

function confirmCreateAliasQos($otherclient) {
	global $config;

	$name_alias = "";
	if (strpos($otherclient, '-') !== false) {
		$name_alias = "FirewallApp_" . implode("_",explode("-",$otherclient)) . "_QoS";
	} else {
		$name_alias = "FirewallApp_" . $otherclient . "_QoS";
	}

	$norepeated = true;
	foreach($config['aliases']['alias'] as $value_alias) {
		if ($value_alias['name'] == $name_alias) {
			$norepeated = false;
			break;
		}
	}
	if ($norepeated) {
		$create_alias = true;
	}
	
	return $create_alias;
}

function confirmExistsAliasQos($otherclient) {
	global $config;

	$name_alias = "";
	if (strpos($otherclient, '-') !== false) {
		$name_alias = "FirewallApp_" . implode("_",explode("-",$otherclient)) . "_QoS";
	} else {
		$name_alias = "FirewallApp_" . $otherclient . "_QoS";
	}

	$exists_alias = false;
	foreach($config['aliases']['alias'] as $value_alias) {
		if ($value_alias['name'] == $name_alias) {
			$exists_alias = true;
			break;
		}
	}
	
	return $exists_alias;
}

function createAliasQos($otherclient) {
	global $config;

	if (strpos($otherclient, '-') !== false) {
		$name_alias = "FirewallApp_" . implode("_",explode("-",$otherclient)) . "_QoS";
		$grep_gid = strtolower(explode("-",$otherclient)[0]);
	} else {
		$name_alias = "FirewallApp_" . $otherclient . "_QoS";
		$grep_gid = strtolower($otherclient);
	}

	$create_alias = confirmCreateAliasQos($otherclient);
	$ugid_address_insert = "";
	$descrption_alias = "";
	$gid_target = intval(trim(shell_exec("grep -r 'msg:\"{$grep_gid}\"' /usr/local/share/suricata/rules_fapp | awk -F\"gid:\" '{print $2}' | awk -F\";\" '{print $1}' | sort | uniq")));

	if ($gid_target > 1) {
		if (
			file_exists("/usr/local/share/suricata/rules/gid:{$gid_target}.txt") ||
			file_exists("/usr/local/share/suricata/rules/ugid:{$gid_target}.txt")
		) {
			$ugid_ignore = [];
			$ugid_address_insert = [];
			if (file_exists('/usr/local/share/suricata/ugid_ignore')) {
				foreach(explode("\n",file_get_contents('/usr/local/share/suricata/ugid_ignore')) as $ipnow) {
					$ipnow = trim($ipnow);
					$ugid_ignore[] = $ipnow;
				}
			}
			if (file_exists('/usr/local/share/suricata/ugid_ignore_local')) {
				foreach(explode("\n",file_get_contents('/usr/local/share/suricata/ugid_ignore_local')) as $ipnow) {
					$ipnow = trim($ipnow);
					$ugid_ignore[] = $ipnow;
				}
			}
			$ugid_ignore = array_filter(array_unique($ugid_ignore));
			if (file_exists("/usr/local/share/suricata/rules/gid:{$gid_target}.txt")) {
				foreach(explode("\n",file_get_contents("/usr/local/share/suricata/rules/gid:{$gid_target}.txt")) as $ipnow) {
					$ipnow = trim($ipnow);
					if (!in_array($ipnow, $ugid_ignore)) {
						$ugid_address_insert[] = $ipnow;
					}
				}
			}
			if (file_exists("/usr/local/share/suricata/rules/ugid:{$gid_target}.txt")) {
				foreach(explode("\n",file_get_contents("/usr/local/share/suricata/rules/ugid:{$gid_target}.txt")) as $ipnow) {
					$ipnow = trim($ipnow);
					if (!in_array($ipnow, $ugid_ignore)) {
						$ugid_address_insert[] = $ipnow;
					}
				}
			}
			
			$ugid_address_insert = array_filter(array_unique($ugid_address_insert));
			if (count($ugid_address_insert) > 0) {
				foreach($config['aliases']['alias'] as $value_alias) {
					if ($value_alias['name'] == $name_alias) {
						$create_alias = false;
						break;
					}
				}
				
				$descrption_alias = $grep_gid;
				if (count($ugid_address_insert) > 1) {
					$descrption_alias = [];
					$ugid_address_insert_tmp = [];
					foreach($ugid_address_insert as $line) {
						if (!empty($line) && $line != "") {
							$ugid_address_insert_tmp[] = trim($line);
							$descrption_alias[] = $grep_gid;
						}
					}
					$descrption_alias = implode("||", $descrption_alias);
					$ugid_address_insert = implode(" ", array_filter($ugid_address_insert_tmp));
				}
			}
		}
	}

	$customizadeField = ["Sites_http", "Sites_https"];
	if (in_array($otherclient, $customizadeField)) {
		$ugid_address_insert = [];
		$descrption_alias = [];	
		if (isset($config['ezshaper']['step_special_http']['addressgroupsites'])) {
			$addressNow = array_filter(array_unique(explode(",",($config['ezshaper']['step_special_http']['addressgroupsites']))));
			foreach($addressNow as $lineAddress) {
				if (!empty($lineAddress) && $lineAddress != "") {
					$ugid_address_insert[] = trim($lineAddress);
					$descrption_alias[] = $grep_gid;
				}
			}
		}
		$descrption_alias = implode("||", $descrption_alias);
		$ugid_address_insert = implode(" ", array_filter($ugid_address_insert));
	}


	if ($create_alias) {
		if (!empty($ugid_address_insert)) {
			$alias['name'] = $name_alias;
			$alias['type'] = 'host';
			$alias['address'] = $ugid_address_insert;
			$alias['descr'] = $name_alias;
			$alias['detail'] = $descrption_alias;
			$config['aliases']['alias'][] = $alias;
		} else {
			$alias['name'] = $name_alias;
			$alias['type'] = 'host';
			$alias['address'] = '';
			$alias['descr'] = $name_alias;
			$alias['detail'] = '';
			$config['aliases']['alias'][] = $alias;	
		}
		write_config("Alias ​​created according to the needs of the FirewallApp QoS service");
	}

}

function repopulationCustomizeField($otherclient) {

	global $config;

	if (strpos($otherclient, '-') !== false) {
		$name_alias = "FirewallApp_" . implode("_",explode("-",$otherclient)) . "_QoS";
		$grep_gid = strtolower(explode("-",$otherclient)[0]);
	} else {
		$name_alias = "FirewallApp_" . $otherclient . "_QoS";
		$grep_gid = strtolower($otherclient);
	}

	$counterInteral = 0;
	$idalias = "";
	foreach($config['aliases']['alias'] as $value_alias) {
		if ($value_alias['name'] == $name_alias) {
			$idalias = $counterInteral;
			break;
		}
		$counterInteral++;
	}
	
	$descrption_alias = [];
	$ugid_address_insert = [];
	
	if (!empty($idalias) && intval($idalias)) {
		$customizadeField = ["Sites_http", "Sites_https"];
		if (in_array($otherclient, $customizadeField)) {
			if (isset($config['ezshaper']['step_special_http']['addressgroupsites'])) {
				$addressNow = array_filter(array_unique(explode(",",($config['ezshaper']['step_special_http']['addressgroupsites']))));
				foreach($addressNow as $lineAddress) {
					if (!empty($lineAddress) && $lineAddress != "") {
						$ugid_address_insert[] = trim($lineAddress);
						$descrption_alias[] = $grep_gid;
					}
				}
			}
			$descrption_alias = implode("||", $descrption_alias);
			$ugid_address_insert = implode(" ", array_filter($ugid_address_insert));
			$config['aliases']['alias'][$idalias]['address'] = $ugid_address_insert;
			$config['aliases']['alias'][$idalias]['detail'] = $descrption_alias;
			write_config("Repopulating aliases with IPs field");
		}
	}
}

function populatingQoSGroup() {
	global $config;

	foreach($config['aliases']['alias'] as $key => $alias_now) {
		if ((strpos($alias_now['name'], 'FirewallApp_') !== false) && (strpos($alias_now['name'], '_QoS') !== false)) {

			$msg_grep = explode("_", $alias_now['name'])[1];
			$msgDescr = $msg_grep;
			$msg_grep = strtolower($msg_grep);

			$addresExists = [];
			if (isset($alias_now['address']) && !empty($alias_now['address'])) {
				foreach(array_filter(array_unique(explode(" ", $alias_now['address']))) as $lineip) {
					if (!empty($lineip) && $lineip != "") {
						$addresExists[] = $lineip;
					}
				}
			}
			if (isset($alias_now['descr']) && !empty($alias_now['descr'])) {
				if (strpos($alias_now['descr'], '||') !== false) {
					$msgDescr = explode("||", $alias_now['descr'])[0];
				} else {
					$msgDescr = $alias_now['descr'];
				}
			}

			$getGid = intval(trim(shell_exec("grep -r 'msg:\"{$msg_grep}\"' /usr/local/share/suricata/rules_fapp/ | awk -F\"gid:\" '{print $2}' | awk -F\";\" '{print $1}' | sort | uniq")));

			if ($getGid > 1) {
				if (
					file_exists("/usr/local/share/suricata/rules/gid:{$getGid}.txt") ||
					file_exists("/usr/local/share/suricata/rules/ugid:{$getGid}.txt")
				) {

					$ugid_ignore = [];
					$ugid_address_insert = [];
					if (file_exists('/usr/local/share/suricata/ugid_ignore')) {
						foreach(explode("\n",file_get_contents('/usr/local/share/suricata/ugid_ignore')) as $ipnow) {
							$ipnow = trim($ipnow);
							$ugid_ignore[] = $ipnow;
						}
					}
					if (file_exists('/usr/local/share/suricata/ugid_ignore_local')) {
						foreach(explode("\n",file_get_contents('/usr/local/share/suricata/ugid_ignore_local')) as $ipnow) {
							$ipnow = trim($ipnow);
							$ugid_ignore[] = $ipnow;
						}
					}
					$ugid_ignore = array_filter(array_unique($ugid_ignore));
					if (file_exists("/usr/local/share/suricata/rules/gid:{$getGid}.txt")) {
						foreach(explode("\n",file_get_contents("/usr/local/share/suricata/rules/gid:{$getGid}.txt")) as $ipnow) {
							$ipnow = trim($ipnow);
							if (!in_array($ipnow, $ugid_ignore)) {
								$ugid_address_insert[] = $ipnow;
							}
						}
					}
					if (file_exists("/usr/local/share/suricata/rules/ugid:{$getGid}.txt")) {
						foreach(explode("\n",file_get_contents("/usr/local/share/suricata/rules/ugid:{$getGid}.txt")) as $ipnow) {
							$ipnow = trim($ipnow);
							if (!in_array($ipnow, $ugid_ignore)) {
								$ugid_address_insert[] = $ipnow;
							}
						}
					}

					foreach($addresExists as $ipnow) {
						if (!in_array($ipnow, $ugid_address_insert)) {
							$ugid_address_insert[] = $ipnow;
						}
					}

					$ugid_address_insert = array_filter(array_unique($ugid_address_insert));

					if (count($ugid_address_insert) > 0) {
						$descrption_alias = [];
						$ugid_address_insert_tmp = [];
						foreach($ugid_address_insert as $line) {
							$descrption_alias[] = $msgDescr;
							if (!empty($line) && $line != "") {
								$ugid_address_insert_tmp[] = trim($line);
							}
						}
						$descrption_alias = implode("||", $descrption_alias);
						$ugid_address_insert = implode(" ", array_filter($ugid_address_insert_tmp));

						if (!empty($ugid_address_insert)) {
							$config['aliases']['alias'][$key]['address'] = $ugid_address_insert;
							$config['aliases']['alias'][$key]['detail'] = $descrption_alias;
						}
					}

				}
			}
		}
	}

	if (write_config("Populating aliases with new IP values")) {
		mark_subsystem_dirty('aliases');
		$retval = 0;
        $retval |= filter_configure();
        if ($retval == 0) {
            clear_subsystem_dirty('aliases');
        }
	}

}

?>
