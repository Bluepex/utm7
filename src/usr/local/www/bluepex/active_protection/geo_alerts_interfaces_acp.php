<?php
require_once("config.inc");
require_once("firewallapp_functions.inc");
require_once("/usr/local/pkg/suricata/suricata.inc");

init_config_arr(array('gateways', 'gateway_item'));
$a_gtw = &$config['gateways']['gateway_item'];
$a_gateways = return_gateways_array(true, false, true, true);

$all_gtw = getInterfacesInGatewaysWithNoExceptions();

if (file_exists('/tmp/acp_alerts_geo')) {
    unlink('/tmp/acp_alerts_geo');
}
foreach ($config['installedpackages']['suricata']['rule'] as $key => $suricatacfg) {
    $if = get_real_interface(strtolower($suricatacfg['interface']));
    $uuid = $suricatacfg['uuid'];
    if (in_array($if, $all_gtw, true)) {
        if (file_exists("/var/log/suricata/suricata_{$if}{$uuid}/alerts.log")) {
            shell_exec("tail -n1000 /var/log/suricata/suricata_{$if}{$uuid}/alerts.log >> /tmp/acp_alerts_geo");
        }
    }
}

function returnCountry($url) {
    $url = "https://api.ip.sb/geoip/" . $url;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_MAXREDIRS => 100,
        CURLOPT_FOLLOWLOCATION => 1,
        CURLOPT_HTTPHEADER => array("Content-Type: application/json;charset=utf-8")
    ));
    $valores = json_decode(curl_exec($curl), true);
    curl_close($curl);
    if (isset($valores['country'])) {
        return $valores['country'];
    } else {
        return '';
    }
}


$arrayIps = [];
if (file_exists('/tmp/acp_alerts_geo')) {
    foreach(explode("\n", trim(shell_exec("awk -F\" -> \" '{print $2}' /tmp/acp_alerts_geo | awk -F\":\" '{print $1}' | sort | uniq -c"))) as $lineCount) {
        $valuesLine = explode("_", preg_replace("/\s+/","_",$lineCount));
        if (count($valuesLine) == 2) {
            $count = $valuesLine[0];
            $ips = $valuesLine[1];
            $country = returnCountry($ips);
            if ($country != "") {
                $findValue=false;
                foreach($arrayIps as $key => $valueArray) {
                    if ($valueArray['name'] == $country) {
                        $arrayIps[$key] = ["name" => $country, "value" => $valueArray['value'] + $count];
                        $findValue=true; 
                    }
                }
                if (!$findValue) {
                    $arrayIps[] = ["name" => $country, "value" => $count];
                }
            }
        } elseif (count($valuesLine) == 3) {
            $count = $valuesLine[1];
            $ips = $valuesLine[2];
            $country = returnCountry($ips);
            if ($country != "") {
                $findValue=false;
                foreach($arrayIps as $key => $valueArray) {
                    if ($valueArray['name'] == $country) {
                        $arrayIps[$key] = ["name" => $country, "value" => $valueArray['value'] + $count];
                        $findValue=true; 
                    }
                }
                if (!$findValue) {
                    $arrayIps[] = ["name" => $country, "value" => $count];
                }
            }
        }
    }
}
$data["data"] = $arrayIps;

file_put_contents("/usr/local/www/active_protection/geo_ameacas_map", json_encode($data));

if (strlen(file_get_contents('/usr/local/www/active_protection/geo_ameacas_map')) == 0) {
    file_put_contents('/usr/local/www/active_protection/geo_ameacas_map', '{"data":[]}');
}

#Example
#{"data":[{"name": "United Arab Emirates", "value": 0},{"name": "Argentina", "value": 0},{"name": "Australia", "value": 0},{"name": "Bangladesh", "value": 0},{"name": "Bulgaria", "value": 0},{"name": "Bahrain", "value": 0},{"name": "Brazil", "value": 0},{"name": "Switzerland", "value": 0},{"name": "China", "value": 0},{"name": "Czechia", "value": 0},{"name": "Germany", "value": 0},{"name": "Egypt", "value": 0},{"name": "Spain", "value": 0},{"name": "France", "value": 0},{"name": "United Kingdom", "value": 0},{"name": "Georgia", "value": 0},{"name": "Hong Kong", "value": 0},{"name": "Indonesia", "value": 0},{"name": "Ireland", "value": 0},{"name": "India", "value": 0},{"name": "Italy", "value": 0},{"name": "Jordan", "value": 0},{"name": "Japan", "value": 0},{"name": "Cambodia", "value": 0},{"name": "South Korea", "value": 0},{"name": "Lithuania", "value": 0},{"name": "Luxembourg", "value": 0},{"name": "Mongolia", "value": 0},{"name": "Mexico", "value": 0},{"name": "Netherlands", "value": 0},{"name": "Norway", "value": 0},{"name": "Poland", "value": 0},{"name": "Portugal", "value": 0},{"name": "Romania", "value": 0},{"name": "Russia", "value": 0},{"name": "Singapore", "value": 0},{"name": "Thailand", "value": 0},{"name": "Turkey", "value": 0},{"name": "Taiwan", "value": 0},{"name": "Ukraine", "value": 0},{"name": "United States", "value": 0},{"name": "Venezuela", "value": 0},{"name": "Vietnam", "value": 0},{"name": "South Africa", "value": 0}]} 
#Return in tests
#{"data":[{"name":"United States","value":"360"}]}